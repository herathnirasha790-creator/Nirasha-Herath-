<?php
session_start();
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'owner')) {
    http_response_code(403);
    exit;
}
$page = $_GET['page'] ?? '';
$file = '../data/pages/' . $page . '.json';
if (file_exists($file)) {
    header('Content-Type: application/json');
    echo file_get_contents($file);
} else {
    echo json_encode(['title'=>'', 'description'=>'', 'images'=>[], 'videos'=>[]]);
}