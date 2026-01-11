<?php
/**
 * GCash Order Processing
 * Processes orders paid via GCash QR code
 */
session_start();
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

$customerInfo = $input['customerInfo'] ?? null;
$cartData = $input['cart'] ?? [];

// Validate customer info
if (!$customerInfo || empty($customerInfo['first_name']) || empty($customerInfo['last_name']) 
    || empty($customerInfo['email']) || empty($customerInfo['phone'])) {
    echo json_encode(['error' => 'Please fill in all required contact information.']);
    exit;
}

// Validate cart
if (empty($cartData)) {
    echo json_encode(['error' => 'Your cart is empty.']);
    exit;
}

// Validate email
if (!isValidEmail($customerInfo['email'])) {
    echo json_encode(['error' => 'Please enter a valid email address.']);
    exit;
}

// Calculate totals
$subtotal = 0;
foreach ($cartData as $item) {
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}

$tax = $subtotal * 0.08;
$total = $subtotal + $tax;

// Generate unique order number
$orderNumber = 'GC' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

// Order data
$orderData = [
    'first_name' => sanitize($customerInfo['first_name']),
    'last_name' => sanitize($customerInfo['last_name']),
    'email' => sanitize($customerInfo['email']),
    'phone' => sanitize($customerInfo['phone']),
    'delivery_method' => 'pickup',
    'address' => '',
    'city' => '',
    'state' => '',
    'zip' => '',
    'instructions' => sanitize($customerInfo['instructions'] ?? ''),
    'order_date' => date('Y-m-d H:i:s')
];

// Save to database
if ($pdo) {
    try {
        $pdo->beginTransaction();
        
        // Insert order with GCash payment method
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                user_id, order_number, first_name, last_name, email, phone,
                delivery_method, address, city, state, zip, instructions,
                subtotal, delivery_fee, tax, total, status, payment_method, 
                payment_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'gcash', 'pending_verification', NOW())
        ");
        
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        $stmt->execute([
            $userId,
            $orderNumber,
            $orderData['first_name'],
            $orderData['last_name'],
            $orderData['email'],
            $orderData['phone'],
            $orderData['delivery_method'],
            $orderData['address'],
            $orderData['city'],
            $orderData['state'],
            $orderData['zip'],
            $orderData['instructions'],
            $subtotal,
            0.00, // delivery fee
            $tax,
            $total
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // Insert order items
        $itemStmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, quantity, price, total)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($cartData as $item) {
            $itemTotal = floatval($item['price']) * intval($item['quantity']);
            $itemStmt->execute([
                $orderId,
                isset($item['id']) ? intval($item['id']) : null,
                $item['name'],
                intval($item['quantity']),
                floatval($item['price']),
                $itemTotal
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
        
        // Clear user's cart if logged in
        if ($userId) {
            $cartStmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
            $cartStmt->execute([$userId]);
            $cart = $cartStmt->fetch();
            
            if ($cart) {
                $deleteStmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
                $deleteStmt->execute([$cart['id']]);
            }
        }
        
        $pdo->commit();
        
        // Store order info in session for success page
        $_SESSION['last_order'] = [
            'order_number' => $orderNumber,
            'order_id' => $orderId,
            'total' => $total,
            'payment_method' => 'gcash',
            'gcash_payment' => true
        ];

        // Send Order Confirmation Email
        require_once 'mailer.php';
        $orderSubject = "Bake & Take - Order Confirmation #{$orderNumber} (GCash Payment)";
        $orderBody = "
            <h2>Thank you for your order!</h2>
            <p>Hi {$orderData['first_name']},</p>
            <p>Your order <strong>#{$orderNumber}</strong> has been received.</p>
            <p style='background: #FFF3CD; padding: 15px; border-radius: 8px; border-left: 4px solid #FFC107;'>
                <strong>⏳ Payment Verification Pending</strong><br>
                Your GCash payment is pending verification. We will confirm your order once the payment is verified.
            </p>
            <p>It will be ready for pickup at our store once confirmed.</p>
            <p><strong>Total:</strong> ₱" . number_format($total, 2) . "</p>
            <br>
            <h3>Order Details</h3>
            <ul>
        ";
        
        foreach ($cartData as $item) {
            $orderBody .= "<li>{$item['quantity']}x {$item['name']} - ₱" . number_format($item['price'] * $item['quantity'], 2) . "</li>";
        }
        
        $orderBody .= "
            </ul>
            <p>We will notify you once your payment is verified and your order is being prepared.</p>
            <br>
            <p>Best regards,<br>Bake & Take Team</p>
        ";
        
        // Send email to Customer
        sendMail($orderData['email'], $orderSubject, $orderBody);

        // Send email to Admin
        $adminSubject = "New GCash Order #{$orderNumber} - Payment Verification Required";
        $adminBody = "
            <h2>New GCash Order Received</h2>
            <p style='background: #FFF3CD; padding: 15px; border-radius: 8px; border-left: 4px solid #FFC107;'>
                <strong>⚠️ Payment Verification Required</strong><br>
                Please verify the GCash payment for this order.
            </p>
            <p><strong>Order #:</strong> {$orderNumber}</p>
            <p><strong>Payment Method:</strong> GCash QR</p>
            <p><strong>Customer:</strong> {$orderData['first_name']} {$orderData['last_name']}</p>
            <p><strong>Email:</strong> {$orderData['email']}</p>
            <p><strong>Phone:</strong> {$orderData['phone']}</p>
            <p><strong>Total:</strong> ₱" . number_format($total, 2) . "</p>
            <a href='" . SITE_URL . "/admin/orders.php?id={$orderId}'>View Order</a>
        ";
        sendMail(SMTP_USER, $adminSubject, $adminBody);

        // Send SMS Order Confirmation
        require_once 'sms_service.php';
        $smsOrderData = [
            'first_name' => $orderData['first_name'],
            'phone' => $orderData['phone'],
            'order_number' => $orderNumber,
            'total' => $total,
            'order_id' => $orderId,
            'user_id' => $userId
        ];
        sendOrderConfirmationSMS($smsOrderData);
        
        // Return success
        echo json_encode([
            'success' => true,
            'order_number' => $orderNumber,
            'order_id' => $orderId,
            'total' => $total,
            'message' => 'Order placed successfully'
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("GCash order processing error: " . $e->getMessage());
        echo json_encode(['error' => 'An error occurred while processing your order. Please try again.']);
    }
} else {
    // No database - just store in session for demo
    $_SESSION['last_order'] = [
        'order_number' => $orderNumber,
        'total' => $total,
        'payment_method' => 'gcash',
        'gcash_payment' => true
    ];
    
    echo json_encode([
        'success' => true,
        'order_number' => $orderNumber,
        'total' => $total,
        'message' => 'Order placed successfully (demo mode)'
    ]);
}
?>
