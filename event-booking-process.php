<?php
session_start();
header('Content-Type: application/json');

// ✅ Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'error' => 'Please login to book an event.'
    ]);
    exit();
}

// ✅ Database & Helpers
require_once 'config/db_connection.php';
require_once 'config/booking_pricing.php';
require_once 'config/stripe_helper.php';
require_once 'config/email_config.php'; // ✅ Email Config

// ✅ Get POST data
$package_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
$hall_id = isset($_POST['hall_id']) ? intval($_POST['hall_id']) : 0;
$event_date = isset($_POST['event_date']) ? trim($_POST['event_date']) : '';
$guests = isset($_POST['guests']) ? intval($_POST['guests']) : 1;
$special_requests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';
$payment_intent_id = isset($_POST['payment_intent_id']) ? trim($_POST['payment_intent_id']) : '';
$booking_type = isset($_POST['booking_type']) ? trim($_POST['booking_type']) : 'package';
$user_id = $_SESSION['user_id'];

// ------------------------------------------------------------------
// ✅ Step 1: A booking can only be created after a real payment.
// ------------------------------------------------------------------
if (empty($payment_intent_id)) {
    echo json_encode(['success' => false, 'error' => 'Payment is required to confirm this booking.']);
    exit();
}

try {
    $intent = stripe_retrieve_payment_intent($payment_intent_id);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Could not verify payment: ' . $e->getMessage()]);
    exit();
}

if (!isset($intent['status']) || $intent['status'] !== 'succeeded') {
    echo json_encode(['success' => false, 'error' => 'Payment was not completed successfully.']);
    exit();
}

if (!isset($intent['metadata']['user_id']) || intval($intent['metadata']['user_id']) !== intval($user_id)) {
    echo json_encode(['success' => false, 'error' => 'Payment verification failed.']);
    exit();
}

// ------------------------------------------------------------------
// ✅ Step 2: Re-validate the hall/package/date
// ------------------------------------------------------------------
$pricing = validate_and_price_event_booking($conn, $hall_id, $package_id, $event_date, $guests);

if (!$pricing['success']) {
    // Can't fulfil the booking anymore — refund automatically
    try { stripe_create_refund($payment_intent_id); } catch (Exception $e) { /* ignore */ }
    echo json_encode(['success' => false, 'error' => $pricing['error'] . ' Your payment has been refunded automatically.']);
    exit();
}

$hall = $pricing['hall'];
$package = $pricing['package'];
$estimated_price = $pricing['total'];

// Make sure what was actually charged still matches what this booking costs.
$charged_cents = intval($intent['amount']);
$expected_cents = (int) round($estimated_price * 100);
if (abs($charged_cents - $expected_cents) > 1) {
    try { stripe_create_refund($payment_intent_id); } catch (Exception $e) {}
    echo json_encode(['success' => false, 'error' => 'Payment amount did not match the booking total. Your payment has been refunded — please try again.']);
    exit();
}

// ------------------------------------------------------------------
// ✅ Step 3: Payment confirmed & valid — create the booking.
// ✅ booking_type is passed from frontend ('event_hall' or 'package')
// ------------------------------------------------------------------
$insert = $conn->prepare("
    INSERT INTO event_bookings (user_id, hall_id, package_id, event_date, guests, special_requests, estimated_price, status, booking_type, payment_status, stripe_payment_intent_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, 'paid', ?)
");
$insert->bind_param("iiisissss", $user_id, $hall_id, $package_id, $event_date, $guests, $special_requests, $estimated_price, $booking_type, $payment_intent_id);

if ($insert->execute()) {
    $booking_id = $conn->insert_id;

    // Link the audit payments row to the booking we just created.
    $upd = $conn->prepare("UPDATE payments SET status = 'succeeded', booking_id = ? WHERE stripe_payment_intent_id = ?");
    $upd->bind_param("is", $booking_id, $payment_intent_id);
    $upd->execute();

    // ✅ Fetch user details for email
    $user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user = $user_stmt->get_result()->fetch_assoc();

    // ✅ Send Email Confirmation
    $email_details = [
        'event_date' => $event_date,
        'hall' => $hall['name'],
        'package' => $package['name'],
        'guests' => $guests,
        'total' => number_format($estimated_price, 2)
    ];
    
    // Determine email booking type
    $email_type = ($booking_type === 'event_hall') ? 'event_hall' : 'package';
    
    $email_sent = sendBookingConfirmationEmail(
        $user['email'],
        $user['name'],
        $email_type,
        $booking_id,
        $email_details
    );

    // ✅ Log email status
    error_log("Event Booking Email sent status: " . ($email_sent ? 'SUCCESS' : 'FAILED') . " to " . $user['email']);

    // Determine redirect based on booking type
    $redirect = ($booking_type === 'event_hall') ? 'user/my-event-bookings.php' : 'user/my-package-bookings.php';

    $details = "
        <strong>Booking ID:</strong> #$booking_id<br>
        <strong>Hall:</strong> " . htmlspecialchars($hall['name']) . "<br>
        <strong>Package:</strong> " . htmlspecialchars($package['name']) . "<br>
        <strong>Event Date:</strong> $event_date<br>
        <strong>Guests:</strong> $guests<br>
        <strong>Total Paid:</strong> $" . number_format($estimated_price, 2) . "<br>
        <strong>Payment:</strong> <span style='color:#155724;'>✅ Paid</span><br>
        <strong>Status:</strong> <span style='color:#856404;'>Pending Approval</span>
        " . ($email_sent ? "<br><span style='color:#28a745;'>📧 Confirmation email sent!</span>" : "<br><span style='color:#dc3545;'>⚠️ Email failed to send</span>");
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment successful — booking submitted!',
        'details' => $details,
        'booking_id' => $booking_id,
        'booking_type' => $booking_type,
        'redirect' => $redirect
    ]);
} else {
    // DB failed after a successful charge — refund to be safe
    try { stripe_create_refund($payment_intent_id); } catch (Exception $e) {}
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $conn->error . ' Your payment has been refunded.'
    ]);
}
exit();
?>