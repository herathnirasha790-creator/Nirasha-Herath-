<?php
$is_localhost = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1' || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0);

if ($is_localhost) {
    // Localhost Database Credentials
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db = 'royalestate';
} else {
    // InfinityFree Database Credentials
    $host = 'sql103.infinityfree.com';
    $user = 'if0_42879158';
    // Ensure this password is correct exactly as shown on your dashboard
    $pass = 'HMTPC5O87zO'; 
    $db = 'if0_42879158_royalestate';
}

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
?>