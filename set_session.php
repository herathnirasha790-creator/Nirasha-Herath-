<?php
session_start();

// Get data from URL
$email = isset($_GET['email']) ? $_GET['email'] : '';
$name = isset($_GET['name']) ? $_GET['name'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

// Set session variables
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = $name;
$_SESSION['user_email'] = $email;
$_SESSION['user_role'] = $role;
$_SESSION['logged_in'] = true;

// Redirect to dashboard
header("Location: " . $redirect);
exit();
?>