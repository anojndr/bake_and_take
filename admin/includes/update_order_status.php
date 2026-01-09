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
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $orderId]);
    
    if ($stmt->rowCount() > 0) {
        
        // If status is 'ready', send email notification
        if ($status === 'ready') {
            // Fetch order details
            $orderStmt = $pdo->prepare("SELECT email, first_name, order_number FROM orders WHERE id = ?");
            $orderStmt->execute([$orderId]);
            $order = $orderStmt->fetch();

            if ($order) {
                require_once '../../includes/mailer.php';
                
                $firstName = $order['first_name'];
                $email = $order['email'];
                $orderNumber = $order['order_number'];
                
                $subject = "Your Order #{$orderNumber} is Ready!";
                $body = "
                    <h2>Good news!</h2>
                    <p>Hi {$firstName},</p>
                    <p>Your order <strong>#{$orderNumber}</strong> is fresh out of the oven and ready for pickup.</p>
                    <p>Please come by our store to collect your delicious treats.</p>
                    <br>
                    <p>See you soon!<br>Bake & Take Team</p>
                ";
                
                sendMail($email, $subject, $body);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Order status updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
