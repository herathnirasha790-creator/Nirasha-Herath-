<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../login.php');
    exit();
}
require_once '../config/db_connection.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_avatar_session = $_SESSION['user_avatar'] ?? '';

// ✅ Avatar: Check if user has uploaded avatar, otherwise use UI Avatars (initials)
$user_avatar = '';
if (!empty($user_avatar_session) && file_exists('../' . $user_avatar_session)) {
    $user_avatar = '../' . $user_avatar_session;
} else {
    $user_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=c5a263&color=fff&size=45&font-size=0.45&bold=true';
}

// ✅ Fetch ONLY Event Hall Bookings (booking_type = 'event_hall') with payment_status
$stmt = $conn->prepare("
    SELECT eb.id, h.name AS hall, h.id AS hall_id, eb.event_date, p.name AS package, p.id AS package_id,
           eb.guests, eb.estimated_price, eb.final_price, eb.status, eb.payment_status,
           eb.created_at
    FROM event_bookings eb 
    JOIN event_halls h ON eb.hall_id = h.id 
    LEFT JOIN packages p ON eb.package_id = p.id 
    WHERE eb.user_id = ? AND eb.booking_type = 'event_hall'
    ORDER BY eb.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ✅ Fetch all halls for filter dropdown
$halls_list = $conn->query("SELECT id, name FROM event_halls WHERE status = 'active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// ✅ Fetch all packages for filter dropdown
$packages_list = $conn->query("SELECT id, name FROM packages WHERE status = 'active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// ✅ Get filter values from GET (for pre-filling after page load)
$filter_start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$filter_end = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$filter_hall = isset($_GET['hall_id']) ? intval($_GET['hall_id']) : 0;
$filter_package = isset($_GET['package_id']) ? intval($_GET['package_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Event Bookings | Royal Estate</title>
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
        .logout-btn { background:#dc3545; color:white; padding:0.4rem 1rem; border-radius:30px; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem; transition:0.2s; }
        .logout-btn:hover { background:#c82333; }
        .card {
            background:white;
            border-radius:20px;
            padding:1.5rem;
            margin-bottom:2rem;
            box-shadow:0 5px 15px rgba(0,0,0,0.05);
        }
        .card-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            flex-wrap:wrap;
            gap:1rem;
            margin-bottom:1.5rem;
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

        /* ✅ Filter Bar Styles */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            background: #f9f5f0;
            padding: 1rem 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        .filter-bar label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #2c1810;
            margin-right: 0.3rem;
        }
        .filter-bar input[type="date"],
        .filter-bar select {
            padding: 0.5rem 0.8rem;
            border-radius: 30px;
            border: 1px solid #ddd;
            font-family: 'Poppins', sans-serif;
            font-size: 0.8rem;
            background: white;
            outline: none;
            transition: 0.2s;
        }
        .filter-bar input[type="date"]:focus,
        .filter-bar select:focus {
            border-color: #c5a263;
            box-shadow: 0 0 0 3px rgba(197,162,99,0.1);
        }
        .filter-bar .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
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
        }
        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(197,162,99,0.3);
        }
        .btn-filter-clear {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.8rem;
            transition: 0.2s;
            font-family: 'Poppins', sans-serif;
        }
        .btn-filter-clear:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220,53,69,0.3);
        }
        .filter-result-count {
            font-size: 0.8rem;
            color: #666;
            margin-left: auto;
        }
        .filter-result-count strong {
            color: #c5a263;
        }

        table {
            width:100%;
            border-collapse:collapse;
            font-size:0.85rem;
        }
        th, td {
            padding:12px 8px;
            border-bottom:1px solid #eee;
            text-align:left;
        }
        th {
            background:#f8f4ef;
            font-weight:600;
            color:#2c1810;
        }
        .badge-pending { background:#fff3cd; color:#856404; padding:3px 10px; border-radius:20px; display:inline-block; font-size:0.7rem; }
        .badge-approved { background:#d4edda; color:#155724; }
        .badge-completed { background:#d1ecf1; color:#0c5460; }
        .badge-rejected { background:#f8d7da; color:#721c24; }
        .badge-paid { background:#28a745; color:white; font-weight:600; padding:3px 12px; border-radius:20px; display:inline-block; font-size:0.7rem; }
        .no-results { text-align:center; padding:2rem; color:#999; }

        @media (max-width:768px) {
            .sidebar { left:-280px; }
            .sidebar.active { left:0; }
            .main-content { margin-left:0; }
            .filter-bar { flex-direction:column; align-items:stretch; }
            .filter-bar .filter-group { flex-wrap:wrap; }
            .filter-result-count { margin-left:0; }
            th,td { display:block; }
            td { position:relative; padding-left:50%; }
            td::before { content:attr(data-label); position:absolute; left:10px; font-weight:600; }
            thead { display:none; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar" id="sidebar">
        <?php include 'sidebar-common.php'; ?>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="top-bar-left">
                <img src="<?php echo $user_avatar; ?>" class="top-avatar" alt="Profile" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=c5a263&color=fff&size=45&font-size=0.45&bold=true'">
                <h2><i class="fas fa-calendar-alt"></i> My Event Bookings</h2>
            </div>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>🎉 Event Hall Booking List</h3>
                <a href="../event-halls.php" class="btn-book-new"><i class="fas fa-plus-circle"></i> Book New Event</a>
            </div>

            <!-- ✅ Filter Bar (No All/Past/Future) -->
            <div class="filter-bar">
                <div class="filter-group">
                    <label><i class="fas fa-calendar-alt"></i> From:</label>
                    <input type="date" id="filterStartDate" value="<?php echo $filter_start; ?>">
                </div>
                <div class="filter-group">
                    <label>To:</label>
                    <input type="date" id="filterEndDate" value="<?php echo $filter_end; ?>">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-building"></i> Hall:</label>
                    <select id="filterHall">
                        <option value="0">All Halls</option>
                        <?php foreach($halls_list as $hall): ?>
                            <option value="<?php echo $hall['id']; ?>" <?php echo ($filter_hall == $hall['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($hall['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-tag"></i> Package:</label>
                    <select id="filterPackage">
                        <option value="0">All Packages</option>
                        <?php foreach($packages_list as $pkg): ?>
                            <option value="<?php echo $pkg['id']; ?>" <?php echo ($filter_package == $pkg['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pkg['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn-filter" onclick="applyFilters()"><i class="fas fa-search"></i> Filter</button>
                <button class="btn-filter-clear" onclick="clearFilters()"><i class="fas fa-times"></i> Clear</button>
                <span class="filter-result-count" id="resultCount">Showing <strong id="countDisplay"><?php echo count($bookings); ?></strong> bookings</span>
            </div>

            <div style="overflow-x:auto;">
                <table id="bookingsTable">
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
                    <tbody id="bookingsTableBody">
                        <?php if (empty($bookings)): ?>
                            <tr><td colspan="7" class="no-results">No event hall bookings found.</td></tr>
                        <?php else: ?>
                            <?php foreach($bookings as $b): ?>
                            <tr data-hall-id="<?php echo $b['hall_id']; ?>" data-package-id="<?php echo $b['package_id']; ?>" data-event-date="<?php echo $b['event_date']; ?>">
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
    // ✅ Filter Function
    function applyFilters() {
        const startDate = document.getElementById('filterStartDate').value;
        const endDate = document.getElementById('filterEndDate').value;
        const hallId = parseInt(document.getElementById('filterHall').value);
        const packageId = parseInt(document.getElementById('filterPackage').value);
        const rows = document.querySelectorAll('#bookingsTableBody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            // Skip "No results" row
            if (row.querySelector('.no-results')) {
                row.style.display = 'none';
                return;
            }

            const eventDate = row.getAttribute('data-event-date');
            const hallIdAttr = parseInt(row.getAttribute('data-hall-id'));
            const packageIdAttr = parseInt(row.getAttribute('data-package-id'));
            let show = true;

            // Date range filter
            if (startDate && eventDate < startDate) show = false;
            if (endDate && eventDate > endDate) show = false;
            
            // Hall filter
            if (hallId > 0 && hallIdAttr !== hallId) show = false;

            // Package filter
            if (packageId > 0 && packageIdAttr !== packageId) show = false;

            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        // Update count
        document.getElementById('countDisplay').textContent = visibleCount;

        // Show "No results" message if no rows visible
        const noResultRow = document.querySelector('#bookingsTableBody .no-results-row');
        if (visibleCount === 0) {
            if (!noResultRow) {
                const tbody = document.getElementById('bookingsTableBody');
                const tr = document.createElement('tr');
                tr.className = 'no-results-row';
                tr.innerHTML = `<td colspan="7" class="no-results"><i class="fas fa-search" style="font-size:1.5rem;color:#c5a263;display:block;margin-bottom:0.5rem;"></i>No event bookings match your filters.</td>`;
                tbody.appendChild(tr);
            }
        } else {
            if (noResultRow) noResultRow.remove();
        }
    }

    // ✅ Clear Filters
    function clearFilters() {
        document.getElementById('filterStartDate').value = '';
        document.getElementById('filterEndDate').value = '';
        document.getElementById('filterHall').value = '0';
        document.getElementById('filterPackage').value = '0';
        applyFilters();
    }

    // ✅ Auto-filter on Enter key
    document.addEventListener('DOMContentLoaded', function() {
        // Apply filters if there are pre-filled values
        if (document.getElementById('filterStartDate').value || 
            document.getElementById('filterEndDate').value || 
            document.getElementById('filterHall').value != '0' ||
            document.getElementById('filterPackage').value != '0') {
            applyFilters();
        }

        document.querySelectorAll('#filterStartDate, #filterEndDate, #filterHall, #filterPackage').forEach(input => {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    applyFilters();
                }
            });
        });
    });

    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if(menuToggle) {
        menuToggle.addEventListener('click', () => sidebar.classList.toggle('active'));
    }
</script>
</body>
</html>