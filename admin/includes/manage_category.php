<?php
/**
 * Category Management - CRUD Operations
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
    header('Location: ../index.php?page=categories');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid request. Please try again.');
    header('Location: ../index.php?page=categories');
    exit;
}

$action = $_POST['action'] ?? '';

global $conn;
if (!$conn) {
    setFlashMessage('error', 'Database connection error.');
    header('Location: ../index.php?page=categories');
    exit;
}

try {
    switch ($action) {
        case 'add':
            $name = sanitize($_POST['name'] ?? '');
            $slug = sanitize($_POST['slug'] ?? '');
            $icon = sanitize($_POST['icon'] ?? 'bi-box');
            
            // Clean slug
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $slug ?: $name));
            
            $stmt = mysqli_prepare($conn, "INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $name, $slug, $icon);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            setFlashMessage('success', 'Category added successfully!');
            break;
            
        case 'edit':
            $id = (int)($_POST['id'] ?? 0);
            $name = sanitize($_POST['name'] ?? '');
            $icon = sanitize($_POST['icon'] ?? 'bi-box');
            
            $stmt = mysqli_prepare($conn, "UPDATE categories SET name = ?, icon = ? WHERE category_id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $name, $icon, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            setFlashMessage('success', 'Category updated successfully!');
            break;
            
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            
            // Update products to uncategorized
            $stmt = mysqli_prepare($conn, "UPDATE products SET category_id = NULL WHERE category_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            // Delete category
            $stmt = mysqli_prepare($conn, "DELETE FROM categories WHERE category_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            setFlashMessage('success', 'Category deleted successfully!');
            break;
            
        default:
            setFlashMessage('error', 'Invalid action.');
    }
} catch (Exception $e) {
    setFlashMessage('error', 'Operation failed: ' . mysqli_error($conn));
}

header('Location: ../index.php?page=categories');
exit;
?>
