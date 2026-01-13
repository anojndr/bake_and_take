<?php
/**
 * Phone Change Recovery Verification Handler
 * Handles email-based recovery for phone changes when user can't access old phone
 */
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'sms_service.php';
require_once 'mailer.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    redirect('../index.php?page=login', 'Invalid recovery link.', 'error');
}

// Check if database connection exists
if (!$pdo) {
    redirect('../index.php?page=login', 'Database connection error. Please try again later.', 'error');
}

try {
    // Find user with this recovery token
    $stmt = $pdo->prepare("
        SELECT id, first_name, email, phone, pending_phone, phone_recovery_token, 
               pending_phone_expires, phone_change_step 
        FROM users 
        WHERE phone_recovery_token = ?
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        redirect('../index.php?page=login', 'Invalid or expired recovery link.', 'error');
    }
    
    // Check if still in email_recovery step
    if ($user['phone_change_step'] !== 'email_recovery') {
        redirect('../index.php?page=profile', 'This recovery link is no longer valid.', 'error');
    }
    
    // Check if token is expired
    if (strtotime($user['pending_phone_expires']) < time()) {
        // Clear expired recovery
        $stmt = $pdo->prepare("
            UPDATE users 
            SET pending_phone = NULL, pending_phone_otp = NULL, pending_phone_expires = NULL,
                phone_change_step = NULL, phone_recovery_token = NULL
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        
        redirect('../index.php?page=profile', 'Recovery link has expired. Please start the phone change process again.', 'error');
    }
    
    // Email recovery verified! Now send OTP to NEW phone
    $newOtp = generateOTP(6);
    $newExpiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Update to verify_new step
    $stmt = $pdo->prepare("
        UPDATE users 
        SET pending_phone_otp = ?, pending_phone_expires = ?,
            phone_change_step = 'verify_new', phone_recovery_token = NULL
        WHERE id = ?
    ");
    $stmt->execute([$newOtp, $newExpiresAt, $user['id']]);
    
    // Send OTP to NEW phone
    $message = "Your " . SMS_SENDER_NAME . " verification code is: " . $newOtp . ". Enter this to verify your new phone number. Valid for 10 minutes.";
    $result = sendSMS($user['pending_phone'], $message, null, $user['id']);
    
    // Log the user in if not already
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'];
    }
    
    if ($result['success']) {
        redirect('../index.php?page=profile', 'Email verified! OTP sent to your new phone number. Enter the code to complete the change.', 'success');
    } else {
        error_log("SMS to new phone failed after email recovery: " . $result['message']);
        redirect('../index.php?page=profile', 'Email verified, but failed to send OTP to new phone. Please try resending.', 'error');
    }
    
} catch (PDOException $e) {
    error_log("Phone recovery verification error: " . $e->getMessage());
    redirect('../index.php?page=login', 'Recovery verification failed. Please try again.', 'error');
}
?>
