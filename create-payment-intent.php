<?php
/**
 * Step 1 of the booking flow: validate the booking details and create a Stripe
 * PaymentIntent for the correct amount. Nothing is written to room_bookings /
 * event_bookings here — that only happens after the payment succeeds
 * (see room-booking-process.php / event-booking-process.php).
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Please login to continue.']);
    exit();
}

require_once 'config/db_connection.php';
require_once 'config/booking_pricing.php';
require_once 'config/stripe_helper.php';

$user_id = $_SESSION['user_id'];
$booking_type = $_POST['booking_type'] ?? '';

$metadata = [
    'user_id' => $user_id,
    'booking_type' => $booking_type,
];
$summary = [];
$pricing = ['success' => false, 'error' => 'Invalid booking type.'];

if ($booking_type === 'room') {
    $room_id = intval($_POST['room_id'] ?? 0);
    $checkin = $_POST['check_in'] ?? '';
    $checkout = $_POST['check_out'] ?? '';
    $guests = intval($_POST['guests'] ?? 1);

    $pricing = validate_and_price_room_booking($conn, $room_id, $checkin, $checkout, $guests);

    if ($pricing['success']) {
        $metadata['room_id'] = $room_id;
        $metadata['check_in'] = $checkin;
        $metadata['check_out'] = $checkout;
        $nightsLabel = $pricing['nights'] . ($pricing['nights'] == 1 ? ' night' : ' nights');
        $summary = [
            'title' => $pricing['room']['name'],
            'subtitle' => "$checkin to $checkout ($nightsLabel)",
        ];
    }
} elseif ($booking_type === 'event_hall' || $booking_type === 'package') {
    $hall_id = intval($_POST['hall_id'] ?? 0);
    $package_id = intval($_POST['package_id'] ?? 0);
    $event_date = $_POST['event_date'] ?? '';
    $guests = intval($_POST['guests'] ?? 1);

    $pricing = validate_and_price_event_booking($conn, $hall_id, $package_id, $event_date, $guests);

    if ($pricing['success']) {
        $metadata['hall_id'] = $hall_id;
        $metadata['package_id'] = $package_id;
        $metadata['event_date'] = $event_date;
        $summary = [
            'title' => $pricing['package']['name'] . ' at ' . $pricing['hall']['name'],
            'subtitle' => 'Event date: ' . $event_date,
        ];
    }
}

if (!$pricing['success']) {
    echo json_encode(['success' => false, 'error' => $pricing['error']]);
    exit();
}

$amount = $pricing['total'];
$currency = STRIPE_CURRENCY;
$amount_cents = (int) round($amount * 100);

if ($amount_cents <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid amount.']);
    exit();
}

try {
    $intent = stripe_create_payment_intent($amount_cents, $currency, $metadata);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Could not start payment: ' . $e->getMessage()]);
    exit();
}

$payment_intent_id = $intent['id'];

// Audit trail row — lets you see abandoned/attempted payments even if the booking never completes.
$stmt = $conn->prepare("INSERT INTO payments (user_id, booking_type, stripe_payment_intent_id, amount, currency, status) VALUES (?, ?, ?, ?, ?, 'pending')");
$stmt->bind_param("issds", $user_id, $booking_type, $payment_intent_id, $amount, $currency);
$stmt->execute();

echo json_encode([
    'success' => true,
    'client_secret' => $intent['client_secret'],
    'payment_intent_id' => $payment_intent_id,
    'amount' => $amount,
    'currency' => $currency,
    'summary' => $summary,
]);
exit();
