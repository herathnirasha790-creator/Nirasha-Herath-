<?php
session_start();
require_once 'config/db_connection.php';

// Get search parameters
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';
$guests = intval($_GET['guests'] ?? 1);

$available_rooms = [];
$error = '';

// Validate dates
if ($checkin && $checkout) {
    if ($checkin >= $checkout) {
        $error = 'Check-out date must be after check-in date.';
    } elseif ($checkin < date('Y-m-d')) {
        $error = 'Check-in date cannot be in the past.';
    } else {
        // Fetch all active rooms
        $stmt = $conn->prepare("SELECT * FROM rooms WHERE status = 'active' AND max_guests >= ?");
        $stmt->bind_param("i", $guests);
        $stmt->execute();
        $result = $stmt->get_result();
        $all_rooms = $result->fetch_all(MYSQLI_ASSOC);

        // For each room, check if it's booked during the selected dates
        foreach ($all_rooms as $room) {
            $room_id = $room['id'];
            // Check overlapping bookings (status confirmed or pending)
            $book_stmt = $conn->prepare("
                SELECT COUNT(*) as booked_count 
                FROM room_bookings 
                WHERE room_id = ? 
                AND status IN ('confirmed', 'pending')
                AND (
                    (check_in <= ? AND check_out >= ?) OR
                    (check_in BETWEEN ? AND ?) OR
                    (check_out BETWEEN ? AND ?)
                )
            ");
            $book_stmt->bind_param("issssss", $room_id, $checkin, $checkin, $checkin, $checkout, $checkin, $checkout);
            $book_stmt->execute();
            $book_res = $book_stmt->get_result();
            $book_row = $book_res->fetch_assoc();
            
            if ($book_row['booked_count'] == 0) {
                $available_rooms[] = $room;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Availability | Royal Estate</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f0eb; }
        .navbar { background: white; padding: 1rem 5%; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .logo h1 { font-size: 1.5rem; background: linear-gradient(135deg, #2c1810, #c5a263); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .container { max-width: 1400px; margin: 2rem auto; padding: 0 5%; }
        .search-summary { background: white; border-radius: 20px; padding: 1.5rem; margin-bottom: 2rem; text-align: center; }
        .search-summary h2 { color: #2c1810; margin-bottom: 0.5rem; }
        .search-summary p { color: #666; }
        .rooms-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; }
        .room-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: 0.3s; }
        .room-card:hover { transform: translateY(-10px); }
        .room-image { height: 220px; background: #2c1810; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; }
        .room-info { padding: 1.5rem; }
        .room-info h3 { font-size: 1.4rem; color: #2c1810; margin-bottom: 0.5rem; }
        .price { font-size: 1.5rem; color: #c5a263; font-weight: 600; margin: 0.5rem 0; }
        .features { display: flex; gap: 1rem; margin: 1rem 0; flex-wrap: wrap; }
        .features span { font-size: 0.8rem; color: #888; }
        .features i { color: #c5a263; margin-right: 0.3rem; }
        .btn-book { background: linear-gradient(135deg, #c5a263, #8b691f); color: white; padding: 0.6rem 1.5rem; border-radius: 50px; text-decoration: none; display: inline-block; transition: 0.2s; }
        .btn-book:hover { transform: translateX(5px); }
        .error-msg { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 12px; text-align: center; margin-bottom: 1rem; }
        .no-results { text-align: center; padding: 3rem; background: white; border-radius: 20px; }
        .back-link { display: inline-block; margin-top: 2rem; color: #c5a263; text-decoration: none; }
        footer { background: #1a0f0a; color: #999; text-align: center; padding: 1.5rem; margin-top: 2rem; }
        @media (max-width: 768px) { .rooms-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo"><h1>Royal Estate</h1></div>
    <a href="index.php" style="color:#c5a263;"><i class="fas fa-home"></i> Home</a>
</div>

<div class="container">
    <div class="search-summary">
        <h2>🔍 Available Rooms</h2>
        <?php if ($checkin && $checkout): ?>
            <p><strong>Check In:</strong> <?php echo htmlspecialchars($checkin); ?> &nbsp;|&nbsp; 
               <strong>Check Out:</strong> <?php echo htmlspecialchars($checkout); ?> &nbsp;|&nbsp;
               <strong>Guests:</strong> <?php echo $guests; ?></p>
        <?php else: ?>
            <p>Please select dates and number of guests.</p>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <div class="error-msg"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (empty($available_rooms) && !$error && $checkin && $checkout): ?>
        <div class="no-results">
            <i class="fas fa-bed" style="font-size: 3rem; color: #c5a263;"></i>
            <h3>No rooms available for selected dates</h3>
            <p>Please try different dates or reduce number of guests.</p>
            <a href="index.php" class="btn-book">← Back to Home</a>
        </div>
    <?php elseif (!empty($available_rooms)): ?>
        <div class="rooms-grid">
            <?php foreach ($available_rooms as $room): ?>
            <div class="room-card">
                <div class="room-image">
                    <i class="fas fa-hotel"></i>
                </div>
                <div class="room-info">
                    <h3><?php echo htmlspecialchars($room['name']); ?></h3>
                    <div class="price">LKR <?php echo number_format($room['price']); ?> <span style="font-size: 0.9rem;">/ night</span></div>
                    <div class="features">
                        <span><i class="fas fa-users"></i> Max <?php echo $room['max_guests']; ?> guests</span>
                        <span><i class="fas fa-wifi"></i> Free WiFi</span>
                        <span><i class="fas fa-tv"></i> Smart TV</span>
                    </div>
                    <p style="color: #666; margin-bottom: 1rem;"><?php echo htmlspecialchars(substr($room['description'], 0, 100)); ?>...</p>
                    <a href="room-details.php?id=<?php echo $room['id']; ?>" class="btn-book">View Details →</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php elseif (!$checkin || !$checkout): ?>
        <div class="no-results">
            <i class="fas fa-calendar-alt" style="font-size: 3rem; color: #c5a263;"></i>
            <h3>Please provide check-in and check-out dates</h3>
            <a href="index.php" class="btn-book">← Back to Home</a>
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Homepage</a>
    </div>
</div>

<footer>
    <p>&copy; 2024 Royal Estate. All rights reserved.</p>
</footer>

</body>
</html>