<?php
session_start();
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'owner')) {
    header('Location: ../index.php');
    exit();
}
$admin_name = $_SESSION['user_name'];
$admin_avatar = $_SESSION['user_avatar'] ?? '../assets/images/admin-avatar.jpg';
$default_avatar = 'https://randomuser.me/api/portraits/men/2.jpg';

// Dummy data – replace with DB queries
$total_rooms = 24;
$total_halls = 8;
$pending_approvals = 12;
$total_bookings = 156;
$monthly_revenue = 28450;
$occupancy_rate = 68;

// Recent Room Bookings
$recent_room_bookings = [
    ['id'=>101, 'customer'=>'Saman Perera', 'room'=>'Deluxe King', 'checkin'=>'2024-12-20', 'checkout'=>'2024-12-25', 'total'=>600, 'status'=>'Confirmed'],
    ['id'=>102, 'customer'=>'Nuwan Silva', 'room'=>'Executive Suite', 'checkin'=>'2024-12-10', 'checkout'=>'2024-12-15', 'total'=>1000, 'status'=>'Pending'],
    ['id'=>103, 'customer'=>'Amali Fernando', 'room'=>'Presidential Suite', 'checkin'=>'2025-01-05', 'checkout'=>'2025-01-10', 'total'=>1750, 'status'=>'Confirmed'],
];

// Recent Event Bookings
$recent_event_bookings = [
    ['id'=>201, 'customer'=>'Saman Perera', 'hall'=>'Royal Wedding Hall', 'date'=>'2025-02-14', 'package'=>'Silver Wedding', 'guests'=>250, 'total'=>2500, 'status'=>'Pending'],
    ['id'=>202, 'customer'=>'Amali Fernando', 'hall'=>'Garden Party Hall', 'date'=>'2024-11-20', 'package'=>'Birthday Bash', 'guests'=>80, 'total'=>800, 'status'=>'Approved'],
];

// Popular Rooms
$popular_rooms = [
    ['name'=>'Deluxe King Room', 'bookings'=>45],
    ['name'=>'Executive Suite', 'bookings'=>32],
    ['name'=>'Presidential Suite', 'bookings'=>18],
];
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
            left: 0;
            top: 0;
            bottom: 0;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.2rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            font-size: 1.8rem;
            color: #c5a263;
            margin-bottom: 0.25rem;
        }
        .stat-card p {
            color: #666;
            font-size: 0.8rem;
        }
        .quick-actions-bar {
            background: white;
            border-radius: 20px;
            padding: 0.8rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .quick-actions-bar a {
            background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white;
            padding: 8px 20px;
            border-radius: 40px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .quick-actions-bar a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(197,162,99,0.3);
        }
        .two-col {
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
        .card h3 {
            color: #2c1810;
            border-left: 4px solid #c5a263;
            padding-left: 0.8rem;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }
        th, td {
            padding: 8px 5px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f4ef;
            font-weight: 600;
            color: #2c1810;
        }
        .badge-pending {
            background: #fff3cd;
            color: #856404;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-block;
        }
        .badge-confirmed,
        .badge-approved {
            background: #d4edda;
            color: #155724;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-block;
        }
        .popular-item {
            margin-bottom: 1rem;
        }
        .popular-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            margin-bottom: 4px;
        }
        .bar-bg {
            background: #f0e5d8;
            border-radius: 20px;
            height: 24px;
            overflow: hidden;
        }
        .bar-fill {
            background: linear-gradient(90deg, #c5a263, #8b691f);
            height: 24px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 8px;
            color: white;
            font-size: 0.7rem;
            font-weight: 500;
        }
        .system-info {
            background: #fef5e6;
            border-radius: 20px;
            padding: 1rem;
        }
        .system-info p {
            font-size: 0.85rem;
            margin: 6px 0;
        }
        @media (max-width: 768px) {
            .sidebar { left: -280px; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0; }
            .two-col { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
        }
    </style>
</head>
<body>
<div class="admin-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo htmlspecialchars($admin_avatar); ?>" onerror="this.src='<?php echo $default_avatar; ?>'">
            <h2>ROYAL ESTATE</h2>
            <p style="font-size:0.7rem;">Admin Panel</p>
        </div>
        <div class="sidebar-nav">
            <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage-rooms.php"><i class="fas fa-bed"></i> Manage Rooms</a>
            <a href="manage-halls.php"><i class="fas fa-building"></i> Event Halls</a>
            <a href="manage-packages.php"><i class="fas fa-gift"></i> Packages</a>
            <a href="bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a>
            <a href="approve-events.php"><i class="fas fa-check-circle"></i> Approve Events</a>
            <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h2>Welcome back, <?php echo htmlspecialchars($admin_name); ?>!</h2>
            <div class="top-bar-right">
                <img src="<?php echo htmlspecialchars($admin_avatar); ?>" onerror="this.src='<?php echo $default_avatar; ?>'">
                <a href="logout.php" style="color:#c5a263;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card"><h3><?php echo $total_rooms; ?></h3><p>Total Rooms</p></div>
            <div class="stat-card"><h3><?php echo $total_halls; ?></h3><p>Event Halls</p></div>
            <div class="stat-card"><h3><?php echo $pending_approvals; ?></h3><p>Pending Approvals</p></div>
            <div class="stat-card"><h3><?php echo $total_bookings; ?></h3><p>Total Bookings</p></div>
            <div class="stat-card"><h3>LKR <?php echo number_format($monthly_revenue); ?></h3><p>Monthly Revenue</p></div>
            <div class="stat-card"><h3><?php echo $occupancy_rate; ?>%</h3><p>Occupancy Rate</p></div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions-bar">
            <a href="manage-rooms.php"><i class="fas fa-bed"></i> Add Room</a>
            <a href="manage-halls.php"><i class="fas fa-building"></i> Add Hall</a>
            <a href="manage-packages.php"><i class="fas fa-gift"></i> Add Package</a>
            <a href="approve-events.php"><i class="fas fa-check-circle"></i> Approve Events</a>
            <a href="reports.php"><i class="fas fa-chart-line"></i> View Reports</a>
            <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> View Website</a>
        </div>

        <!-- Recent Bookings (two columns) -->
        <div class="two-col">
            <div class="card">
                <h3>🛏️ Recent Room Bookings</h3>
                <div style="overflow-x: auto;">
                    <table>
                        <thead><tr><th>ID</th><th>Customer</th><th>Room</th><th>Check In</th><th>Check Out</th><th>Total</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach($recent_room_bookings as $b): ?>
                            <tr>
                                <td>#<?php echo $b['id']; ?></td>
                                <td><?php echo htmlspecialchars($b['customer']); ?></td>
                                <td><?php echo htmlspecialchars($b['room']); ?></td>
                                <td><?php echo $b['checkin']; ?></td>
                                <td><?php echo $b['checkout']; ?></td>
                                <td>LKR <?php echo number_format($b['total']); ?></td>
                                <td><span class="badge-<?php echo strtolower($b['status']); ?>"><?php echo $b['status']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </tr>
                </div>
                <div style="margin-top: 0.8rem; text-align: right;"><a href="bookings.php" style="color:#c5a263; font-size:0.8rem;">View all →</a></div>
            </div>
            <div class="card">
                <h3>🎉 Recent Event Bookings</h3>
                <div style="overflow-x: auto;">
                    <table>
                        <thead><tr><th>ID</th><th>Customer</th><th>Hall</th><th>Event Date</th><th>Package</th><th>Guests</th><th>Total</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach($recent_event_bookings as $b): ?>
                            <tr>
                                <td>#<?php echo $b['id']; ?></td>
                                <td><?php echo htmlspecialchars($b['customer']); ?></td>
                                <td><?php echo htmlspecialchars($b['hall']); ?></td>
                                <td><?php echo $b['date']; ?></td>
                                <td><?php echo htmlspecialchars($b['package']); ?></td>
                                <td><?php echo $b['guests']; ?></td>
                                <td>LKR <?php echo number_format($b['total']); ?></td>
                                <td><span class="badge-<?php echo strtolower($b['status']); ?>"><?php echo $b['status']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 0.8rem; text-align: right;"><a href="bookings.php" style="color:#c5a263; font-size:0.8rem;">View all →</a></div>
            </div>
        </div>

        <!-- Popular Rooms & Revenue Chart -->
        <div class="two-col">
            <div class="card">
                <h3>🏆 Most Popular Rooms (Last 30 days)</h3>
                <?php foreach($popular_rooms as $room): ?>
                <div class="popular-item">
                    <div class="popular-label">
                        <span><?php echo htmlspecialchars($room['name']); ?></span>
                        <span><?php echo $room['bookings']; ?> bookings</span>
                    </div>
                    <div class="bar-bg">
                        <div class="bar-fill" style="width: <?php echo min(100, ($room['bookings'] / 45) * 100); ?>%;">
                            <?php echo $room['bookings']; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="card">
                <h3>📊 Monthly Revenue Trend</h3>
                <?php
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May'];
                $revenues = [18200, 21500, 24800, 19300, 28450];
                $max_rev = max($revenues);
                foreach($months as $i => $mon):
                    $percent = ($revenues[$i] / $max_rev) * 100;
                ?>
                <div class="popular-item">
                    <div class="popular-label">
                        <span><?php echo $mon; ?></span>
                        <span>LKR <?php echo number_format($revenues[$i]); ?></span>
                    </div>
                    <div class="bar-bg">
                        <div class="bar-fill" style="width: <?php echo $percent; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- System Info -->
        <div class="card system-info">
            <h3>ℹ️ System Information</h3>
            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
            <p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>Last Login:</strong> <?php echo htmlspecialchars($admin_name); ?> (<?php echo date('Y-m-d H:i:s'); ?>)</p>
        </div>
    </div>
</div>
</body>
</html>