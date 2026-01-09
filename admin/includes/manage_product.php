<?php
/**
 * Product Management - CRUD Operations
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
    header('Location: ../index.php?page=products');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid request. Please try again.');
    header('Location: ../index.php?page=products');
    exit;
}

$action = $_POST['action'] ?? '';

if (!$pdo) {
    setFlashMessage('error', 'Database connection error.');
    header('Location: ../index.php?page=products');
    exit;
}

try {
    switch ($action) {
        case 'add':
            $name = sanitize($_POST['name'] ?? '');
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $description = sanitize($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $featured = isset($_POST['featured']) ? 1 : 0;
            
            // Generate slug
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            
            // Handle image upload
            $imageName = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../assets/images/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $imageName = $slug . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO products (category_id, name, slug, description, price, image, featured, active)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$categoryId, $name, $slug, $description, $price, $imageName, $featured]);
            
            setFlashMessage('success', 'Product added successfully!');
            break;
            
        case 'edit':
            $id = (int)($_POST['id'] ?? 0);
            $name = sanitize($_POST['name'] ?? '');
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $description = sanitize($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $featured = isset($_POST['featured']) ? 1 : 0;
            
            $stmt = $pdo->prepare("
                UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, featured = ?
                WHERE id = ?
            ");
            $stmt->execute([$categoryId, $name, $description, $price, $featured, $id]);
            
            setFlashMessage('success', 'Product updated successfully!');
            break;
            
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            
            setFlashMessage('success', 'Product deleted successfully!');
            break;
            
        case 'toggle':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE products SET active = NOT active WHERE id = ?");
            $stmt->execute([$id]);
            
            setFlashMessage('success', 'Product status updated!');
            break;
            
        default:
            setFlashMessage('error', 'Invalid action.');
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Operation failed: ' . $e->getMessage());
}

header('Location: ../index.php?page=products');
exit;
?>
