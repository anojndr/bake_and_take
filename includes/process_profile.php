<?php
/**
 * Profile Update Handler
 * Handles profile updates including email and phone changes with verification
 */
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'mailer.php';
require_once 'sms_service.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('../index.php?page=login', 'Please login to continue.', 'error');
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php?page=profile', 'Invalid request method.', 'error');
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    redirect('../index.php?page=profile', 'Invalid request. Please try again.', 'error');
}

// Get action
$action = $_POST['action'] ?? '';

// Get user ID
$userId = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'update_name':
            handleNameUpdate($pdo, $userId);
            break;
        
        case 'update_profile':
            handleProfileUpdate($pdo, $userId);
            break;
            
        case 'request_email_change':
            handleEmailChangeRequest($pdo, $userId);
            break;

        case 'verify_old_email_code':
            handleVerifyOldEmailCode($pdo, $userId);
            break;
            
        case 'request_phone_change':
            handlePhoneChangeRequest($pdo, $userId);
            break;
            
        case 'verify_old_phone_otp':
            handleVerifyOldPhoneOtp($pdo, $userId);
            break;
            
        case 'verify_new_phone_otp':
            handleVerifyNewPhoneOtp($pdo, $userId);
            break;
            
        case 'phone_change_email_recovery':
            handlePhoneChangeEmailRecovery($pdo, $userId);
            break;
            
        case 'resend_new_phone_otp':
            handleResendNewPhoneOtp($pdo, $userId);
            break;
            
        case 'resend_phone_recovery_email':
            handleResendPhoneRecoveryEmail($pdo, $userId);
            break;
            
        case 'cancel_phone_change':
            handleCancelPhoneChange($pdo, $userId);
            break;
            
        case 'cancel_email_change':
            handleCancelEmailChange($pdo, $userId);
            break;
            
        case 'change_password':
            handlePasswordChange($pdo, $userId);
            break;
        
        // Phone verification for existing users
        case 'send_phone_verification_otp':
            handleSendPhoneVerificationOtp($pdo, $userId);
            break;
            
        case 'verify_phone_otp':
            handleVerifyPhoneOtp($pdo, $userId);
            break;
            
        case 'resend_phone_verification_otp':
            handleResendPhoneVerificationOtp($pdo, $userId);
            break;
            
        case 'cancel_phone_verification':
            handleCancelPhoneVerification($pdo, $userId);
            break;
        
        // Email verification for existing users
        case 'send_email_verification_link':
            handleSendEmailVerificationLink($pdo, $userId);
            break;
            
        case 'resend_email_verification_link':
            handleResendEmailVerificationLink($pdo, $userId);
            break;
            
        case 'cancel_email_verification':
            handleCancelEmailVerification($pdo, $userId);
            break;
            
        default:
            redirect('../index.php?page=profile', 'Invalid action.', 'error');
    }
} catch (Exception $e) {
    error_log("Profile update error: " . $e->getMessage());
    redirect('../index.php?page=profile', 'An error occurred. Please try again.', 'error');
}

/**
 * Handle name update
 * Requires password verification for security
 */
function handleNameUpdate($pdo, $userId) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    
    if (empty($firstName) || empty($lastName)) {
        redirect('../index.php?page=profile', 'First name and last name are required.', 'error');
    }
    
    if (empty($currentPassword)) {
        redirect('../index.php?page=profile', 'Please enter your password to confirm changes.', 'error');
    }
    
    // Get current user password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // Verify password
    if (!password_verify($currentPassword, $user['password'])) {
        redirect('../index.php?page=profile', 'Incorrect password. Please try again.', 'error');
    }
    
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE user_id = ?");
    $stmt->execute([$firstName, $lastName, $userId]);
    
    // Update session
    $_SESSION['user_name'] = $firstName . ' ' . $lastName;
    
    redirect('../index.php?page=profile', 'Name updated successfully!', 'success');
}

/**
 * Handle profile update (full name only)
 */
function handleProfileUpdate($pdo, $userId) {
    $fullName = trim($_POST['full_name'] ?? '');
    
    if (empty($fullName)) {
        redirect('../index.php?page=profile&edit=profile', 'Full name is required.', 'error');
    }
    
    // Parse full name into first and last name
    $nameParts = explode(' ', $fullName, 2);
    $firstName = $nameParts[0];
    $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
    
    // Update user profile
    $stmt = $pdo->prepare("
        UPDATE users 
        SET first_name = ?, last_name = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$firstName, $lastName, $userId]);
    
    // Update session
    $_SESSION['user_name'] = trim($firstName . ' ' . $lastName);
    
    redirect('../index.php?page=profile', 'Profile updated successfully!', 'success');
}

/**
 * Handle email change request
 * Step 1: Requires password verification, sends OTP code to OLD email.
 * Step 2 (after OTP): sends verification link to NEW email.
 */
function handleEmailChangeRequest($pdo, $userId) {
    $newEmail = trim($_POST['new_email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    
    // Validate password is provided
    if (empty($currentPassword)) {
        redirect('../index.php?page=profile', 'Please enter your current password.', 'error');
    }
    
    // Validate email
    if (empty($newEmail) || !isValidEmail($newEmail)) {
        redirect('../index.php?page=profile', 'Please enter a valid email address.', 'error');
    }
    
    // Get current user with password
    $stmt = $pdo->prepare("SELECT email, password, first_name FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // Verify password
    if (!password_verify($currentPassword, $user['password'])) {
        redirect('../index.php?page=profile', 'Incorrect password. Please try again.', 'error');
    }
    
    // Check if email is the same
    if (strtolower($user['email']) === strtolower($newEmail)) {
        redirect('../index.php?page=profile', 'This is already your current email address.', 'info');
    }
    
    // Check if email is already in use
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$newEmail, $userId]);
    if ($stmt->fetch()) {
        redirect('../index.php?page=profile', 'This email address is already in use.', 'error');
    }
    
    // Generate OTP for OLD email
    $oldEmailOtp = generateOTP(6);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Generate cancel token for old email (also used by profile cancel flow)
    $cancelToken = bin2hex(random_bytes(32));

    // Store pending email change (step 1)
    $stmt = $pdo->prepare("
        UPDATE users 
        SET pending_email = ?, pending_email_expires = ?,
            pending_email_old_otp = ?,
            email_change_step = 'verify_old',
            pending_email_token = NULL,
            email_change_cancel_token = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$newEmail, $expiresAt, $oldEmailOtp, $cancelToken, $userId]);
    
    // Generate URLs
    $baseUrl = getCurrentSiteUrl();
    $cancelUrl = $baseUrl . '/includes/cancel_email_change.php?token=' . $cancelToken;

    // Send OTP code to OLD email address
    $notifySubject = '🔐 Email Change Authorization Code - ' . SITE_NAME;
    $notifyBody = getEmailChangeOldEmailOtpTemplate($user['first_name'], $user['email'], $newEmail, $oldEmailOtp, $cancelUrl);
    $notifyResult = sendMail($user['email'], $notifySubject, $notifyBody);

    if (!$notifyResult['success']) {
        // Clear pending email on failure
        $stmt = $pdo->prepare("
            UPDATE users 
            SET pending_email = NULL, pending_email_token = NULL, pending_email_expires = NULL,
                pending_email_old_otp = NULL,
                email_change_step = NULL,
                email_change_cancel_token = NULL
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);

        error_log("Email OTP send failed: " . $notifyResult['message']);
        redirect('../index.php?page=profile', 'Failed to send authorization code to your current email. Please try again.', 'error');
    }

    redirect('../index.php?page=profile', 'We sent an authorization code to your current email. Enter it to continue with changing to ' . $newEmail . '.', 'success');
}

/**
 * Step 2: Verify OTP from OLD email, then send verification link to NEW email
 */
function handleVerifyOldEmailCode($pdo, $userId) {
    $otpCode = trim($_POST['otp_code'] ?? '');

    if (empty($otpCode) || !preg_match('/^[0-9]{6}$/', $otpCode)) {
        redirect('../index.php?page=profile', 'Please enter a valid 6-digit code.', 'error');
    }

    // Get user with pending email change
    $stmt = $pdo->prepare("SELECT email, first_name, pending_email, pending_email_expires, pending_email_old_otp, email_change_step FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || empty($user['pending_email']) || $user['email_change_step'] !== 'verify_old') {
        redirect('../index.php?page=profile', 'No pending email authorization found.', 'error');
    }

    // Check expiry
    if (empty($user['pending_email_expires']) || strtotime($user['pending_email_expires']) < time()) {
        clearPendingEmailChange($pdo, $userId);
        redirect('../index.php?page=profile', 'Authorization code has expired. Please start over.', 'error');
    }

    // Verify OTP
    if (empty($user['pending_email_old_otp']) || $user['pending_email_old_otp'] !== $otpCode) {
        redirect('../index.php?page=profile', 'Invalid authorization code. Please try again.', 'error');
    }

    // Old email authorized; now generate verification token for NEW email and send link
    $verifyToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt = $pdo->prepare("
        UPDATE users
        SET pending_email_token = ?,
            pending_email_expires = ?,
            pending_email_old_otp = NULL,
            email_change_step = 'verify_new'
        WHERE user_id = ?
    ");
    $stmt->execute([$verifyToken, $expiresAt, $userId]);

    $baseUrl = getCurrentSiteUrl();
    $verifyUrl = $baseUrl . '/includes/verify_email_change.php?token=' . $verifyToken;

    $verifySubject = 'Verify Your New Email Address - ' . SITE_NAME;
    $verifyBody = getEmailChangeVerificationTemplate($user['email'], $user['pending_email'], $verifyUrl);
    $verifyResult = sendMail($user['pending_email'], $verifySubject, $verifyBody);

    if (!$verifyResult['success']) {
        error_log("Email verification send failed: " . $verifyResult['message']);
        // Keep pending change but require re-start for safety
        clearPendingEmailChange($pdo, $userId);
        redirect('../index.php?page=profile', 'Failed to send verification email to your new address. Please start over.', 'error');
    }

    redirect('../index.php?page=profile', 'Authorization confirmed! We sent a verification link to ' . $user['pending_email'] . '. Please click it to finish changing your email.', 'success');
}

/**
 * Helper: Clear pending email change data
 */
function clearPendingEmailChange($pdo, $userId) {
    $stmt = $pdo->prepare("
        UPDATE users
        SET pending_email = NULL,
            pending_email_token = NULL,
            pending_email_expires = NULL,
            pending_email_old_otp = NULL,
            email_change_step = NULL,
            email_change_cancel_token = NULL
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
}

/**
 * Handle phone change request - Step 1: Password verification + send OTP to old phone
 */
function handlePhoneChangeRequest($pdo, $userId) {
    $newPhone = trim($_POST['new_phone'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    
    // Validate password
    if (empty($currentPassword)) {
        redirect('../index.php?page=profile', 'Please enter your current password.', 'error');
    }
    
    // Validate phone
    if (empty($newPhone) || !preg_match('/^[0-9]{10}$/', $newPhone)) {
        redirect('../index.php?page=profile', 'Please enter a valid 10-digit phone number.', 'error');
    }
    
    // Format phone with country code
    $formattedPhone = '+63' . $newPhone;
    
    // Get current user with password
    $stmt = $pdo->prepare("SELECT phone, password FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Require an existing current phone so we can authorize via OTP to the old number
    if (empty($user['phone'])) {
        redirect('../index.php?page=profile', 'You must have a current phone number on file to change it. Please add/verify a phone number first.', 'error');
    }
    
    // Verify password
    if (!password_verify($currentPassword, $user['password'])) {
        redirect('../index.php?page=profile', 'Incorrect password. Please try again.', 'error');
    }
    
    // Check if phone is the same
    if ($user['phone'] === $formattedPhone) {
        redirect('../index.php?page=profile', 'This is already your current phone number.', 'info');
    }
    
    // Check if phone is already in use
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE phone = ? AND user_id != ?");
    $stmt->execute([$formattedPhone, $userId]);
    if ($stmt->fetch()) {
        redirect('../index.php?page=profile', 'This phone number is already in use.', 'error');
    }
    
    // Generate OTP for old phone
    $otp = generateOTP(6);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Store pending phone change with step indicator
    $stmt = $pdo->prepare("
        UPDATE users 
        SET pending_phone = ?, pending_phone_otp = ?, pending_phone_expires = ?,
            phone_change_step = 'verify_old'
        WHERE user_id = ?
    ");
    $stmt->execute([$formattedPhone, $otp, $expiresAt, $userId]);
    
    // Send OTP to OLD phone
    $message = "Your " . SMS_SENDER_NAME . " authorization code is: " . $otp . ". Enter this to authorize changing your phone number. Valid for 10 minutes.";
    $result = sendSMS($user['phone'], $message, null, $userId);
    
    if ($result['success']) {
        redirect('../index.php?page=profile', 'OTP sent to your current phone. Please enter the code to authorize the change.', 'success');
    } else {
        // Clear pending phone on failure
        $stmt = $pdo->prepare("
            UPDATE users 
            SET pending_phone = NULL, pending_phone_otp = NULL, pending_phone_expires = NULL,
                phone_change_step = NULL
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        
        error_log("SMS send failed: " . $result['message']);
        redirect('../index.php?page=profile', 'Failed to send OTP. Please try again.', 'error');
    }
}

/**
 * Step 2: Verify OTP from old phone, then send OTP to new phone
 */
function handleVerifyOldPhoneOtp($pdo, $userId) {
    $otpCode = trim($_POST['otp_code'] ?? '');
    
    if (empty($otpCode) || !preg_match('/^[0-9]{6}$/', $otpCode)) {
        redirect('../index.php?page=profile', 'Please enter a valid 6-digit OTP code.', 'error');
    }
    
    // Get user with pending phone change
    $stmt = $pdo->prepare("
        SELECT pending_phone, pending_phone_otp, pending_phone_expires, phone_change_step 
        FROM users WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user['pending_phone'] || $user['phone_change_step'] !== 'verify_old') {
        redirect('../index.php?page=profile', 'No pending phone authorization found.', 'error');
    }
    
    // Check if OTP expired
    if (strtotime($user['pending_phone_expires']) < time()) {
        clearPendingPhoneChange($pdo, $userId);
        redirect('../index.php?page=profile', 'OTP has expired. Please start over.', 'error');
    }
    
    // Verify OTP
    if ($user['pending_phone_otp'] !== $otpCode) {
        redirect('../index.php?page=profile', 'Invalid OTP code. Please try again.', 'error');
    }
    
    // Old phone verified! Now send OTP to NEW phone
    $newOtp = generateOTP(6);
    $newExpiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Update to step 2
    $stmt = $pdo->prepare("
        UPDATE users 
        SET pending_phone_otp = ?, pending_phone_expires = ?,
            phone_change_step = 'verify_new'
        WHERE user_id = ?
    ");
    $stmt->execute([$newOtp, $newExpiresAt, $userId]);
    
    // Send OTP to NEW phone
    $message = "Your " . SMS_SENDER_NAME . " verification code is: " . $newOtp . ". Enter this to verify your new phone number. Valid for 10 minutes.";
    $result = sendSMS($user['pending_phone'], $message, null, $userId);
    
    if ($result['success']) {
        redirect('../index.php?page=profile', 'Identity confirmed! OTP sent to your new phone number. Enter the code to complete the change.', 'success');
    } else {
        error_log("SMS to new phone failed: " . $result['message']);
        redirect('../index.php?page=profile', 'Failed to send OTP to new phone. Please try again.', 'error');
    }
}

/**
 * Step 3: Verify OTP from new phone, complete the change, send notifications
 */
function handleVerifyNewPhoneOtp($pdo, $userId) {
    $otpCode = trim($_POST['otp_code'] ?? '');
    
    if (empty($otpCode) || !preg_match('/^[0-9]{6}$/', $otpCode)) {
        redirect('../index.php?page=profile', 'Please enter a valid 6-digit OTP code.', 'error');
    }
    
    // Get user with pending phone change
    $stmt = $pdo->prepare("
        SELECT email, first_name, phone, pending_phone, pending_phone_otp, pending_phone_expires, phone_change_step 
        FROM users WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user['pending_phone'] || $user['phone_change_step'] !== 'verify_new') {
        redirect('../index.php?page=profile', 'No pending phone verification found.', 'error');
    }
    
    // Check if OTP expired
    if (strtotime($user['pending_phone_expires']) < time()) {
        clearPendingPhoneChange($pdo, $userId);
        redirect('../index.php?page=profile', 'OTP has expired. Please start over.', 'error');
    }
    
    // Verify OTP
    if ($user['pending_phone_otp'] !== $otpCode) {
        redirect('../index.php?page=profile', 'Invalid OTP code. Please try again.', 'error');
    }
    
    $oldPhone = $user['phone'];
    $newPhone = $user['pending_phone'];
    
    // Update phone number
    $stmt = $pdo->prepare("
        UPDATE users 
        SET phone = ?, phone_verified = TRUE,
            pending_phone = NULL, pending_phone_otp = NULL, pending_phone_expires = NULL,
            phone_change_step = NULL, phone_recovery_token = NULL
        WHERE user_id = ?
    ");
    $stmt->execute([$newPhone, $userId]);
    
    // Send notification to OLD phone
    $oldPhoneMsg = "Your " . SMS_SENDER_NAME . " phone number has been changed to " . $newPhone . ". If this wasn't you, contact support immediately.";
    sendSMS($oldPhone, $oldPhoneMsg, null, $userId);
    
    // Send notification to email
    $emailSubject = '📱 Phone Number Changed - ' . SITE_NAME;
    $emailBody = getPhoneChangeNotificationTemplate($user['first_name'], $oldPhone, $newPhone);
    sendMail($user['email'], $emailSubject, $emailBody);
    
    redirect('../index.php?page=profile', 'Phone number updated and verified successfully! Notifications sent to your old phone and email.', 'success');
}

/**
 * Handle email recovery for phone change (when old phone is inaccessible)
 */
function handlePhoneChangeEmailRecovery($pdo, $userId) {
    // Get user
    $stmt = $pdo->prepare("
        SELECT email, first_name, pending_phone, phone_change_step 
        FROM users WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user['pending_phone'] || $user['phone_change_step'] !== 'verify_old') {
        redirect('../index.php?page=profile', 'No pending phone change to recover.', 'error');
    }
    
    // Generate recovery token
    $recoveryToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Update to email recovery step
    $stmt = $pdo->prepare("
        UPDATE users 
        SET phone_change_step = 'email_recovery',
            phone_recovery_token = ?,
            pending_phone_expires = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$recoveryToken, $expiresAt, $userId]);
    
    // Generate recovery URL
    $baseUrl = getCurrentSiteUrl();
    $recoveryUrl = $baseUrl . '/includes/verify_phone_recovery.php?token=' . $recoveryToken;
    
    // Send recovery email
    $subject = '🔐 Phone Change Recovery - ' . SITE_NAME;
    $body = getPhoneRecoveryEmailTemplate($user['first_name'], $user['pending_phone'], $recoveryUrl);
    $result = sendMail($user['email'], $subject, $body);
    
    if ($result['success']) {
        redirect('../index.php?page=profile', 'Recovery email sent! Please check your inbox and click the link to proceed.', 'success');
    } else {
        error_log("Recovery email failed: " . $result['message']);
        redirect('../index.php?page=profile', 'Failed to send recovery email. Please try again.', 'error');
    }
}

/**
 * Resend OTP to new phone
 */
function handleResendNewPhoneOtp($pdo, $userId) {
    // Get user
    $stmt = $pdo->prepare("
        SELECT pending_phone, phone_change_step 
        FROM users WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user['pending_phone'] || $user['phone_change_step'] !== 'verify_new') {
        redirect('../index.php?page=profile', 'No pending phone verification found.', 'error');
    }
    
    // Generate new OTP
    $newOtp = generateOTP(6);
    $newExpiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Update OTP
    $stmt = $pdo->prepare("
        UPDATE users 
        SET pending_phone_otp = ?, pending_phone_expires = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$newOtp, $newExpiresAt, $userId]);
    
    // Send OTP
    $message = "Your " . SMS_SENDER_NAME . " verification code is: " . $newOtp . ". Valid for 10 minutes.";
    $result = sendSMS($user['pending_phone'], $message, null, $userId);
    
    if ($result['success']) {
        redirect('../index.php?page=profile', 'New OTP sent to your new phone number.', 'success');
    } else {
        redirect('../index.php?page=profile', 'Failed to resend OTP. Please try again.', 'error');
    }
}

/**
 * Resend phone recovery email
 */
function handleResendPhoneRecoveryEmail($pdo, $userId) {
    // Get user
    $stmt = $pdo->prepare("
        SELECT email, first_name, pending_phone, phone_change_step 
        FROM users WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user['pending_phone'] || $user['phone_change_step'] !== 'email_recovery') {
        redirect('../index.php?page=profile', 'No pending recovery found.', 'error');
    }
    
    // Generate new recovery token
    $recoveryToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Update token
    $stmt = $pdo->prepare("
        UPDATE users 
        SET phone_recovery_token = ?,
            pending_phone_expires = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$recoveryToken, $expiresAt, $userId]);
    
    // Generate recovery URL
    $baseUrl = getCurrentSiteUrl();
    $recoveryUrl = $baseUrl . '/includes/verify_phone_recovery.php?token=' . $recoveryToken;
    
    // Send recovery email
    $subject = '🔐 Phone Change Recovery - ' . SITE_NAME;
    $body = getPhoneRecoveryEmailTemplate($user['first_name'], $user['pending_phone'], $recoveryUrl);
    $result = sendMail($user['email'], $subject, $body);
    
    if ($result['success']) {
        redirect('../index.php?page=profile', 'Recovery email resent! Please check your inbox.', 'success');
    } else {
        redirect('../index.php?page=profile', 'Failed to resend email. Please try again.', 'error');
    }
}

/**
 * Handle cancel phone change
 */
function handleCancelPhoneChange($pdo, $userId) {
    clearPendingPhoneChange($pdo, $userId);
    redirect('../index.php?page=profile', 'Phone change cancelled.', 'info');
}

/**
 * Helper: Clear pending phone change data
 */
function clearPendingPhoneChange($pdo, $userId) {
    $stmt = $pdo->prepare("
        UPDATE users 
        SET pending_phone = NULL, pending_phone_otp = NULL, pending_phone_expires = NULL,
            phone_change_step = NULL, phone_recovery_token = NULL
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
}


/**
 * Handle password change
 */
function handlePasswordChange($pdo, $userId) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        redirect('../index.php?page=profile', 'All password fields are required.', 'error');
    }
    
    if (strlen($newPassword) < 8) {
        redirect('../index.php?page=profile', 'New password must be at least 8 characters.', 'error');
    }
    
    if ($newPassword !== $confirmPassword) {
        redirect('../index.php?page=profile', 'New passwords do not match.', 'error');
    }
    
    // Get current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        redirect('../index.php?page=profile', 'Current password is incorrect.', 'error');
    }
    
    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->execute([$hashedPassword, $userId]);
    
    redirect('../index.php?page=profile', 'Password updated successfully!', 'success');
}

/**
 * Generate email change verification email template
 */
function getEmailChangeVerificationTemplate($oldEmail, $newEmail, $verifyUrl) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Verify Your New Email Address</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
        <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #D4A574 0%, #B8896A 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                <h1 style="color: white; margin: 0; font-size: 28px;">🍞 ' . SITE_NAME . '</h1>
            </div>
            <div style="background: white; padding: 40px 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h2 style="color: #2C1810; margin-top: 0;">Verify Your New Email Address</h2>
                <p style="color: #666; line-height: 1.6;">
                    You requested to change your email address from:<br>
                    <strong>' . htmlspecialchars($oldEmail) . '</strong><br><br>
                    To your new email address:<br>
                    <strong>' . htmlspecialchars($newEmail) . '</strong>
                </p>
                <p style="color: #666; line-height: 1.6;">
                    Click the button below to verify this new email address:
                </p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $verifyUrl . '" style="background: linear-gradient(135deg, #D4A574 0%, #B8896A 100%); color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block;">Verify Email Address</a>
                </div>
                <p style="color: #999; font-size: 14px; line-height: 1.6;">
                    If you didn\'t request this change, please ignore this email. Your current email address will remain unchanged.
                </p>
                <p style="color: #999; font-size: 14px;">
                    This link expires in 24 hours.
                </p>
                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                <p style="color: #999; font-size: 12px; text-align: center; margin: 0;">
                    &copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.
                </p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Handle cancel email change (from profile page)
 */
function handleCancelEmailChange($pdo, $userId) {
    clearPendingEmailChange($pdo, $userId);
    
    redirect('../index.php?page=profile', 'Email change request cancelled.', 'info');
}

/**
 * Generate email authorization code template (sent to OLD email)
 */
function getEmailChangeOldEmailOtpTemplate($firstName, $oldEmail, $newEmail, $otp, $cancelUrl) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Email Change Authorization Code</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
        <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #D4A574 0%, #B8896A 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                <h1 style="color: white; margin: 0; font-size: 28px;">🍞 ' . SITE_NAME . '</h1>
            </div>
            <div style="background: white; padding: 40px 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="background: #e7f3ff; border: 1px solid #b6d4fe; border-radius: 8px; padding: 15px; margin-bottom: 25px;">
                    <p style="color: #084298; margin: 0; font-weight: bold;">🔐 Email Change Authorization</p>
                </div>
                
                <p style="color: #666; line-height: 1.6;">
                    Hi ' . htmlspecialchars($firstName) . ',
                </p>
                
                <p style="color: #666; line-height: 1.6;">
                    A request was made to change the email address on your ' . SITE_NAME . ' account.
                </p>
                
                <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 25px 0;">
                    <p style="margin: 0 0 10px 0; color: #666;">
                        <strong>Current email:</strong><br>
                        <span style="color: #2C1810;">' . htmlspecialchars($oldEmail) . '</span>
                    </p>
                    <p style="margin: 0; color: #666;">
                        <strong>New email (pending):</strong><br>
                        <span style="color: #2C1810;">' . htmlspecialchars($newEmail) . '</span>
                    </p>
                </div>

                <p style="color: #666; line-height: 1.6; margin-bottom: 10px;">
                    <strong>Your authorization code:</strong>
                </p>
                <div style="text-align:center; margin: 15px 0 25px 0;">
                    <div style="display:inline-block; background:#2C1810; color:#fff; letter-spacing: 6px; font-size: 22px; padding: 14px 18px; border-radius: 10px; font-weight: bold;">
                        ' . htmlspecialchars($otp) . '
                    </div>
                </div>
                
                <p style="color: #666; line-height: 1.6;">
                    Enter this code in your Profile page to authorize the change. The code expires in 10 minutes.
                </p>
                
                <p style="color: #666; line-height: 1.6;">
                    <strong>If you did NOT request this change:</strong><br>
                    Click the button below to cancel the email change immediately.
                </p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $cancelUrl . '" style="background: #dc3545; color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block;">Cancel Email Change</a>
                </div>
                
                <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; padding: 15px; margin-top: 25px;">
                    <p style="color: #721c24; margin: 0; font-size: 14px;">
                        <strong>Security Tips:</strong><br>
                        • If you didn\'t request this, someone may have your password.<br>
                        • Consider changing your password immediately.<br>
                        • Enable two-factor authentication if available.
                    </p>
                </div>
                
                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                
                <p style="color: #999; font-size: 12px; text-align: center; margin: 0;">
                    This is an automated security notification from ' . SITE_NAME . '.<br>
                    &copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.
                </p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Generate phone change notification email template (sent after successful change)
 */
function getPhoneChangeNotificationTemplate($firstName, $oldPhone, $newPhone) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Phone Number Changed</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
        <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #D4A574 0%, #B8896A 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                <h1 style="color: white; margin: 0; font-size: 28px;">🍞 ' . SITE_NAME . '</h1>
            </div>
            <div style="background: white; padding: 40px 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="background: #d1fae5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 15px; margin-bottom: 25px;">
                    <p style="color: #065f46; margin: 0; font-weight: bold;">
                        ✅ Phone Number Successfully Changed
                    </p>
                </div>
                
                <p style="color: #666; line-height: 1.6;">
                    Hi ' . htmlspecialchars($firstName) . ',
                </p>
                
                <p style="color: #666; line-height: 1.6;">
                    Your phone number has been successfully changed on your ' . SITE_NAME . ' account.
                </p>
                
                <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 25px 0;">
                    <p style="margin: 0 0 10px 0; color: #666;">
                        <strong>Previous phone:</strong><br>
                        <span style="color: #999; text-decoration: line-through;">' . htmlspecialchars($oldPhone) . '</span>
                    </p>
                    <p style="margin: 0; color: #666;">
                        <strong>New phone:</strong><br>
                        <span style="color: #16a34a; font-weight: bold;">' . htmlspecialchars($newPhone) . '</span>
                    </p>
                </div>
                
                <div style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px; padding: 15px; margin-top: 25px;">
                    <p style="color: #92400e; margin: 0; font-size: 14px;">
                        <strong>⚠️ Didn\'t make this change?</strong><br>
                        If you did not change your phone number, please contact our support team immediately as your account may have been compromised.
                    </p>
                </div>
                
                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                
                <p style="color: #999; font-size: 12px; text-align: center; margin: 0;">
                    This is an automated notification from ' . SITE_NAME . '.<br>
                    &copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.
                </p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Generate phone recovery email template (sent when user can\'t access old phone)
 */
function getPhoneRecoveryEmailTemplate($firstName, $newPhone, $recoveryUrl) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Phone Change Recovery</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
        <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #D4A574 0%, #B8896A 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                <h1 style="color: white; margin: 0; font-size: 28px;">🍞 ' . SITE_NAME . '</h1>
            </div>
            <div style="background: white; padding: 40px 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="background: #ede9fe; border: 1px solid #c4b5fd; border-radius: 8px; padding: 15px; margin-bottom: 25px;">
                    <p style="color: #5b21b6; margin: 0; font-weight: bold;">
                        🔐 Phone Change Recovery Request
                    </p>
                </div>
                
                <p style="color: #666; line-height: 1.6;">
                    Hi ' . htmlspecialchars($firstName) . ',
                </p>
                
                <p style="color: #666; line-height: 1.6;">
                    You requested to verify your phone change via email because you can\'t access your current phone number.
                </p>
                
                <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 25px 0;">
                    <p style="margin: 0; color: #666;">
                        <strong>New phone number to be set:</strong><br>
                        <span style="color: #2C1810; font-weight: bold;">' . htmlspecialchars($newPhone) . '</span>
                    </p>
                </div>
                
                <p style="color: #666; line-height: 1.6;">
                    Click the button below to authorize this phone change and proceed to verify your new phone number:
                </p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $recoveryUrl . '" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block;">Authorize Phone Change</a>
                </div>
                
                <p style="color: #999; font-size: 14px; line-height: 1.6;">
                    If you didn\'t request this change, please ignore this email. Your phone number will not be changed.
                </p>
                
                <p style="color: #999; font-size: 14px;">
                    This link expires in 1 hour.
                </p>
                
                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                
                <p style="color: #999; font-size: 12px; text-align: center; margin: 0;">
                    This is an automated email from ' . SITE_NAME . '.<br>
                    &copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.
                </p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Handle sending OTP for phone verification (verifying existing phone)
 */
function handleSendPhoneVerificationOtp($pdo, $userId) {
    // Get user
    $stmt = $pdo->prepare("SELECT phone, phone_verified FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (empty($user['phone'])) {
        redirect('../index.php?page=profile', 'No phone number found. Please add a phone number first.', 'error');
    }
    
    if ($user['phone_verified']) {
        redirect('../index.php?page=profile', 'Your phone number is already verified.', 'info');
    }
    
    // Send OTP to the user's phone
    $otpResult = sendOTP($user['phone'], 'phone_verify', $userId);
    
    if ($otpResult['success']) {
        // Set session variable to track verification step
        $_SESSION['phone_verify_step'] = 'verify_otp';
        $_SESSION['phone_verify_phone'] = $user['phone'];
        
        redirect('../index.php?page=profile&action=verify_phone', 'Verification code sent to ' . $user['phone'] . '. Please enter the code to verify your phone.', 'success');
    } else {
        error_log("Phone verification OTP send failed: " . ($otpResult['message'] ?? 'Unknown error'));
        redirect('../index.php?page=profile&action=verify_phone', 'Failed to send verification code. Please try again.', 'error');
    }
}

/**
 * Handle verifying OTP for phone verification
 */
function handleVerifyPhoneOtp($pdo, $userId) {
    $otpCode = trim($_POST['otp_code'] ?? '');
    
    if (empty($otpCode) || !preg_match('/^[0-9]{6}$/', $otpCode)) {
        redirect('../index.php?page=profile&action=verify_phone', 'Please enter a valid 6-digit code.', 'error');
    }
    
    // Get user
    $stmt = $pdo->prepare("SELECT phone, phone_verified FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (empty($user['phone'])) {
        unset($_SESSION['phone_verify_step']);
        unset($_SESSION['phone_verify_phone']);
        redirect('../index.php?page=profile', 'No phone number found.', 'error');
    }
    
    if ($user['phone_verified']) {
        unset($_SESSION['phone_verify_step']);
        unset($_SESSION['phone_verify_phone']);
        redirect('../index.php?page=profile', 'Your phone number is already verified.', 'info');
    }
    
    // Verify OTP
    $verifyResult = verifyOTP($user['phone'], $otpCode);
    
    if ($verifyResult['success']) {
        // Update user as phone verified
        $stmt = $pdo->prepare("UPDATE users SET phone_verified = TRUE WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Clear session variables
        unset($_SESSION['phone_verify_step']);
        unset($_SESSION['phone_verify_phone']);
        
        redirect('../index.php?page=profile', 'Phone number verified successfully! Your account is now fully verified.', 'success');
    } else {
        redirect('../index.php?page=profile&action=verify_phone', $verifyResult['message'] ?? 'Invalid verification code. Please try again.', 'error');
    }
}

/**
 * Handle resending OTP for phone verification
 */
function handleResendPhoneVerificationOtp($pdo, $userId) {
    // Get user
    $stmt = $pdo->prepare("SELECT phone, phone_verified FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (empty($user['phone'])) {
        unset($_SESSION['phone_verify_step']);
        redirect('../index.php?page=profile', 'No phone number found.', 'error');
    }
    
    if ($user['phone_verified']) {
        unset($_SESSION['phone_verify_step']);
        redirect('../index.php?page=profile', 'Your phone number is already verified.', 'info');
    }
    
    // Send new OTP
    $otpResult = sendOTP($user['phone'], 'phone_verify', $userId);
    
    if ($otpResult['success']) {
        $_SESSION['phone_verify_step'] = 'verify_otp';
        redirect('../index.php?page=profile&action=verify_phone', 'New verification code sent to ' . $user['phone'] . '.', 'success');
    } else {
        redirect('../index.php?page=profile&action=verify_phone', 'Failed to resend code. Please try again.', 'error');
    }
}

/**
 * Handle canceling phone verification
 */
function handleCancelPhoneVerification($pdo, $userId) {
    unset($_SESSION['phone_verify_step']);
    unset($_SESSION['phone_verify_phone']);
    redirect('../index.php?page=profile', 'Phone verification cancelled.', 'info');
}

/**
 * Handle sending email verification link (verifying existing email)
 */
function handleSendEmailVerificationLink($pdo, $userId) {
    // Get user
    $stmt = $pdo->prepare("SELECT email, email_verified, first_name FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (empty($user['email'])) {
        redirect('../index.php?page=profile', 'No email address found.', 'error');
    }
    
    if ($user['email_verified']) {
        redirect('../index.php?page=profile', 'Your email address is already verified.', 'info');
    }
    
    // Generate verification token
    $verifyToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Store verification token
    $stmt = $pdo->prepare("
        UPDATE users 
        SET email_verify_token = ?, email_verify_expires = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$verifyToken, $expiresAt, $userId]);
    
    // Generate verification URL
    $baseUrl = getCurrentSiteUrl();
    $verifyUrl = $baseUrl . '/includes/verify_existing_email.php?token=' . $verifyToken;
    
    // Send verification email
    $subject = '✉️ Verify Your Email Address - ' . SITE_NAME;
    $body = getEmailVerificationTemplate($user['first_name'], $user['email'], $verifyUrl);
    $result = sendMail($user['email'], $subject, $body);
    
    if ($result['success']) {
        $_SESSION['email_verify_step'] = 'check_inbox';
        redirect('../index.php?page=profile&action=verify_email', 'Verification link sent to ' . $user['email'] . '. Please check your inbox.', 'success');
    } else {
        error_log("Email verification send failed: " . $result['message']);
        redirect('../index.php?page=profile&action=verify_email', 'Failed to send verification email. Please try again.', 'error');
    }
}

/**
 * Handle resending email verification link
 */
function handleResendEmailVerificationLink($pdo, $userId) {
    // Get user
    $stmt = $pdo->prepare("SELECT email, email_verified, first_name FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (empty($user['email'])) {
        unset($_SESSION['email_verify_step']);
        redirect('../index.php?page=profile', 'No email address found.', 'error');
    }
    
    if ($user['email_verified']) {
        unset($_SESSION['email_verify_step']);
        redirect('../index.php?page=profile', 'Your email address is already verified.', 'info');
    }
    
    // Generate new verification token
    $verifyToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Store verification token
    $stmt = $pdo->prepare("
        UPDATE users 
        SET email_verify_token = ?, email_verify_expires = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$verifyToken, $expiresAt, $userId]);
    
    // Generate verification URL
    $baseUrl = getCurrentSiteUrl();
    $verifyUrl = $baseUrl . '/includes/verify_existing_email.php?token=' . $verifyToken;
    
    // Send verification email
    $subject = '✉️ Verify Your Email Address - ' . SITE_NAME;
    $body = getEmailVerificationTemplate($user['first_name'], $user['email'], $verifyUrl);
    $result = sendMail($user['email'], $subject, $body);
    
    if ($result['success']) {
        $_SESSION['email_verify_step'] = 'check_inbox';
        redirect('../index.php?page=profile&action=verify_email', 'New verification link sent to ' . $user['email'] . '.', 'success');
    } else {
        redirect('../index.php?page=profile&action=verify_email', 'Failed to resend verification email. Please try again.', 'error');
    }
}

/**
 * Handle canceling email verification
 */
function handleCancelEmailVerification($pdo, $userId) {
    unset($_SESSION['email_verify_step']);
    redirect('../index.php?page=profile', 'Email verification cancelled.', 'info');
}

/**
 * Generate email verification template
 */
function getEmailVerificationTemplate($firstName, $email, $verifyUrl) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Verify Your Email Address</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
        <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #D4A574 0%, #B8896A 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                <h1 style="color: white; margin: 0; font-size: 28px;">🍞 ' . SITE_NAME . '</h1>
            </div>
            <div style="background: white; padding: 40px 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h2 style="color: #2C1810; margin-top: 0;">Hi ' . htmlspecialchars($firstName) . '!</h2>
                <p style="color: #666; line-height: 1.6;">
                    Please verify your email address to complete your account setup and unlock all features.
                </p>
                <p style="color: #666; line-height: 1.6;">
                    Email to verify: <strong>' . htmlspecialchars($email) . '</strong>
                </p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $verifyUrl . '" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block;">Verify Email Address</a>
                </div>
                <p style="color: #999; font-size: 14px; line-height: 1.6;">
                    If you didn\'t request this verification, you can safely ignore this email.
                </p>
                <p style="color: #999; font-size: 14px;">
                    This link expires in 24 hours.
                </p>
                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                <p style="color: #999; font-size: 12px; text-align: center; margin: 0;">
                    &copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.
                </p>
            </div>
        </div>
    </body>
    </html>';
}
?>
