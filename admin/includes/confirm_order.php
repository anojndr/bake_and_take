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

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

try {
    // Get the order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND status = 'pending'");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or not in pending status']);
        exit;
    }
    
    // Update order status to confirmed
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'confirmed', 
            confirmed_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$orderId]);
    
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
        'order_id' => $order['id'],
        'user_id' => $order['user_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => "Order #{$order['order_number']} has been confirmed"
    ]);
    
} catch (PDOException $e) {
    error_log("Manual confirmation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
