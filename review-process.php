<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Please login to submit a review.', 'redirect' => 'index.php?review=login']);
    exit();
}

require_once 'config/db_connection.php';

$user_id = $_SESSION['user_id'];
$rating = intval($_POST['rating'] ?? 5);
$comment = trim($_POST['comment'] ?? '');

// Validation
if (empty($comment)) {
    echo json_encode(['success' => false, 'error' => 'Please write a comment.']);
    exit();
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Invalid rating.']);
    exit();
}

// Insert review
$stmt = $conn->prepare("INSERT INTO reviews (user_id, rating, comment, status) VALUES (?, ?, ?, 'pending')");
$stmt->bind_param("iis", $user_id, $rating, $comment);

if ($stmt->execute()) {
    // Get user info for response
    $user_stmt = $conn->prepare("SELECT name, avatar FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user = $user_stmt->get_result()->fetch_assoc();

    // Determine avatar
    $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=c5a263&color=fff&size=40&font-size=0.4&bold=true';
    if (!empty($user['avatar']) && file_exists($user['avatar'])) {
        $avatar = $user['avatar'];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Review submitted successfully! Awaiting approval.',
        'review' => [
            'name' => $user['name'],
            'avatar' => $avatar,
            'rating' => $rating,
            'comment' => $comment,
            'created_at' => date('M d, Y')
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error. Please try again.']);
}
exit();
?>