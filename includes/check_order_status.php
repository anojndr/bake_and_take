<?php
/**
 * Order Status Check API
 * 
 * Returns the current status of an order.
 * Used for real-time polling to auto-redirect when order is confirmed via SMS.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$orderNumber = $_GET['order_number'] ?? '';

if (empty($orderNumber)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order number required']);
    exit;
}

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database unavailable']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT status, confirmation_token, order_number, total 
        FROM orders 
        WHERE order_number = ?
    ");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'status' => $order['status'],
        'order_number' => $order['order_number'],
        'total' => $order['total'],
        'confirmation_token' => $order['confirmation_token']
    ]);
    
} catch (PDOException $e) {
    error_log("Check order status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
