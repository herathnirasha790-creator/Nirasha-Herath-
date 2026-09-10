<?php
session_start();
header('Content-Type: application/json');

// ✅ Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Please login to book a room.']);
    exit();
}

// ✅ Database & Helpers
require_once 'config/db_connection.php';
require_once 'config/booking_pricing.php';
require_once 'config/stripe_helper.php';
require_once 'config/email_config.php'; // ✅ Email Config

$user_id = $_SESSION['user_id'];
$room_id = intval($_POST['room_id'] ?? 0);
$checkin = $_POST['check_in'] ?? '';
$checkout = $_POST['check_out'] ?? '';
$guests = intval($_POST['guests'] ?? 1);
$special_requests = trim($_POST['special_requests'] ?? '');
$payment_intent_id = trim($_POST['payment_intent_id'] ?? '');

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
// ✅ Step 2: Re-validate the room/dates (something may have changed)
// ------------------------------------------------------------------
$pricing = validate_and_price_room_booking($conn, $room_id, $checkin, $checkout, $guests);

if (!$pricing['success']) {
    // Can't fulfil the booking anymore — refund automatically
    try { stripe_create_refund($payment_intent_id); } catch (Exception $e) { /* ignore */ }
    echo json_encode(['success' => false, 'error' => $pricing['error'] . ' Your payment has been refunded automatically.']);
    exit();
}

$total_price = $pricing['total'];
$nights = $pricing['nights'];
$room = $pricing['room'];

// Make sure what was actually charged still matches what this booking costs.
$charged_cents = intval($intent['amount']);
$expected_cents = (int) round($total_price * 100);
if (abs($charged_cents - $expected_cents) > 1) {
    try { stripe_create_refund($payment_intent_id); } catch (Exception $e) {}
    echo json_encode(['success' => false, 'error' => 'Payment amount did not match the booking total. Your payment has been refunded — please try again.']);
    exit();
}

// ------------------------------------------------------------------
// ✅ Step 3: Payment confirmed & valid — create the booking.
// ------------------------------------------------------------------
$insert = $conn->prepare("
    INSERT INTO room_bookings (user_id, room_id, check_in, check_out, guests, total_price, special_requests, status, payment_status, stripe_payment_intent_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'paid', ?)
");
$insert->bind_param("iissidss", $user_id, $room_id, $checkin, $checkout, $guests, $total_price, $special_requests, $payment_intent_id);

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
        'check_in' => $checkin,
        'check_out' => $checkout,
        'nights' => $nights,
        'total' => number_format($total_price, 2),
        'room' => $room['name']
    ];
    
    $email_sent = sendBookingConfirmationEmail(
        $user['email'],
        $user['name'],
        'room',
        $booking_id,
        $email_details
    );

    // ✅ Log email status for debugging
    error_log("Room Booking Email sent status: " . ($email_sent ? 'SUCCESS' : 'FAILED') . " to " . $user['email']);

    $details = "
        <strong>Booking ID:</strong> #$booking_id<br>
        <strong>Room:</strong> " . htmlspecialchars($room['name']) . "<br>
        <strong>Check-in:</strong> $checkin<br>
        <strong>Check-out:</strong> $checkout<br>
        <strong>Nights:</strong> $nights<br>
        <strong>Guests:</strong> $guests<br>
        <strong>Total Paid:</strong> $" . number_format($total_price, 2) . "<br>
        <strong>Payment:</strong> <span style='color:#155724;'>✅ Paid</span><br>
        <strong>Status:</strong> <span style='color:#856404;'>Pending Approval</span>
        " . ($email_sent ? "<br><span style='color:#28a745;'>📧 Confirmation email sent!</span>" : "<br><span style='color:#dc3545;'>⚠️ Email failed to send</span>");
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment successful — room booking confirmed!',
        'details' => $details,
        'booking_id' => $booking_id,
        'booking_type' => 'room'
    ]);
} else {
    // DB failed after a successful charge — refund to be safe
    try { stripe_create_refund($payment_intent_id); } catch (Exception $e) {}
    echo json_encode(['success' => false, 'error' => 'Database error. Your payment has been refunded.']);
}
exit();
?>