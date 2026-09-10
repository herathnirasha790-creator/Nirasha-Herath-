<?php
session_start();
header('Content-Type: application/json');

require_once 'config/db_connection.php'; // Includes dynamic $host, $user, $pass, $db

try {
    // Reusing the credentials from db_connection.php
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

$email = trim($_POST['email'] ?? '');
$pass = $_POST['password'] ?? '';

// Hardcoded demo users (existing)
$demo_users = [
    'user@gmail.com'      => ['id'=>1, 'name'=>'Saman Perera', 'role'=>'user', 'pass'=>'123', 'avatar'=>'https://randomuser.me/api/portraits/men/1.jpg'],
    'admin@royalestate.lk' => ['id'=>2, 'name'=>'Admin User', 'role'=>'admin', 'pass'=>'123', 'avatar'=>'https://randomuser.me/api/portraits/men/2.jpg'],
    'owner@royalestate.lk' => ['id'=>3, 'name'=>'Owner Name', 'role'=>'owner', 'pass'=>'123', 'avatar'=>'https://randomuser.me/api/portraits/men/3.jpg'],
];

if (isset($demo_users[$email]) && $demo_users[$email]['pass'] === $pass) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $demo_users[$email]['id'];
    $_SESSION['user_name'] = $demo_users[$email]['name'];
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $demo_users[$email]['role'];
    $_SESSION['user_avatar'] = $demo_users[$email]['avatar']; // Demo users only
    
    if ($demo_users[$email]['role'] === 'user') {
        echo json_encode(['success' => true, 'redirect' => 'index.php']);
    } else {
        echo json_encode(['success' => true, 'redirect' => 'admin/index.php']);
    }
    exit();
}

// Database users (registered via register-process.php)
$stmt = $pdo->prepare("SELECT id, name, email, password, role, avatar FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($pass, $user['password'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    
    // ✅ Only set avatar if user has one in database
    if (!empty($user['avatar']) && file_exists($user['avatar'])) {
        $_SESSION['user_avatar'] = $user['avatar'];
    } else {
        // ✅ Remove default avatar - set to empty or null
        $_SESSION['user_avatar'] = ''; // හෝ NULL
    }
    
    if ($user['role'] === 'user') {
        echo json_encode(['success' => true, 'redirect' => 'index.php']);
    } else {
        echo json_encode(['success' => true, 'redirect' => 'admin/index.php']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
}
exit();
?>