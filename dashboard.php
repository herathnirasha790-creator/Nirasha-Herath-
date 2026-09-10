<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}
// If admin/owner goes to user dashboard, redirect to admin
if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'owner') {
    header('Location: ../admin/index.php');
    exit();
}
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_avatar = 'https://randomuser.me/api/portraits/men/32.jpg';
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard | Royal Estate</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f0eb; }
        .dashboard-container { display: flex; min-height: 100vh; }
        /* Sidebar styles */
        .sidebar { width: 280px; background: linear-gradient(180deg,#2c1810,#1a0f0a); color: white; position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; }
        .sidebar-header { padding: 2rem 1.5rem; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { font-family: 'Playfair Display'; color: #c5a263; }
        .user-info-sidebar { text-align: center; padding: 1.5rem; }
        .user-avatar { width: 80px; height: 80px; margin: 0 auto 1rem; border-radius: 50%; border: 3px solid #c5a263; overflow: hidden; }
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .sidebar-nav .nav-item { display: flex; align-items: center; gap: 1rem; padding: 0.8rem 1.5rem; color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s; border-left: 3px solid transparent; }
        .sidebar-nav .nav-item:hover, .sidebar-nav .nav-item.active { background: rgba(197,162,99,0.2); color: #c5a263; border-left-color: #c5a263; }
        .main-content { flex: 1; margin-left: 280px; }
        .top-header { background: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .content-area { padding: 2rem; }
        .section-card { background: white; border-radius: 20px; padding: 1.5rem; margin-bottom: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; border-radius: 20px; padding: 1.5rem; text-align: center; }
        .stat-card h3 { font-size: 2rem; color: #c5a263; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; }
        .badge-confirmed { background: #d4edda; color: #155724; }
        .badge-pending { background: #fff3cd; color: #856404; }
        @media (max-width: 768px) {
            .sidebar { left: -280px; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
        }
        .menu-toggle { display: none; font-size: 1.5rem; cursor: pointer; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar" id="sidebar">
        <?php include 'sidebar-common.php'; ?>
    </div>
    <div class="main-content">
        <div class="top-header">
            <div class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></div>
            <h3>Welcome, <?php echo htmlspecialchars($user_name); ?></h3>
            <a href="../logout.php" style="color:#c5a263;">Logout</a>
        </div>
        <div class="content-area">
            <div class="stats-grid">
                <div class="stat-card"><h3>3</h3><p>Room Bookings</p></div>
                <div class="stat-card"><h3>2</h3><p>Event Bookings</p></div>
                <div class="stat-card"><h3>1</h3><p>Pending</p></div>
            </div>
            <div class="section-card">
                <h2>Recent Room Bookings</h2>
                <table>...</table>
            </div>
        </div>
    </div>
</div>
<script>
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if(menuToggle) menuToggle.onclick = () => sidebar.classList.toggle('active');
</script>
</body>
</html>