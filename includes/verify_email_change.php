<?php
/**
 * Email Change Verification Handler
 * Verifies email change requests and updates user's email
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
    SELECT user_id, first_name, last_name, email, pending_email, pending_email_token, pending_email_expires, email_change_step
    FROM users 
    WHERE pending_email_token = ?
");
if (!$stmt) {
    error_log("Email change verification error: " . mysqli_error($conn));
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

// Ensure flow step is correct
if (($user['email_change_step'] ?? '') !== 'verify_new') {
    redirect('../index.php?page=login', 'Invalid verification state. Please request a new email change.', 'error');
}

// Check if token is expired
if (strtotime($user['pending_email_expires']) < time()) {
    // Clear expired pending change
    $stmt = mysqli_prepare($conn, "
        UPDATE users 
        SET pending_email = NULL, pending_email_token = NULL, pending_email_expires = NULL,
            pending_email_old_otp = NULL,
            email_change_step = NULL,
            email_change_cancel_token = NULL
        WHERE user_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    redirect('../index.php?page=login', 'Verification link has expired. Please request a new email change.', 'error');
}

// Check if the new email is still available
$stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? AND user_id != ?");
mysqli_stmt_bind_param($stmt, "si", $user['pending_email'], $user['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$existingUser = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($existingUser) {
    // Email was taken by someone else
    $stmt = mysqli_prepare($conn, "
        UPDATE users 
        SET pending_email = NULL, pending_email_token = NULL, pending_email_expires = NULL,
            pending_email_old_otp = NULL,
            email_change_step = NULL,
            email_change_cancel_token = NULL
        WHERE user_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    redirect('../index.php?page=profile', 'This email address is now in use by another account.', 'error');
}

// Update user's email
$stmt = mysqli_prepare($conn, "
    UPDATE users 
    SET email = ?, email_verified = TRUE,
        pending_email = NULL, pending_email_token = NULL, pending_email_expires = NULL,
        pending_email_old_otp = NULL,
        email_change_step = NULL,
        email_change_cancel_token = NULL
    WHERE user_id = ?
");
if (!$stmt) {
    error_log("Email change verification error: " . mysqli_error($conn));
    redirect('../index.php?page=login', 'Verification failed. Please try again.', 'error');
}
mysqli_stmt_bind_param($stmt, "si", $user['pending_email'], $user['user_id']);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Update session if user is logged in
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['user_id']) {
    $_SESSION['user_email'] = $user['pending_email'];
}

// Redirect to profile or login based on session
if (isset($_SESSION['user_id'])) {
    redirect('../index.php?page=profile', 'Email address updated and verified successfully!', 'success');
} else {
    redirect('../index.php?page=login', 'Email address updated and verified! Please login with your new email.', 'success');
}
?>;
}
?>
