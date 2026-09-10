<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'user') {
    header('Location: login.php');
    exit();
}

require_once 'config/db_connection.php';

$user_id = $_SESSION['user_id'];
$hall_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($hall_id == 0) {
    header('Location: event-halls.php');
    exit();
}

// Fetch hall details
$stmt = $conn->prepare("SELECT * FROM event_halls WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $hall_id);
$stmt->execute();
$hall = $stmt->get_result()->fetch_assoc();
if (!$hall) {
    header('Location: event-halls.php');
    exit();
}

// Fetch all active packages
$packages = $conn->query("SELECT * FROM packages WHERE status = 'active' ORDER BY category, price")->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';
$booking_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_event'])) {
    $package_id = intval($_POST['package_id'] ?? 0);
    $event_date = $_POST['event_date'] ?? '';
    $guests = intval($_POST['guests'] ?? 1);
    $special_requests = trim($_POST['special_requests'] ?? '');

    // Validate package exists
    $pkg = $conn->query("SELECT * FROM packages WHERE id = $package_id")->fetch_assoc();
    if (!$pkg) {
        $error = 'Invalid package selected.';
    } elseif ($event_date < date('Y-m-d')) {
        $error = 'Event date cannot be in the past.';
    } elseif ($guests < 1 || $guests > $hall['capacity']) {
        $error = "Number of guests must be between 1 and {$hall['capacity']}.";
    } else {
        // Check if hall is already booked on that date (pending/approved)
        $check_stmt = $conn->prepare("
            SELECT COUNT(*) as booked FROM event_bookings 
            WHERE hall_id = ? AND event_date = ? AND status IN ('pending', 'approved')
        ");
        $check_stmt->bind_param("is", $hall_id, $event_date);
        $check_stmt->execute();
        $check = $check_stmt->get_result()->fetch_assoc();
        if ($check['booked'] > 0) {
            $error = 'This hall is already booked on the selected date. Please choose another date.';
        } else {
            // Calculate estimated price
            $estimated_price = $pkg['price'];
            
            // ✅ Insert booking with booking_type = 'event_hall'
            $insert = $conn->prepare("
                INSERT INTO event_bookings (user_id, hall_id, package_id, event_date, guests, special_requests, estimated_price, status, booking_type)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'event_hall')
            ");
            $insert->bind_param("iiisisd", $user_id, $hall_id, $package_id, $event_date, $guests, $special_requests, $estimated_price);
            
            if ($insert->execute()) {
                $booking_id = $conn->insert_id;
                $success = "Event booking submitted! Booking ID: #$booking_id. Estimated price: $$estimated_price. Waiting for admin approval.";
                $booking_data = [
                    'id' => $booking_id,
                    'hall' => $hall['name'],
                    'package' => $pkg['name'],
                    'date' => $event_date,
                    'guests' => $guests,
                    'estimated' => $estimated_price
                ];
            } else {
                $error = 'Database error. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Plan Event - <?php echo htmlspecialchars($hall['name']); ?> | Royal Estate</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#f5f0eb; }
        .navbar { background:white; padding:1rem 5%; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
        .logo h1 { font-size:1.5rem; background:linear-gradient(135deg,#2c1810,#c5a263); -webkit-background-clip:text; color:transparent; }
        .container { max-width:800px; margin:2rem auto; padding:0 1rem; }
        .card { background:white; border-radius:20px; padding:2rem; box-shadow:0 10px 30px rgba(0,0,0,0.1); }
        h2 { color:#2c1810; margin-bottom:1.5rem; border-left:4px solid #c5a263; padding-left:1rem; }
        .form-group { margin-bottom:1.2rem; }
        label { display:block; font-weight:500; margin-bottom:0.3rem; color:#2c1810; }
        input, select, textarea { width:100%; padding:0.8rem; border-radius:10px; border:1px solid #ddd; font-family:'Poppins',sans-serif; }
        button { background:linear-gradient(135deg,#c5a263,#8b691f); color:white; border:none; padding:0.8rem 2rem; border-radius:50px; cursor:pointer; font-weight:600; width:100%; }
        .alert-error { background:#f8d7da; color:#721c24; padding:1rem; border-radius:12px; margin-bottom:1rem; border-left:4px solid #dc3545; }
        .alert-success { background:#d4edda; color:#155724; padding:1rem; border-radius:12px; margin-bottom:1rem; border-left:4px solid #28a745; }
        .booking-summary { background:#fef5e6; padding:1rem; border-radius:12px; margin-top:1rem; }
        .back-link { display:inline-block; margin-top:1rem; color:#c5a263; text-decoration:none; }
        footer { background:#1a0f0a; color:#999; text-align:center; padding:1rem; margin-top:2rem; }
        @media (max-width:768px) { .container { padding:0 1rem; } }
    </style>
</head>
<body>
<div class="navbar">
    <div class="logo"><h1>Royal Estate</h1></div>
    <a href="event-halls.php" style="color:#c5a263;"><i class="fas fa-arrow-left"></i> Back to Halls</a>
</div>
<div class="container">
    <div class="card">
        <h2>Plan Event at <?php echo htmlspecialchars($hall['name']); ?></h2>
        <p><strong>Capacity:</strong> <?php echo $hall['capacity']; ?> guests</p>
        <p><strong>Base Price:</strong> LKR <?php echo number_format($hall['base_price']); ?></p>

        <?php if ($error): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-success"><?php echo $success; ?></div>
            <div class="booking-summary">
                <h3>Booking Details</h3>
                <p><strong>Booking ID:</strong> #<?php echo $booking_data['id']; ?></p>
                <p><strong>Hall:</strong> <?php echo htmlspecialchars($booking_data['hall']); ?></p>
                <p><strong>Package:</strong> <?php echo htmlspecialchars($booking_data['package']); ?></p>
                <p><strong>Event Date:</strong> <?php echo $booking_data['date']; ?></p>
                <p><strong>Guests:</strong> <?php echo $booking_data['guests']; ?></p>
                <p><strong>Estimated Price:</strong> LKR <?php echo number_format($booking_data['estimated']); ?></p>
                <p><strong>Status:</strong> Pending</p>
                <hr>
                <p>You can view all your bookings in your <a href="user/my-event-bookings.php" style="color:#c5a263;">dashboard</a>.</p>
            </div>
            <a href="event-halls.php" class="back-link">← Plan another event</a>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>Select Package</label>
                    <select name="package_id" required>
                        <option value="">Choose a package</option>
                        <?php foreach($packages as $pkg): ?>
                        <option value="<?php echo $pkg['id']; ?>">
                            <?php echo htmlspecialchars($pkg['name']); ?> - LKR <?php echo number_format($pkg['price']); ?> (<?php echo ucfirst($pkg['category']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Event Date</label>
                    <input type="date" name="event_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <div class="form-group">
                    <label>Number of Guests (max <?php echo $hall['capacity']; ?>)</label>
                    <input type="number" name="guests" min="1" max="<?php echo $hall['capacity']; ?>" value="1" required>
                </div>
                <div class="form-group">
                    <label>Special Requests (optional)</label>
                    <textarea name="special_requests" rows="3" placeholder="e.g., vegetarian meals, decoration preferences..."></textarea>
                </div>
                <button type="submit" name="book_event">Submit Booking Request</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<footer><p>&copy; 2024 Royal Estate. All rights reserved.</p></footer>
</body>
</html>