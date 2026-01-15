<?php
/**
 * Cancel Order - AJAX Handler for Customers
 */
session_start();
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to cancel orders']);
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
    // Get current order data - ensure it belongs to the logged-in user
    $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $orderStmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $orderStmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    // Only allow cancellation for pending or confirmed orders
    $cancellableStatuses = ['pending', 'confirmed'];
    if (!in_array($order['status'], $cancellableStatuses)) {
        $statusMessages = [
            'preparing' => 'This order is already being prepared and cannot be cancelled.',
            'ready' => 'This order is ready for pickup and cannot be cancelled.',
            'delivered' => 'This order has already been picked up.',
            'cancelled' => 'This order has already been cancelled.'
        ];
        $message = $statusMessages[$order['status']] ?? 'This order cannot be cancelled.';
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
    
    // Update the order status to cancelled
    $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$orderId]);
    
    if ($stmt->rowCount() > 0) {
        // Send cancellation email notification
        require_once 'mailer.php';
        
        $subject = "Order #{$order['order_number']} Cancelled";
        $body = "
            <h2>Order Cancelled</h2>
            <p>Hi {$order['first_name']},</p>
            <p>Your order <strong>#{$order['order_number']}</strong> has been cancelled as requested.</p>
            <p>If you didn't request this cancellation or have any questions, please contact us immediately.</p>
            <br>
            <p>Thank you,<br>Bake & Take Team</p>
        ";
        sendMail($order['email'], $subject, $body);
        
        // Send SMS notification if phone is available
        if (!empty($order['phone'])) {
            require_once 'sms_service.php';
            $message = SMS_SENDER_NAME . ": Your order #{$order['order_number']} has been cancelled. If you have questions, please contact us.";
            sendSMS($order['phone'], $message, $orderId, $order['user_id']);
        }
        
        echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made']);
    }
} catch (PDOException $e) {
    error_log("Order cancellation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
