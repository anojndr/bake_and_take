<?php
/**
 * User Management - Admin Operations
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
    header('Location: ../index.php?page=users');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid request. Please try again.');
    header('Location: ../index.php?page=users');
    exit;
}

$action = $_POST['action'] ?? '';

global $conn;
if (!$conn) {
    setFlashMessage('error', 'Database connection error.');
    header('Location: ../index.php?page=users');
    exit;
}

try {
    switch ($action) {
        case 'toggle_admin':
            $id = (int)($_POST['id'] ?? 0);
            
            // Prevent removing your own admin status
            if ($id === $_SESSION['admin_id']) {
                setFlashMessage('error', 'You cannot modify your own admin status.');
                break;
            }
            
            $stmt = mysqli_prepare($conn, "UPDATE users SET is_admin = NOT is_admin WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            setFlashMessage('success', 'User admin status updated!');
            break;
            
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            
            // Prevent deleting yourself
            if ($id === $_SESSION['admin_id']) {
                setFlashMessage('error', 'You cannot delete your own account.');
                break;
            }
            
            // Don't delete if it's the only admin
            $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
            $adminCount = mysqli_fetch_assoc($result)['count'];
            mysqli_free_result($result);
            
            $stmt = mysqli_prepare($conn, "SELECT is_admin FROM users WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if ($user && $user['is_admin'] && $adminCount <= 1) {
                setFlashMessage('error', 'Cannot delete the only admin account.');
                break;
            }
            
            $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            setFlashMessage('success', 'User deleted successfully!');
            break;
            
        default:
            setFlashMessage('error', 'Invalid action.');
    }
} catch (Exception $e) {
    setFlashMessage('error', 'Operation failed: ' . mysqli_error($conn));
}

header('Location: ../index.php?page=users');
exit;
?>
