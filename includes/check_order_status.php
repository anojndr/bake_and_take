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

global $conn;
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database unavailable']);
    exit;
}

$stmt = mysqli_prepare($conn, "
    SELECT status, confirmation_token, order_number, total 
    FROM orders 
    WHERE order_number = ?
");
if (!$stmt) {
    error_log("Check order status error: " . mysqli_error($conn));
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
mysqli_stmt_bind_param($stmt, "s", $orderNumber);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

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
?>
