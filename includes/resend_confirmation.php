<?php
/**
 * Resend Order Confirmation
 * 
 * This script resends the confirmation email or SMS for a pending order.
 * Allows users to re-confirm orders they haven't confirmed yet.
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to continue']);
    exit;
}

$orderId = intval($_POST['order_id'] ?? 0);

if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

global $conn;
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database unavailable']);
    exit;
}

// Get the order details - must be pending and belong to user
$stmt = mysqli_prepare($conn, "
    SELECT o.*, u.first_name, u.last_name, u.email, u.phone
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = ? 
    AND o.user_id = ?
    AND o.status = 'pending'
");
if (!$stmt) {
    error_log("Resend confirmation error: " . mysqli_error($conn));
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
mysqli_stmt_bind_param($stmt, "ii", $orderId, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found or already confirmed']);
    exit;
}

// Get order items for email
$itemsStmt = mysqli_prepare($conn, "
    SELECT p.name, oi.quantity, oi.price
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
mysqli_stmt_bind_param($itemsStmt, "i", $orderId);
mysqli_stmt_execute($itemsStmt);
$itemsResult = mysqli_stmt_get_result($itemsStmt);
$orderItems = [];
$orderItemsHtml = '';
while ($item = mysqli_fetch_assoc($itemsResult)) {
    $orderItems[] = $item;
    $orderItemsHtml .= "<li>{$item['quantity']}x {$item['name']} - ₱" . number_format($item['price'] * $item['quantity'], 2) . "</li>";
}
mysqli_stmt_close($itemsStmt);

$confirmationMethod = $order['confirmation_method'] ?? 'sms';
$confirmationToken = $order['confirmation_token'];

// Generate new token if needed (for email confirmations)
if ($confirmationMethod === 'email' && empty($confirmationToken)) {
    $confirmationToken = bin2hex(random_bytes(32));
    $updateStmt = mysqli_prepare($conn, "UPDATE orders SET confirmation_token = ? WHERE order_id = ?");
    mysqli_stmt_bind_param($updateStmt, "si", $confirmationToken, $orderId);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);
}

require_once 'mailer.php';
require_once 'sms_service.php';

$siteUrl = getCurrentSiteUrl();

try {
    if ($confirmationMethod === 'email') {
        // Resend email confirmation
        $confirmationUrl = $siteUrl . '/includes/confirm_order.php?token=' . $confirmationToken;
        
        $orderSubject = "Bake & Take - Please Confirm Your Order #{$order['order_number']}";
        $orderBody = "
            <h2>Please Confirm Your Order</h2>
            <p>Hi {$order['first_name']},</p>
            <p>This is a reminder to confirm your order.</p>
            <p>To complete your order, please click the button below to confirm:</p>
            <p style='margin: 30px 0;'>
                <a href='{$confirmationUrl}' style='background-color: #8B4513; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                    Confirm My Order
                </a>
            </p>
            <p><strong>Order #:</strong> {$order['order_number']}</p>
            <p><strong>Total:</strong> ₱" . number_format($order['total'], 2) . "</p>
            <br>
            <h3>Order Details</h3>
            <ul>{$orderItemsHtml}</ul>
            <br>
            <p><em>If you did not place this order, please ignore this email.</em></p>
            <br>
            <p>Best regards,<br>Bake & Take Team</p>
        ";
        
        sendMail($order['email'], $orderSubject, $orderBody);
        
        echo json_encode([
            'success' => true,
            'message' => 'Confirmation email has been resent. Please check your inbox.',
            'confirmation_method' => 'email'
        ]);
    } else {
        // Resend SMS confirmation request
        $smsOrderData = [
            'first_name' => $order['first_name'],
            'phone' => $order['phone'],
            'order_number' => $order['order_number'],
            'total' => $order['total'],
            'order_id' => $orderId,
            'user_id' => $order['user_id'],
            'items' => $orderItems
        ];
        sendOrderConfirmationRequestSMS($smsOrderData);
        
        echo json_encode([
            'success' => true,
            'message' => 'Confirmation SMS has been resent. Please reply CONFIRM to the message.',
            'confirmation_method' => 'sms'
        ]);
    }
} catch (Exception $e) {
    error_log("Resend confirmation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to resend confirmation. Please try again.']);
}
?>
