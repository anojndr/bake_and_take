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

// Check if database connection exists
if (!$pdo) {
    redirect('../index.php?page=login', 'Database connection error. Please try again later.', 'error');
}

// Verify credentials against database
try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        redirect('../index.php?page=login', 'Invalid email or password. Please try again.', 'error');
    }
    
    // Set session variables for logged in user
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    
    redirect('../index.php', 'Welcome back, ' . $user['first_name'] . '! You\'re now logged in.', 'success');
} catch (PDOException $e) {
    redirect('../index.php?page=login', 'Login failed. Please try again.', 'error');
}
?>
