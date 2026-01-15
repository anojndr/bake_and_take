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

global $conn;
if (!$conn) {
    setFlashMessage('error', 'Database connection error.');
    header('Location: ../index.php?page=products');
    exit;
}

switch ($action) {
    case 'add':
        $name = sanitize($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
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
        
        $stmt = mysqli_prepare($conn, "
            INSERT INTO products (category_id, name, slug, description, price, image, stock, featured, active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isssdsii", $categoryId, $name, $slug, $description, $price, $imageName, $stock, $featured);
            if (mysqli_stmt_execute($stmt)) {
                setFlashMessage('success', 'Product added successfully!');
            } else {
                setFlashMessage('error', 'Operation failed: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            setFlashMessage('error', 'Operation failed: ' . mysqli_error($conn));
        }
        break;
        
    case 'edit':
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        $stmt = mysqli_prepare($conn, "
            UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, stock = ?, featured = ?
            WHERE product_id = ?
        ");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "issdiii", $categoryId, $name, $description, $price, $stock, $featured, $id);
            if (mysqli_stmt_execute($stmt)) {
                setFlashMessage('success', 'Product updated successfully!');
            } else {
                setFlashMessage('error', 'Operation failed: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            setFlashMessage('error', 'Operation failed: ' . mysqli_error($conn));
        }
        break;
        
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE product_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                setFlashMessage('success', 'Product deleted successfully!');
            } else {
                setFlashMessage('error', 'Operation failed: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            setFlashMessage('error', 'Operation failed: ' . mysqli_error($conn));
        }
        break;
        
    case 'toggle':
        $id = (int)($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "UPDATE products SET active = NOT active WHERE product_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                setFlashMessage('success', 'Product status updated!');
            } else {
                setFlashMessage('error', 'Operation failed: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            setFlashMessage('error', 'Operation failed: ' . mysqli_error($conn));
        }
        break;
        
    default:
        setFlashMessage('error', 'Invalid action.');
}

header('Location: ../index.php?page=products');
exit;
?>
