<?php
session_start();

// Get room ID from URL
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Dummy room data – replace with DB query
$rooms = [
    1 => ['name'=>'Deluxe King Room', 'price'=>120, 'max_guests'=>2, 'size'=>'35 sqm', 'bed'=>'King Bed', 'view'=>'City View', 'description'=>'Spacious room with modern amenities and stunning city views. Includes free WiFi, smart TV, and coffee maker.'],
    2 => ['name'=>'Executive Suite', 'price'=>200, 'max_guests'=>2, 'size'=>'55 sqm', 'bed'=>'Queen Bed', 'view'=>'Garden View', 'description'=>'Luxury suite with separate living area, jacuzzi, and executive lounge access.'],
    3 => ['name'=>'Presidential Suite', 'price'=>350, 'max_guests'=>4, 'size'=>'85 sqm', 'bed'=>'King Bed', 'view'=>'Panoramic', 'description'=>'The epitome of luxury with private terrace, butler service, and premium amenities.'],
    4 => ['name'=>'Family Suite', 'price'=>180, 'max_guests'=>4, 'size'=>'65 sqm', 'bed'=>'2 Queen Beds', 'view'=>'City View', 'description'=>'Perfect for families with two connecting bedrooms and kid-friendly amenities.'],
    5 => ['name'=>'Ocean View Room', 'price'=>150, 'max_guests'=>2, 'size'=>'40 sqm', 'bed'=>'King Bed', 'view'=>'Ocean View', 'description'=>'Beautiful room with breathtaking ocean views and private balcony.'],
    6 => ['name'=>'Honeymoon Suite', 'price'=>250, 'max_guests'=>2, 'size'=>'60 sqm', 'bed'=>'King Bed', 'view'=>'Sunset View', 'description'=>'Romantic suite with heart-shaped jacuzzi, rose petal decorations, and champagne on arrival.'],
];

$room = isset($rooms[$room_id]) ? $rooms[$room_id] : $rooms[1];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($room['name']); ?> | Royal Estate</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f0eb; }
        .navbar { background: white; padding: 1rem 5%; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .logo h1 { font-size: 1.5rem; background: linear-gradient(135deg, #2c1810, #c5a263); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .detail-card { background: white; border-radius: 30px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .room-image { height: 100%; min-height: 400px; background: linear-gradient(135deg, #2c1810, #1a0f0a); display: flex; align-items: center; justify-content: center; color: white; font-size: 4rem; }
        .room-info { padding: 2rem; }
        .room-info h1 { font-size: 2rem; color: #2c1810; margin-bottom: 0.5rem; }
        .price { font-size: 1.8rem; color: #c5a263; font-weight: 600; margin-bottom: 1rem; }
        .price small { font-size: 0.9rem; color: #666; }
        .features { display: flex; gap: 1.5rem; margin: 1.5rem 0; flex-wrap: wrap; }
        .feature { display: flex; align-items: center; gap: 0.5rem; color: #555; }
        .feature i { color: #c5a263; width: 20px; }
        .description { color: #666; line-height: 1.8; margin-bottom: 2rem; }
        .btn-book { background: linear-gradient(135deg, #c5a263, #8b691f); color: white; border: none; padding: 12px 30px; border-radius: 50px; font-weight: 600; font-size: 1rem; cursor: pointer; text-decoration: none; display: inline-block; transition: 0.3s; }
        .btn-book:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(197,162,99,0.3); }
        .back-link { display: inline-block; margin-top: 1rem; color: #c5a263; text-decoration: none; margin-left: 1rem; }
        @media (max-width: 768px) { .detail-card { grid-template-columns: 1fr; } .room-image { min-height: 250px; } }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo"><h1>Royal Estate</h1></div>
    <div><a href="rooms.php" style="color:#c5a263;"><i class="fas fa-arrow-left"></i> Back to Rooms</a></div>
</div>

<div class="container">
    <div class="detail-card">
        <div class="room-image">
            <i class="fas fa-hotel"></i>
        </div>
        <div class="room-info">
            <h1><?php echo htmlspecialchars($room['name']); ?></h1>
            <div class="price">LKR <?php echo number_format($room['price']); ?> <small>/ night</small></div>
            
            <div class="features">
                <div class="feature"><i class="fas fa-users"></i> Max <?php echo $room['max_guests']; ?> Guests</div>
                <div class="feature"><i class="fas fa-expand"></i> <?php echo $room['size']; ?></div>
                <div class="feature"><i class="fas fa-bed"></i> <?php echo $room['bed']; ?></div>
                <div class="feature"><i class="fas fa-eye"></i> <?php echo $room['view']; ?></div>
            </div>
            
            <div class="description">
                <?php echo htmlspecialchars($room['description']); ?>
            </div>
            
            <a href="room-booking.php?id=<?php echo $room_id; ?>" class="btn-book">
                <i class="fas fa-calendar-check"></i> Book This Room
            </a>
            <a href="rooms.php" class="back-link">← View All Rooms</a>
        </div>
    </div>
</div>

</body>
</html>