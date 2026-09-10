<?php
session_start();
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'royalestate';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$email = trim($_POST['email'] ?? '');
$pass = $_POST['password'] ?? '';

// Hardcoded demo users
$demo_users = [
    'user@gmail.com'      => ['name'=>'Saman Perera', 'role'=>'user', 'pass'=>'123', 'avatar'=>'https://randomuser.me/api/portraits/men/1.jpg'],
    'admin@royalestate.lk' => ['name'=>'Admin User', 'role'=>'admin', 'pass'=>'123', 'avatar'=>'https://randomuser.me/api/portraits/men/2.jpg'],
    'owner@royalestate.lk' => ['name'=>'Owner Name', 'role'=>'owner', 'pass'=>'123', 'avatar'=>'https://randomuser.me/api/portraits/men/3.jpg'],
];

if (isset($demo_users[$email]) && $demo_users[$email]['pass'] === $pass) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = ($email === 'user@gmail.com') ? 1 : (($email === 'admin@royalestate.lk') ? 2 : 3);
    $_SESSION['user_name'] = $demo_users[$email]['name'];
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $demo_users[$email]['role'];
    $_SESSION['user_avatar'] = $demo_users[$email]['avatar'];
    
    // ✅ User → reload current page (stay on same page)
    if ($demo_users[$email]['role'] === 'user') {
        echo json_encode(['success' => true, 'reload' => true]);
    } else {
        echo json_encode(['success' => true, 'redirect' => 'admin/index.php']);
    }
    exit();
}

// Database users (registered via register-process.php)
$stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($pass, $user['password'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_avatar'] = 'https://randomuser.me/api/portraits/men/32.jpg';
    
    if ($user['role'] === 'user') {
        echo json_encode(['success' => true, 'reload' => true]);
    } else {
        echo json_encode(['success' => true, 'redirect' => 'admin/index.php']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
}
exit();