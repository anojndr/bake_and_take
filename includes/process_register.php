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

// Check if database connection exists
if (!$pdo) {
    redirect('../index.php?page=register', 'Database connection error. Please try again later.', 'error');
}

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    redirect('../index.php?page=register', 'An account with this email already exists. Please login instead.', 'error');
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert user into database
try {
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$firstName, $lastName, $email, $hashedPassword]);
    
    $userId = $pdo->lastInsertId();
    
    // Auto-login after registration
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $firstName . ' ' . $lastName;
    
    redirect('../index.php', 'Account created successfully! Welcome to Bake & Take.', 'success');
} catch (PDOException $e) {
    redirect('../index.php?page=register', 'Registration failed. Please try again.', 'error');
}
?>
