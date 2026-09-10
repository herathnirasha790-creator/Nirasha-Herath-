<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../login.php');
    exit();
}

require_once '../config/db_connection.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_avatar_session = $_SESSION['user_avatar'] ?? '';

// ✅ Avatar: Check if user has uploaded avatar, otherwise use UI Avatars (initials)
$user_avatar = '';
if (!empty($user_avatar_session) && file_exists('../' . $user_avatar_session)) {
    $user_avatar = '../' . $user_avatar_session;
} else {
    $user_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=c5a263&color=fff&size=45&font-size=0.45&bold=true';
}

// ✅ Stats: Room Bookings count
$room_stats = $conn->query("SELECT COUNT(*) as cnt FROM room_bookings WHERE user_id = $user_id")->fetch_assoc();
$total_room_bookings = $room_stats['cnt'];

// ✅ Stats: Event Bookings count
$event_stats = $conn->query("SELECT COUNT(*) as cnt FROM event_bookings WHERE user_id = $user_id")->fetch_assoc();
$total_event_bookings = $event_stats['cnt'];

// ✅ Stats: Package Bookings count
$package_stats = $conn->query("SELECT COUNT(*) as cnt FROM event_bookings WHERE user_id = $user_id AND booking_type = 'package'")->fetch_assoc();
$total_package_bookings = $package_stats['cnt'];

// ✅ Recent room bookings (limit 5) - Status shows "Paid" based on payment_status
$recent_rooms = $conn->query("
    SELECT rb.id, r.name AS room, rb.check_in, rb.check_out, rb.guests, rb.total_price, rb.status, rb.payment_status 
    FROM room_bookings rb 
    JOIN rooms r ON rb.room_id = r.id 
    WHERE rb.user_id = $user_id 
    ORDER BY rb.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// ✅ Recent event bookings (limit 5) - With Price column only
$recent_events = $conn->query("
    SELECT eb.id, h.name AS hall, eb.event_date, p.name AS package, eb.guests, 
           eb.estimated_price, eb.final_price, eb.status, eb.payment_status
    FROM event_bookings eb 
    JOIN event_halls h ON eb.hall_id = h.id 
    LEFT JOIN packages p ON eb.package_id = p.id 
    WHERE eb.user_id = $user_id 
    ORDER BY eb.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard | Royal Estate</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#f5f0eb; }
        .dashboard-container { display:flex; min-height:100vh; }
        .sidebar {
            width:280px;
            background:linear-gradient(180deg,#2c1810,#1a0f0a);
            color:white;
            position:fixed;
            left:0; top:0; bottom:0;
            overflow-y:auto;
            z-index:100;
        }
        .sidebar-header { padding:2rem 1.5rem; text-align:center; border-bottom:1px solid rgba(255,255,255,0.1); }
        .sidebar-header img { width:80px; height:80px; border-radius:50%; border:3px solid #c5a263; margin-bottom:1rem; object-fit:cover; }
        .sidebar-header h2 { font-family:'Playfair Display',serif; color:#c5a263; font-size:1.5rem; }
        .sidebar-nav { margin-top:2rem; }
        .sidebar-nav a { display:flex; align-items:center; gap:1rem; padding:0.8rem 1.5rem; color:rgba(255,255,255,0.8); text-decoration:none; transition:0.3s; border-left:3px solid transparent; }
        .sidebar-nav a i { width:24px; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background:rgba(197,162,99,0.2); color:#c5a263; border-left-color:#c5a263; }
        .main-content { flex:1; margin-left:280px; padding:2rem; }
        .top-bar {
            background:white;
            padding:1rem 2rem;
            border-radius:15px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:2rem;
            box-shadow:0 2px 10px rgba(0,0,0,0.05);
        }
        .top-bar-left { display:flex; align-items:center; gap:1rem; }
        .top-avatar { width:45px; height:45px; border-radius:50%; object-fit:cover; border:2px solid #c5a263; }
        .top-bar-right { display:flex; align-items:center; gap:0.8rem; }
        .logout-btn { background:#dc3545; color:white; padding:0.4rem 1rem; border-radius:30px; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem; transition:0.2s; font-size:0.85rem; }
        .logout-btn:hover { background:#c82333; }
        .btn-home {
            background:linear-gradient(135deg,#2c1810,#c5a263);
            color:white;
            padding:0.4rem 1rem;
            border-radius:30px;
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            gap:0.5rem;
            transition:0.2s;
            font-size:0.85rem;
        }
        .btn-home:hover { transform:translateY(-2px); opacity:0.9; }

        /* ✅ Stats Grid - 3 Cards */
        .stats-grid {
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:1.2rem;
            margin-bottom:2rem;
        }
        .stat-card {
            background:white;
            border-radius:20px;
            padding:1.2rem;
            text-align:center;
            box-shadow:0 2px 8px rgba(0,0,0,0.05);
            transition:0.2s;
        }
        .stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,0.1); }
        .stat-card h3 { font-size:1.8rem; color:#c5a263; margin-bottom:0.25rem; }
        .stat-card p { color:#666; font-size:0.85rem; }
        .stat-card .stat-icon { font-size:1.5rem; color:#c5a263; margin-bottom:0.3rem; display:block; }

        /* ✅ Quick Actions - Below Stats */
        .quick-actions {
            display:flex;
            gap:1rem;
            flex-wrap:wrap;
            margin-bottom:2rem;
            background:white;
            padding:1rem 1.5rem;
            border-radius:20px;
            box-shadow:0 5px 15px rgba(0,0,0,0.05);
            justify-content:center;
        }
        .quick-actions a {
            background:linear-gradient(135deg,#c5a263,#8b691f);
            color:white;
            padding:8px 20px;
            border-radius:30px;
            text-decoration:none;
            font-size:0.85rem;
            transition:0.2s;
            display:inline-flex;
            align-items:center;
            gap:0.5rem;
        }
        .quick-actions a:hover { transform:translateY(-2px); box-shadow:0 5px 15px rgba(197,162,99,0.3); }

        .card {
            background:white;
            border-radius:20px;
            padding:1.2rem;
            margin-bottom:2rem;
            box-shadow:0 5px 15px rgba(0,0,0,0.05);
        }
        .card-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            flex-wrap:wrap;
            gap:1rem;
            margin-bottom:1rem;
        }
        .card h3 { color:#2c1810; border-left:4px solid #c5a263; padding-left:0.8rem; font-size:1.2rem; margin:0; }
        .btn-book-new {
            background:linear-gradient(135deg,#c5a263,#8b691f);
            color:white;
            padding:0.5rem 1.2rem;
            border-radius:50px;
            text-decoration:none;
            font-weight:600;
            font-size:0.85rem;
            display:inline-flex;
            align-items:center;
            gap:0.5rem;
        }

        table { width:100%; border-collapse:collapse; font-size:0.85rem; }
        th, td { padding:10px 8px; border-bottom:1px solid #eee; text-align:left; }
        th { background:#f8f4ef; font-weight:600; color:#2c1810; }
        .badge-pending { background:#fff3cd; color:#856404; padding:3px 10px; border-radius:20px; display:inline-block; font-size:0.7rem; }
        .badge-confirmed { background:#d4edda; color:#155724; }
        .badge-completed { background:#d1ecf1; color:#0c5460; }
        .badge-paid { background:#28a745; color:white; font-weight:600; padding:3px 12px; border-radius:20px; display:inline-block; font-size:0.7rem; }
        .no-results { text-align:center; padding:2rem; color:#999; }

        @media (max-width:768px) {
            .sidebar { left:-280px; }
            .sidebar.active { left:0; }
            .main-content { margin-left:0; }
            .stats-grid { grid-template-columns:1fr; }
            .top-bar { flex-wrap:wrap; gap:0.5rem; }
            .top-bar-right { width:100%; justify-content:flex-end; }
            th,td { display:block; }
            td { position:relative; padding-left:50%; }
            td::before { content:attr(data-label); position:absolute; left:10px; font-weight:600; }
            thead { display:none; }
            .quick-actions { flex-direction:column; align-items:stretch; }
            .quick-actions a { justify-content:center; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <?php include 'sidebar-common.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar with Profile Image, Logout, and Home Button -->
        <div class="top-bar">
            <div class="top-bar-left">
                <img src="<?php echo $user_avatar; ?>" class="top-avatar" alt="Profile" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=c5a263&color=fff&size=45&font-size=0.45&bold=true'">
                <h2>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h2>
            </div>
            <div class="top-bar-right">
                <a href="../index.php" class="btn-home"><i class="fas fa-home"></i> Home</a>
                <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- ✅ Stats Cards - 3 Cards (Room, Event, Package) -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon"><i class="fas fa-bed"></i></span>
                <h3><?php echo $total_room_bookings; ?></h3>
                <p>Room Bookings</p>
            </div>
            <div class="stat-card">
                <span class="stat-icon"><i class="fas fa-calendar-alt"></i></span>
                <h3><?php echo $total_event_bookings; ?></h3>
                <p>Event Bookings</p>
            </div>
            <div class="stat-card">
                <span class="stat-icon"><i class="fas fa-gift"></i></span>
                <h3><?php echo $total_package_bookings; ?></h3>
                <p>Package Bookings</p>
            </div>
        </div>

        <!-- ✅ Quick Actions - Below Stats Cards -->
        <div class="quick-actions">
            <a href="../rooms.php"><i class="fas fa-bed"></i> Book a Room</a>
            <a href="../event-halls.php"><i class="fas fa-glass-cheers"></i> Plan an Event</a>
            <a href="../packages.php"><i class="fas fa-gift"></i> View Packages</a>
            <a href="profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="../index.php"><i class="fas fa-globe"></i> Visit Website</a>
        </div>

        <!-- Recent Room Bookings -->
        <div class="card">
            <div class="card-header">
                <h3>🛏️ Recent Room Bookings</h3>
                <a href="my-room-bookings.php" class="btn-book-new"><i class="fas fa-eye"></i> View All</a>
            </div>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Room</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Guests</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($recent_rooms)): ?>
                            <tr><td colspan="7" class="no-results">No room bookings yet.</td></tr>
                        <?php else: ?>
                            <?php foreach($recent_rooms as $b): ?>
                            <tr>
                                <td data-label="ID">#<?php echo $b['id']; ?></td>
                                <td data-label="Room"><?php echo htmlspecialchars($b['room']); ?></td>
                                <td data-label="Check In"><?php echo $b['check_in']; ?></td>
                                <td data-label="Check Out"><?php echo $b['check_out']; ?></td>
                                <td data-label="Guests"><?php echo $b['guests']; ?></td>
                                <td data-label="Total">LKR <?php echo number_format($b['total_price']); ?></td>
                                <td data-label="Status">
                                    <?php 
                                    if (isset($b['payment_status']) && $b['payment_status'] === 'paid') {
                                        echo '<span class="badge-paid"><i class="fas fa-check-circle"></i> Paid</span>';
                                    } else {
                                        $status_class = strtolower($b['status']);
                                        echo '<span class="badge-' . $status_class . '">' . ucfirst($b['status']) . '</span>';
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

        <!-- Recent Event Bookings -->
        <div class="card">
            <div class="card-header">
                <h3>🎉 Recent Event Bookings</h3>
                <a href="my-event-bookings.php" class="btn-book-new"><i class="fas fa-eye"></i> View All</a>
            </div>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hall</th>
                            <th>Event Date</th>
                            <th>Package</th>
                            <th>Guests</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($recent_events)): ?>
                            <tr><td colspan="7" class="no-results">No event bookings yet.</td></tr>
                        <?php else: ?>
                            <?php foreach($recent_events as $b): ?>
                            <tr>
                                <td data-label="ID">#<?php echo $b['id']; ?></td>
                                <td data-label="Hall"><?php echo htmlspecialchars($b['hall']); ?></td>
                                <td data-label="Event Date"><?php echo $b['event_date']; ?></td>
                                <td data-label="Package"><?php echo htmlspecialchars($b['package'] ?? 'N/A'); ?></td>
                                <td data-label="Guests"><?php echo $b['guests']; ?></td>
                                <td data-label="Price">
                                    <?php 
                                    if ($b['final_price']) {
                                        echo 'LKR ' . number_format($b['final_price']);
                                    } else {
                                        echo 'LKR ' . number_format($b['estimated_price']);
                                    }
                                    ?>
                                </td>
                                <td data-label="Status">
                                    <?php 
                                    // ✅ Always show "Paid" for event bookings
                                    echo '<span class="badge-paid"><i class="fas fa-check-circle"></i> Paid</span>';
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
    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if(menuToggle) {
        menuToggle.addEventListener('click', () => sidebar.classList.toggle('active'));
    }
</script>
</body>
</html>