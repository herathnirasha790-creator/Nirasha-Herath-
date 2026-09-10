<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/db_connection.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_avatar = $_SESSION['user_avatar'] ?? 'https://randomuser.me/api/portraits/men/32.jpg';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = trim($_POST['current_password'] ?? '');
    $new = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && password_verify($current, $user['password'])) {
        if ($new === $confirm && strlen($new) >= 4) {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $new_hash, $user_id);
            if ($update->execute()) {
                $message = '<div class="alert-success"><i class="fas fa-check-circle"></i> ✅ Password changed successfully!</div>';
            } else {
                $error = '<div class="alert-danger"><i class="fas fa-exclamation-triangle"></i> Database error. Please try again.</div>';
            }
        } else {
            $error = '<div class="alert-danger"><i class="fas fa-exclamation-triangle"></i> New password mismatch or too short (min 4 characters).</div>';
        }
    } else {
        $error = '<div class="alert-danger"><i class="fas fa-exclamation-triangle"></i> Current password is incorrect.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security | Royal Estate</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f0eb; }
        .dashboard-container { display: flex; min-height: 100vh; }
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
        .sidebar-header { padding: 2rem 1.5rem; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header img { width: 80px; height: 80px; border-radius: 50%; border: 3px solid #c5a263; margin-bottom: 1rem; object-fit: cover; }
        .sidebar-header h2 { font-family: 'Playfair Display', serif; color: #c5a263; font-size: 1.5rem; }
        .sidebar-nav { margin-top: 2rem; }
        .sidebar-nav a { display: flex; align-items: center; gap: 1rem; padding: 0.8rem 1.5rem; color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s; border-left: 3px solid transparent; }
        .sidebar-nav a i { width: 24px; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(197,162,99,0.2); color: #c5a263; border-left-color: #c5a263; }
        .main-content { flex: 1; margin-left: 280px; padding: 2rem; }
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
        .top-bar-left { display: flex; align-items: center; gap: 1rem; }
        .top-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #c5a263; }
        .logout-btn { background: #dc3545; color: white; padding: 0.4rem 1rem; border-radius: 30px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: 0.2s; }
        .logout-btn:hover { background: #c82333; }
        .card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            max-width: 550px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .card h3 {
            color: #2c1810;
            border-left: 4px solid #c5a263;
            padding-left: 0.8rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.2rem;
            position: relative;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            color: #2c1810;
        }
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrapper input {
            width: 100%;
            padding: 12px 40px 12px 16px;
            border-radius: 40px;
            border: 1px solid #ddd;
            outline: none;
            transition: 0.2s;
        }
        .password-wrapper input:focus {
            border-color: #c5a263;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #888;
            font-size: 1rem;
            background: transparent;
            border: none;
        }
        .toggle-password:hover { color: #c5a263; }
        button[type="submit"] {
            background: linear-gradient(135deg, #c5a263, #8b691f);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 0.5rem;
            transition: 0.2s;
        }
        button[type="submit"]:hover {
            transform: translateY(-2px);
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid #28a745;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid #dc3545;
        }
        @media (max-width: 768px) {
            .sidebar { left: -280px; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0; }
            .card { max-width: 100%; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar"><?php include 'sidebar-common.php'; ?></div>
    <div class="main-content">
        <div class="top-bar">
            <div class="top-bar-left">
                <!-- ✅ Fixed: Add '../' prefix for correct path from user/ folder -->
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=c5a263&color=fff&size=45&font-size=0.45&bold=true" class="top-avatar" alt="Avatar" onerror="this.src='https://ui-avatars.com/api/?name=User&background=c5a263&color=fff&size=45'">
                <h2><i class="fas fa-shield-alt"></i> Security</h2>
            </div>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="card">
            <h3>🔐 Change Password</h3>
            <?php echo $message; ?>
            <?php echo $error; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="current_password" id="current_password" required autocomplete="off">
                        <i class="fas fa-eye toggle-password" data-target="current_password"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="new_password" id="new_password" required>
                        <i class="fas fa-eye toggle-password" data-target="new_password"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" required>
                        <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
                    </div>
                </div>
                <button type="submit" name="change_password">Update Password</button>
            </form>
        </div>

        <!-- Optional security info card -->
        <div class="card" style="background: #fef5e6;">
            <h3>📋 Security Overview</h3>
            <p><i class="fas fa-clock"></i> Last password change: <?php echo date('F j, Y'); ?></p>
            <p><i class="fas fa-laptop"></i> You are logged in as: <strong><?php echo htmlspecialchars($user_name); ?></strong></p>
            <p><i class="fas fa-key"></i> Password must be at least 4 characters long.</p>
            <p><i class="fas fa-shield-alt"></i> For better security, use a strong password with letters, numbers, and symbols.</p>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    });
</script>
</body>
</html>