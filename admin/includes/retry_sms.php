<?php
/**
 * Retry SMS - Admin AJAX Handler
 */
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/sms_service.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$smsId = filter_input(INPUT_POST, 'sms_id', FILTER_VALIDATE_INT);

if (!$smsId) {
    echo json_encode(['success' => false, 'message' => 'Invalid SMS ID']);
    exit;
}

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

try {
    // Get the original SMS
    $stmt = $pdo->prepare("SELECT * FROM sms_log WHERE id = ? AND direction = 'outbound'");
    $stmt->execute([$smsId]);
    $sms = $stmt->fetch();
    
    if (!$sms) {
        echo json_encode(['success' => false, 'message' => 'SMS not found or not outbound']);
        exit;
    }
    
    // Retry sending
    $result = sendSMS($sms['phone_number'], $sms['message'], $sms['order_id'], $sms['user_id']);
    
    echo json_encode($result);
    
} catch (PDOException $e) {
    error_log("SMS retry error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
