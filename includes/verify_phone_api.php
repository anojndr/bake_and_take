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
                
                // Set flash message for login page
                setFlashMessage('Phone verified successfully! You can now log in.', 'success');
                
                $response['success'] = true;
                $response['message'] = 'Phone verified successfully!';
                $response['redirect'] = '../index.php?page=login';
                
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
