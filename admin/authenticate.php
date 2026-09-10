<?php
session_start();
$username = $_POST['username'];
$password = $_POST['password'];
// Dummy credentials – change to DB check
if ($username === 'admin' && $password === 'admin123') {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_name'] = 'Administrator';
    header('Location: index.php');
} else {
    $_SESSION['admin_error'] = 'Invalid credentials';
    header('Location: login.php');
}
?>