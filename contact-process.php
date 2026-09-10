<?php
session_start();
header('Content-Type: application/json');

// Error Reporting for Debugging
ini_set('display_errors', 0);
error_reporting(0);

require_once 'config/db_connection.php';

// Get POST data
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validation
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all required fields.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
    exit();
}

// Check if user is logged in
$user_id = null;
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $user_id = $_SESSION['user_id'] ?? null;
}

// ✅ Check which columns exist in the table
$check_name = $conn->query("SHOW COLUMNS FROM contact_messages LIKE 'user_name'");
$has_user_name = ($check_name && $check_name->num_rows > 0);

if ($has_user_name) {
    // Columns are user_name, user_email
    if ($user_id !== null) {
        $stmt = $conn->prepare("
            INSERT INTO contact_messages (user_id, user_name, user_email, phone, subject, message, is_read) 
            VALUES (?, ?, ?, ?, ?, ?, 0)
        ");
        $stmt->bind_param("isssss", $user_id, $name, $email, $phone, $subject, $message);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO contact_messages (user_name, user_email, phone, subject, message, is_read) 
            VALUES (?, ?, ?, ?, ?, 0)
        ");
        $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
    }
} else {
    // Columns are name, email (original)
    if ($user_id !== null) {
        $stmt = $conn->prepare("
            INSERT INTO contact_messages (user_id, name, email, phone, subject, message, is_read) 
            VALUES (?, ?, ?, ?, ?, ?, 0)
        ");
        $stmt->bind_param("isssss", $user_id, $name, $email, $phone, $subject, $message);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO contact_messages (name, email, phone, subject, message, is_read) 
            VALUES (?, ?, ?, ?, ?, 0)
        ");
        $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
    }
}

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Your message has been sent successfully! We will get back to you within 24 hours.',
        'is_logged_in' => ($user_id !== null)
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $conn->error
    ]);
}
exit();
?>