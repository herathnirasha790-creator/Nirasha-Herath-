<?php
// user/sidebar-common.php - Shared Sidebar for User Dashboard

$current_page = basename($_SERVER['PHP_SELF']);

// ✅ Get user name from session
$user_name = $_SESSION['user_name'] ?? 'User';

// ✅ Avatar logic: Use UI Avatars (initials-based) instead of default image
// If user has uploaded avatar, use it; otherwise use UI Avatars
$avatar = '';
if (isset($_SESSION['user_avatar']) && !empty($_SESSION['user_avatar']) && file_exists('../' . $_SESSION['user_avatar'])) {
    $avatar = '../' . htmlspecialchars($_SESSION['user_avatar']);
} else {
    // ✅ Use UI Avatars API - generates avatar from user's initials
    $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=c5a263&color=fff&size=100&font-size=0.5&bold=true';
}
?>
<div class="sidebar-header">
    <img src="<?php echo $avatar; ?>" alt="User Avatar" onerror="this.src='https://ui-avatars.com/api/?name=User&background=c5a263&color=fff&size=100'">
    <h2>ROYAL ESTATE</h2>
    <p style="font-size:0.7rem;">User Panel</p>
</div>
<div class="sidebar-nav">
    <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a href="my-room-bookings.php" class="<?php echo $current_page == 'my-room-bookings.php' ? 'active' : ''; ?>">
        <i class="fas fa-bed"></i> Room Bookings
    </a>
    <a href="my-package-bookings.php" class="<?php echo $current_page == 'my-package-bookings.php' ? 'active' : ''; ?>">
        <i class="fas fa-gift"></i> Package Bookings
    </a>
    <a href="my-event-bookings.php" class="<?php echo $current_page == 'my-event-bookings.php' ? 'active' : ''; ?>">
        <i class="fas fa-calendar-alt"></i> Event Bookings
    </a>
    <a href="notifications.php" class="<?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
        <i class="fas fa-bell"></i> Notifications
    </a>
    <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
        <i class="fas fa-user-circle"></i> My Profile
    </a>
    <a href="change-password.php" class="<?php echo $current_page == 'change-password.php' ? 'active' : ''; ?>">
        <i class="fas fa-shield-alt"></i> Security
    </a>
    <a href="../logout.php" style="margin-top:20px; border-top:1px solid rgba(255,255,255,0.1);">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>