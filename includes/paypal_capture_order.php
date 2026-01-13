<?php
/**
 * PayPal Capture Order API
 * 
 * This endpoint captures a PayPal payment after the customer approves it.
 * It also creates the order in the database and sends confirmation emails.
 */

header('Content-Type: application/json');
session_start();

require_once 'config.php';
require_once 'functions.php';
require_once 'secrets.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the request body
$input = json_decode(file_get_contents('php://input'), true);

$paypalOrderId = $input['orderID'] ?? null;
$customerInfo = $input['customerInfo'] ?? [];

if (!$paypalOrderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing PayPal order ID']);
    exit;
}

// Validate customer info
$requiredFields = ['first_name', 'last_name', 'email', 'phone'];
foreach ($requiredFields as $field) {
    if (empty($customerInfo[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

// Get confirmation method (default to SMS if not specified)
$confirmationMethod = $customerInfo['confirmation_method'] ?? 'sms';
if (!in_array($confirmationMethod, ['sms', 'email'])) {
    $confirmationMethod = 'sms';
}

// Generate confirmation token for email confirmations
$confirmationToken = null;
if ($confirmationMethod === 'email') {
    $confirmationToken = bin2hex(random_bytes(32));
}

// Get pending order from session
$pendingOrder = $_SESSION['pending_paypal_order'] ?? null;
if (!$pendingOrder || $pendingOrder['paypal_order_id'] !== $paypalOrderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or expired PayPal order']);
    exit;
}

// Get access token
$accessToken = getPayPalAccessToken();
if (!$accessToken) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to authenticate with PayPal']);
    exit;
}

// Capture the payment
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, getPayPalApiUrl() . '/v2/checkout/orders/' . $paypalOrderId . '/capture');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $accessToken
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode >= 200 && $httpCode < 300 && isset($result['status']) && $result['status'] === 'COMPLETED') {
    // Payment successful! Create the order in database
    $cartData = $pendingOrder['cart'];
    $subtotal = $pendingOrder['subtotal'];
    $tax = $pendingOrder['tax'];
    $total = $pendingOrder['total'];
    
    // Extract PayPal payment details
    $payerInfo = $result['payer'] ?? [];
    $captureInfo = $result['purchase_units'][0]['payments']['captures'][0] ?? [];
    
    $paypalPayerId = $payerInfo['payer_id'] ?? '';
    $paypalCaptureId = $captureInfo['id'] ?? '';
    $paypalPaymentStatus = $captureInfo['status'] ?? 'COMPLETED';
    
    // Generate unique order number
    $orderNumber = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    
    // Sanitize customer data
    $orderData = [
        'first_name' => sanitize($customerInfo['first_name']),
        'last_name' => sanitize($customerInfo['last_name']),
        'email' => sanitize($customerInfo['email']),
        'phone' => sanitize($customerInfo['phone'])
    ];
    
    $orderId = null;
    
    // Save to database
    if ($pdo) {
        try {
            $pdo->beginTransaction();
            
            // Insert order with PayPal details - status is 'pending' until confirmed
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id, order_number,
                    subtotal, tax, total, status, confirmation_method, confirmation_token, payment_status,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, 'completed', NOW())
            ");
            
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            $stmt->execute([
                $userId,
                $orderNumber,
                $subtotal,
                $tax,
                $total,
                $confirmationMethod,
                $confirmationToken
            ]);
            
            $orderId = $pdo->lastInsertId();
            
            // Insert order items
            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($cartData as $item) {
                $itemTotal = floatval($item['price']) * intval($item['quantity']);
                $itemStmt->execute([
                    $orderId,
                    isset($item['id']) ? intval($item['id']) : null,
                    intval($item['quantity']),
                    floatval($item['price'])
                ]);
                
                // Decrease stock for the product
                if (isset($item['id']) && $item['id']) {
                    $stockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                    $stockStmt->execute([
                        intval($item['quantity']),
                        intval($item['id']),
                        intval($item['quantity'])
                    ]);
                }
            }
            
            // Log PayPal transaction
            try {
                $logStmt = $pdo->prepare("
                    INSERT INTO paypal_transactions (
                        order_id, paypal_order_id, paypal_capture_id, paypal_payer_id,
                        amount, currency, status, raw_response, created_at
                    ) VALUES (?, ?, ?, ?, ?, 'USD', ?, ?, NOW())
                ");
                $logStmt->execute([
                    $orderId,
                    $paypalOrderId,
                    $paypalCaptureId,
                    $paypalPayerId,
                    $total,
                    $paypalPaymentStatus,
                    json_encode($result)
                ]);
            } catch (PDOException $e) {
                // Table might not exist yet, log but don't fail
                error_log('PayPal transaction logging failed: ' . $e->getMessage());
            }
            
            $pdo->commit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Order creation error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create order in database']);
            exit;
        }
    }
    
    // Store order info in session for success page
    $_SESSION['last_order'] = [
        'order_number' => $orderNumber,
        'order_id' => $orderId,
        'total' => $total,
        'paypal_order_id' => $paypalOrderId,
        'paypal_capture_id' => $paypalCaptureId,
        'confirmation_method' => $confirmationMethod
    ];
    
    // Clear pending order
    unset($_SESSION['pending_paypal_order']);
    
    // Send confirmation emails
    try {
        require_once 'mailer.php';
        
        // Build order items HTML
        $orderItemsHtml = '';
        foreach ($cartData as $item) {
            $orderItemsHtml .= "<li>{$item['quantity']}x {$item['name']} - ₱" . number_format($item['price'] * $item['quantity'], 2) . "</li>";
        }
        
        if ($confirmationMethod === 'email') {
            // Email confirmation - send confirmation link (dynamically detect localhost or domain)
            $siteUrl = getCurrentSiteUrl();
            $confirmationUrl = $siteUrl . '/includes/confirm_order.php?token=' . $confirmationToken;
            
            $orderSubject = "Bake & Take - Please Confirm Your Order #{$orderNumber}";
            $orderBody = "
                <h2>Please Confirm Your Order</h2>
                <p>Hi {$orderData['first_name']},</p>
                <p>Thank you for your order! Your payment has been received via PayPal.</p>
                <p>To complete your order, please click the button below to confirm:</p>
                <p style='margin: 30px 0;'>
                    <a href='{$confirmationUrl}' style='background-color: #8B4513; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                        Confirm My Order
                    </a>
                </p>
                <p><strong>Order #:</strong> {$orderNumber}</p>
                <p><strong>Total:</strong> ₱" . number_format($total, 2) . "</p>
                <p><strong>PayPal Transaction ID:</strong> {$paypalCaptureId}</p>
                <br>
                <h3>Order Details</h3>
                <ul>{$orderItemsHtml}</ul>
                <br>
                <p><em>If you did not place this order, please ignore this email.</em></p>
                <br>
                <p>Best regards,<br>Bake & Take Team</p>
            ";
        } else {
            // SMS confirmation - just send receipt email, confirmation will be via SMS
            $orderSubject = "Bake & Take - Order #{$orderNumber} Awaiting Confirmation";
            $orderBody = "
                <h2>Order Received - Awaiting Your Confirmation</h2>
                <p>Hi {$orderData['first_name']},</p>
                <p>Thank you for your order! Your payment has been received via PayPal.</p>
                <p><strong>Please check your phone!</strong> We've sent you an SMS to confirm your order. Simply reply <strong>CONFIRM</strong> to complete your order.</p>
                <p><strong>Order #:</strong> {$orderNumber}</p>
                <p><strong>Total:</strong> ₱" . number_format($total, 2) . "</p>
                <p><strong>PayPal Transaction ID:</strong> {$paypalCaptureId}</p>
                <br>
                <h3>Order Details</h3>
                <ul>{$orderItemsHtml}</ul>
                <p>Once you confirm via SMS, we'll start preparing your order for pickup.</p>
                <br>
                <p>Best regards,<br>Bake & Take Team</p>
            ";
        }
        
        sendMail($orderData['email'], $orderSubject, $orderBody);
        
        // Email to admin - inform about pending order
        $siteUrl = getCurrentSiteUrl();
        $adminSubject = "New PayPal Order #{$orderNumber} - Awaiting Customer Confirmation";
        $adminBody = "
            <h2>New Order Received (Pending Confirmation)</h2>
            <p><strong>Order #:</strong> {$orderNumber}</p>
            <p><strong>Status:</strong> Pending Customer Confirmation</p>
            <p><strong>Confirmation Method:</strong> " . ucfirst($confirmationMethod) . "</p>
            <p><strong>Payment Method:</strong> PayPal</p>
            <p><strong>PayPal Transaction:</strong> {$paypalCaptureId}</p>
            <p><strong>Method:</strong> Pickup</p>
            <p><strong>Customer:</strong> {$orderData['first_name']} {$orderData['last_name']}</p>
            <p><strong>Email:</strong> {$orderData['email']}</p>
            <p><strong>Phone:</strong> {$orderData['phone']}</p>
            <p><strong>Total:</strong> ₱" . number_format($total, 2) . "</p>
            <p><em>The customer will confirm this order via {$confirmationMethod}. You'll be notified when confirmed.</em></p>
            <a href='{$siteUrl}/admin/orders.php?id={$orderId}'>View Order</a>
        ";
        sendMail(SMTP_USER, $adminSubject, $adminBody);
        
    } catch (Exception $e) {
        error_log('Email sending failed: ' . $e->getMessage());
    }
    
    // Send SMS notification based on confirmation method
    try {
        require_once 'sms_service.php';
        
        if ($confirmationMethod === 'sms') {
            // Send SMS confirmation request
            $smsOrderData = [
                'first_name' => $orderData['first_name'],
                'phone' => $orderData['phone'],
                'order_number' => $orderNumber,
                'total' => $total,
                'order_id' => $orderId,
                'user_id' => $userId ?? null,
                'items' => $cartData
            ];
            sendOrderConfirmationRequestSMS($smsOrderData);
        } else {
            // For email confirmation, send a simple SMS notification that confirmation email was sent
            $smsMessage = SMS_SENDER_NAME . ": Hi {$orderData['first_name']}! Your order #{$orderNumber} is awaiting confirmation. Please check your email and click the confirmation link.";
            sendSMS($orderData['phone'], $smsMessage, $orderId, $userId ?? null);
        }
    } catch (Exception $e) {
        error_log('SMS sending failed: ' . $e->getMessage());
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'orderNumber' => $orderNumber,
        'orderId' => $orderId,
        'total' => $total,
        'paypalOrderId' => $paypalOrderId,
        'captureId' => $paypalCaptureId,
        'payer' => [
            'name' => ($payerInfo['name']['given_name'] ?? '') . ' ' . ($payerInfo['name']['surname'] ?? ''),
            'email' => $payerInfo['email_address'] ?? ''
        ]
    ]);
    
} else {
    error_log('PayPal Capture Error: ' . $response);
    http_response_code(500);
    echo json_encode([
        'error' => 'Payment capture failed',
        'details' => $result['message'] ?? ($result['details'][0]['description'] ?? 'Unknown error'),
        'debug_id' => $result['debug_id'] ?? null
    ]);
}

/**
 * Get PayPal OAuth2 Access Token
 */
function getPayPalAccessToken() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, getPayPalApiUrl() . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300 && isset($result['access_token'])) {
        return $result['access_token'];
    }
    
    error_log('PayPal Auth Error: ' . $response);
    return null;
}

/**
 * Get PayPal API URL based on environment
 */
function getPayPalApiUrl() {
    return defined('PAYPAL_SANDBOX') && !PAYPAL_SANDBOX 
        ? 'https://api-m.paypal.com' 
        : 'https://api-m.sandbox.paypal.com';
}
?>
