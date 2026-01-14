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
    setFlashMessage('Invalid verification link.', 'error');
    header('Location: ../index.php?page=profile');
    exit;
}

if (!$pdo) {
    setFlashMessage('Database connection error.', 'error');
    header('Location: ../index.php?page=profile');
    exit;
}

try {
    // Find user with this token
    $stmt = $pdo->prepare("
        SELECT id, email, first_name, email_verify_token, email_verify_expires, email_verified
        FROM users 
        WHERE email_verify_token = ?
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        setFlashMessage('Invalid or expired verification link.', 'error');
        header('Location: ../index.php?page=profile');
        exit;
    }
    
    // Check if already verified
    if ($user['email_verified']) {
        // Clear token
        $stmt = $pdo->prepare("UPDATE users SET email_verify_token = NULL, email_verify_expires = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        setFlashMessage('Your email address is already verified.', 'info');
        header('Location: ../index.php?page=profile');
        exit;
    }
    
    // Check if token expired
    if (!empty($user['email_verify_expires']) && strtotime($user['email_verify_expires']) < time()) {
        // Clear expired token
        $stmt = $pdo->prepare("UPDATE users SET email_verify_token = NULL, email_verify_expires = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        setFlashMessage('Verification link has expired. Please request a new one.', 'error');
        header('Location: ../index.php?page=profile');
        exit;
    }
    
    // Verify the email
    $stmt = $pdo->prepare("
        UPDATE users 
        SET email_verified = TRUE, 
            email_verify_token = NULL, 
            email_verify_expires = NULL
        WHERE id = ?
    ");
    $stmt->execute([$user['id']]);
    
    // Clear session step if exists
    if (isset($_SESSION['email_verify_step'])) {
        unset($_SESSION['email_verify_step']);
    }
    
    setFlashMessage('Email verified successfully! Your account is now fully verified.', 'success');
    header('Location: ../index.php?page=profile');
    exit;
    
} catch (PDOException $e) {
    error_log("Email verification error: " . $e->getMessage());
    setFlashMessage('An error occurred during verification. Please try again.', 'error');
    header('Location: ../index.php?page=profile');
    exit;
}
?>
