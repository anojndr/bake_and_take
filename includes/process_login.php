<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php?page=login');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirect('../index.php?page=login', 'Invalid request. Please try again.', 'error');
}

$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if (empty($email) || empty($password)) {
    redirect('../index.php?page=login', 'Please enter both email and password.', 'error');
}

if (!isValidEmail($email)) {
    redirect('../index.php?page=login', 'Please enter a valid email address.', 'error');
}

// In production, verify against database
// For demo, we'll simulate a successful login
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = $email;
$_SESSION['user_name'] = 'Guest User';

redirect('../index.php', 'Welcome back! You\'re now logged in.', 'success');
?>
