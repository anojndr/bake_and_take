<?php
/**
 * Message Management - Admin Operations
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
    header('Location: ../index.php?page=messages');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid request. Please try again.');
    header('Location: ../index.php?page=messages');
    exit;
}

$action = $_POST['action'] ?? '';

if (!$pdo) {
    setFlashMessage('error', 'Database connection error.');
    header('Location: ../index.php?page=messages');
    exit;
}

try {
    switch ($action) {
        case 'mark_read':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE contact_messages SET read_status = 1 WHERE id = ?");
            $stmt->execute([$id]);
            
            setFlashMessage('success', 'Message marked as read.');
            break;
            
        case 'mark_unread':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE contact_messages SET read_status = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            setFlashMessage('success', 'Message marked as unread.');
            break;
            
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$id]);
            
            setFlashMessage('success', 'Message deleted successfully!');
            break;
            
        case 'delete_all_read':
            $stmt = $pdo->query("DELETE FROM contact_messages WHERE read_status = 1");
            
            setFlashMessage('success', 'All read messages deleted.');
            break;
            
        default:
            setFlashMessage('error', 'Invalid action.');
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Operation failed: ' . $e->getMessage());
}

header('Location: ../index.php?page=messages');
exit;
?>
