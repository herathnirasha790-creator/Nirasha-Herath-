<?php
session_start();
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'owner')) {
    header('Location: ../index.php');
    exit();
}
$admin_name = $_SESSION['user_name'];
$admin_avatar = '../assets/images/admin-avatar.jpg';
$default_avatar = 'https://randomuser.me/api/portraits/men/2.jpg';

require_once '../config/db_connection.php';

// ✅ Get filter parameters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';

// ✅ Build WHERE clause
$where = "WHERE 1=1";
if (!empty($from_date) && !empty($to_date)) {
    $where .= " AND DATE(rb.check_in) >= '$from_date' AND DATE(rb.check_in) <= '$to_date'";
}
if (!empty($filter_month)) {
    $where .= " AND DATE_FORMAT(rb.check_in, '%Y-%m') = '$filter_month'";
}

// ✅ Fetch Room Bookings with filter
$bookings = $conn->query("
    SELECT rb.id, u.name AS customer, u.email, r.name AS room, 
           rb.check_in, rb.check_out, rb.guests, rb.total_price, 
           rb.status, rb.payment_status 
    FROM room_bookings rb 
    JOIN users u ON rb.user_id = u.id 
    JOIN rooms r ON rb.room_id = r.id 
    $where
    ORDER BY rb.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$total_count = count($bookings);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Bookings | Royal Estate Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ========== BASE STYLES ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f0eb; }
        .admin-container { display: flex; min-height: 100vh; }
        
        /* ========== SIDEBAR - EXACT COPY FROM DASHBOARD ========== */
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
        /* ✅ EXACT SAME AS DASHBOARD */
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
        .sidebar-nav a:hover i,
        .sidebar-nav a.active i {
            color: #c5a263;
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

        /* ========== CARD ========== */
        .card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.2rem;
        }
        .card-header h3 {
            color: #2c1810;
            border-left: 4px solid #c5a263;
            padding-left: 1rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .badge-count {
            background: #c5a263;
            color: white;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
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

        /* ========== TABLE - ACTIONS COLUMN REMOVED ========== */
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th, td { padding: 10px 8px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        th { background: #f8f4ef; font-weight: 600; color: #2c1810; white-space: nowrap; }
        td { word-break: break-word; }

        /* ========== BADGES ========== */
        .badge { 
            padding: 3px 10px; 
            border-radius: 20px; 
            display: inline-block; 
            font-size: 0.7rem; 
            font-weight: 600; 
            white-space: nowrap; 
        }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-confirmed { background: #d4edda; color: #155724; }
        .badge-completed { background: #d1ecf1; color: #0c5460; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        .badge-paid { background: #28a745; color: white; }

        .no-results { text-align: center; padding: 2rem; color: #999; }

        /* ========== RESPONSIVE ========== */
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
            .filter-bar { 
                flex-direction: column; 
                align-items: stretch; 
            }
            .filter-bar .filter-group { 
                display: flex; 
                flex-wrap: wrap; 
                align-items: center; 
                gap: 0.3rem; 
            }
            table, thead, tbody, th, td, tr { display: block; }
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
                font-size: 0.75rem; 
            }
            td:last-child { border-bottom: none; }
            td::before { 
                content: attr(data-label); 
                font-weight: 600; 
                color: #2c1810; 
                margin-right: 1rem; 
                flex-shrink: 0; 
                font-size: 0.7rem; 
            }
        }
        @media (max-width: 480px) {
            .card { padding: 1rem; }
            .filter-bar { padding: 0.8rem; }
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
            <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage-rooms.php"><i class="fas fa-bed"></i> Manage Rooms</a>
            <a href="manage-halls.php"><i class="fas fa-building"></i> Event Halls</a>
            <a href="manage-packages.php"><i class="fas fa-gift"></i> Packages</a>
            <a href="room-bookings.php" class="active"><i class="fas fa-bed"></i> Room Bookings</a>
            <a href="event-bookings.php"><i class="fas fa-calendar-alt"></i> Event Bookings</a>
            <a href="package-bookings.php"><i class="fas fa-gift"></i> Package Bookings</a>
            <a href="manage-messages.php"><i class="fas fa-envelope"></i> Messages</a>
            <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h2><i class="fas fa-bed"></i> Room Bookings</h2>
            <div class="top-bar-right">
                <img src="<?php echo htmlspecialchars($admin_avatar); ?>" onerror="this.src='<?php echo $default_avatar; ?>'">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> All Room Bookings <span class="badge-count"><?php echo $total_count; ?></span></h3>
            </div>

            <!-- Filter Bar -->
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
                <a href="room-bookings.php" class="btn-clear"><i class="fas fa-times"></i> Clear</a>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Room</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Guests</th>
                            <th>Total (LKR)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($bookings)): ?>
                            <tr><td colspan="9" class="no-results">No room bookings found.</td></tr>
                        <?php else: ?>
                            <?php foreach($bookings as $b): ?>
                            <tr>
                                <td data-label="ID">#<?php echo $b['id']; ?></td>
                                <td data-label="Customer"><?php echo htmlspecialchars($b['customer']); ?></td>
                                <td data-label="Email"><?php echo htmlspecialchars($b['email']); ?></td>
                                <td data-label="Room"><?php echo htmlspecialchars($b['room']); ?></td>
                                <td data-label="Check In"><?php echo $b['check_in']; ?></td>
                                <td data-label="Check Out"><?php echo $b['check_out']; ?></td>
                                <td data-label="Guests"><?php echo $b['guests']; ?></td>
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
    </div>
</div>

<script>
    function applyFilter() {
        const fromDate = document.getElementById('fromDate').value;
        const toDate = document.getElementById('toDate').value;
        const filterMonth = document.getElementById('filterMonth').value;
        let url = 'room-bookings.php?';
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

    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if(menuToggle) {
        menuToggle.addEventListener('click', () => sidebar.classList.toggle('active'));
    }
</script>
</body>
</html>