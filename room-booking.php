<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Database connection
require_once 'config/db_connection.php';

$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($room_id == 0) {
    header('Location: rooms.php');
    exit();
}

// Fetch room details
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    header('Location: rooms.php');
    exit();
}

$max_guests = $room['max_guests'];
$price_per_night = $room['price'];
$room_name = $room['name'];

$error = '';
$success = '';
$booking_data = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_room'])) {
    $checkin = $_POST['checkin'] ?? '';
    $checkout = $_POST['checkout'] ?? '';
    $guests = intval($_POST['guests'] ?? 1);
    $special_requests = trim($_POST['special_requests'] ?? '');

    // Validation
    $today = date('Y-m-d');
    if (empty($checkin) || empty($checkout)) {
        $error = 'Please select check-in and check-out dates.';
    } elseif ($checkin < $today) {
        $error = 'Check-in date cannot be in the past.';
    } elseif ($checkin >= $checkout) {
        $error = 'Check-out date must be after check-in date.';
    } elseif ($guests < 1 || $guests > $max_guests) {
        $error = "Number of guests must be between 1 and $max_guests.";
    } else {
        // Check availability: overlapping bookings with status confirmed or pending
        $check_stmt = $conn->prepare("
            SELECT COUNT(*) as booked 
            FROM room_bookings 
            WHERE room_id = ? 
            AND status IN ('confirmed', 'pending')
            AND (
                (check_in <= ? AND check_out >= ?) OR
                (check_in BETWEEN ? AND ?) OR
                (check_out BETWEEN ? AND ?)
            )
        ");
        $check_stmt->bind_param("issssss", $room_id, $checkin, $checkin, $checkin, $checkout, $checkin, $checkout);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['booked'] > 0) {
            $error = 'Selected dates are not available. Please choose different dates.';
        } else {
            // Calculate total price
            $date1 = new DateTime($checkin);
            $date2 = new DateTime($checkout);
            $nights = $date1->diff($date2)->days;
            $total_price = $nights * $price_per_night;

            // Insert booking
            $insert_stmt = $conn->prepare("
                INSERT INTO room_bookings (user_id, room_id, check_in, check_out, guests, total_price, special_requests, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $insert_stmt->bind_param("iissiis", $user_id, $room_id, $checkin, $checkout, $guests, $total_price, $special_requests);
            if ($insert_stmt->execute()) {
                $booking_id = $conn->insert_id;
                $success = "Booking confirmed! Your booking ID is #$booking_id. Total amount: $$total_price. Status: Pending confirmation.";
                $booking_data = [
                    'id' => $booking_id,
                    'room' => $room_name,
                    'checkin' => $checkin,
                    'checkout' => $checkout,
                    'nights' => $nights,
                    'guests' => $guests,
                    'total' => $total_price
                ];
                // Clear form or not? Keep success message.
            } else {
                $error = 'Database error: Could not complete booking. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Room - <?php echo htmlspecialchars($room_name); ?> | Royal Estate</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f0eb; }
        .navbar { background: white; padding: 1rem 5%; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .logo h1 { font-size: 1.5rem; background: linear-gradient(135deg, #2c1810, #c5a263); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .container { max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        h2 { color: #2c1810; margin-bottom: 1.5rem; border-left: 4px solid #c5a263; padding-left: 1rem; }
        .form-group { margin-bottom: 1.2rem; }
        label { display: block; font-weight: 500; margin-bottom: 0.3rem; color: #2c1810; }
        input, select, textarea { width: 100%; padding: 0.8rem; border-radius: 10px; border: 1px solid #ddd; font-family: 'Poppins', sans-serif; }
        button { background: linear-gradient(135deg, #c5a263, #8b691f); color: white; border: none; padding: 0.8rem 2rem; border-radius: 50px; cursor: pointer; font-weight: 600; width: 100%; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 12px; margin-bottom: 1rem; border-left: 4px solid #dc3545; }
        .alert-success { background: #d4edda; color: #155724; padding: 1rem; border-radius: 12px; margin-bottom: 1rem; border-left: 4px solid #28a745; }
        .booking-summary { background: #fef5e6; padding: 1rem; border-radius: 12px; margin-top: 1rem; }
        .back-link { display: inline-block; margin-top: 1rem; color: #c5a263; text-decoration: none; }
        footer { background: #1a0f0a; color: #999; text-align: center; padding: 1rem; margin-top: 2rem; }
        @media (max-width: 768px) { .container { padding: 0 1rem; } }
    </style>
</head>
<body>
<div class="navbar">
    <div class="logo"><h1>Royal Estate</h1></div>
    <a href="rooms.php" style="color:#c5a263;"><i class="fas fa-arrow-left"></i> Back to Rooms</a>
</div>

<div class="container">
    <div class="card">
        <h2>Book Room: <?php echo htmlspecialchars($room_name); ?></h2>
        <p><strong>Price per night:</strong> LKR <?php echo number_format($price_per_night); ?></p>
        <p><strong>Max guests:</strong> <?php echo $max_guests; ?></p>

        <?php if ($error): ?>
            <div class="alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <div class="booking-summary">
                <h3>Booking Details</h3>
                <p><strong>Booking ID:</strong> #<?php echo $booking_data['id']; ?></p>
                <p><strong>Room:</strong> <?php echo htmlspecialchars($booking_data['room']); ?></p>
                <p><strong>Check-in:</strong> <?php echo $booking_data['checkin']; ?></p>
                <p><strong>Check-out:</strong> <?php echo $booking_data['checkout']; ?></p>
                <p><strong>Nights:</strong> <?php echo $booking_data['nights']; ?></p>
                <p><strong>Guests:</strong> <?php echo $booking_data['guests']; ?></p>
                <p><strong>Total:</strong> LKR <?php echo number_format($booking_data['total']); ?></p>
                <p><strong>Status:</strong> Pending</p>
                <hr>
                <p>You can view all your bookings in your <a href="user/my-room-bookings.php" style="color:#c5a263;">dashboard</a>.</p>
            </div>
            <a href="rooms.php" class="back-link">← Book another room</a>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>Check-in Date</label>
                    <input type="date" name="checkin" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Check-out Date</label>
                    <input type="date" name="checkout" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <div class="form-group">
                    <label>Number of Guests (max <?php echo $max_guests; ?>)</label>
                    <input type="number" name="guests" min="1" max="<?php echo $max_guests; ?>" value="1" required>
                </div>
                <div class="form-group">
                    <label>Special Requests (optional)</label>
                    <textarea name="special_requests" rows="3" placeholder="e.g., extra pillows, late check-in..."></textarea>
                </div>
                <button type="submit" name="book_room">Confirm Booking</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<footer>
    <p>&copy; 2024 Royal Estate. All rights reserved.</p>
</footer>
</body>
</html>