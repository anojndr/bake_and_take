<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'mailer.php';
require_once 'sms_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php?page=register');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirect('../index.php?page=register', 'Invalid request. Please try again.', 'error');
}

$firstName = sanitize($_POST['first_name'] ?? '');
$lastName = sanitize($_POST['last_name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$verificationMethod = sanitize($_POST['verification_method'] ?? 'email');

// Prepend +63 to phone number (form only collects the 10 digits)
if (!empty($phone)) {
    $phone = preg_replace('/[^0-9]/', '', $phone); // Remove any non-numeric characters
    if (!empty($phone)) {
        $phone = '+63' . $phone;
    }
}

// Validation
if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
    redirect('../index.php?page=register', 'Please fill in all required fields.', 'error');
}

if (!isValidEmail($email)) {
    redirect('../index.php?page=register', 'Please enter a valid email address.', 'error');
}

// If phone verification is selected, phone number is required
if ($verificationMethod === 'phone' && empty($phone)) {
    redirect('../index.php?page=register', 'Phone number is required for phone verification.', 'error');
}

if (strlen($password) < 8) {
    redirect('../index.php?page=register', 'Password must be at least 8 characters.', 'error');
}

if ($password !== $confirmPassword) {
    redirect('../index.php?page=register', 'Passwords do not match.', 'error');
}

// Check if database connection exists
if (!$pdo) {
    redirect('../index.php?page=register', 'Database connection error. Please try again later.', 'error');
}

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    redirect('../index.php?page=register', 'An account with this email already exists. Please login instead.', 'error');
}

// Check if phone number already exists (if provided)
if (!empty($phone)) {
    $formattedPhone = formatPhoneNumber($phone);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$formattedPhone]);
    if ($stmt->fetch()) {
        redirect('../index.php?page=register', 'An account with this phone number already exists.', 'error');
    }
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Generate verification token for email verification
$verificationToken = bin2hex(random_bytes(32));
$tokenExpiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

// Insert user into database
try {
    $stmt = $pdo->prepare("
        INSERT INTO users (first_name, last_name, email, phone, password, is_verified, verification_method, verification_token, verification_token_expires_at) 
        VALUES (?, ?, ?, ?, ?, FALSE, ?, ?, ?)
    ");
    $stmt->execute([
        $firstName, 
        $lastName, 
        $email, 
        !empty($phone) ? formatPhoneNumber($phone) : null, 
        $hashedPassword,
        $verificationMethod,
        $verificationMethod === 'email' ? $verificationToken : null,
        $verificationMethod === 'email' ? $tokenExpiresAt : null
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Handle verification based on chosen method
    if ($verificationMethod === 'email') {
        // Send verification email
        $verificationLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') 
                          . $_SERVER['HTTP_HOST'] 
                          . dirname($_SERVER['SCRIPT_NAME']) 
                          . '/verify_email.php?token=' . $verificationToken;
        
        $emailBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #8B4513;'>Welcome to Bake & Take!</h2>
                <p>Hi {$firstName},</p>
                <p>Thank you for creating an account with us. Please verify your email address by clicking the button below:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$verificationLink}' 
                       style='background: linear-gradient(135deg, #E8B482 0%, #D4A574 100%); 
                              color: white; 
                              padding: 14px 32px; 
                              text-decoration: none; 
                              border-radius: 8px; 
                              font-weight: bold;
                              display: inline-block;'>
                        Verify My Email
                    </a>
                </div>
                <p style='color: #666;'>Or copy and paste this link in your browser:</p>
                <p style='word-break: break-all; color: #8B4513;'>{$verificationLink}</p>
                <p style='color: #999; font-size: 12px; margin-top: 30px;'>This link will expire in 24 hours.</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                <p style='color: #999; font-size: 12px;'>If you didn't create this account, please ignore this email.</p>
            </div>
        ";
        
        $mailResult = sendMail($email, "Verify Your Email - Bake & Take", $emailBody);
        
        if ($mailResult['success']) {
            redirect('../index.php?page=login', 'Account created! Please check your email to verify your account before logging in.', 'success');
        } else {
            // Email failed but account created - still show verification message
            redirect('../index.php?page=login', 'Account created! Please check your email to verify your account. (Note: Email may be delayed)', 'success');
        }
        
    } else {
        // Phone verification - Send OTP
        $formattedPhone = formatPhoneNumber($phone);
        
        $otpResult = sendOTP($formattedPhone, 'registration', $userId);
        
        if ($otpResult['success']) {
            // Store user ID in session for verification page
            $_SESSION['pending_verification_user_id'] = $userId;
            $_SESSION['pending_verification_phone'] = $formattedPhone;
            $_SESSION['pending_verification_method'] = 'phone';
            
            redirect('../index.php?page=verify-phone', 'Account created! Please enter the OTP code sent to your phone.', 'success');
        } else {
            // OTP failed - delete the user and show error
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            redirect('../index.php?page=register', 'Failed to send verification code. Please try again or choose email verification.', 'error');
        }
    }
    
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    redirect('../index.php?page=register', 'Registration failed. Please try again.', 'error');
}
?>
