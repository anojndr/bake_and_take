<?php
/**
 * SMS Service
 * 
 * Provides functions for sending SMS messages via SMSGate Android gateway
 * and managing OTP verification
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sms_config.php';

/**
 * Send SMS via SMSGate Android Gateway
 * 
 * @param string $phoneNumber Recipient phone number
 * @param string $message Message content
 * @param int|null $orderId Related order ID (optional)
 * @param int|null $userId Related user ID (optional)
 * @return array ['success' => bool, 'message' => string, 'data' => mixed]
 */
function sendSMS($phoneNumber, $message, $orderId = null, $userId = null) {
    global $pdo;
    
    // Check if SMS is enabled
    if (!SMS_ENABLED) {
        return [
            'success' => false,
            'message' => 'SMS service is disabled',
            'data' => null
        ];
    }
    
    // Format phone number
    $phoneNumber = formatPhoneNumber($phoneNumber);
    
    // Truncate message if needed
    if (strlen($message) > SMS_MAX_LENGTH) {
        $message = substr($message, 0, SMS_MAX_LENGTH - 3) . '...';
    }
    
    // Log the outbound SMS attempt
    $smsLogId = logSMS('outbound', $phoneNumber, $message, 'pending', null, $orderId, $userId);
    
    // Prepare the API request to SMSGate
    $apiUrl = SMS_GATEWAY_URL . SMS_GATEWAY_SEND_PATH;
    
    // SMSGate Cloud API payload format
    // - deviceId: specifies which Android device to use for sending
    // - id: unique identifier for this specific message (prevents duplicate errors)
    $uniqueMessageId = uniqid('sms_', true) . '_' . bin2hex(random_bytes(4));
    $payload = json_encode([
        'message' => $message,
        'phoneNumbers' => [$phoneNumber],
        'deviceId' => SMS_GATEWAY_DEVICE_ID,
        'id' => $uniqueMessageId
    ]);
    
    // Retry configuration
    $maxRetries = 3;
    $retryDelay = 1; // seconds
    $lastError = '';
    $lastResponse = '';
    $lastHttpCode = 0;
    
    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        // Initialize cURL
        $ch = curl_init($apiUrl);
        
        // Build headers array
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        // Add Bearer token if API key is set
        if (!empty(SMS_GATEWAY_API_KEY)) {
            $headers[] = 'Authorization: Bearer ' . SMS_GATEWAY_API_KEY;
        }
        
        // Set cURL options
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => SMS_GATEWAY_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);
        
        // Add Basic Auth if no API key but username/password provided
        if (empty(SMS_GATEWAY_API_KEY) && !empty(SMS_GATEWAY_USERNAME) && !empty(SMS_GATEWAY_PASSWORD)) {
            curl_setopt($ch, CURLOPT_USERPWD, SMS_GATEWAY_USERNAME . ':' . SMS_GATEWAY_PASSWORD);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }
        
        // Execute the request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        
        $lastResponse = $response;
        $lastHttpCode = $httpCode;
        $lastError = $error;
        
        // Log attempt for debugging
        error_log("SMS API Attempt $attempt: URL=$apiUrl, HTTP=$httpCode, Error=$error, Errno=$errno");
        
        // If successful, break out of retry loop
        if (!$error && $httpCode >= 200 && $httpCode < 300) {
            break;
        }
        
        // Check if this is a retryable error
        $retryableErrors = [
            CURLE_COULDNT_CONNECT,
            CURLE_COULDNT_RESOLVE_HOST,
            CURLE_OPERATION_TIMEDOUT,
            CURLE_GOT_NOTHING,
            CURLE_RECV_ERROR,
            CURLE_SEND_ERROR
        ];
        
        $retryableHttpCodes = [408, 429, 500, 502, 503, 504];
        
        $shouldRetry = in_array($errno, $retryableErrors) || in_array($httpCode, $retryableHttpCodes);
        
        if ($attempt < $maxRetries && $shouldRetry) {
            error_log("SMS API: Retrying in {$retryDelay}s (attempt $attempt of $maxRetries)");
            sleep($retryDelay);
            $retryDelay *= 2; // Exponential backoff
        } else if (!$shouldRetry) {
            // Non-retryable error, break immediately
            break;
        }
    }
    
    // Process final response
    if ($lastError) {
        $errorMessage = "cURL Error after $maxRetries attempts: $lastError";
        updateSMSLog($smsLogId, 'failed', $errorMessage);
        error_log("SMS Send Failed: $errorMessage");
        return [
            'success' => false,
            'message' => "Failed to connect to SMS gateway: $lastError",
            'data' => null
        ];
    }
    
    $responseData = json_decode($lastResponse, true);
    
    // Check for JSON decode errors
    if ($responseData === null && json_last_error() !== JSON_ERROR_NONE) {
        error_log("SMS API: Invalid JSON response - " . substr($lastResponse, 0, 500));
        $responseData = ['raw_response' => substr($lastResponse, 0, 500)];
    }
    
    if ($lastHttpCode >= 200 && $lastHttpCode < 300) {
        updateSMSLog($smsLogId, 'sent', $lastResponse);
        return [
            'success' => true,
            'message' => 'SMS sent successfully',
            'data' => $responseData
        ];
    } else {
        $errorMsg = $responseData['message'] ?? $responseData['error'] ?? "HTTP $lastHttpCode";
        updateSMSLog($smsLogId, 'failed', $lastResponse);
        error_log("SMS Gateway Error: HTTP $lastHttpCode - $errorMsg");
        return [
            'success' => false,
            'message' => 'SMS gateway returned error: ' . $errorMsg,
            'data' => $responseData
        ];
    }
}

/**
 * Send OTP verification code
 * 
 * @param string $phoneNumber Phone number to send OTP to
 * @param string $purpose Purpose of OTP (order_verify, phone_verify, login, other)
 * @param int|null $referenceId Reference ID (e.g., order ID)
 * @return array ['success' => bool, 'message' => string, 'otp' => string|null]
 */
function sendOTP($phoneNumber, $purpose = 'other', $referenceId = null) {
    global $pdo;
    
    if (!SMS_OTP_ENABLED) {
        return [
            'success' => false,
            'message' => 'OTP service is disabled',
            'otp' => null
        ];
    }
    
    $phoneNumber = formatPhoneNumber($phoneNumber);
    
    // Generate OTP
    $otp = generateOTP(SMS_OTP_LENGTH);
    
    // Calculate expiry time
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . SMS_OTP_EXPIRY_MINUTES . ' minutes'));
    
    // Store OTP in database
    if ($pdo) {
        try {
            // Invalidate any existing OTPs for this phone number
            $stmt = $pdo->prepare("DELETE FROM sms_otp WHERE phone_number = ? AND verified_at IS NULL");
            $stmt->execute([$phoneNumber]);
            
            // Insert new OTP
            $stmt = $pdo->prepare("
                INSERT INTO sms_otp (phone_number, otp_code, purpose, reference_id, expires_at, max_attempts)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $phoneNumber,
                $otp,
                $purpose,
                $referenceId,
                $expiresAt,
                SMS_OTP_MAX_ATTEMPTS
            ]);
        } catch (PDOException $e) {
            error_log("OTP storage error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate OTP',
                'otp' => null
            ];
        }
    }
    
    // Prepare OTP message
    $message = str_replace(
        ['{otp}', '{store_name}', '{purpose}', '{expires}'],
        [$otp, SMS_SENDER_NAME, $purpose, SMS_OTP_EXPIRY_MINUTES],
        SMS_TEMPLATE_OTP
    );
    
    // Send OTP via SMS
    $result = sendSMS($phoneNumber, $message);
    
    if ($result['success']) {
        return [
            'success' => true,
            'message' => 'OTP sent successfully',
            'otp' => $otp // Only return for testing; remove in production
        ];
    } else {
        return [
            'success' => false,
            'message' => $result['message'],
            'otp' => null
        ];
    }
}

/**
 * Verify OTP code
 * 
 * @param string $phoneNumber Phone number
 * @param string $otpCode OTP code to verify
 * @return array ['success' => bool, 'message' => string]
 */
function verifyOTP($phoneNumber, $otpCode) {
    global $pdo;
    
    if (!$pdo) {
        return [
            'success' => false,
            'message' => 'Database connection required'
        ];
    }
    
    $phoneNumber = formatPhoneNumber($phoneNumber);
    $currentTime = date('Y-m-d H:i:s');
    
    try {
        // Find the OTP record - check expiry using PHP time for timezone consistency
        $stmt = $pdo->prepare("
            SELECT * FROM sms_otp 
            WHERE phone_number = ? 
            AND otp_code = ?
            AND verified_at IS NULL
            AND expires_at > ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$phoneNumber, $otpCode, $currentTime]);
        $otpRecord = $stmt->fetch();
        
        if (!$otpRecord) {
            // Check if there's an expired or used OTP
            $stmt = $pdo->prepare("
                SELECT * FROM sms_otp 
                WHERE phone_number = ? 
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$phoneNumber]);
            $lastOtp = $stmt->fetch();
            
            if ($lastOtp) {
                if ($lastOtp['verified_at']) {
                    return ['success' => false, 'message' => 'OTP already used'];
                }
                if (strtotime($lastOtp['expires_at']) < time()) {
                    return ['success' => false, 'message' => 'OTP expired'];
                }
                if ($lastOtp['attempts'] >= $lastOtp['max_attempts']) {
                    return ['success' => false, 'message' => 'Maximum attempts exceeded'];
                }
                
                // Increment attempts
                $stmt = $pdo->prepare("UPDATE sms_otp SET attempts = attempts + 1 WHERE id = ?");
                $stmt->execute([$lastOtp['id']]);
            }
            
            return ['success' => false, 'message' => 'Invalid OTP'];
        }
        
        // Check attempts
        if ($otpRecord['attempts'] >= $otpRecord['max_attempts']) {
            return ['success' => false, 'message' => 'Maximum attempts exceeded'];
        }
        
        // Mark as verified
        $stmt = $pdo->prepare("UPDATE sms_otp SET verified_at = NOW() WHERE id = ?");
        $stmt->execute([$otpRecord['id']]);
        
        return [
            'success' => true,
            'message' => 'OTP verified successfully',
            'reference_id' => $otpRecord['reference_id']
        ];
        
    } catch (PDOException $e) {
        error_log("OTP verification error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Verification failed'];
    }
}

/**
 * Send order confirmation SMS
 * 
 * @param array $orderData Order data containing name, phone, order_number, total
 * @return array Result of sendSMS
 */
function sendOrderConfirmationSMS($orderData) {
    if (!SMS_ORDER_NOTIFICATIONS_ENABLED) {
        return ['success' => false, 'message' => 'Order SMS notifications disabled'];
    }
    
    $message = str_replace(
        ['{name}', '{order_number}', '{total}', '{store_name}'],
        [
            $orderData['first_name'],
            $orderData['order_number'],
            number_format($orderData['total'], 2),
            SMS_SENDER_NAME
        ],
        SMS_TEMPLATE_ORDER_CONFIRM
    );
    
    return sendSMS(
        $orderData['phone'],
        $message,
        $orderData['order_id'] ?? null,
        $orderData['user_id'] ?? null
    );
}

/**
 * Send order ready SMS
 * 
 * @param array $orderData Order data
 * @return array Result of sendSMS
 */
function sendOrderReadySMS($orderData) {
    if (!SMS_ORDER_NOTIFICATIONS_ENABLED) {
        return ['success' => false, 'message' => 'Order SMS notifications disabled'];
    }
    
    $message = str_replace(
        ['{name}', '{order_number}', '{store_name}'],
        [
            $orderData['first_name'],
            $orderData['order_number'],
            SMS_SENDER_NAME
        ],
        SMS_TEMPLATE_ORDER_READY
    );
    
    return sendSMS(
        $orderData['phone'],
        $message,
        $orderData['order_id'] ?? null,
        $orderData['user_id'] ?? null
    );
}

/**
 * Send order status update SMS
 * 
 * @param array $orderData Order data
 * @param string $status New status
 * @return array Result of sendSMS
 */
function sendOrderStatusSMS($orderData, $status) {
    if (!SMS_ORDER_NOTIFICATIONS_ENABLED) {
        return ['success' => false, 'message' => 'Order SMS notifications disabled'];
    }
    
    $statusLabels = [
        'confirmed' => 'Confirmed',
        'preparing' => 'Being Prepared',
        'ready' => 'Ready for Pickup',
        'delivered' => 'Completed',
        'cancelled' => 'Cancelled'
    ];
    
    $message = str_replace(
        ['{order_number}', '{status}', '{store_name}'],
        [
            $orderData['order_number'],
            $statusLabels[$status] ?? $status,
            SMS_SENDER_NAME
        ],
        SMS_TEMPLATE_ORDER_STATUS
    );
    
    return sendSMS(
        $orderData['phone'],
        $message,
        $orderData['order_id'] ?? null,
        $orderData['user_id'] ?? null
    );
}

/**
 * Send order confirmation request SMS
 * This is sent when an order is placed to request the customer to confirm
 * 
 * @param array $orderData Order data containing name, phone, order_number, total, items
 * @return array Result of sendSMS
 */
function sendOrderConfirmationRequestSMS($orderData) {
    if (!SMS_ORDER_NOTIFICATIONS_ENABLED) {
        return ['success' => false, 'message' => 'Order SMS notifications disabled'];
    }
    
    // Format items list for SMS (keep it short)
    $itemsList = '';
    if (!empty($orderData['items']) && is_array($orderData['items'])) {
        $itemNames = [];
        foreach ($orderData['items'] as $item) {
            $itemNames[] = $item['quantity'] . 'x ' . $item['name'];
        }
        $itemsList = implode(', ', $itemNames);
        // Truncate if too long
        if (strlen($itemsList) > 80) {
            $itemsList = substr($itemsList, 0, 77) . '...';
        }
    }
    
    $message = str_replace(
        ['{name}', '{order_number}', '{total}', '{items}', '{store_name}'],
        [
            $orderData['first_name'],
            $orderData['order_number'],
            number_format($orderData['total'], 2),
            $itemsList,
            SMS_SENDER_NAME
        ],
        SMS_TEMPLATE_ORDER_CONFIRM_REQUEST
    );
    
    return sendSMS(
        $orderData['phone'],
        $message,
        $orderData['order_id'] ?? null,
        $orderData['user_id'] ?? null
    );
}

/**
 * Send order confirmed notification SMS
 * This is sent after the customer confirms their order via SMS reply
 * 
 * @param array $orderData Order data containing phone, order_number
 * @return array Result of sendSMS
 */
function sendOrderConfirmedSMS($orderData) {
    if (!SMS_ORDER_NOTIFICATIONS_ENABLED) {
        return ['success' => false, 'message' => 'Order SMS notifications disabled'];
    }
    
    $message = str_replace(
        ['{order_number}', '{store_name}'],
        [
            $orderData['order_number'],
            SMS_SENDER_NAME
        ],
        SMS_TEMPLATE_ORDER_CONFIRMED
    );
    
    return sendSMS(
        $orderData['phone'],
        $message,
        $orderData['order_id'] ?? null,
        $orderData['user_id'] ?? null
    );
}

/**
 * Format phone number to international format
 * 
 * @param string $phone Phone number
 * @return string Formatted phone number
 */
function formatPhoneNumber($phone) {
    // Remove all non-numeric characters except +
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // If starts with 0, replace with country code
    if (substr($phone, 0, 1) === '0') {
        $phone = SMS_DEFAULT_COUNTRY_CODE . substr($phone, 1);
    }
    
    // If doesn't start with +, add country code
    if (substr($phone, 0, 1) !== '+') {
        // Check if it already has country code without +
        if (strlen($phone) > 10) {
            $phone = '+' . $phone;
        } else {
            $phone = SMS_DEFAULT_COUNTRY_CODE . $phone;
        }
    }
    
    return $phone;
}

/**
 * Generate random OTP code
 * 
 * @param int $length OTP length
 * @return string OTP code
 */
function generateOTP($length = 6) {
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= mt_rand(0, 9);
    }
    return $otp;
}

/**
 * Log SMS to database
 * 
 * @param string $direction 'outbound' or 'inbound'
 * @param string $phoneNumber Phone number
 * @param string $message Message content
 * @param string $status Status
 * @param string|null $gatewayResponse Gateway response
 * @param int|null $orderId Order ID
 * @param int|null $userId User ID
 * @return int|null SMS log ID
 */
function logSMS($direction, $phoneNumber, $message, $status, $gatewayResponse = null, $orderId = null, $userId = null) {
    global $pdo;
    
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO sms_log (direction, phone_number, message, status, gateway_response, order_id, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $direction,
            $phoneNumber,
            $message,
            $status,
            $gatewayResponse,
            $orderId,
            $userId
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("SMS log error: " . $e->getMessage());
        return null;
    }
}

/**
 * Update SMS log status
 * 
 * @param int $smsLogId SMS log ID
 * @param string $status New status
 * @param string|null $gatewayResponse Gateway response
 * @return bool Success
 */
function updateSMSLog($smsLogId, $status, $gatewayResponse = null) {
    global $pdo;
    
    if (!$pdo || !$smsLogId) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE sms_log SET status = ?, gateway_response = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $gatewayResponse, $smsLogId]);
        return true;
    } catch (PDOException $e) {
        error_log("SMS log update error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get SMS log for an order
 * 
 * @param int $orderId Order ID
 * @return array SMS log entries
 */
function getSMSLogByOrder($orderId) {
    global $pdo;
    
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM sms_log WHERE order_id = ? ORDER BY created_at DESC
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("SMS log fetch error: " . $e->getMessage());
        return [];
    }
}
?>
