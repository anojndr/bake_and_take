<?php
/**
 * Order Email Confirmation Handler
 * 
 * This script handles email-based order confirmations.
 * When a user clicks the confirmation link in their email, 
 * this script validates the token and confirms the order.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Get the token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    redirect('../index.php', 'Invalid confirmation link.', 'error');
}

if (!$pdo) {
    redirect('../index.php', 'Service temporarily unavailable.', 'error');
}

try {
    // Find the order with this confirmation token
    // Find the order with this confirmation token
    $stmt = $pdo->prepare("
        SELECT o.*, u.first_name, u.last_name, u.email, u.phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.confirmation_token = ? 
        AND o.status = 'pending'
        AND o.confirmation_method = 'email'
    ");
    $stmt->execute([$token]);
    $order = $stmt->fetch();
    
    if (!$order) {
        // Check if already confirmed
        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE confirmation_token = ? 
            AND status != 'pending'
        ");
        $stmt->execute([$token]);
        $confirmedOrder = $stmt->fetch();
        
        if ($confirmedOrder) {
            redirect('../index.php', 'This order has already been confirmed.', 'info');
        }
        
        redirect('../index.php', 'Invalid or expired confirmation link.', 'error');
    }
    
    // Update order status to confirmed
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'confirmed', 
            confirmed_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$order['id']]);
    
    // Send confirmation SMS/email to customer
    require_once 'mailer.php';
    require_once 'sms_service.php';
    
    // Send email confirmation
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
    
    // Send SMS notification
    $smsOrderData = [
        'first_name' => $order['first_name'],
        'phone' => $order['phone'],
        'order_number' => $order['order_number'],
        'total' => $order['total'],
        'order_id' => $order['id'],
        'user_id' => $order['user_id']
    ];
    sendOrderStatusSMS($smsOrderData, 'confirmed');
    
    // Notify admin
    $adminSubject = "Order #{$order['order_number']} Confirmed by Customer";
    $adminBody = "
        <h2>Order Confirmed</h2>
        <p>Customer {$order['first_name']} {$order['last_name']} has confirmed their order.</p>
        <p><strong>Order #:</strong> {$order['order_number']}</p>
        <p><strong>Confirmation Method:</strong> Email</p>
        <p><strong>Total:</strong> ₱" . number_format($order['total'], 2) . "</p>
        <a href='" . SITE_URL . "/admin/orders.php?id={$order['id']}'>View Order</a>
    ";
    sendMail(SMTP_USER, $adminSubject, $adminBody);
    
    // Store order info in session for the confirmation page
    $_SESSION['email_order_confirmed'] = [
        'order_number' => $order['order_number'],
        'total' => $order['total']
    ];
    
    // Redirect to email confirmation success page
    redirect('../index.php?page=email-confirmed', 'Your order has been confirmed successfully!', 'success');
    
} catch (PDOException $e) {
    error_log("Order confirmation error: " . $e->getMessage());
    redirect('../index.php', 'An error occurred while confirming your order. Please try again.', 'error');
}
?>
