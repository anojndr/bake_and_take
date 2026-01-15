<?php
/**
 * Verify Existing Email
 * Handles email verification for existing users who want to verify their email address
 */
session_start();
require_once 'config.php';
require_once 'functions.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    setFlashMessage('error', 'Invalid verification link.');
    header('Location: ../index.php?page=profile');
    exit;
}

global $conn;
if (!$conn) {
    setFlashMessage('error', 'Database connection error.');
    header('Location: ../index.php?page=profile');
    exit;
}

// Find user with this token
$stmt = mysqli_prepare($conn, "
    SELECT user_id, email, first_name, email_verify_token, email_verify_expires, email_verified
    FROM users 
    WHERE email_verify_token = ?
");
if (!$stmt) {
    error_log("Email verification error: " . mysqli_error($conn));
    setFlashMessage('error', 'An error occurred during verification. Please try again.');
    header('Location: ../index.php?page=profile');
    exit;
}
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    setFlashMessage('error', 'Invalid or expired verification link.');
    header('Location: ../index.php?page=profile');
    exit;
}

// Check if already verified
if ($user['email_verified']) {
    // Clear token
    $stmt = mysqli_prepare($conn, "UPDATE users SET email_verify_token = NULL, email_verify_expires = NULL WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    setFlashMessage('info', 'Your email address is already verified.');
    header('Location: ../index.php?page=profile');
    exit;
}

// Check if token expired
if (!empty($user['email_verify_expires']) && strtotime($user['email_verify_expires']) < time()) {
    // Clear expired token
    $stmt = mysqli_prepare($conn, "UPDATE users SET email_verify_token = NULL, email_verify_expires = NULL WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    setFlashMessage('error', 'Verification link has expired. Please request a new one.');
    header('Location: ../index.php?page=profile');
    exit;
}

// Verify the email
$stmt = mysqli_prepare($conn, "
    UPDATE users 
    SET email_verified = TRUE, 
        email_verify_token = NULL, 
        email_verify_expires = NULL
    WHERE user_id = ?
");
if (!$stmt) {
    error_log("Email verification error: " . mysqli_error($conn));
    setFlashMessage('error', 'An error occurred during verification. Please try again.');
    header('Location: ../index.php?page=profile');
    exit;
}
mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Clear session step if exists
if (isset($_SESSION['email_verify_step'])) {
    unset($_SESSION['email_verify_step']);
}

setFlashMessage('success', 'Email verified successfully! Your account is now fully verified.');
header('Location: ../index.php?page=profile');
exit;
?>
