<?php
session_start();
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'owner')) {
    header('Location: ../index.php');
    exit();
}
$admin_name = $_SESSION['user_name'];

// Avatar path (adjust if needed)
$admin_avatar = '../assets/images/admin-avatar.jpg';
$default_avatar = 'https://randomuser.me/api/portraits/men/2.jpg';

require_once '../config/db_connection.php';

// ============================================================
// ✅ STATS CARDS - REAL TIME DATA FROM DATABASE
// ============================================================

// Total Rooms (active)
$rooms_total = $conn->query("SELECT COUNT(*) as cnt FROM rooms WHERE status = 'active'")->fetch_assoc()['cnt'] ?? 0;

// Total Event Halls (active)
$halls_total = $conn->query("SELECT COUNT(*) as cnt FROM event_halls WHERE status = 'active'")->fetch_assoc()['cnt'] ?? 0;

// Total Packages (active)
$packages_total = $conn->query("SELECT COUNT(*) as cnt FROM packages WHERE status = 'active'")->fetch_assoc()['cnt'] ?? 0;

// Total Bookings (room + event)
$total_room_bookings = $conn->query("SELECT COUNT(*) as cnt FROM room_bookings")->fetch_assoc()['cnt'] ?? 0;
$total_event_bookings = $conn->query("SELECT COUNT(*) as cnt FROM event_bookings")->fetch_assoc()['cnt'] ?? 0;
$total_bookings = $total_room_bookings + $total_event_bookings;

// ============================================================
// ✅ MONTHLY REVENUE - REAL TIME (LAST 30 DAYS) - ONLY PAID BOOKINGS
// ============================================================

// Room Revenue (pending/confirmed/completed + payment_status = 'paid')
$room_rev = $conn->query("
    SELECT COALESCE(SUM(total_price), 0) as rev 
    FROM room_bookings 
    WHERE status IN ('pending', 'confirmed', 'completed') 
    AND payment_status = 'paid'
    AND check_in >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetch_assoc()['rev'] ?? 0;

// Event Revenue (pending/approved/completed + payment_status = 'paid')
$event_rev = $conn->query("
    SELECT COALESCE(SUM(final_price), 0) as rev 
    FROM event_bookings 
    WHERE status IN ('pending', 'approved', 'completed') 
    AND payment_status = 'paid'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetch_assoc()['rev'] ?? 0;

$monthly_revenue = $room_rev + $event_rev;

// ============================================================
// ✅ MONTHLY REVENUE TREND (Last 6 months - Real Data)
// ============================================================
$trend_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_name = date('M', strtotime("-$i months"));
    
    // Room revenue for that month
    $room_month = $conn->query("
        SELECT COALESCE(SUM(total_price), 0) as rev 
        FROM room_bookings 
        WHERE status IN ('pending', 'confirmed', 'completed') 
        AND payment_status = 'paid'
        AND DATE_FORMAT(check_in, '%Y-%m') = '$month'
    ")->fetch_assoc()['rev'] ?? 0;
    
    // Event revenue for that month
    $event_month = $conn->query("
        SELECT COALESCE(SUM(final_price), 0) as rev 
        FROM event_bookings 
        WHERE status IN ('pending', 'approved', 'completed') 
        AND payment_status = 'paid'
        AND DATE_FORMAT(created_at, '%Y-%m') = '$month'
    ")->fetch_assoc()['rev'] ?? 0;
    
    $trend_data[] = [
        'month' => $month_name,
        'revenue' => ($room_month ?? 0) + ($event_month ?? 0)
    ];
}
$max_revenue = max(array_column($trend_data, 'revenue'));
if ($max_revenue == 0) $max_revenue = 1;

// ============================================================
// ✅ MOST POPULAR ROOMS - ONLY PAID BOOKINGS
// ============================================================
$popular_rooms = $conn->query("
    SELECT r.name, COUNT(rb.id) as bookings 
    FROM room_bookings rb 
    JOIN rooms r ON rb.room_id = r.id 
    WHERE rb.status IN ('pending', 'confirmed', 'completed') 
    AND rb.payment_status = 'paid'
    GROUP BY rb.room_id 
    ORDER BY bookings DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// If no paid bookings, show all rooms with 0
if (empty($popular_rooms)) {
    $popular_rooms = $conn->query("
        SELECT name, 0 as bookings 
        FROM rooms 
        WHERE status = 'active' 
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);
}

// If still empty, use default
if (empty($popular_rooms)) {
    $popular_rooms = [
        ['name' => 'Deluxe King Room', 'bookings' => 0],
        ['name' => 'Executive Suite', 'bookings' => 0],
        ['name' => 'Presidential Suite', 'bookings' => 0],
        ['name' => 'Family Suite', 'bookings' => 0],
        ['name' => 'Ocean View Room', 'bookings' => 0]
    ];
}

// Calculate total bookings for pie chart
$total_bookings_popular = array_sum(array_column($popular_rooms, 'bookings'));
if ($total_bookings_popular == 0) $total_bookings_popular = 1;

// Colors for pie chart
$pie_colors = ['#c5a263', '#8b691f', '#f0d5a8', '#a67c52', '#d4b896'];

// ============================================================
// ✅ BOOKING STATUS DISTRIBUTION (Room Bookings Only)
// ============================================================
$status_counts = [
    'pending' => $conn->query("SELECT COUNT(*) as cnt FROM room_bookings WHERE status = 'pending'")->fetch_assoc()['cnt'] ?? 0,
    'confirmed' => $conn->query("SELECT COUNT(*) as cnt FROM room_bookings WHERE status = 'confirmed'")->fetch_assoc()['cnt'] ?? 0,
    'completed' => $conn->query("SELECT COUNT(*) as cnt FROM room_bookings WHERE status = 'completed'")->fetch_assoc()['cnt'] ?? 0,
    'cancelled' => $conn->query("SELECT COUNT(*) as cnt FROM room_bookings WHERE status = 'cancelled'")->fetch_assoc()['cnt'] ?? 0
];
$total_status = array_sum($status_counts);
if ($total_status == 0) $total_status = 1;

// ============================================================
// ✅ RECENT BOOKINGS (Last 5 records only)
// ============================================================

// Recent Room Bookings (Last 5)
$recent_rooms = $conn->query("
    SELECT rb.id, u.name AS customer, r.name AS room, rb.check_in, rb.check_out, rb.total_price, rb.status, rb.payment_status 
    FROM room_bookings rb 
    JOIN users u ON rb.user_id = u.id 
    JOIN rooms r ON rb.room_id = r.id 
    ORDER BY rb.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Recent Event Bookings (Last 5)
$recent_events = $conn->query("
    SELECT eb.id, u.name AS customer, h.name AS hall, eb.event_date, p.name AS package, eb.guests, eb.estimated_price, eb.final_price, eb.status, eb.payment_status 
    FROM event_bookings eb 
    JOIN users u ON eb.user_id = u.id 
    JOIN event_halls h ON eb.hall_id = h.id 
    JOIN packages p ON eb.package_id = p.id 
    ORDER BY eb.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Royal Estate</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f0eb; }
        .admin-container { display: flex; min-height: 100vh; }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #2c1810 0%, #1a0f0a 100%);
            color: white;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar-header {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #c5a263;
            margin-bottom: 1rem;
            object-fit: cover;
        }
        .sidebar-header h2 {
            font-family: 'Playfair Display', serif;
            color: #c5a263;
            font-size: 1.5rem;
        }
        .sidebar-header p {
            font-size: 0.7rem;
            opacity: 0.7;
        }
        .sidebar-nav {
            margin-top: 2rem;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar-nav a i { 
            width: 24px;
        }
        .sidebar-nav a:hover, 
        .sidebar-nav a.active {
            background: rgba(197,162,99,0.2);
            color: #c5a263;
            border-left-color: #c5a263;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        
        .top-bar {
            background: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .top-bar h2 {
            color: #2c1810;
            font-size: 1.3rem;
        }
        .top-bar h2 span {
            color: #c5a263;
        }
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .top-bar-right img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #c5a263;
        }
        .btn-view-website {
            background: #c5a263;
            color: white;
            padding: 6px 15px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: 0.2s;
        }
        .btn-view-website:hover {
            background: #8b691f;
            transform: translateY(-2px);
        }
        .btn-logout {
            background: #dc3545;
            color: white;
            padding: 6px 15px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: 0.2s;
        }
        .btn-logout:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.2rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.2rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .stat-card .stat-icon {
            font-size: 1.8rem;
            color: #c5a263;
            margin-bottom: 0.3rem;
            display: block;
        }
        .stat-card h3 {
            font-size: 1.8rem;
            color: #c5a263;
            margin-bottom: 0.2rem;
            font-weight: 700;
        }
        .stat-card p {
            color: #666;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .graphs-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .graph-card {
            background: white;
            border-radius: 20px;
            padding: 1.2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .graph-card .graph-title {
            color: #2c1810;
            border-left: 4px solid #c5a263;
            padding-left: 0.8rem;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .pie-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        .pie-chart {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            box-shadow: 0 5px 25px rgba(0,0,0,0.12);
            position: relative;
            transition: transform 0.3s ease;
            flex-shrink: 0;
        }
        .pie-chart:hover {
            transform: scale(1.05);
        }
        .pie-chart::after {
            content: '<?php echo $total_bookings_popular; ?>';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            color: #2c1810;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .pie-legend {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            min-width: 120px;
        }
        .pie-legend .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: #555;
            padding: 2px 6px;
            border-radius: 4px;
            transition: 0.2s;
        }
        .pie-legend .legend-item:hover {
            background: #f5f0eb;
        }
        .pie-legend .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            flex-shrink: 0;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .pie-legend .legend-count {
            margin-left: auto;
            font-weight: 600;
            color: #2c1810;
            font-size: 0.8rem;
        }
        .no-data-message {
            text-align: center;
            padding: 1rem;
            color: #999;
            font-size: 0.85rem;
        }
        .no-data-message i {
            font-size: 2rem;
            color: #c5a263;
            display: block;
            margin-bottom: 0.5rem;
        }

        .progress-item {
            margin-bottom: 0.7rem;
        }
        .progress-item .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #555;
            margin-bottom: 2px;
        }
        .progress-item .progress-label .value {
            font-weight: 600;
            color: #2c1810;
        }
        .progress-bar-bg {
            background: #f0e5d8;
            border-radius: 20px;
            height: 20px;
            overflow: hidden;
            position: relative;
        }
        .progress-bar-fill {
            background: linear-gradient(90deg, #28a745, #17a2b8);
            height: 20px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 8px;
            color: white;
            font-size: 0.55rem;
            font-weight: 600;
            transition: width 1s ease;
            min-width: 25px;
        }
        .progress-bar-fill .bar-value {
            font-size: 0.55rem;
            font-weight: 600;
        }

        .recent-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .card {
            background: white;
            border-radius: 20px;
            padding: 1.2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .card-header h3 {
            color: #2c1810;
            border-left: 4px solid #c5a263;
            padding-left: 0.8rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .card-header .view-all {
            color: #c5a263;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 500;
            transition: 0.2s;
        }
        .card-header .view-all:hover {
            color: #8b691f;
            transform: translateX(3px);
        }

        .table-wrapper {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75rem;
        }
        th, td {
            padding: 6px 4px;
            text-align: left;
            border-bottom: 1px solid #f0e5d8;
            vertical-align: middle;
        }
        th {
            background: #f8f4ef;
            font-weight: 600;
            color: #2c1810;
            white-space: nowrap;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            word-break: break-word;
        }
        .no-results {
            text-align: center;
            padding: 1rem;
            color: #999;
            font-size: 0.8rem;
        }

        .badge {
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-block;
            font-size: 0.6rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-paid {
            background: #d4edda;
            color: #155724;
        }
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        .badge-confirmed {
            background: #d4edda;
            color: #155724;
        }
        .badge-approved {
            background: #d4edda;
            color: #155724;
        }
        .badge-completed {
            background: #d1ecf1;
            color: #0c5460;
        }
        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 1024px) {
            .graphs-grid {
                grid-template-columns: 1fr;
            }
            .recent-section {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            .sidebar { left: -280px; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0; }
            .top-bar {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }
            .top-bar-right {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem;
            }
            .stat-card h3 {
                font-size: 1.4rem;
            }
            .graphs-grid {
                grid-template-columns: 1fr;
            }
            .pie-container {
                flex-direction: column;
                gap: 1rem;
            }
            .pie-chart {
                width: 140px;
                height: 140px;
            }
            .pie-chart::after {
                width: 45px;
                height: 45px;
                font-size: 0.8rem;
            }
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead { display: none; }
            tr {
                border: 1px solid #eee;
                margin-bottom: 0.5rem;
                border-radius: 10px;
                padding: 0.5rem;
            }
            td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 4px 6px;
                border: none;
                border-bottom: 1px solid #f5f0eb;
                font-size: 0.7rem;
            }
            td:last-child { border-bottom: none; }
            td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #2c1810;
                margin-right: 1rem;
                flex-shrink: 0;
                font-size: 0.65rem;
            }
        }
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.5rem;
            }
            .stat-card {
                padding: 0.8rem;
            }
            .stat-card h3 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
<div class="admin-container">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo htmlspecialchars($admin_avatar); ?>" alt="Avatar" onerror="this.src='<?php echo $default_avatar; ?>'">
            <h2>ROYAL ESTATE</h2>
            <p>Admin Panel</p>
        </div>
        <div class="sidebar-nav">
            <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage-rooms.php"><i class="fas fa-bed"></i> Manage Rooms</a>
            <a href="manage-halls.php"><i class="fas fa-building"></i> Event Halls</a>
            <a href="manage-packages.php"><i class="fas fa-gift"></i> Packages</a>
            <a href="room-bookings.php"><i class="fas fa-bed"></i> Room Bookings</a>
            <a href="event-bookings.php"><i class="fas fa-calendar-alt"></i> Event Bookings</a>
            <a href="package-bookings.php"><i class="fas fa-gift"></i> Package Bookings</a>
            <a href="manage-messages.php"><i class="fas fa-envelope"></i> Messages</a>
            <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h2>Welcome back, <span><?php echo htmlspecialchars($admin_name); ?></span>!</h2>
            <div class="top-bar-right">
                <!-- ✅ View Website link is correctly mapped to root index.php -->
                <a href="../index.php?view=website" target="_blank" class="btn-view-website">
                    <i class="fas fa-external-link-alt"></i> View Website
                </a>
                <img src="<?php echo htmlspecialchars($admin_avatar); ?>" onerror="this.src='<?php echo $default_avatar; ?>'">
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon"><i class="fas fa-bed"></i></span>
                <h3><?php echo $rooms_total; ?></h3>
                <p>Total Rooms</p>
            </div>
            <div class="stat-card">
                <span class="stat-icon"><i class="fas fa-building"></i></span>
                <h3><?php echo $halls_total; ?></h3>
                <p>Total Event Halls</p>
            </div>
            <div class="stat-card">
                <span class="stat-icon"><i class="fas fa-gift"></i></span>
                <h3><?php echo $packages_total; ?></h3>
                <p>Total Packages</p>
            </div>
            <div class="stat-card">
                <span class="stat-icon"><i class="fas fa-calendar-check"></i></span>
                <h3><?php echo $total_bookings; ?></h3>
                <p>Total Bookings</p>
            </div>
            <div class="stat-card">
                <span class="stat-icon"><i class="fas fa-money-bill-wave"></i></span>
                <h3>LKR <?php echo number_format($monthly_revenue); ?></h3>
                <p>Monthly Revenue</p>
            </div>
        </div>

        <!-- Graphs -->
        <div class="graphs-grid">
            <!-- Most Popular Rooms - Pie Chart -->
            <div class="graph-card">
                <div class="graph-title"><i class="fas fa-trophy" style="color:#c5a263;"></i> Most Popular Rooms</div>
                
                <?php 
                $has_bookings = $conn->query("SELECT COUNT(*) as cnt FROM room_bookings WHERE status IN ('pending', 'confirmed', 'completed') AND payment_status = 'paid'")->fetch_assoc()['cnt'] ?? 0;
                
                if ($has_bookings == 0): ?>
                    <div class="no-data-message">
                        <i class="fas fa-bed"></i>
                        <p>No paid room bookings yet.</p>
                        <small style="color:#bbb;">Users need to complete payment to see data here.</small>
                    </div>
                <?php else: ?>
                    <div class="pie-container">
                        <div class="pie-chart" style="background: conic-gradient(
                            <?php 
                            $angle = 0;
                            foreach($popular_rooms as $index => $room):
                                $percent = ($room['bookings'] / $total_bookings_popular) * 100;
                                $color = $pie_colors[$index % count($pie_colors)];
                                echo $color . ' ' . $angle . '% ' . ($angle + $percent) . '%, ';
                                $angle += $percent;
                            endforeach;
                            ?>
                            #f5f0eb <?php echo $angle; ?>% 100%
                        );">
                        </div>
                        <div class="pie-legend">
                            <?php foreach($popular_rooms as $index => $room): 
                                $color = $pie_colors[$index % count($pie_colors)];
                            ?>
                            <div class="legend-item">
                                <span class="legend-color" style="background:<?php echo $color; ?>;"></span>
                                <span><?php echo htmlspecialchars(substr($room['name'], 0, 15)); ?></span>
                                <span class="legend-count"><?php echo $room['bookings']; ?></span>
                            </div>
                            <?php endforeach; ?>
                            <div class="legend-item" style="border-top:1px solid #eee; padding-top:0.4rem; margin-top:0.2rem;">
                                <span style="font-weight:600; color:#2c1810;">Total</span>
                                <span class="legend-count" style="font-size:0.9rem;"><?php echo $total_bookings_popular; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Monthly Revenue Trend -->
            <div class="graph-card">
                <div class="graph-title"><i class="fas fa-chart-line" style="color:#c5a263;"></i> Monthly Revenue Trend</div>
                <?php foreach($trend_data as $item): 
                    $percent = ($item['revenue'] / $max_revenue) * 100;
                    if ($percent < 10 && $item['revenue'] > 0) $percent = 10;
                ?>
                <div class="progress-item">
                    <div class="progress-label">
                        <span><i class="fas fa-calendar-alt" style="color:#28a745; width:14px;"></i> <?php echo $item['month']; ?></span>
                        <span class="value">LKR <?php echo number_format($item['revenue']); ?></span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: <?php echo $percent; ?>%;">
                            <?php if($item['revenue'] > 0): ?>
                                <span class="bar-value">LKR <?php echo number_format($item['revenue']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="recent-section">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bed" style="color:#c5a263;"></i> Recent Room Bookings</h3>
                    <a href="room-bookings.php" class="view-all">View All →</a>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Room</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_rooms)): ?>
                                <tr><td colspan="7" class="no-results">No room bookings yet.</td></tr>
                            <?php else: ?>
                                <?php foreach($recent_rooms as $b): ?>
                                <tr>
                                    <td data-label="ID">#<?php echo $b['id']; ?></td>
                                    <td data-label="Customer"><?php echo htmlspecialchars($b['customer']); ?></td>
                                    <td data-label="Room"><?php echo htmlspecialchars($b['room']); ?></td>
                                    <td data-label="Check In"><?php echo $b['check_in']; ?></td>
                                    <td data-label="Check Out"><?php echo $b['check_out']; ?></td>
                                    <td data-label="Total">LKR <?php echo number_format($b['total_price']); ?></td>
                                    <td data-label="Status">
                                        <?php 
                                        if (isset($b['payment_status']) && $b['payment_status'] === 'paid') {
                                            echo '<span class="badge badge-paid"><i class="fas fa-check-circle"></i> Paid</span>';
                                        } else {
                                            echo '<span class="badge badge-' . strtolower($b['status']) . '">' . ucfirst($b['status']) . '</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt" style="color:#c5a263;"></i> Recent Event Bookings</h3>
                    <a href="event-bookings.php" class="view-all">View All →</a>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Hall</th>
                                <th>Event Date</th>
                                <th>Package</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_events)): ?>
                                <tr><td colspan="7" class="no-results">No event bookings yet.</td></tr>
                            <?php else: ?>
                                <?php foreach($recent_events as $b): ?>
                                <tr>
                                    <td data-label="ID">#<?php echo $b['id']; ?></td>
                                    <td data-label="Customer"><?php echo htmlspecialchars($b['customer']); ?></td>
                                    <td data-label="Hall"><?php echo htmlspecialchars($b['hall']); ?></td>
                                    <td data-label="Event Date"><?php echo $b['event_date']; ?></td>
                                    <td data-label="Package"><?php echo htmlspecialchars($b['package']); ?></td>
                                    <td data-label="Total">
                                        <?php 
                                        $price = $b['final_price'] ?? $b['estimated_price'];
                                        echo 'LKR ' . number_format($price);
                                        ?>
                                    </td>
                                    <td data-label="Status">
                                        <?php 
                                        if (isset($b['payment_status']) && $b['payment_status'] === 'paid') {
                                            echo '<span class="badge badge-paid"><i class="fas fa-check-circle"></i> Paid</span>';
                                        } else {
                                            echo '<span class="badge badge-' . strtolower($b['status']) . '">' . ucfirst($b['status']) . '</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ================================================================
    // ✅ MOBILE SIDEBAR TOGGLE
    // ================================================================
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if(menuToggle) {
        menuToggle.addEventListener('click', () => sidebar.classList.toggle('active'));
    }

    // ================================================================
    // ✅ AUTO REFRESH EVERY 60 SECONDS (Real-time updates)
    // ================================================================
    setTimeout(function() {
        location.reload();
    }, 60000);

    console.log('🔄 Dashboard auto-refreshes every 60 seconds for real-time data!');
    console.log('💰 Monthly Revenue: LKR <?php echo number_format($monthly_revenue); ?>');
    console.log('📊 Total Bookings: <?php echo $total_bookings; ?>');
</script>
</body>
</html>