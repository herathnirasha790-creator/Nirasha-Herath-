<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../login.php');
    exit();
}
require_once '../config/db_connection.php';
$user_id = $_SESSION['user_id'];
$user_avatar = $_SESSION['user_avatar'] ?? 'https://randomuser.me/api/portraits/men/32.jpg';

$notifications = $conn->query("SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications | Royal Estate</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#f5f0eb; }
        .dashboard-container { display:flex; min-height:100vh; }
        .sidebar { width:280px; background:linear-gradient(180deg,#2c1810,#1a0f0a); color:white; position:fixed; left:0; top:0; bottom:0; overflow-y:auto; z-index:100; }
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
        .card { background:white; border-radius:20px; padding:1.5rem; margin-bottom:2rem; box-shadow:0 5px 15px rgba(0,0,0,0.05); }
        .card h3 { color:#2c1810; border-left:4px solid #c5a263; padding-left:0.8rem; margin-bottom:1rem; }
        .notif-item { padding:12px; border-bottom:1px solid #eee; display:flex; gap:15px; align-items:center; }
        .notif-item i { font-size:1.5rem; color:#c5a263; }
        @media (max-width:768px){
            .sidebar{ left:-280px; }
            .sidebar.active{ left:0; }
            .main-content{ margin-left:0; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar" id="sidebar"><?php include 'sidebar-common.php'; ?></div>
    <div class="main-content">
        <div class="top-bar">
            <div class="top-bar-left">
                <!-- ✅ Correct avatar path with ../ prefix and onerror fallback -->
                <img src="<?php echo '../' . htmlspecialchars($user_avatar); ?>" class="top-avatar" onerror="this.src='https://randomuser.me/api/portraits/men/32.jpg'">
                <h2><i class="fas fa-bell"></i> Notifications</h2>
            </div>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        <div class="card">
            <h3>🔔 All Notifications</h3>
            <?php if(empty($notifications)): ?>
                <p style="text-align:center; padding:20px;">No notifications yet.</p>
            <?php else: ?>
                <?php foreach($notifications as $n): ?>
                <div class="notif-item">
                    <i class="fas fa-bell"></i>
                    <div>
                        <strong><?php echo htmlspecialchars($n['title']); ?></strong><br>
                        <?php echo nl2br(htmlspecialchars($n['message'])); ?><br>
                        <small style="color:#999;"><?php echo date('d M Y, h:i A', strtotime($n['created_at'])); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
    // Mobile menu toggle (if your sidebar has menuToggle)
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if(menuToggle) {
        menuToggle.addEventListener('click', () => sidebar.classList.toggle('active'));
    }
</script>
</body>
</html>