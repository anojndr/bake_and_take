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
if (!$pdo) {
    redirect('../index.php?page=login', 'Database connection error. Please try again later.', 'error');
}

try {
    // Find user with this token
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, pending_email, pending_email_token, pending_email_expires, email_change_step
        FROM users 
        WHERE pending_email_token = ?
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
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
        $stmt = $pdo->prepare("
            UPDATE users 
            SET pending_email = NULL, pending_email_token = NULL, pending_email_expires = NULL,
                pending_email_old_otp = NULL,
                email_change_step = NULL,
                email_change_cancel_token = NULL
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        
        redirect('../index.php?page=login', 'Verification link has expired. Please request a new email change.', 'error');
    }
    
    // Check if the new email is still available
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$user['pending_email'], $user['id']]);
    if ($stmt->fetch()) {
        // Email was taken by someone else
        $stmt = $pdo->prepare("
            UPDATE users 
            SET pending_email = NULL, pending_email_token = NULL, pending_email_expires = NULL,
                pending_email_old_otp = NULL,
                email_change_step = NULL,
                email_change_cancel_token = NULL
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        
        redirect('../index.php?page=profile', 'This email address is now in use by another account.', 'error');
    }
    
    // Update user's email
    $stmt = $pdo->prepare("
        UPDATE users 
        SET email = ?, email_verified = TRUE,
            pending_email = NULL, pending_email_token = NULL, pending_email_expires = NULL,
            pending_email_old_otp = NULL,
            email_change_step = NULL,
            email_change_cancel_token = NULL
        WHERE id = ?
    ");
    $stmt->execute([$user['pending_email'], $user['id']]);
    
    // Update session if user is logged in
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']) {
        $_SESSION['user_email'] = $user['pending_email'];
    }
    
    // Redirect to profile or login based on session
    if (isset($_SESSION['user_id'])) {
        redirect('../index.php?page=profile', 'Email address updated and verified successfully!', 'success');
    } else {
        redirect('../index.php?page=login', 'Email address updated and verified! Please login with your new email.', 'success');
    }
    
} catch (PDOException $e) {
    error_log("Email change verification error: " . $e->getMessage());
    redirect('../index.php?page=login', 'Verification failed. Please try again.', 'error');
}
?>
