<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php?page=checkout');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirect('../index.php?page=checkout', 'Invalid request. Please try again.', 'error');
}

// Get cart data from POST (sent as JSON)
$cartData = [];
if (isset($_POST['cart_data'])) {
    $cartData = json_decode($_POST['cart_data'], true);
}

// Validate cart is not empty
if (empty($cartData)) {
    redirect('../index.php?page=cart', 'Your cart is empty.', 'error');
}

// Collect order data
$orderData = [
    'first_name' => sanitize($_POST['first_name'] ?? ''),
    'last_name' => sanitize($_POST['last_name'] ?? ''),
    'email' => sanitize($_POST['email'] ?? ''),
    'phone' => sanitize($_POST['phone'] ?? ''),
    'order_date' => date('Y-m-d H:i:s')
];

// Validate required fields
$required = ['first_name', 'last_name', 'email', 'phone'];

foreach ($required as $field) {
    if (empty($orderData[$field])) {
        redirect('../index.php?page=checkout', 'Please fill in all required fields.', 'error');
    }
}

if (!isValidEmail($orderData['email'])) {
    redirect('../index.php?page=checkout', 'Please enter a valid email address.', 'error');
}

// Normalize phone format so it matches inbound SMS webhook lookups.
require_once 'sms_service.php';
$orderData['phone'] = formatPhoneNumber($orderData['phone']);

// Calculate totals
$subtotal = 0;
foreach ($cartData as $item) {
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}

$tax = $subtotal * 0.08;
$total = $subtotal + $tax;

// Generate unique order number
$orderNumber = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

// Get confirmation method (default to SMS)
$confirmationMethod = sanitize($_POST['confirmation_method'] ?? 'sms');
if (!in_array($confirmationMethod, ['sms', 'email'])) {
    $confirmationMethod = 'sms';
}

// Generate confirmation token for email confirmations
$confirmationToken = null;
if ($confirmationMethod === 'email') {
    $confirmationToken = bin2hex(random_bytes(32));
}

// Save to database
if ($pdo) {
    try {
        $pdo->beginTransaction();
        
        // Insert order with status 'pending' until confirmed
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                user_id, first_name, last_name, email, phone, order_number,
                subtotal, tax, total, status, confirmation_method, confirmation_token, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())
        ");
        
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        $stmt->execute([
            $userId,
            $orderData['first_name'],
            $orderData['last_name'],
            $orderData['email'],
            $orderData['phone'],
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
        }
        
        $pdo->commit();
        
        // Store order info in session for success page
        $_SESSION['last_order'] = [
            'order_number' => $orderNumber,
            'order_id' => $orderId,
            'total' => $total,
            'confirmation_method' => $confirmationMethod
        ];

        // Build order items HTML
        $orderItemsHtml = '';
        foreach ($cartData as $item) {
            $orderItemsHtml .= "<li>{$item['quantity']}x {$item['name']} - ₱" . number_format($item['price'] * $item['quantity'], 2) . "</li>";
        }

        // Send Order Confirmation Email based on confirmation method
        require_once 'mailer.php';
        
        if ($confirmationMethod === 'email') {
            // Email confirmation - send confirmation link (dynamically detect localhost or domain)
            $siteUrl = getCurrentSiteUrl();
            $confirmationUrl = $siteUrl . '/includes/confirm_order.php?token=' . $confirmationToken;
            
            $orderSubject = "Bake & Take - Please Confirm Your Order #{$orderNumber}";
            $orderBody = "
                <h2>Please Confirm Your Order</h2>
                <p>Hi {$orderData['first_name']},</p>
                <p>Thank you for your order!</p>
                <p>To complete your order, please click the button below to confirm:</p>
                <p style='margin: 30px 0;'>
                    <a href='{$confirmationUrl}' style='background-color: #8B4513; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                        Confirm My Order
                    </a>
                </p>
                <p><strong>Order #:</strong> {$orderNumber}</p>
                <p><strong>Total:</strong> ₱" . number_format($total, 2) . "</p>
                <br>
                <h3>Order Details</h3>
                <ul>{$orderItemsHtml}</ul>
                <br>
                <p><em>If you did not place this order, please ignore this email.</em></p>
                <br>
                <p>Best regards,<br>Bake & Take Team</p>
            ";
        } else {
            // SMS confirmation - just send receipt email
            $orderSubject = "Bake & Take - Order #{$orderNumber} Awaiting Confirmation";
            $orderBody = "
                <h2>Order Received - Awaiting Your Confirmation</h2>
                <p>Hi {$orderData['first_name']},</p>
                <p>Thank you for your order!</p>
                <p><strong>Please check your phone!</strong> We've sent you an SMS to confirm your order. Simply reply <strong>CONFIRM</strong> to complete your order.</p>
                <p><strong>Order #:</strong> {$orderNumber}</p>
                <p><strong>Total:</strong> ₱" . number_format($total, 2) . "</p>
                <br>
                <h3>Order Details</h3>
                <ul>{$orderItemsHtml}</ul>
                <p>Once you confirm via SMS, we'll start preparing your order for pickup.</p>
                <br>
                <p>Best regards,<br>Bake & Take Team</p>
            ";
        }
        
        // Send email to Customer
        sendMail($orderData['email'], $orderSubject, $orderBody);

        // Send email to Admin
        $siteUrl = getCurrentSiteUrl();
        $adminSubject = "New Order #{$orderNumber} - Awaiting Customer Confirmation";
        $adminBody = "
            <h2>New Order Received (Pending Confirmation)</h2>
            <p><strong>Order #:</strong> {$orderNumber}</p>
            <p><strong>Status:</strong> Pending Customer Confirmation</p>
            <p><strong>Confirmation Method:</strong> " . ucfirst($confirmationMethod) . "</p>
            <p><strong>Method:</strong> Pickup</p>
            <p><strong>Customer:</strong> {$orderData['first_name']} {$orderData['last_name']}</p>
            <p><strong>Email:</strong> {$orderData['email']}</p>
            <p><strong>Phone:</strong> {$orderData['phone']}</p>
            <p><strong>Total:</strong> ₱" . number_format($total, 2) . "</p>
            <p><em>The customer will confirm this order via {$confirmationMethod}. You'll be notified when confirmed.</em></p>
            <a href='{$siteUrl}/admin/orders.php?id={$orderId}'>View Order</a>
        ";
        sendMail(SMTP_USER, $adminSubject, $adminBody);

        // Send SMS based on confirmation method
        
        if ($confirmationMethod === 'sms') {
            // Send SMS confirmation request
            $smsOrderData = [
                'first_name' => $orderData['first_name'],
                'phone' => $orderData['phone'],
                'order_number' => $orderNumber,
                'total' => $total,
                'order_id' => $orderId,
                'user_id' => $userId,
                'items' => $cartData
            ];
            sendOrderConfirmationRequestSMS($smsOrderData);
        } else {
            // For email confirmation, send a simple SMS notification
            $smsMessage = SMS_SENDER_NAME . ": Hi {$orderData['first_name']}! Your order #{$orderNumber} is awaiting confirmation. Please check your email and click the confirmation link.";
            sendSMS($orderData['phone'], $smsMessage, $orderId, $userId);
        }
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Order processing error: " . $e->getMessage());
        redirect('../index.php?page=checkout', 'An error occurred while processing your order. Please try again.', 'error');
    }
} else {
    // No database - just store in session for demo
    $_SESSION['last_order'] = [
        'order_number' => $orderNumber,
        'total' => $total
    ];
}

redirect('../index.php?page=order-success');
?>
