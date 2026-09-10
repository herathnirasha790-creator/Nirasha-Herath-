<?php
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

// Mark message as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $id = intval($_GET['mark_read']);
    $conn->query("UPDATE contact_messages SET is_read = 1 WHERE id = $id");
    header('Location: manage-messages.php');
    exit();
}

// Delete message
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM contact_messages WHERE id = $id");
    header('Location: manage-messages.php');
    exit();
}

// ✅ Check column names
$check_name = $conn->query("SHOW COLUMNS FROM contact_messages LIKE 'user_name'");
$has_user_name = ($check_name && $check_name->num_rows > 0);

if ($has_user_name) {
    $messages = $conn->query("
        SELECT * FROM contact_messages 
        ORDER BY created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);
} else {
    $messages = $conn->query("
        SELECT *, name as user_name, email as user_email FROM contact_messages 
        ORDER BY created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);
}

$unread_count = $conn->query("SELECT COUNT(*) as cnt FROM contact_messages WHERE is_read = 0")->fetch_assoc()['cnt'] ?? 0;
$total_count = count($messages);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Messages | Royal Estate Admin</title>
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
            padding: 1.2rem;
            margin-bottom: 2rem;
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
            padding-left: 1rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }
        .badge-unread {
            background: #dc3545;
            color: white;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        .badge-total {
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
            padding: 0.8rem 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            align-items: center;
        }
        .filter-bar label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #2c1810;
            margin-right: 0.2rem;
        }
        .filter-bar input[type="text"],
        .filter-bar input[type="date"],
        .filter-bar select {
            padding: 0.4rem 0.8rem;
            border-radius: 30px;
            border: 1px solid #ddd;
            font-family: 'Poppins', sans-serif;
            font-size: 0.75rem;
            background: white;
            outline: none;
            transition: 0.2s;
        }
        .filter-bar input:focus,
        .filter-bar select:focus {
            border-color: #c5a263;
            box-shadow: 0 0 0 3px rgba(197,162,99,0.1);
        }
        .filter-bar .filter-group {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            flex-wrap: wrap;
        }
        .btn-filter {
            background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white;
            border: none;
            padding: 0.4rem 1.2rem;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.75rem;
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
            padding: 0.4rem 1.2rem;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.75rem;
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
        .filter-result-count {
            font-size: 0.75rem;
            color: #666;
            margin-left: auto;
        }
        .filter-result-count strong {
            color: #c5a263;
        }

        /* ========== MESSAGE ITEMS - COMPACT ========== */
        .message-item {
            padding: 0.7rem 1rem;
            border-bottom: 1px solid #f0e5d8;
            transition: 0.2s;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            gap: 0.5rem;
        }
        .message-item:hover {
            background: #fefaf5;
        }
        .message-item.unread {
            background: #fff5f5;
            border-left: 4px solid #dc3545;
        }
        .message-item .msg-main {
            flex: 1;
            min-width: 200px;
        }
        .message-item .msg-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }
        .message-item .msg-user {
            font-weight: 600;
            color: #2c1810;
            font-size: 0.9rem;
        }
        .message-item .msg-email {
            color: #666;
            font-size: 0.75rem;
        }
        .message-item .msg-date {
            font-size: 0.7rem;
            color: #999;
        }
        .message-item .msg-subject {
            font-weight: 500;
            color: #c5a263;
            font-size: 0.8rem;
            margin: 0.2rem 0;
        }
        .message-item .msg-body {
            color: #555;
            line-height: 1.5;
            font-size: 0.8rem;
            word-wrap: break-word;
            margin: 0.2rem 0;
        }
        .message-item .msg-meta {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
            margin-top: 0.2rem;
        }
        .message-item .msg-actions {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        /* ========== BADGES ========== */
        .badge { 
            padding: 2px 10px; 
            border-radius: 20px; 
            display: inline-block; 
            font-size: 0.6rem; 
            font-weight: 600; 
            white-space: nowrap; 
        }
        .badge-read {
            background: #d4edda;
            color: #155724;
        }
        .badge-new {
            background: #dc3545;
            color: white;
        }
        .badge-phone {
            background: #f5f0eb;
            color: #666;
            font-size: 0.7rem;
            padding: 1px 8px;
            border-radius: 20px;
        }
        
        /* ========== BUTTONS ========== */
        .btn-read { 
            background: #28a745; 
            color: white; 
            border: none; 
            padding: 3px 12px; 
            border-radius: 30px; 
            cursor: pointer; 
            font-size: 0.65rem; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 3px; 
            transition: 0.2s; 
        }
        .btn-read:hover { background: #218838; transform: translateY(-1px); }
        .btn-delete { 
            background: #dc3545; 
            color: white; 
            border: none; 
            padding: 3px 12px; 
            border-radius: 30px; 
            cursor: pointer; 
            font-size: 0.65rem; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 3px; 
            transition: 0.2s; 
        }
        .btn-delete:hover { background: #c82333; transform: translateY(-1px); }

        .no-messages {
            text-align: center;
            padding: 2rem;
            color: #999;
        }
        .no-messages i {
            font-size: 2.5rem;
            color: #c5a263;
            display: block;
            margin-bottom: 0.5rem;
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
                flex-wrap: wrap; 
            }
            .filter-bar { 
                flex-direction: column; 
                align-items: stretch; 
            }
            .filter-bar .filter-group { 
                flex-wrap: wrap; 
            }
            .filter-result-count {
                margin-left: 0;
                text-align: center;
            }
            .message-item {
                flex-direction: column;
                padding: 0.6rem;
            }
            .message-item .msg-actions {
                width: 100%;
                justify-content: flex-end;
            }
            .card {
                padding: 0.8rem;
            }
        }
        @media (max-width: 480px) {
            .card { padding: 0.6rem; }
            .filter-bar { padding: 0.6rem; }
            .message-item { padding: 0.5rem; }
            .message-item .msg-header { flex-direction: column; align-items: flex-start; }
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
            <a href="manage-messages.php" class="active"><i class="fas fa-envelope"></i> Messages</a>
            <a href="reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reports</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h2><i class="fas fa-envelope"></i> Contact Messages <?php if($unread_count > 0): ?><span class="badge-unread" style="font-size:0.7rem; margin-left:0.5rem;"><?php echo $unread_count; ?> Unread</span><?php endif; ?></h2>
            <div class="top-bar-right">
                <img src="<?php echo htmlspecialchars($admin_avatar); ?>" onerror="this.src='<?php echo $default_avatar; ?>'">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> All Messages <span class="badge-total"><?php echo $total_count; ?></span></h3>
            </div>

            <!-- ✅ FILTER BAR -->
            <div class="filter-bar">
                <div class="filter-group">
                    <label><i class="fas fa-search"></i></label>
                    <input type="text" id="searchInput" placeholder="Search name, email, subject..." onkeyup="applyFilters()">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-filter"></i></label>
                    <select id="statusFilter" onchange="applyFilters()">
                        <option value="all">All Status</option>
                        <option value="read">Read</option>
                        <option value="unread">Unread</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-calendar-alt"></i> From:</label>
                    <input type="date" id="fromDate" onchange="applyFilters()">
                </div>
                <div class="filter-group">
                    <label>To:</label>
                    <input type="date" id="toDate" onchange="applyFilters()">
                </div>
                <button class="btn-filter" onclick="applyFilters()"><i class="fas fa-search"></i> Filter</button>
                <button class="btn-clear" onclick="clearFilters()"><i class="fas fa-times"></i> Clear</button>
                <span class="filter-result-count" id="resultCount">Showing <strong id="countDisplay"><?php echo $total_count; ?></strong> messages</span>
            </div>
            
            <?php if(empty($messages)): ?>
                <div class="no-messages">
                    <i class="fas fa-inbox"></i>
                    <p>No messages received yet.</p>
                </div>
            <?php else: ?>
                <div id="messagesContainer">
                    <?php foreach($messages as $msg): ?>
                    <div class="message-item <?php echo $msg['is_read'] ? '' : 'unread'; ?>" 
                         data-name="<?php echo strtolower(htmlspecialchars($msg['user_name'])); ?>"
                         data-email="<?php echo strtolower(htmlspecialchars($msg['user_email'])); ?>"
                         data-subject="<?php echo strtolower(htmlspecialchars($msg['subject'])); ?>"
                         data-message="<?php echo strtolower(htmlspecialchars($msg['message'])); ?>"
                         data-isread="<?php echo $msg['is_read']; ?>"
                         data-date="<?php echo date('Y-m-d', strtotime($msg['created_at'])); ?>">
                        
                        <div class="msg-main">
                            <div class="msg-header">
                                <span class="msg-user">
                                    <?php 
                                    $display_name = isset($msg['user_name']) ? $msg['user_name'] : (isset($msg['name']) ? $msg['name'] : 'Unknown User');
                                    echo htmlspecialchars($display_name);
                                    ?>
                                </span>
                                <span class="msg-email">&lt;<?php 
                                    $display_email = isset($msg['user_email']) ? $msg['user_email'] : (isset($msg['email']) ? $msg['email'] : '');
                                    echo htmlspecialchars($display_email);
                                ?>&gt;</span>
                                <span class="msg-date">
                                    <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                                </span>
                                <?php if($msg['is_read']): ?>
                                    <span class="badge badge-read"><i class="fas fa-check"></i> Read</span>
                                <?php else: ?>
                                    <span class="badge badge-new"><i class="fas fa-clock"></i> New</span>
                                <?php endif; ?>
                            </div>
                            <?php if(!empty($msg['phone'])): ?>
                                <span class="badge-phone"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($msg['phone']); ?></span>
                            <?php endif; ?>
                            <div class="msg-subject"><i class="fas fa-tag"></i> <?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $msg['subject']))); ?></div>
                            <div class="msg-body"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                            <div class="msg-meta">
                                <span style="font-size:0.65rem; color:#999;">
                                    <i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($msg['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="msg-actions">
                            <?php if(!$msg['is_read']): ?>
                                <a href="?mark_read=<?php echo $msg['id']; ?>" class="btn-read"><i class="fas fa-check"></i> Read</a>
                            <?php endif; ?>
                            <a href="?delete=<?php echo $msg['id']; ?>" class="btn-delete" onclick="return confirm('Delete this message?')"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // ================================================================
    // ✅ FILTER FUNCTION
    // ================================================================
    function applyFilters() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;
        const fromDate = document.getElementById('fromDate').value;
        const toDate = document.getElementById('toDate').value;
        
        const items = document.querySelectorAll('.message-item');
        let visibleCount = 0;
        
        items.forEach(item => {
            const name = item.getAttribute('data-name') || '';
            const email = item.getAttribute('data-email') || '';
            const subject = item.getAttribute('data-subject') || '';
            const message = item.getAttribute('data-message') || '';
            const isRead = item.getAttribute('data-isread') === '1';
            const date = item.getAttribute('data-date') || '';
            
            let show = true;
            
            // Search filter
            if (search) {
                const searchText = name + ' ' + email + ' ' + subject + ' ' + message;
                if (!searchText.includes(search)) show = false;
            }
            
            // Status filter
            if (statusFilter === 'read' && !isRead) show = false;
            if (statusFilter === 'unread' && isRead) show = false;
            
            // Date filter
            if (fromDate && date < fromDate) show = false;
            if (toDate && date > toDate) show = false;
            
            item.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        
        // Update count
        document.getElementById('countDisplay').textContent = visibleCount;
        
        // Show no results message
        const container = document.getElementById('messagesContainer');
        let noResult = container.querySelector('.no-results-message');
        
        if (visibleCount === 0 && items.length > 0) {
            if (!noResult) {
                const div = document.createElement('div');
                div.className = 'no-results-message';
                div.style.cssText = 'text-align:center; padding:2rem; color:#999;';
                div.innerHTML = '<i class="fas fa-search" style="font-size:2rem; color:#c5a263; display:block; margin-bottom:0.5rem;"></i> No messages match your filters.';
                container.appendChild(div);
            }
        } else if (noResult) {
            noResult.remove();
        }
    }

    // ================================================================
    // ✅ CLEAR FILTERS
    // ================================================================
    function clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = 'all';
        document.getElementById('fromDate').value = '';
        document.getElementById('toDate').value = '';
        applyFilters();
    }

    // ================================================================
    // ✅ AUTO FILTER ON ENTER KEY
    // ================================================================
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') applyFilters();
    });

    // ================================================================
    // ✅ MOBILE SIDEBAR TOGGLE
    // ================================================================
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if(menuToggle) {
        menuToggle.addEventListener('click', () => sidebar.classList.toggle('active'));
    }

    console.log('📩 Messages loaded: <?php echo $total_count; ?>');
</script>
</body>
</html>