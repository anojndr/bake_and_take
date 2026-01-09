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
    'delivery_method' => sanitize($_POST['delivery_method'] ?? 'delivery'),
    'address' => sanitize($_POST['address'] ?? ''),
    'city' => sanitize($_POST['city'] ?? ''),
    'state' => sanitize($_POST['state'] ?? ''),
    'zip' => sanitize($_POST['zip'] ?? ''),
    'instructions' => sanitize($_POST['instructions'] ?? ''),
    'order_date' => date('Y-m-d H:i:s')
];

// Validate required fields
$required = ['first_name', 'last_name', 'email', 'phone'];
if ($orderData['delivery_method'] === 'delivery') {
    $required = array_merge($required, ['address', 'city', 'state', 'zip']);
}

foreach ($required as $field) {
    if (empty($orderData[$field])) {
        redirect('../index.php?page=checkout', 'Please fill in all required fields.', 'error');
    }
}

if (!isValidEmail($orderData['email'])) {
    redirect('../index.php?page=checkout', 'Please enter a valid email address.', 'error');
}

// Calculate totals
$subtotal = 0;
foreach ($cartData as $item) {
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}

$deliveryFee = $orderData['delivery_method'] === 'delivery' ? 5.00 : 0.00;
$tax = $subtotal * 0.08;
$total = $subtotal + $deliveryFee + $tax;

// Generate unique order number
$orderNumber = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

// Save to database
if ($pdo) {
    try {
        $pdo->beginTransaction();
        
        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                user_id, order_number, first_name, last_name, email, phone,
                delivery_method, address, city, state, zip, instructions,
                subtotal, delivery_fee, tax, total, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
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
            $deliveryFee,
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
        }
        
        $pdo->commit();
        
        // Store order info in session for success page
        $_SESSION['last_order'] = [
            'order_number' => $orderNumber,
            'order_id' => $orderId,
            'total' => $total
        ];

        // Send Order Confirmation Email
        require_once 'mailer.php';
        $orderSubject = "Bake & Take - Order Confirmation #{$orderNumber}";
        $orderBody = "
            <h2>Thank you for your order!</h2>
            <p>Hi {$orderData['first_name']},</p>
            <p>Your order <strong>#{$orderNumber}</strong> has been received and is being processed.</p>
            <p><strong>Total:</strong> $" . number_format($total, 2) . "</p>
            <br>
            <h3>Order Details</h3>
            <ul>
        ";
        
        foreach ($cartData as $item) {
            $orderBody .= "<li>{$item['quantity']}x {$item['name']} - $" . number_format($item['price'] * $item['quantity'], 2) . "</li>";
        }
        
        $orderBody .= "
            </ul>
            <p>We will notify you when your order is ready.</p>
            <br>
            <p>Best regards,<br>Bake & Take Team</p>
        ";
        
        // Send email to Customer
        sendMail($orderData['email'], $orderSubject, $orderBody);

        // Send email to Admin
        $adminSubject = "New Order #{$orderNumber}";
        $adminBody = "
            <h2>New Order Received</h2>
            <p><strong>Order #:</strong> {$orderNumber}</p>
            <p><strong>Customer:</strong> {$orderData['first_name']} {$orderData['last_name']}</p>
            <p><strong>Email:</strong> {$orderData['email']}</p>
            <p><strong>Total:</strong> $" . number_format($total, 2) . "</p>
            <a href='" . SITE_URL . "/admin/orders.php?id={$orderId}'>View Order</a>
        ";
        sendMail(SMTP_USER, $adminSubject, $adminBody);
        
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
