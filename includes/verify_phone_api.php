<?php
/**
 * Phone OTP Verification API
 * Handles OTP verification for users who chose phone verification
 */
session_start();
header('Content-Type: application/json');

require_once 'config.php';
require_once 'functions.php';
require_once 'sms_service.php';

$response = ['success' => false, 'message' => ''];

// Check if there's a pending verification
if (!isset($_SESSION['pending_verification_user_id']) || 
    !isset($_SESSION['pending_verification_phone']) ||
    $_SESSION['pending_verification_method'] !== 'phone') {
    $response['message'] = 'No pending verification found. Please register again.';
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['pending_verification_user_id'];
$phone = $_SESSION['pending_verification_phone'];

if (!$pdo) {
    $response['message'] = 'Database connection error.';
    echo json_encode($response);
    exit;
}

switch ($action) {
    case 'verify':
        $otp = sanitize($_POST['otp'] ?? '');
        
        if (empty($otp)) {
            $response['message'] = 'Please enter the OTP code.';
            echo json_encode($response);
            exit;
        }
        
        // Verify OTP
        $verifyResult = verifyOTP($phone, $otp);
        
        if ($verifyResult['success']) {
            try {
                // Get user data for auto-login
                $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    $response['message'] = 'User not found. Please register again.';
                    echo json_encode($response);
                    exit;
                }
                
                // Update user as verified
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET is_verified = TRUE, 
                        phone_verified = TRUE 
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
                
                // Clear pending verification session data
                unset($_SESSION['pending_verification_user_id']);
                unset($_SESSION['pending_verification_phone']);
                unset($_SESSION['pending_verification_method']);
                
                // Automatically log the user in
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                // Set flash message
                setFlashMessage('Phone verified successfully! Welcome, ' . $user['first_name'] . '!', 'success');
                
                $response['success'] = true;
                $response['message'] = 'Phone verified successfully! Logging you in...';
                $response['redirect'] = '../index.php';
                
            } catch (PDOException $e) {
                error_log("Phone verification DB error: " . $e->getMessage());
                $response['message'] = 'Verification failed. Please try again.';
            }
        } else {
            $response['message'] = $verifyResult['message'];
        }
        break;
        
    case 'resend':
        // Resend OTP
        $otpResult = sendOTP($phone, 'registration', $userId);
        
        if ($otpResult['success']) {
            $response['success'] = true;
            $response['message'] = 'A new OTP has been sent to your phone.';
        } else {
            $response['message'] = 'Failed to resend OTP. Please try again.';
        }
        break;
        
    default:
        $response['message'] = 'Invalid action.';
}

echo json_encode($response);
?>
