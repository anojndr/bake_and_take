<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php?page=login');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirect('../index.php?page=login', 'Invalid request. Please try again.', 'error');
}

$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    redirect('../index.php?page=login', 'Please enter both email and password.', 'error');
}

if (!isValidEmail($email)) {
    redirect('../index.php?page=login', 'Please enter a valid email address.', 'error');
}

// Check if database connection exists
if (!$pdo) {
    redirect('../index.php?page=login', 'Database connection error. Please try again later.', 'error');
}

// Verify credentials against database
try {
    $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email, phone, password, is_admin, is_verified, verification_method FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        redirect('../index.php?page=login', 'Invalid email or password. Please try again.', 'error');
    }
    
    // Reject admin accounts - they must use the admin login page
    if ($user['is_admin']) {
        redirect('../index.php?page=login', 'Admin accounts cannot login here. Please use the admin portal.', 'error');
    }
    
    // Check if user is verified
    if (!$user['is_verified']) {
        $verificationMethod = $user['verification_method'] ?? 'email';
        
        if ($verificationMethod === 'phone' && !empty($user['phone'])) {
            // For phone verification, set up session and redirect to OTP page
            require_once 'sms_service.php';
            
            // Send new OTP
            $otpResult = sendOTP($user['phone'], 'registration', $user['user_id']);
            
            if ($otpResult['success']) {
                $_SESSION['pending_verification_user_id'] = $user['user_id'];
                $_SESSION['pending_verification_phone'] = $user['phone'];
                $_SESSION['pending_verification_method'] = 'phone';
                
                redirect('../index.php?page=verify-phone', 'Your account is not verified. A new OTP has been sent to your phone.', 'info');
            } else {
                redirect('../index.php?page=login', 'Your account is not verified. Failed to send verification code. Please contact support.', 'error');
            }
        } else {
            // Email verification - prompt user to check their email
            redirect('../index.php?page=login', 'Your account is not verified. Please check your email for the verification link.', 'error');
        }
    }
    
    // Set session variables for logged in user
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    
    redirect('../index.php', 'Welcome back, ' . $user['first_name'] . '! You\'re now logged in.', 'success');
} catch (PDOException $e) {
    redirect('../index.php?page=login', 'Login failed. Please try again.', 'error');
}
?>
