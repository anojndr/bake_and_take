<?php
/**
 * Update Order Status - AJAX Handler
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
$status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);

$validStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'];

if (!$orderId || !in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

try {
    // Get current order data before update
    $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    $oldStatus = $order['status'];
    
    // Update the order status
    $updateFields = "status = ?, updated_at = NOW()";
    $updateParams = [$status, $orderId];
    
    // If manually confirming a pending order, also set confirmed_at
    if ($status === 'confirmed' && $oldStatus === 'pending') {
        $updateFields = "status = ?, confirmed_at = NOW(), updated_at = NOW()";
    }
    
    $stmt = $pdo->prepare("UPDATE orders SET $updateFields WHERE id = ?");
    $stmt->execute($updateParams);
    
    if ($stmt->rowCount() > 0) {
        
        // Prepare order data for notifications
        $notifyData = [
            'order_id' => $orderId,
            'order_number' => $order['order_number'],
            'first_name' => $order['first_name'],
            'email' => $order['email'],
            'phone' => $order['phone'],
            'user_id' => $order['user_id']
        ];
        
        // Send email notifications for status changes
        if ($oldStatus !== $status) {
            require_once '../../includes/mailer.php';
            
            switch ($status) {
                case 'preparing':
                    $subject = "Your Order #{$order['order_number']} is Being Prepared!";
                    $body = "
                        <h2>We're on it!</h2>
                        <p>Hi {$order['first_name']},</p>
                        <p>Great news! Your order <strong>#{$order['order_number']}</strong> is now being prepared by our bakers.</p>
                        <p>We'll notify you as soon as it's ready for pickup.</p>
                        <br>
                        <p>Thank you for your patience!<br>Bake & Take Team</p>
                    ";
                    sendMail($order['email'], $subject, $body);
                    break;
                    
                case 'ready':
                    $subject = "Your Order #{$order['order_number']} is Ready for Pickup!";
                    $body = "
                        <h2>Good news!</h2>
                        <p>Hi {$order['first_name']},</p>
                        <p>Your order <strong>#{$order['order_number']}</strong> is fresh out of the oven and ready for pickup.</p>
                        <p>Please come by our store to collect your delicious treats.</p>
                        <br>
                        <p>See you soon!<br>Bake & Take Team</p>
                    ";
                    sendMail($order['email'], $subject, $body);
                    break;
                    
                case 'delivered':
                    $subject = "Thank You for Your Order #{$order['order_number']}!";
                    $body = "
                        <h2>Thank you!</h2>
                        <p>Hi {$order['first_name']},</p>
                        <p>Thank you for picking up your order <strong>#{$order['order_number']}</strong>!</p>
                        <p>We hope you enjoy your treats. We'd love to see you again soon!</p>
                        <br>
                        <p>With warm regards,<br>Bake & Take Team</p>
                    ";
                    sendMail($order['email'], $subject, $body);
                    break;
            }
        }
        
        // Send SMS notifications for status changes
        require_once '../../includes/sms_service.php';
        
        // Only send SMS if status actually changed
        if ($oldStatus !== $status) {
            switch ($status) {
                case 'confirmed':
                    // Send confirmation SMS
                    $message = SMS_SENDER_NAME . ": Great news {$notifyData['first_name']}! Your order #{$notifyData['order_number']} has been confirmed and will be prepared shortly.";
                    sendSMS($notifyData['phone'], $message, $orderId, $notifyData['user_id']);
                    break;
                    
                case 'preparing':
                    $message = SMS_SENDER_NAME . ": {$notifyData['first_name']}, your order #{$notifyData['order_number']} is now being prepared. We'll notify you when it's ready!";
                    sendSMS($notifyData['phone'], $message, $orderId, $notifyData['user_id']);
                    break;
                    
                case 'ready':
                    sendOrderReadySMS($notifyData);
                    break;
                    
                case 'delivered':
                    $message = SMS_SENDER_NAME . ": Thank you for picking up your order #{$notifyData['order_number']}! We hope you enjoy your treats. See you again soon!";
                    sendSMS($notifyData['phone'], $message, $orderId, $notifyData['user_id']);
                    break;
                    
                case 'cancelled':
                    $message = SMS_SENDER_NAME . ": Your order #{$notifyData['order_number']} has been cancelled. If you have questions, please contact us.";
                    sendSMS($notifyData['phone'], $message, $orderId, $notifyData['user_id']);
                    break;
            }
        }

        echo json_encode(['success' => true, 'message' => 'Order status updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made']);
    }
} catch (PDOException $e) {
    error_log("Order status update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
