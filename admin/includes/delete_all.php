<?php
/**
 * Delete All - Admin Operations
 */
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

global $conn;

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

if (!$conn) {
    setFlashMessage('error', 'Database connection error.');
    header('Location: ../index.php');
    exit;
}

$error = null;

switch ($type) {
    case 'users':
        // Delete all users except the current admin
        mysqli_begin_transaction($conn);
        
        // Getting IDs of users to delete (everyone except current admin)
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE user_id != ?");
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['admin_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $userIds = [];
        while ($row = mysqli_fetch_row($result)) {
            $userIds[] = $row[0];
        }
        mysqli_stmt_close($stmt);
        
        if (!empty($userIds)) {
            $inQuery = implode(',', array_fill(0, count($userIds), '?'));
            $types = str_repeat('i', count($userIds));

            // 1. Get order IDs for these users to clean up order_items first
            $stmtOrders = mysqli_prepare($conn, "SELECT order_id FROM orders WHERE user_id IN ($inQuery)");
            mysqli_stmt_bind_param($stmtOrders, $types, ...$userIds);
            mysqli_stmt_execute($stmtOrders);
            $resultOrders = mysqli_stmt_get_result($stmtOrders);
            $orderIds = [];
            while ($row = mysqli_fetch_row($resultOrders)) {
                $orderIds[] = $row[0];
            }
            mysqli_stmt_close($stmtOrders);
            
            if (!empty($orderIds)) {
                $inOrdersQuery = implode(',', array_fill(0, count($orderIds), '?'));
                $orderTypes = str_repeat('i', count($orderIds));
                
                // 2. Delete order_items for these orders
                $deleteItems = mysqli_prepare($conn, "DELETE FROM order_items WHERE order_id IN ($inOrdersQuery)");
                mysqli_stmt_bind_param($deleteItems, $orderTypes, ...$orderIds);
                if (!mysqli_stmt_execute($deleteItems)) {
                    $error = mysqli_stmt_error($deleteItems);
                }
                mysqli_stmt_close($deleteItems);
                
                if (!$error) {
                    // 3. Delete orders for these users
                    $deleteOrders = mysqli_prepare($conn, "DELETE FROM orders WHERE user_id IN ($inQuery)");
                    mysqli_stmt_bind_param($deleteOrders, $types, ...$userIds);
                    if (!mysqli_stmt_execute($deleteOrders)) {
                        $error = mysqli_stmt_error($deleteOrders);
                    }
                    mysqli_stmt_close($deleteOrders);
                }
            }

            if (!$error) {
                // 4. Delete users
                $deleteUsers = mysqli_prepare($conn, "DELETE FROM users WHERE user_id IN ($inQuery)");
                mysqli_stmt_bind_param($deleteUsers, $types, ...$userIds);
                if (!mysqli_stmt_execute($deleteUsers)) {
                    $error = mysqli_stmt_error($deleteUsers);
                }
                mysqli_stmt_close($deleteUsers);
            }
            
            if ($error) {
                mysqli_rollback($conn);
                setFlashMessage('error', 'Operation failed: ' . $error);
            } else {
                mysqli_commit($conn);
                setFlashMessage('success', 'All users (except you) and their data have been deleted.');
            }
        } else {
            mysqli_commit($conn);
            setFlashMessage('info', 'No other users to delete.');
        }
        
        header('Location: ../index.php?page=users');
        break;

    case 'orders':
        mysqli_begin_transaction($conn);
        
        // Delete all order items first
        if (!mysqli_query($conn, "DELETE FROM order_items")) {
            $error = mysqli_error($conn);
        }
        
        // Delete all orders
        if (!$error && !mysqli_query($conn, "DELETE FROM orders")) {
            $error = mysqli_error($conn);
        }
        
        if ($error) {
            mysqli_rollback($conn);
            setFlashMessage('error', 'Operation failed: ' . $error);
        } else {
            mysqli_commit($conn);
            setFlashMessage('success', 'All orders have been deleted.');
        }
        
        header('Location: ../index.php?page=orders');
        break;

    case 'products':
        mysqli_begin_transaction($conn);
        
        // NB: This might fail if there are order_items referencing products
        // Ideally should check if products are in any active orders or soft delete
        // But requirement is "Delete All", so we will try to force it.
        // If we have Foreign Key constraints preventing deletion of products in orders:
        // We might need to delete order_items or set product_id to NULL.
        
        // For now, assuming standard delete. If it fails due to FK, we'll catch exception.
        if (!mysqli_query($conn, "DELETE FROM products")) {
            $error = mysqli_error($conn);
        }
        
        if ($error) {
            mysqli_rollback($conn);
            setFlashMessage('error', 'Operation failed: ' . $error);
        } else {
            mysqli_commit($conn);
            setFlashMessage('success', 'All products have been deleted.');
        }
        
        header('Location: ../index.php?page=products');
        break;

    default:
        setFlashMessage('error', 'Invalid deletion type.');
        header('Location: ../index.php');
}
exit;
?>
