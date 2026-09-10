<?php
// ✅ Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'owner')) {
    header('Location: ../index.php');
    exit();
}
$admin_name = $_SESSION['user_name'];
$admin_avatar = '../assets/images/admin-avatar.jpg';
$default_avatar = 'https://randomuser.me/api/portraits/men/2.jpg';

// ✅ Current page detection
$current_page = basename($_SERVER['PHP_SELF']);

require_once '../config/db_connection.php';

// ✅ GET filter parameters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';

// ============================================================
// ✅ STATS CARDS - REAL TIME DATA
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
// ✅ REVENUE DATA (With Date Filter)
// ============================================================

// ✅ Build WHERE clause
$where_clause = "";
if (!empty($from_date) && !empty($to_date)) {
    $where_clause .= " AND DATE(created_at) >= '$from_date' AND DATE(created_at) <= '$to_date'";
}
if (!empty($filter_month)) {
    $where_clause .= " AND DATE_FORMAT(created_at, '%Y-%m') = '$filter_month'";
}

// Room Revenue (Removed status filter to include all bookings)
$room_rev_sql = "SELECT COALESCE(SUM(total_price), 0) as rev FROM room_bookings WHERE 1=1 $where_clause";
$room_revenue = $conn->query($room_rev_sql)->fetch_assoc()['rev'] ?? 0;

// Event Revenue (Removed status filter to include all bookings)
$event_rev_sql = "SELECT COALESCE(SUM(final_price), 0) as rev FROM event_bookings WHERE 1=1 $where_clause";
$event_revenue = $conn->query($event_rev_sql)->fetch_assoc()['rev'] ?? 0;

$total_revenue = $room_revenue + $event_revenue;

// ============================================================
// ✅ MOST BOOKED ROOM (Removed status filter)
// ============================================================
$most_booked = $conn->query("
    SELECT r.name, COUNT(rb.id) as cnt 
    FROM room_bookings rb 
    JOIN rooms r ON rb.room_id = r.id 
    GROUP BY rb.room_id 
    ORDER BY cnt DESC LIMIT 1
")->fetch_assoc();
$most_booked_room = $most_booked['name'] ?? 'No bookings yet';

// ============================================================
// ✅ OCCUPANCY RATE (Removed status filter)
// ============================================================
$total_rooms_count = $conn->query("SELECT COUNT(*) as cnt FROM rooms WHERE status = 'active'")->fetch_assoc()['cnt'] ?? 1;
$occupied_days = $conn->query("
    SELECT COUNT(DISTINCT check_in) as days 
    FROM room_bookings 
    WHERE check_in >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetch_assoc()['days'] ?? 0;
$occupancy_rate = round(($occupied_days / (30 * $total_rooms_count)) * 100);
$occupancy_rate = min(100, $occupancy_rate);

// ============================================================
// ✅ NOTES - Display only, no export logic here
// ============================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports | Royal Estate Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ========== BASE STYLES ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f0eb; }
        .admin-container { display: flex; min-height: 100vh; }
        
        /* ========== SIDEBAR ========== */
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

        /* ========== MAIN CONTENT ========== */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        
        /* ========== TOP BAR ========== */
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
        .top-bar h2 i {
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
        .top-bar-right a {
            color: #c5a263;
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
        }
        .top-bar-right a:hover {
            color: #8b691f;
        }

        /* ========== FILTER BAR ========== */
        .filter-bar {
            background: #f9f5f0;
            padding: 1rem 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }
        .filter-bar label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #2c1810;
            margin-right: 0.3rem;
        }
        .filter-bar input[type="date"],
        .filter-bar input[type="month"] {
            padding: 0.5rem 0.8rem;
            border-radius: 30px;
            border: 1px solid #ddd;
            font-family: 'Poppins', sans-serif;
            font-size: 0.8rem;
            background: white;
            outline: none;
            transition: 0.2s;
        }
        .filter-bar input:focus {
            border-color: #c5a263;
            box-shadow: 0 0 0 3px rgba(197,162,99,0.1);
        }
        .btn-filter {
            background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.8rem;
            transition: 0.2s;
            font-family: 'Poppins', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .btn-filter:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(197,162,99,0.3); 
        }
        .btn-clear {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.8rem;
            transition: 0.2s;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .btn-clear:hover { 
            background: #5a6268; 
            transform: translateY(-2px); 
        }

        /* ========== EXPORT BUTTONS ========== */
        .export-buttons {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
            margin: 1rem 0;
        }
        .btn-export-config {
            background: linear-gradient(135deg, #2c1810, #c5a263);
            color: white;
            border: none;
            padding: 0.6rem 1.8rem;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: 0.2s;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-export-config:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(197,162,99,0.3);
        }

        /* ========== STATS CARDS ========== */
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

        /* ========== REVENUE CARDS ========== */
        .revenue-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.2rem;
            margin-bottom: 1.5rem;
        }
        .revenue-card {
            background: white;
            border-radius: 16px;
            padding: 1.2rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: 0.2s;
        }
        .revenue-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .revenue-card .revenue-icon {
            font-size: 1.5rem;
            color: #c5a263;
            margin-bottom: 0.3rem;
            display: block;
        }
        .revenue-card h3 {
            font-size: 1.5rem;
            color: #c5a263;
            margin-bottom: 0.2rem;
            font-weight: 700;
        }
        .revenue-card p {
            color: #666;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* ========== EXTRA STATS ========== */
        .extra-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.2rem;
            margin-bottom: 1.5rem;
        }
        .extra-card {
            background: white;
            border-radius: 16px;
            padding: 1.2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: 0.2s;
            text-align: center;
        }
        .extra-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .extra-card h4 {
            color: #666;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .extra-card .value {
            font-size: 1.5rem;
            color: #c5a263;
            font-weight: 700;
        }

        /* ========== NOTES ========== */
        .notes-card {
            background: #fef5e6;
            border-radius: 16px;
            padding: 1.2rem;
            margin-top: 1.5rem;
        }
        .notes-card h4 {
            color: #2c1810;
            margin-bottom: 0.5rem;
        }
        .notes-card ul {
            margin-left: 1.5rem;
            color: #666;
            line-height: 1.8;
            font-size: 0.85rem;
        }

        /* ============================================================
           ✅ EXPORT CONFIG MODAL - Form Action Points to export_handler.php
           ============================================================ */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-container {
            background: white;
            border-radius: 24px;
            max-width: 600px;
            width: 100%;
            padding: 2rem;
            position: relative;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            animation: fadeInUp 0.3s;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 1.3rem;
            cursor: pointer;
            color: #999;
            transition: 0.2s;
            background: none;
            border: none;
        }
        .modal-close:hover {
            color: #c5a263;
            transform: rotate(90deg);
        }
        .modal-title {
            font-size: 1.5rem;
            color: #2c1810;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }
        .modal-subtitle {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        .modal-divider {
            height: 1px;
            background: #f0e5d8;
            margin: 1.5rem 0;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #2c1810;
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1px solid #e0d5cc;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            background: #fefcf8;
            transition: 0.2s;
        }
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #c5a263;
            box-shadow: 0 0 0 3px rgba(197,162,99,0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .radio-group {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            padding-top: 0.3rem;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 400;
            color: #555;
            font-size: 0.85rem;
            cursor: pointer;
        }
        .radio-group input[type="radio"] {
            width: auto;
            accent-color: #c5a263;
            cursor: pointer;
        }

        .btn-generate {
            background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: 0.2s;
            font-family: 'Poppins', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            justify-content: center;
        }
        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(197,162,99,0.3);
        }
        .btn-generate:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1024px) {
            .revenue-grid {
                grid-template-columns: 1fr 1fr;
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
            .revenue-grid {
                grid-template-columns: 1fr;
            }
            .extra-grid {
                grid-template-columns: 1fr;
            }
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .modal-container {
                padding: 1.5rem;
                margin: 1rem;
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
            <img src="<?php echo htmlspecialchars($admin_avatar); ?>" onerror="this.src='<?php echo $default_avatar; ?>'">
            <h2>ROYAL ESTATE</h2>
            <p>Admin Panel</p>
        </div>
        <div class="sidebar-nav">
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage-rooms.php" class="<?php echo ($current_page == 'manage-rooms.php') ? 'active' : ''; ?>"><i class="fas fa-bed"></i> Manage Rooms</a>
            <a href="manage-halls.php" class="<?php echo ($current_page == 'manage-halls.php') ? 'active' : ''; ?>"><i class="fas fa-building"></i> Event Halls</a>
            <a href="manage-packages.php" class="<?php echo ($current_page == 'manage-packages.php') ? 'active' : ''; ?>"><i class="fas fa-gift"></i> Packages</a>
            <a href="room-bookings.php" class="<?php echo ($current_page == 'room-bookings.php') ? 'active' : ''; ?>"><i class="fas fa-bed"></i> Room Bookings</a>
            <a href="event-bookings.php" class="<?php echo ($current_page == 'event-bookings.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Event Bookings</a>
            <a href="package-bookings.php" class="<?php echo ($current_page == 'package-bookings.php') ? 'active' : ''; ?>"><i class="fas fa-gift"></i> Package Bookings</a>
            <a href="manage-messages.php" class="<?php echo ($current_page == 'manage-messages.php') ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> Messages</a>
            <a href="reports.php" class="active"><i class="fas fa-chart-line"></i> Reports</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h2><i class="fas fa-chart-line"></i> Reports &amp; Exports</h2>
            <div class="top-bar-right">
                <img src="<?php echo htmlspecialchars($admin_avatar); ?>" onerror="this.src='<?php echo $default_avatar; ?>'">
                <a href="logout.php" style="color:#c5a263;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- ✅ Filter Bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <label><i class="fas fa-calendar-alt"></i> From:</label>
                <input type="date" id="fromDate" value="<?php echo $from_date; ?>">
            </div>
            <div class="filter-group">
                <label>To:</label>
                <input type="date" id="toDate" value="<?php echo $to_date; ?>">
            </div>
            <div class="filter-group">
                <label><i class="fas fa-calendar"></i> Month:</label>
                <input type="month" id="filterMonth" value="<?php echo $filter_month; ?>">
            </div>
            <button class="btn-filter" onclick="applyFilter()"><i class="fas fa-search"></i> Filter</button>
            <a href="reports.php" class="btn-clear"><i class="fas fa-times"></i> Clear</a>
        </div>

        <!-- ✅ Export Configuration Button -->
        <div class="export-buttons">
            <button class="btn-export-config" onclick="openExportModal()">
                <i class="fas fa-file-export"></i> Export Configuration
            </button>
        </div>

        <!-- ✅ Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon"><i class="fas fa-bed"></i></span>
                <h3><?php echo $rooms_total; ?></h3>
                <p>Total Rooms</p>
            </div>
            <div class="stat-card">
                <span class="stat-icon"><i class="fas fa-building"></i></span>
                <h3><?php echo $halls_total; ?></h3>
                <p>Event Halls</p>
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
        </div>

        <!-- ✅ Revenue Breakdown -->
        <div class="revenue-grid">
            <div class="revenue-card">
                <span class="revenue-icon"><i class="fas fa-hotel"></i></span>
                <h3>LKR <?php echo number_format($room_revenue); ?></h3>
                <p>Room Revenue</p>
            </div>
            <div class="revenue-card">
                <span class="revenue-icon"><i class="fas fa-calendar-alt"></i></span>
                <h3>LKR <?php echo number_format($event_revenue); ?></h3>
                <p>Event Revenue</p>
            </div>
            <div class="revenue-card">
                <span class="revenue-icon"><i class="fas fa-money-bill-wave"></i></span>
                <h3>LKR <?php echo number_format($total_revenue); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>

        <!-- ✅ Extra Stats -->
        <div class="extra-grid">
            <div class="extra-card">
                <h4>🏆 Most Booked Room</h4>
                <div class="value"><?php echo htmlspecialchars($most_booked_room); ?></div>
            </div>
            <div class="extra-card">
                <h4>📊 Occupancy Rate</h4>
                <div class="value"><?php echo $occupancy_rate; ?>%</div>
            </div>
        </div>

        <!-- ✅ Notes -->
        <div class="notes-card">
            <h4>📋 Report Notes</h4>
            <ul>
                <li>Room and Event revenue includes all bookings made in the system.</li>
                <li>Occupancy rate is calculated as (occupied days / total available days) × 100.</li>
                <li>Use date filters above to view data for specific periods.</li>
                <li>Click <strong>"Export Configuration"</strong> to generate custom reports.</li>
            </ul>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ✅ EXPORT CONFIGURATION MODAL - Form action points to export_handler.php -->
<!-- ============================================================ -->
<div class="modal-overlay" id="exportModal">
    <div class="modal-container">
        <button class="modal-close" onclick="closeExportModal()">
            <i class="fas fa-times"></i>
        </button>
        
        <h2 class="modal-title"><i class="fas fa-file-export" style="color:#c5a263;"></i> Export Configuration</h2>
        <p class="modal-subtitle">Generate and export organization data sheets safely.</p>
        
        <div class="modal-divider"></div>
        
        <!-- ✅ Form Action points to export_handler.php, opens in new tab -->
        <form method="POST" action="export_handler.php" target="_blank" id="exportForm">
            <div class="form-group">
                <label for="reportType">📄 Select Report Type</label>
                <select name="report_type" id="reportType" required>
                    <option value="revenue">💰 Revenue Summary Report</option>
                    <option value="room_bookings">🛏️ Room Bookings Report</option>
                    <option value="event_bookings">🎉 Event Bookings Report</option>
                    <option value="package_bookings">📦 Package Bookings Report</option>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>📅 From Date</label>
                    <input type="date" name="from_date" id="fromDateModal">
                </div>
                <div class="form-group">
                    <label>📅 To Date</label>
                    <input type="date" name="to_date" id="toDateModal">
                </div>
            </div>
            
            <div class="form-group">
                <label>📁 File Format</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="file_format" value="pdf" checked>
                        <i class="fas fa-file-pdf" style="color:#dc3545;"></i> PDF Document (.pdf)
                    </label>
                    <label>
                        <input type="radio" name="file_format" value="excel">
                        <i class="fas fa-file-excel" style="color:#28a745;"></i> Excel Document (.xlsx)
                    </label>
                </div>
            </div>
            
            <button type="submit" name="export_report" class="btn-generate" id="generateBtn">
                <i class="fas fa-download"></i> Generate &amp; Download Report
            </button>
        </form>
    </div>
</div>

<script>
    // ================================================================
    // ✅ APPLY FILTER
    // ================================================================
    function applyFilter() {
        const fromDate = document.getElementById('fromDate').value;
        const toDate = document.getElementById('toDate').value;
        const filterMonth = document.getElementById('filterMonth').value;
        let url = 'reports.php?';
        if (fromDate) url += 'from_date=' + fromDate + '&';
        if (toDate) url += 'to_date=' + toDate + '&';
        if (filterMonth) url += 'filter_month=' + filterMonth;
        window.location.href = url;
    }

    document.getElementById('fromDate').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') applyFilter();
    });
    document.getElementById('toDate').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') applyFilter();
    });
    document.getElementById('filterMonth').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') applyFilter();
    });

    // ================================================================
    // ✅ EXPORT MODAL
    // ================================================================
    function openExportModal() {
        document.getElementById('exportModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        const fromDate = document.getElementById('fromDate').value;
        const toDate = document.getElementById('toDate').value;
        if (fromDate) document.getElementById('fromDateModal').value = fromDate;
        if (toDate) document.getElementById('toDateModal').value = toDate;
    }

    function closeExportModal() {
        document.getElementById('exportModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    document.getElementById('exportModal').addEventListener('click', function(e) {
        if (e.target === this) closeExportModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeExportModal();
    });

    // ================================================================
    // ✅ FORM SUBMIT - Disable button, show loading, auto-reset after 5 seconds
    // ================================================================
    document.getElementById('exportForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('generateBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        
        // ✅ After 5 seconds, reset button (download should be complete by then)
        setTimeout(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-download"></i> Generate &amp; Download Report';
        }, 5000);
    });

    // ================================================================
    // ✅ MOBILE SIDEBAR TOGGLE
    // ================================================================
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if(menuToggle) {
        menuToggle.addEventListener('click', () => sidebar.classList.toggle('active'));
    }

    console.log('📊 Reports & Exports loaded successfully!');
</script>
</body>
</html>