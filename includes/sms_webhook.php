<?php
/**
 * SMS Webhook Receiver
 * 
 * This endpoint receives incoming SMS messages from the Android SMS Forwarder app
 * (https://github.com/bogkonstantin/android_income_sms_gateway_webhook)
 * 
 * The forwarder sends POST requests with JSON payload containing SMS data
 * 
 * Usage:
 * Configure your SMS Forwarder Android app to point to:
 * http://your-server/bake_and_take/includes/sms_webhook.php
 */

// Enable error logging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/sms_webhook_errors.log');

// Set JSON response header
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sms_config.php';
require_once __DIR__ . '/sms_service.php';

// Validate IP if restrictions are set
if (!empty(SMS_WEBHOOK_ALLOWED_IPS)) {
    $clientIP = $_SERVER['REMOTE_ADDR'];
    if (!in_array($clientIP, SMS_WEBHOOK_ALLOWED_IPS)) {
        http_response_code(403);
        logWebhookEvent('blocked', "Blocked IP: $clientIP", null);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied'
        ]);
        exit;
    }
}

// Get the raw POST data
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Log raw input for debugging
logWebhookEvent('received', 'Raw input received', $rawInput);

// Validate JSON parsing
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON: ' . json_last_error_msg()
    ]);
    exit;
}

// Validate webhook secret if configured
if (!empty(SMS_WEBHOOK_SECRET)) {
    $providedSecret = $data['secret'] ?? $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    if ($providedSecret !== SMS_WEBHOOK_SECRET) {
        http_response_code(401);
        logWebhookEvent('unauthorized', 'Invalid webhook secret', $rawInput);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid authentication'
        ]);
        exit;
    }
}

// Extract SMS data from the payload
// The SMS Forwarder app typically sends data in this format:
// {
//     "from": "+639123456789",
//     "text": "Message content here",
//     "sentStamp": 1234567890,
//     "receivedStamp": 1234567890,
//     "sim": "SIM1"
// }

$smsData = [
    'from' => $data['from'] ?? $data['sender'] ?? $data['phone'] ?? '',
    'message' => $data['text'] ?? $data['message'] ?? $data['body'] ?? '',
    'sent_timestamp' => $data['sentStamp'] ?? $data['sent_at'] ?? null,
    'received_timestamp' => $data['receivedStamp'] ?? $data['received_at'] ?? time(),
    'sim_slot' => $data['sim'] ?? $data['sim_slot'] ?? 'SIM1',
    'device_id' => $data['device_id'] ?? 'unknown'
];

// Validate required fields
if (empty($smsData['from']) || empty($smsData['message'])) {
    http_response_code(400);
    logWebhookEvent('error', 'Missing required fields', $rawInput);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: from and message'
    ]);
    exit;
}

// Format phone number
$phoneNumber = formatPhoneNumber($smsData['from']);

// Log the inbound SMS
$smsLogId = logSMS(
    'inbound',
    $phoneNumber,
    $smsData['message'],
    'received',
    $rawInput,
    null,
    null
);

// Process the SMS (check for OTP replies, order responses, etc.)
$processingResult = processInboundSMS($phoneNumber, $smsData['message']);

// Log the webhook event
logWebhookEvent('processed', "SMS from $phoneNumber processed", json_encode([
    'sms_log_id' => $smsLogId,
    'processing_result' => $processingResult
]));

// Return success response
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'SMS received and processed',
    'data' => [
        'sms_id' => $smsLogId,
        'from' => $phoneNumber,
        'action_taken' => $processingResult['action'] ?? 'logged'
    ]
]);

/**
 * Process inbound SMS and take appropriate action
 * 
 * @param string $phoneNumber Sender phone number
 * @param string $message Message content
 * @return array Processing result
 */
function processInboundSMS($phoneNumber, $message) {
    global $pdo;
    
    $result = [
        'action' => 'logged',
        'details' => null
    ];
    
    // Normalize message for comparison
    $normalizedMessage = strtoupper(trim($message));
    
    // Check if this is an OTP verification reply
    if (preg_match('/^\d{4,8}$/', $normalizedMessage)) {
        $otpResult = verifyOTP($phoneNumber, $normalizedMessage);
        if ($otpResult['success']) {
            $result = [
                'action' => 'otp_verified',
                'details' => $otpResult
            ];
        } else {
            $result = [
                'action' => 'otp_failed',
                'details' => $otpResult['message']
            ];
        }
        return $result;
    }
    
    // Check for common reply keywords
    switch ($normalizedMessage) {
        case 'YES':
        case 'CONFIRM':
        case 'OK':
            $result = handleConfirmReply($phoneNumber);
            break;
            
        case 'NO':
        case 'CANCEL':
            $result = handleCancelReply($phoneNumber);
            break;
            
        case 'STATUS':
        case 'ORDER':
            $result = handleStatusRequest($phoneNumber);
            break;
            
        case 'HELP':
            $result = handleHelpRequest($phoneNumber);
            break;
            
        default:
            // Store as general inquiry
            $result = [
                'action' => 'general_inquiry',
                'details' => 'Message logged for review'
            ];
    }
    
    return $result;
}

/**
 * Handle confirmation reply
 */
function handleConfirmReply($phoneNumber) {
    global $pdo;
    
    if (!$pdo) {
        return ['action' => 'logged', 'details' => 'No database'];
    }
    
    // First, check for pending orders that need confirmation via SMS
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE phone = ? AND status = 'pending' AND confirmation_method = 'sms'
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([formatPhoneNumber($phoneNumber)]);
        $pendingOrder = $stmt->fetch();
        
        if ($pendingOrder) {
            // Confirm the pending order
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'confirmed', 
                    confirmed_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$pendingOrder['id']]);
            
            // Send confirmation SMS
            sendOrderConfirmedSMS([
                'phone' => $phoneNumber,
                'order_number' => $pendingOrder['order_number'],
                'order_id' => $pendingOrder['id'],
                'user_id' => $pendingOrder['user_id']
            ]);
            
            // Notify admin about the confirmation
            try {
                require_once __DIR__ . '/mailer.php';
                require_once __DIR__ . '/functions.php';
                $siteUrl = getCurrentSiteUrl();
                $adminSubject = "Order #{$pendingOrder['order_number']} Confirmed by Customer";
                $adminBody = "
                    <h2>Order Confirmed</h2>
                    <p>Customer {$pendingOrder['first_name']} {$pendingOrder['last_name']} has confirmed their order via SMS.</p>
                    <p><strong>Order #:</strong> {$pendingOrder['order_number']}</p>
                    <p><strong>Confirmation Method:</strong> SMS</p>
                    <p><strong>Phone:</strong> {$pendingOrder['phone']}</p>
                    <p><strong>Total:</strong> ₱" . number_format($pendingOrder['total'], 2) . "</p>
                    <a href='{$siteUrl}/admin/orders.php?id={$pendingOrder['id']}'>View Order</a>
                ";
                sendMail(SMTP_USER, $adminSubject, $adminBody);
            } catch (Exception $e) {
                error_log("Admin notification email failed: " . $e->getMessage());
            }
            
            return [
                'action' => 'order_confirmed',
                'details' => ['order_id' => $pendingOrder['id'], 'order_number' => $pendingOrder['order_number']]
            ];
        }
        
        // If no pending order, check for already confirmed orders
        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE phone = ? AND status = 'confirmed'
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([formatPhoneNumber($phoneNumber)]);
        $order = $stmt->fetch();
        
        if ($order) {
            // Order is already confirmed, send acknowledgement
            sendSMS($phoneNumber, SMS_SENDER_NAME . ": Your order #{$order['order_number']} is already confirmed!");
            
            return [
                'action' => 'order_already_confirmed',
                'details' => ['order_id' => $order['id'], 'order_number' => $order['order_number']]
            ];
        }
    } catch (PDOException $e) {
        error_log("Confirm reply error: " . $e->getMessage());
    }
    
    return ['action' => 'no_order_found', 'details' => 'No recent order found'];
}

/**
 * Handle cancel reply
 */
function handleCancelReply($phoneNumber) {
    global $pdo;
    
    if (!$pdo) {
        return ['action' => 'logged', 'details' => 'No database'];
    }
    
    // Find confirmed order for this phone number to cancel
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE phone = ? AND status = 'confirmed'
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([formatPhoneNumber($phoneNumber)]);
        $order = $stmt->fetch();
        
        if ($order) {
            // Cancel the order
            $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$order['id']]);
            
            // Send cancellation SMS
            sendSMS($phoneNumber, SMS_SENDER_NAME . ": Your order #{$order['order_number']} has been cancelled.");
            
            return [
                'action' => 'order_cancelled',
                'details' => ['order_id' => $order['id'], 'order_number' => $order['order_number']]
            ];
        }
    } catch (PDOException $e) {
        error_log("Cancel reply error: " . $e->getMessage());
    }
    
    return ['action' => 'no_cancelable_order', 'details' => 'No order found to cancel'];
}

/**
 * Handle status request
 */
function handleStatusRequest($phoneNumber) {
    global $pdo;
    
    if (!$pdo) {
        return ['action' => 'logged', 'details' => 'No database'];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE phone = ?
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([formatPhoneNumber($phoneNumber)]);
        $order = $stmt->fetch();
        
        if ($order) {
            $statusLabels = [
                'pending' => 'Awaiting Confirmation',
                'confirmed' => 'Confirmed',
                'preparing' => 'Being Prepared',
                'ready' => 'Ready for Pickup',
                'delivered' => 'Completed',
                'cancelled' => 'Cancelled'
            ];
            
            $statusText = $statusLabels[$order['status']] ?? $order['status'];
            $message = SMS_SENDER_NAME . ": Your order #{$order['order_number']} is: {$statusText}";
            
            // If pending, remind them to confirm
            if ($order['status'] === 'pending') {
                $message .= ". Reply CONFIRM to confirm your order.";
            }
            
            sendSMS($phoneNumber, $message);
            
            return [
                'action' => 'status_sent',
                'details' => ['order_id' => $order['id'], 'status' => $order['status']]
            ];
        }
    } catch (PDOException $e) {
        error_log("Status request error: " . $e->getMessage());
    }
    
    sendSMS($phoneNumber, SMS_SENDER_NAME . ": No orders found for your number.");
    return ['action' => 'no_orders', 'details' => 'No orders found'];
}

/**
 * Handle help request
 */
function handleHelpRequest($phoneNumber) {
    $helpMessage = SMS_SENDER_NAME . ": Reply with: STATUS (check order), CONFIRM (confirm order), CANCEL (cancel order)";
    sendSMS($phoneNumber, $helpMessage);
    
    return ['action' => 'help_sent', 'details' => 'Help message sent'];
}

/**
 * Log webhook events
 */
function logWebhookEvent($type, $message, $data) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/sms_webhook.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$type] $message";
    if ($data) {
        $logEntry .= " | Data: " . (is_string($data) ? $data : json_encode($data));
    }
    $logEntry .= "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
?>
