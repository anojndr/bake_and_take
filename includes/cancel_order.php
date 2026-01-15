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

global $conn;
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

// Get current order data - ensure it belongs to the logged-in user
$orderStmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
if (!$orderStmt) {
    error_log("Order cancellation error: " . mysqli_error($conn));
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
mysqli_stmt_bind_param($orderStmt, "ii", $orderId, $_SESSION['user_id']);
mysqli_stmt_execute($orderStmt);
$result = mysqli_stmt_get_result($orderStmt);
$order = mysqli_fetch_assoc($result);
mysqli_stmt_close($orderStmt);

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
$stmt = mysqli_prepare($conn, "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE order_id = ?");
if (!$stmt) {
    error_log("Order cancellation error: " . mysqli_error($conn));
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);
$affectedRows = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($affectedRows > 0) {
    // Return stock to inventory for cancelled order items
    $itemsStmt = mysqli_prepare($conn, "SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    if ($itemsStmt) {
        mysqli_stmt_bind_param($itemsStmt, "i", $orderId);
        mysqli_stmt_execute($itemsStmt);
        $itemsResult = mysqli_stmt_get_result($itemsStmt);
        
        while ($item = mysqli_fetch_assoc($itemsResult)) {
            if ($item['product_id']) {
                $stockStmt = mysqli_prepare($conn, "UPDATE products SET stock = stock + ? WHERE product_id = ?");
                if ($stockStmt) {
                    mysqli_stmt_bind_param($stockStmt, "ii", $item['quantity'], $item['product_id']);
                    mysqli_stmt_execute($stockStmt);
                    mysqli_stmt_close($stockStmt);
                }
            }
        }
        mysqli_stmt_close($itemsStmt);
    }
    
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
?>
