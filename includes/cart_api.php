<?php
/**
 * Cart API - Handles all cart operations with the database
 */

require_once __DIR__ . '/config.php';

// Suppress HTML error output for JSON API (must be after config.php)
ini_set('display_errors', 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Get the action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get user's cart ID (creates one if doesn't exist)
function getCartId($userId) {
    global $conn;
    
    // Check if cart exists
    $stmt = mysqli_prepare($conn, "SELECT cart_id FROM cart WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $cart = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($cart) {
        return $cart['cart_id'];
    }
    
    // Create new cart
    $stmt = mysqli_prepare($conn, "INSERT INTO cart (user_id) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $cartId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    return $cartId;
}

// Get all cart items for user
function getCartItems($userId) {
    global $conn;
    
    $cartId = getCartId($userId);
    
    $stmt = mysqli_prepare($conn, "
        SELECT ci.cart_item_id, ci.product_id, ci.quantity,
               p.price, p.name, p.image, p.slug, p.stock
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.cart_id = ?
        ORDER BY ci.created_at DESC
    ");
    mysqli_stmt_bind_param($stmt, "i", $cartId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = [
            'id' => (int)$row['product_id'],
            'cartItemId' => (int)$row['cart_item_id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'quantity' => (int)$row['quantity'],
            'image' => 'assets/images/products/' . $row['image'],
            'slug' => $row['slug'],
            'stock' => (int)$row['stock']
        ];
    }
    mysqli_stmt_close($stmt);
    
    return $items;
}

// Add item to cart
function addToCart($userId, $productId, $quantity = 1) {
    global $conn;
    
    $cartId = getCartId($userId);
    
    // Get product info including stock
    $stmt = mysqli_prepare($conn, "SELECT product_id, price, stock FROM products WHERE product_id = ? AND active = TRUE");
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found'];
    }
    
    $stock = (int)$product['stock'];
    
    // Check if item already in cart
    $stmt = mysqli_prepare($conn, "SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $cartId, $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existing = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($existing) {
        // Update quantity
        $newQty = $existing['quantity'] + $quantity;
        
        // Validate against stock
        if ($newQty > $stock) {
            return ['success' => false, 'message' => "Only $stock items available in stock", 'stock' => $stock];
        }
        
        $stmt = mysqli_prepare($conn, "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE cart_item_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $newQty, $existing['cart_item_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        // Validate quantity against stock
        if ($quantity > $stock) {
            return ['success' => false, 'message' => "Only $stock items available in stock", 'stock' => $stock];
        }
        
        // Add new item
        $stmt = mysqli_prepare($conn, "INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iii", $cartId, $productId, $quantity);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    return ['success' => true, 'message' => 'Item added to cart'];
}

// Update cart item quantity
function updateCartQuantity($userId, $productId, $quantity) {
    global $conn;
    
    $cartId = getCartId($userId);
    
    if ($quantity <= 0) {
        // Remove item
        $stmt = mysqli_prepare($conn, "DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $cartId, $productId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return ['success' => true, 'message' => 'Item removed from cart'];
    }
    
    // Get product stock
    $stmt = mysqli_prepare($conn, "SELECT stock FROM products WHERE product_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found'];
    }
    
    $stock = (int)$product['stock'];
    
    // Validate against stock
    if ($quantity > $stock) {
        return ['success' => false, 'message' => "Only $stock items available in stock", 'stock' => $stock];
    }
    
    $stmt = mysqli_prepare($conn, "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE cart_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($stmt, "iii", $quantity, $cartId, $productId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return ['success' => true, 'message' => 'Quantity updated'];
}

// Remove item from cart
function removeFromCartDb($userId, $productId) {
    global $conn;
    
    $cartId = getCartId($userId);
    
    $stmt = mysqli_prepare($conn, "DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $cartId, $productId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return ['success' => true, 'message' => 'Item removed from cart'];
}

// Clear entire cart
function clearCart($userId) {
    global $conn;
    
    $cartId = getCartId($userId);
    
    $stmt = mysqli_prepare($conn, "DELETE FROM cart_items WHERE cart_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $cartId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return ['success' => true, 'message' => 'Cart cleared'];
}

// Get cart count
function getCartCount($userId) {
    global $conn;
    
    $cartId = getCartId($userId);
    
    $stmt = mysqli_prepare($conn, "SELECT SUM(quantity) as count FROM cart_items WHERE cart_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $cartId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return (int)($row['count'] ?? 0);
}

// Handle API requests
try {
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($action) {
        case 'get':
            if (!isLoggedIn()) {
                $response = ['success' => true, 'items' => [], 'count' => 0, 'loggedIn' => false];
            } else {
                $items = getCartItems($_SESSION['user_id']);
                $count = getCartCount($_SESSION['user_id']);
                $response = ['success' => true, 'items' => $items, 'count' => $count, 'loggedIn' => true];
            }
            break;
            
        case 'add':
            if (!isLoggedIn()) {
                $response = ['success' => false, 'message' => 'Please login to add items to cart', 'requireLogin' => true];
            } else {
                $productId = (int)($_POST['product_id'] ?? 0);
                $quantity = (int)($_POST['quantity'] ?? 1);
                if ($productId > 0) {
                    $response = addToCart($_SESSION['user_id'], $productId, $quantity);
                    $response['count'] = getCartCount($_SESSION['user_id']);
                } else {
                    $response = ['success' => false, 'message' => 'Invalid product'];
                }
            }
            break;
            
        case 'update':
            if (!isLoggedIn()) {
                $response = ['success' => false, 'message' => 'Please login first', 'requireLogin' => true];
            } else {
                $productId = (int)($_POST['product_id'] ?? 0);
                $quantity = (int)($_POST['quantity'] ?? 0);
                if ($productId > 0) {
                    $response = updateCartQuantity($_SESSION['user_id'], $productId, $quantity);
                    $response['count'] = getCartCount($_SESSION['user_id']);
                } else {
                    $response = ['success' => false, 'message' => 'Invalid product'];
                }
            }
            break;
            
        case 'remove':
            if (!isLoggedIn()) {
                $response = ['success' => false, 'message' => 'Please login first', 'requireLogin' => true];
            } else {
                $productId = (int)($_POST['product_id'] ?? 0);
                if ($productId > 0) {
                    $response = removeFromCartDb($_SESSION['user_id'], $productId);
                    $response['count'] = getCartCount($_SESSION['user_id']);
                } else {
                    $response = ['success' => false, 'message' => 'Invalid product'];
                }
            }
            break;
            
        case 'clear':
            if (!isLoggedIn()) {
                $response = ['success' => false, 'message' => 'Please login first', 'requireLogin' => true];
            } else {
                $response = clearCart($_SESSION['user_id']);
                $response['count'] = 0;
            }
            break;
            
        case 'count':
            if (!isLoggedIn()) {
                $response = ['success' => true, 'count' => 0, 'loggedIn' => false];
            } else {
                $count = getCartCount($_SESSION['user_id']);
                $response = ['success' => true, 'count' => $count, 'loggedIn' => true];
            }
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Unknown action: ' . $action];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
