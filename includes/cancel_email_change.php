<?php
/**
 * Cancel Email Change Handler
 * Cancels a pending email change request via token link from security notification email
 * This allows the account owner to cancel an unauthorized email change from their old email
 */
session_start();
require_once 'config.php';
require_once 'functions.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    redirect('../index.php?page=login', 'Invalid cancellation link.', 'error');
}

// Check if database connection exists
if (!$conn) {
    redirect('../index.php?page=login', 'Database connection error. Please try again later.', 'error');
}

// Find user with this cancel token
$stmt = mysqli_prepare($conn, "
    SELECT user_id, first_name, email, pending_email, pending_email_expires, email_change_cancel_token 
    FROM users 
    WHERE email_change_cancel_token = ?
");
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    redirect('../index.php?page=login', 'Invalid or expired cancellation link. The email change may have already been cancelled or completed.', 'error');
}

// Check if there's actually a pending email change
if (empty($user['pending_email'])) {
    // Clear the cancel token if it exists but no pending change
    $stmt = mysqli_prepare($conn, "UPDATE users SET email_change_cancel_token = NULL WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    redirect('../index.php?page=login', 'No pending email change to cancel. Your email address has not been changed.', 'info');
}

// Store pending email info for the message
$pendingEmail = $user['pending_email'];

// Cancel the email change by clearing all pending fields
$stmt = mysqli_prepare($conn, "
    UPDATE users 
    SET pending_email = NULL, 
        pending_email_token = NULL, 
        pending_email_expires = NULL,
        pending_email_old_otp = NULL,
        email_change_step = NULL,
        email_change_cancel_token = NULL
    WHERE user_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
    
    // Success message
    $message = 'Email change to "' . htmlspecialchars($pendingEmail) . '" has been cancelled successfully. ';
    $message .= 'Your email address remains: ' . htmlspecialchars($user['email']) . '. ';
    $message .= 'If you did not request this change, we recommend changing your password immediately.';
    
    // Redirect based on whether user is logged in
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['user_id']) {
        redirect('../index.php?page=profile', $message, 'success');
    } else {
        redirect('../index.php?page=login', $message, 'success');
    }
?>
