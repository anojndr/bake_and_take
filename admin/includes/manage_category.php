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

if (!$pdo) {
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
            
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)");
            $stmt->execute([$name, $slug, $icon]);
            
            setFlashMessage('success', 'Category added successfully!');
            break;
            
        case 'edit':
            $id = (int)($_POST['id'] ?? 0);
            $name = sanitize($_POST['name'] ?? '');
            $icon = sanitize($_POST['icon'] ?? 'bi-box');
            
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, icon = ? WHERE id = ?");
            $stmt->execute([$name, $icon, $id]);
            
            setFlashMessage('success', 'Category updated successfully!');
            break;
            
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            
            // Update products to uncategorized
            $stmt = $pdo->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?");
            $stmt->execute([$id]);
            
            // Delete category
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            
            setFlashMessage('success', 'Category deleted successfully!');
            break;
            
        default:
            setFlashMessage('error', 'Invalid action.');
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Operation failed: ' . $e->getMessage());
}

header('Location: ../index.php?page=categories');
exit;
?>
