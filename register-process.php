<?php
session_start();
header('Content-Type: application/json');

// Database connection
require_once 'config/db_connection.php';

// Get POST data
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validation
if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all fields.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
    exit();
}

if (strlen($password) < 4) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 4 characters.']);
    exit();
}

// Check if email already exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Email already registered. Please login.']);
    exit();
}

// Hash password and insert
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
$stmt->bind_param("sss", $name, $email, $hashed_password);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Please login.',
        'email' => $email
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
}
exit();
?>