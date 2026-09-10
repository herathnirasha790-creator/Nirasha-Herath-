<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/db_connection.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$message = '';
$error = '';

// Fetch current user data
$stmt = $conn->prepare("SELECT name, email, avatar, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: ../login.php');
    exit;
}

// ✅ Avatar logic - Check if uploaded avatar exists
$avatar = '';
if (!empty($user['avatar']) && file_exists('../' . $user['avatar'])) {
    $avatar = '../' . $user['avatar'];
} else {
    // Use UI Avatars (initials)
    $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=c5a263&color=fff&size=180&font-size=0.5&bold=true';
}

// Update profile name
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_name = trim($_POST['fullname'] ?? '');
    if (!empty($new_name)) {
        $update = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
        $update->bind_param("si", $new_name, $user_id);
        if ($update->execute()) {
            $_SESSION['user_name'] = $new_name;
            $user['name'] = $new_name;
            $message = '<div class="alert-success"><i class="fas fa-check-circle"></i> Profile updated successfully!</div>';
            // Refresh avatar URL if name changed
            $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($new_name) . '&background=c5a263&color=fff&size=180&font-size=0.5&bold=true';
            if (!empty($user['avatar']) && file_exists('../' . $user['avatar'])) {
                $avatar = '../' . $user['avatar'];
            }
        } else {
            $error = '<div class="alert-danger"><i class="fas fa-exclamation-triangle"></i> Failed to update profile.</div>';
        }
    }
}

// ✅ Avatar Upload Handler (Fixed Path & Session)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $error = '<div class="alert-danger">Invalid file type. Only JPG, PNG, GIF, WEBP allowed.</div>';
        } elseif ($file['size'] > $max_size) {
            $error = '<div class="alert-danger">File too large. Max 2MB.</div>';
        } else {
            // Create directory if not exists (relative to root)
            $upload_dir = '../uploads/avatars/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Store relative path from root in database
                $db_path = 'uploads/avatars/' . $filename;
                $update = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $update->bind_param("si", $db_path, $user_id);
                if ($update->execute()) {
                    // ✅ Update session
                    $_SESSION['user_avatar'] = $db_path;
                    $user['avatar'] = $db_path;
                    $avatar = '../' . $db_path;
                    $message = '<div class="alert-success"><i class="fas fa-check-circle"></i> Profile picture updated successfully!</div>';
                    // Force reload to update all pages
                    echo '<meta http-equiv="refresh" content="2">';
                } else {
                    $error = '<div class="alert-danger">Database update failed.</div>';
                    if (file_exists($filepath)) unlink($filepath);
                }
            } else {
                $error = '<div class="alert-danger">Failed to move uploaded file. Check folder permissions.</div>';
            }
        }
    } else {
        $error = '<div class="alert-danger">Please select a file.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | Royal Estate</title>
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
        .profile-container { display:flex; gap:2rem; flex-wrap:wrap; }
        .avatar-section { flex:0 0 280px; background:white; border-radius:20px; padding:1.5rem; text-align:center; box-shadow:0 5px 15px rgba(0,0,0,0.05); }
        .avatar-section img { width:180px; height:180px; border-radius:50%; object-fit:cover; border:4px solid #c5a263; margin-bottom:1rem; }
        .info-section { flex:1; background:white; border-radius:20px; padding:1.5rem; box-shadow:0 5px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom:1.2rem; }
        .form-group input { width:100%; padding:12px 16px; border-radius:40px; border:1px solid #ddd; }
        button { background:linear-gradient(135deg,#c5a263,#8b691f); color:white; border:none; padding:10px 24px; border-radius:40px; cursor:pointer; }
        .alert-success { background:#d4edda; color:#155724; padding:12px; border-radius:12px; margin-bottom:15px; }
        .alert-danger { background:#f8d7da; color:#721c24; padding:12px; border-radius:12px; margin-bottom:15px; }
        @media (max-width:768px){ .sidebar{ left:-280px; } .main-content{ margin-left:0; } }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- ✅ Sidebar - Same as other pages -->
    <div class="sidebar" id="sidebar"><?php include 'sidebar-common.php'; ?></div>

    <div class="main-content">
        <div class="top-bar">
            <div class="top-bar-left">
                <!-- ✅ Avatar - Shows uploaded image if exists, otherwise UI Avatars -->
                <img src="<?php echo $avatar; ?>" class="top-avatar" alt="Profile" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=c5a263&color=fff&size=45&font-size=0.45&bold=true'">
                <h2><i class="fas fa-user-circle"></i> My Profile</h2>
            </div>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <?php echo $message; ?>
        <?php echo $error; ?>

        <div class="profile-container">
            <div class="avatar-section">
                <!-- ✅ Large Avatar - Uploaded image if exists, else UI Avatars -->
                <img src="<?php echo $avatar; ?>" id="avatarPreview" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=c5a263&color=fff&size=180&font-size=0.5&bold=true'">
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" required style="margin-bottom:10px;">
                    <button type="submit" name="upload_avatar"><i class="fas fa-upload"></i> Upload New Picture</button>
                </form>
                <p>Max 2MB (JPG, PNG, GIF, WEBP)</p>
            </div>
            <div class="info-section">
                <h3>📝 Personal Information</h3>
                <form method="POST">
                    <div class="form-group"><label>Full Name</label><input type="text" name="fullname" value="<?php echo htmlspecialchars($user['name']); ?>" required></div>
                    <div class="form-group"><label>Email</label><input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled></div>
                    <div class="form-group"><label>Member Since</label><input type="text" value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" disabled></div>
                    <button type="submit" name="update_profile">Update Profile</button>
                </form>
                <a href="change-password.php" style="color:#c5a263; display:inline-block; margin-top:1rem;">Change Password</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Preview avatar before upload
    document.querySelector('input[name="avatar"]').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('avatarPreview').src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
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