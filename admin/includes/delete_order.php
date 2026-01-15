<?php
/**
 * Delete Order - Admin Operations
 */
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

global $conn;

header('Content-Type: application/json');

// Check admin auth
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get order ID
$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit;
}

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection error']);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

// First, delete order items
$stmt = mysqli_prepare($conn, "DELETE FROM order_items WHERE order_id = ?");
mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Then, delete the order
$stmt = mysqli_prepare($conn, "DELETE FROM orders WHERE order_id = ?");
mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);
$affectedRows = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

// Check if order was actually deleted
if ($affectedRows === 0) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

// Commit transaction
mysqli_commit($conn);

echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
?>
