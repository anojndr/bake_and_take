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
if (!$pdo) {
    redirect('../index.php?page=login', 'Database connection error. Please try again later.', 'error');
}

try {
    // Find user with this token
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, verification_token_expires_at, is_verified 
        FROM users 
        WHERE verification_token = ? AND verification_method = 'email'
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
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
    $stmt = $pdo->prepare("
        UPDATE users 
        SET is_verified = TRUE, 
            email_verified = TRUE,
            verification_token = NULL, 
            verification_token_expires_at = NULL 
        WHERE id = ?
    ");
    $stmt->execute([$user['id']]);
    
    // Automatically log the user in
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    
    redirect('../index.php', 'Email verified successfully! Welcome, ' . $user['first_name'] . '!', 'success');
    
} catch (PDOException $e) {
    error_log("Email verification error: " . $e->getMessage());
    redirect('../index.php?page=login', 'Verification failed. Please try again.', 'error');
}
?>
