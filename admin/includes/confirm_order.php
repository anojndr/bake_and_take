<?php
/**
 * Manual Order Confirmation - Admin Handler
 * 
 * This allows admins to manually confirm a pending order
 * which simulates the customer confirming via SMS/email
 */
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

global $conn;

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

$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

// Get the order
$stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE order_id = ? AND status = 'pending'");
mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found or not in pending status']);
    exit;
}

// Update order status to confirmed
$stmt = mysqli_prepare($conn, "
    UPDATE orders 
    SET status = 'confirmed', 
        confirmed_at = NOW(),
        updated_at = NOW()
    WHERE order_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Send confirmation notifications
require_once '../../includes/mailer.php';
require_once '../../includes/sms_service.php';

// Send email to customer
$orderSubject = "Bake & Take - Order #{$order['order_number']} Confirmed!";
$orderBody = "
    <h2>Your Order is Confirmed!</h2>
    <p>Hi {$order['first_name']},</p>
    <p>Great news! Your order <strong>#{$order['order_number']}</strong> has been confirmed and is now being processed.</p>
    <p><strong>Total:</strong> ₱" . number_format($order['total'], 2) . "</p>
    <p>We'll notify you when your order is ready for pickup.</p>
    <br>
    <p>Best regards,<br>Bake & Take Team</p>
";
sendMail($order['email'], $orderSubject, $orderBody);

// Send SMS confirmation
sendOrderConfirmedSMS([
    'phone' => $order['phone'],
    'order_number' => $order['order_number'],
    'order_id' => $order['order_id'],
    'user_id' => $order['user_id']
]);

echo json_encode([
    'success' => true,
    'message' => "Order #{$order['order_number']} has been confirmed"
]);
?>
