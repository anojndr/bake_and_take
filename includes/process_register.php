<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php?page=register');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirect('../index.php?page=register', 'Invalid request. Please try again.', 'error');
}

$firstName = sanitize($_POST['first_name'] ?? '');
$lastName = sanitize($_POST['last_name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validation
if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
    redirect('../index.php?page=register', 'Please fill in all required fields.', 'error');
}

if (!isValidEmail($email)) {
    redirect('../index.php?page=register', 'Please enter a valid email address.', 'error');
}

if (strlen($password) < 8) {
    redirect('../index.php?page=register', 'Password must be at least 8 characters.', 'error');
}

if ($password !== $confirmPassword) {
    redirect('../index.php?page=register', 'Passwords do not match.', 'error');
}

// In production, save to database with hashed password
// For demo, auto-login
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = $email;
$_SESSION['user_name'] = $firstName . ' ' . $lastName;

redirect('../index.php', 'Account created successfully! Welcome to Bake & Take.', 'success');
?>
