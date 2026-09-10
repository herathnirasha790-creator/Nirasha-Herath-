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

// Fetch Room Bookings with user and room details
$room_sql = "SELECT rb.id, u.name AS customer, u.email, r.name AS room, rb.check_in, rb.check_out, rb.guests, rb.total_price, rb.status 
             FROM room_bookings rb 
             JOIN users u ON rb.user_id = u.id 
             JOIN rooms r ON rb.room_id = r.id 
             ORDER BY rb.created_at DESC";
$room_result = $conn->query($room_sql);
$room_bookings = $room_result->fetch_all(MYSQLI_ASSOC);

// Fetch Event Bookings with user, hall, package details
$event_sql = "SELECT eb.id, u.name AS customer, u.email, h.name AS hall, eb.event_date, p.name AS package, eb.guests, eb.estimated_price AS total, eb.status 
              FROM event_bookings eb 
              JOIN users u ON eb.user_id = u.id 
              JOIN event_halls h ON eb.hall_id = h.id 
              JOIN packages p ON eb.package_id = p.id 
              ORDER BY eb.created_at DESC";
$event_result = $conn->query($event_sql);
$event_bookings = $event_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Bookings | Royal Estate Admin</title>
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
        .sidebar-nav a i { width: 24px; }
        .sidebar-nav a:hover, .sidebar-nav a.active {
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
        .top-bar-right a {
            color: #c5a263;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        .top-bar-right a:hover {
            color: #8b691f;
        }

        /* ========== CARDS ========== */
        .card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .card h3 {
            margin-bottom: 1.2rem;
            color: #2c1810;
            border-left: 4px solid #c5a263;
            padding-left: 1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }
        .card-badge {
            background: #c5a263;
            color: white;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        /* ========== SEARCH BOX ========== */
        .search-box {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            align-items: center;
        }
        .search-box input {
            flex: 1;
            min-width: 200px;
            padding: 8px 14px;
            border-radius: 30px;
            border: 1px solid #ddd;
            font-family: 'Poppins', sans-serif;
            outline: none;
            transition: 0.2s;
        }
        .search-box input:focus {
            border-color: #c5a263;
            box-shadow: 0 0 0 3px rgba(197,162,99,0.1);
        }
        .btn-search {
            background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 500;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: 'Poppins', sans-serif;
        }
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(197,162,99,0.3);
        }
        .btn-clear {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 500;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: 'Poppins', sans-serif;
        }
        .btn-clear:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* ========== TABLES ========== */
        .table-wrapper {
            overflow-x: auto;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        .data-table th, .data-table td {
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        .data-table th {
            background: #f8f4ef;
            font-weight: 600;
            color: #2c1810;
            white-space: nowrap;
        }
        .data-table td {
            word-break: break-word;
        }

        /* ========== STATUS BADGES ========== */
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-confirmed { background: #d4edda; color: #155724; }
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-completed { background: #d1ecf1; color: #0c5460; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        .badge-rejected { background: #f8d7da; color: #721c24; }

        /* ========== ACTION BUTTONS ========== */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .btn-approve {
            background: #28a745;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 0.7rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: 0.2s;
            font-family: 'Poppins', sans-serif;
        }
        .btn-approve:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        .btn-reject {
            background: #dc3545;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 0.7rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: 0.2s;
            font-family: 'Poppins', sans-serif;
        }
        .btn-reject:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        .btn-view {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 0.7rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: 0.2s;
            font-family: 'Poppins', sans-serif;
        }
        .btn-view:hover {
            background: #138496;
            transform: translateY(-1px);
        }

        /* ========== NO RESULTS ========== */
        .no-results {
            text-align: center;
            padding: 2rem;
            color: #999;
        }

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
            }
            .search-box {
                flex-direction: column;
            }
            .search-box input {
                width: 100%;
            }
            .search-box .btn-search,
            .search-box .btn-clear {
                width: 100%;
                justify-content: center;
            }
            /* Mobile Table - Stack Layout */
            .data-table, .data-table thead, .data-table tbody, 
            .data-table th, .data-table td, .data-table tr {
                display: block;
            }
            .data-table thead { display: none; }
            .data-table tr {
                border: 1px solid #eee;
                margin-bottom: 1rem;
                border-radius: 12px;
                padding: 8px;
            }
            .data-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 6px 8px;
                border: none;
                border-bottom: 1px solid #f5f0eb;
            }
            .data-table td:last-child { border-bottom: none; }
            .data-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #2c1810;
                margin-right: 1rem;
                flex-shrink: 0;
            }
            .action-buttons {
                justify-content: flex-end;
            }
            .card h3 {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="admin-container">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo htmlspecialchars($admin_avatar); ?>" alt="Admin Avatar" onerror="this.src='<?php echo $default_avatar; ?>'">
            <h2>ROYAL ESTATE</h2>
            <p style="font-size:0.7rem;">Admin Panel</p>
        </div>
        <div class="sidebar-nav">
            <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage-rooms.php"><i class="fas fa-bed"></i> Manage Rooms</a>
            <a href="manage-halls.php"><i class="fas fa-building"></i> Event Halls</a>
            <a href="manage-packages.php"><i class="fas fa-gift"></i> Packages</a>
            <a href="room-bookings.php"><i class="fas fa-bed"></i> Room Bookings</a>
            <a href="event-bookings.php"><i class="fas fa-calendar-alt"></i> Event Bookings</a>
            <a href="package-bookings.php" class="active"><i class="fas fa-gift"></i> Package Bookings</a>
            <a href="manage-messages.php"><i class="fas fa-envelope"></i> Messages</a>
            <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h2><i class="fas fa-calendar-check" style="color:#c5a263;"></i> All Bookings</h2>
            <div class="top-bar-right">
                <a href="../index.php" target="_blank" style="background:#c5a263; color:white; padding:6px 15px; border-radius:30px; text-decoration:none;">
                    <i class="fas fa-external-link-alt"></i> View Website
                </a>
                <img src="<?php echo htmlspecialchars($admin_avatar); ?>" onerror="this.src='<?php echo $default_avatar; ?>'">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- ROOM BOOKINGS TABLE -->
        <!-- ============================================================ -->
        <div class="card">
            <h3>
                <i class="fas fa-bed" style="color:#c5a263;"></i> Room Bookings
                <span class="card-badge"><?php echo count($room_bookings); ?></span>
            </h3>
            
            <div class="search-box">
                <input type="text" id="roomSearch" placeholder="Search by customer name, email, or room..." onkeyup="filterRoomBookings()">
                <button class="btn-search" onclick="filterRoomBookings()"><i class="fas fa-search"></i> Search</button>
                <button class="btn-clear" onclick="clearRoomSearch()"><i class="fas fa-times"></i> Clear</button>
            </div>
            
            <div class="table-wrapper">
                <table class="data-table" id="roomTable">
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($room_bookings)): ?>
                            <tr>
                                <td colspan="10" class="no-results">
                                    <i class="fas fa-bed" style="font-size:2rem; color:#c5a263; display:block; margin-bottom:0.5rem;"></i>
                                    No room bookings found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($room_bookings as $b): ?>
                            <tr>
                                <td data-label="ID">#<?php echo $b['id']; ?></td>
                                <td data-label="Customer"><?php echo htmlspecialchars($b['customer']); ?></td>
                                <td data-label="Email"><?php echo htmlspecialchars($b['email']); ?></td>
                                <td data-label="Room"><?php echo htmlspecialchars($b['room']); ?></td>
                                <td data-label="Check In"><?php echo $b['check_in']; ?></td>
                                <td data-label="Check Out"><?php echo $b['check_out']; ?></td>
                                <td data-label="Guests"><?php echo $b['guests']; ?></td>
                                <td data-label="Total (LKR)">LKR <?php echo number_format($b['total_price']); ?></td>
                                <td data-label="Status">
                                    <span class="badge badge-<?php echo strtolower($b['status']); ?>">
                                        <?php echo ucfirst($b['status']); ?>
                                    </span>
                                </td>
                                <td data-label="Actions">
                                    <div class="action-buttons">
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <a href="approve-room-bookings.php" class="btn-approve"><i class="fas fa-check"></i> Approve</a>
                                            <a href="approve-room-bookings.php" class="btn-reject"><i class="fas fa-times"></i> Reject</a>
                                        <?php else: ?>
                                            <span style="font-size:0.7rem; color:#888;">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- EVENT BOOKINGS TABLE -->
        <!-- ============================================================ -->
        <div class="card">
            <h3>
                <i class="fas fa-calendar-alt" style="color:#c5a263;"></i> Event Bookings
                <span class="card-badge"><?php echo count($event_bookings); ?></span>
            </h3>
            
            <div class="search-box">
                <input type="text" id="eventSearch" placeholder="Search by customer name, email, hall, or package..." onkeyup="filterEventBookings()">
                <button class="btn-search" onclick="filterEventBookings()"><i class="fas fa-search"></i> Search</button>
                <button class="btn-clear" onclick="clearEventSearch()"><i class="fas fa-times"></i> Clear</button>
            </div>
            
            <div class="table-wrapper">
                <table class="data-table" id="eventTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Hall</th>
                            <th>Event Date</th>
                            <th>Package</th>
                            <th>Guests</th>
                            <th>Total (LKR)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($event_bookings)): ?>
                            <tr>
                                <td colspan="10" class="no-results">
                                    <i class="fas fa-calendar-alt" style="font-size:2rem; color:#c5a263; display:block; margin-bottom:0.5rem;"></i>
                                    No event bookings found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($event_bookings as $b): ?>
                            <tr>
                                <td data-label="ID">#<?php echo $b['id']; ?></td>
                                <td data-label="Customer"><?php echo htmlspecialchars($b['customer']); ?></td>
                                <td data-label="Email"><?php echo htmlspecialchars($b['email']); ?></td>
                                <td data-label="Hall"><?php echo htmlspecialchars($b['hall']); ?></td>
                                <td data-label="Event Date"><?php echo $b['event_date']; ?></td>
                                <td data-label="Package"><?php echo htmlspecialchars($b['package']); ?></td>
                                <td data-label="Guests"><?php echo $b['guests']; ?></td>
                                <td data-label="Total (LKR)">LKR <?php echo number_format($b['total']); ?></td>
                                <td data-label="Status">
                                    <span class="badge badge-<?php echo strtolower($b['status']); ?>">
                                        <?php echo ucfirst($b['status']); ?>
                                    </span>
                                </td>
                                <td data-label="Actions">
                                    <div class="action-buttons">
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <a href="approve-events.php" class="btn-approve"><i class="fas fa-check"></i> Approve</a>
                                            <a href="approve-events.php" class="btn-reject"><i class="fas fa-times"></i> Reject</a>
                                        <?php else: ?>
                                            <span style="font-size:0.7rem; color:#888;">—</span>
                                        <?php endif; ?>
                                    </div>
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
    // ================================================================
    // ✅ ROOM BOOKINGS FILTER
    // ================================================================
    function filterRoomBookings() {
        let input = document.getElementById('roomSearch').value.toLowerCase();
        let rows = document.querySelectorAll('#roomTable tbody tr');
        let hasVisible = false;
        
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            if (text.includes(input)) {
                row.style.display = '';
                hasVisible = true;
            } else {
                row.style.display = 'none';
            }
        });
        
        let noResult = document.querySelector('#roomTable tbody .no-results');
        if (!hasVisible && rows.length > 0) {
            if (!noResult) {
                let tbody = document.querySelector('#roomTable tbody');
                let tr = document.createElement('tr');
                tr.className = 'no-results';
                tr.innerHTML = '<td colspan="10" style="text-align:center; padding:20px; color:#999;">No room bookings match your search.</td>';
                tbody.appendChild(tr);
            }
        } else if (noResult) {
            noResult.remove();
        }
    }

    function clearRoomSearch() {
        document.getElementById('roomSearch').value = '';
        filterRoomBookings();
    }

    // ================================================================
    // ✅ EVENT BOOKINGS FILTER
    // ================================================================
    function filterEventBookings() {
        let input = document.getElementById('eventSearch').value.toLowerCase();
        let rows = document.querySelectorAll('#eventTable tbody tr');
        let hasVisible = false;
        
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            if (text.includes(input)) {
                row.style.display = '';
                hasVisible = true;
            } else {
                row.style.display = 'none';
            }
        });
        
        let noResult = document.querySelector('#eventTable tbody .no-results');
        if (!hasVisible && rows.length > 0) {
            if (!noResult) {
                let tbody = document.querySelector('#eventTable tbody');
                let tr = document.createElement('tr');
                tr.className = 'no-results';
                tr.innerHTML = '<td colspan="10" style="text-align:center; padding:20px; color:#999;">No event bookings match your search.</td>';
                tbody.appendChild(tr);
            }
        } else if (noResult) {
            noResult.remove();
        }
    }

    function clearEventSearch() {
        document.getElementById('eventSearch').value = '';
        filterEventBookings();
    }

    // ================================================================
    // ✅ AUTO SEARCH ON ENTER KEY
    // ================================================================
    document.getElementById('roomSearch').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') filterRoomBookings();
    });
    document.getElementById('eventSearch').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') filterEventBookings();
    });

    // ================================================================
    // ✅ MOBILE SIDEBAR TOGGLE
    // ================================================================
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if(menuToggle) {
        menuToggle.addEventListener('click', () => sidebar.classList.toggle('active'));
    }
</script>
</body>
</html>