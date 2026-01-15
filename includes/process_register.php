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
if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($password)) {
    redirect('../index.php?page=register', 'Please fill in all required fields.', 'error');
}

if (!isValidEmail($email)) {
    redirect('../index.php?page=register', 'Please enter a valid email address.', 'error');
}

// Phone number validation
if (empty($phone)) {
    redirect('../index.php?page=register', 'Phone number is required.', 'error');
}

// Validate phone format (10 digits starting with 9)
$phoneDigits = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
if (!isValidPhoneNumber($phoneDigits)) {
    redirect('../index.php?page=register', 'Please enter a valid Philippine mobile number (10 digits starting with 9).', 'error');
}

// Password strength validation
$passwordCheck = isStrongPassword($password);
if (!$passwordCheck['valid']) {
    $missing = implode(', ', $passwordCheck['errors']);
    redirect('../index.php?page=register', 'Password must contain: ' . $missing . '.', 'error');
}

if ($password !== $confirmPassword) {
    redirect('../index.php?page=register', 'Passwords do not match.', 'error');
}

// Check if database connection exists
if (!$conn) {
    redirect('../index.php?page=register', 'Database connection error. Please try again later.', 'error');
}

// Check if email already exists
$stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_fetch_assoc($result)) {
    mysqli_stmt_close($stmt);
    redirect('../index.php?page=register', 'An account with this email already exists. Please login instead.', 'error');
}
mysqli_stmt_close($stmt);

// Check if phone number already exists (if provided)
if (!empty($phone)) {
    $formattedPhone = formatPhoneNumber($phone);
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE phone = ?");
    mysqli_stmt_bind_param($stmt, "s", $formattedPhone);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        redirect('../index.php?page=register', 'An account with this phone number already exists.', 'error');
    }
    mysqli_stmt_close($stmt);
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Generate verification token for email verification
$verificationToken = bin2hex(random_bytes(32));
$tokenExpiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

// Insert user into database
$stmt = mysqli_prepare($conn, "
    INSERT INTO users (first_name, last_name, email, phone, password, is_verified, verification_method, verification_token, verification_token_expires_at) 
    VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?)
");

if (!$stmt) {
    error_log("Registration error: " . mysqli_error($conn));
    redirect('../index.php?page=register', 'Registration failed. Please try again.', 'error');
}

$phoneValue = !empty($phone) ? formatPhoneNumber($phone) : null;
$tokenValue = $verificationMethod === 'email' ? $verificationToken : null;
$expiresValue = $verificationMethod === 'email' ? $tokenExpiresAt : null;

mysqli_stmt_bind_param($stmt, "ssssssss", 
    $firstName, 
    $lastName, 
    $email, 
    $phoneValue, 
    $hashedPassword,
    $verificationMethod,
    $tokenValue,
    $expiresValue
);

if (!mysqli_stmt_execute($stmt)) {
    error_log("Registration error: " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    redirect('../index.php?page=register', 'Registration failed. Please try again.', 'error');
}

mysqli_stmt_close($stmt);
$userId = mysqli_insert_id($conn);

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
        $deleteStmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($deleteStmt, "i", $userId);
        mysqli_stmt_execute($deleteStmt);
        mysqli_stmt_close($deleteStmt);
        redirect('../index.php?page=register', 'Failed to send verification code. Please try again or choose email verification.', 'error');
    }
}
?>
