<?php
/**
 * Delete All - Admin Operations
 */
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check admin auth
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid request. Please try again.');
    header('Location: ../index.php');
    exit;
}

$type = $_POST['type'] ?? '';

if (!$pdo) {
    setFlashMessage('error', 'Database connection error.');
    header('Location: ../index.php');
    exit;
}

try {
    switch ($type) {
        case 'users':
            // Delete all users except the current admin
            $pdo->beginTransaction();
            
            // Getting IDs of users to delete (everyone except current admin)
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id != ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($userIds)) {
                $inQuery = implode(',', array_fill(0, count($userIds), '?'));

                // 1. Get order IDs for these users to clean up order_items first
                $stmtOrders = $pdo->prepare("SELECT order_id FROM orders WHERE user_id IN ($inQuery)");
                $stmtOrders->execute($userIds);
                $orderIds = $stmtOrders->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($orderIds)) {
                    $inOrdersQuery = implode(',', array_fill(0, count($orderIds), '?'));
                    
                    // 2. Delete order_items for these orders
                    $deleteItems = $pdo->prepare("DELETE FROM order_items WHERE order_id IN ($inOrdersQuery)");
                    $deleteItems->execute($orderIds);
                    
                    // 3. Delete orders for these users
                    $deleteOrders = $pdo->prepare("DELETE FROM orders WHERE user_id IN ($inQuery)");
                    $deleteOrders->execute($userIds);
                }

                // 4. Delete users
                $deleteUsers = $pdo->prepare("DELETE FROM users WHERE user_id IN ($inQuery)");
                $deleteUsers->execute($userIds);
                
                setFlashMessage('success', 'All users (except you) and their data have been deleted.');
            } else {
                setFlashMessage('info', 'No other users to delete.');
            }
            
            $pdo->commit();
            header('Location: ../index.php?page=users');
            break;

        case 'orders':
            $pdo->beginTransaction();
            
            // Delete all order items first
            $pdo->exec("DELETE FROM order_items");
            
            // Delete all orders
            $pdo->exec("DELETE FROM orders");
            
            $pdo->commit();
            
            setFlashMessage('success', 'All orders have been deleted.');
            header('Location: ../index.php?page=orders');
            break;

        case 'products':
            $pdo->beginTransaction();
            
            // NB: This might fail if there are order_items referencing products
            // Ideally should check if products are in any active orders or soft delete
            // But requirement is "Delete All", so we will try to force it.
            // If we have Foreign Key constraints preventing deletion of products in orders:
            // We might need to delete order_items or set product_id to NULL.
            
            // For now, assuming standard delete. If it fails due to FK, we'll catch exception.
            $pdo->exec("DELETE FROM products");
            
            $pdo->commit();
            
            setFlashMessage('success', 'All products have been deleted.');
            header('Location: ../index.php?page=products');
            break;

        default:
            setFlashMessage('error', 'Invalid deletion type.');
            header('Location: ../index.php');
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlashMessage('error', 'Operation failed: ' . $e->getMessage());
    header('Location: ../index.php?page=' . $type);
}
exit;
?>
