<?php
/**
 * Email Verification Handler
 * Verifies users who chose email verification during registration
 */
session_start();
require_once 'config.php';
require_once 'functions.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    redirect('../index.php?page=login', 'Invalid verification link.', 'error');
}

// Check if database connection exists
global $conn;
if (!$conn) {
    redirect('../index.php?page=login', 'Database connection error. Please try again later.', 'error');
}

// Find user with this token
$stmt = mysqli_prepare($conn, "
    SELECT user_id, first_name, last_name, email, verification_token_expires_at, is_verified 
    FROM users 
    WHERE verification_token = ? AND verification_method = 'email'
");
if (!$stmt) {
    error_log("Email verification error: " . mysqli_error($conn));
    redirect('../index.php?page=login', 'Verification failed. Please try again.', 'error');
}
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    redirect('../index.php?page=login', 'Invalid or expired verification link.', 'error');
}

// Check if already verified
if ($user['is_verified']) {
    redirect('../index.php?page=login', 'Your email is already verified. Please login.', 'info');
}

// Check if token is expired
if (strtotime($user['verification_token_expires_at']) < time()) {
    redirect('../index.php?page=login', 'Verification link has expired. Please register again.', 'error');
}

// Verify the user
$stmt = mysqli_prepare($conn, "
    UPDATE users 
    SET is_verified = TRUE, 
        email_verified = TRUE,
        verification_token = NULL, 
        verification_token_expires_at = NULL 
    WHERE user_id = ?
");
if (!$stmt) {
    error_log("Email verification error: " . mysqli_error($conn));
    redirect('../index.php?page=login', 'Verification failed. Please try again.', 'error');
}
mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Automatically log the user in
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];

redirect('../index.php', 'Email verified successfully! Welcome, ' . $user['first_name'] . '!', 'success');
?>
