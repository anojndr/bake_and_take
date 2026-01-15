<?php
/**
 * Cart API - Handles all cart operations with the database
 */

require_once __DIR__ . '/config.php';

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
function getCartId($pdo, $userId) {
    // Check if cart exists
    $stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cart = $stmt->fetch();
    
    if ($cart) {
        return $cart['cart_id'];
    }
    
    // Create new cart
    $stmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
    $stmt->execute([$userId]);
    return $pdo->lastInsertId();
}

// Get all cart items for user
function getCartItems($pdo, $userId) {
    $cartId = getCartId($pdo, $userId);
    
    $stmt = $pdo->prepare("
        SELECT ci.cart_item_id, ci.product_id, ci.quantity,
               p.price, p.name, p.image, p.slug, p.stock
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.cart_id = ?
        ORDER BY ci.created_at DESC
    ");
    $stmt->execute([$cartId]);
    
    $items = [];
    while ($row = $stmt->fetch()) {
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
    
    return $items;
}

// Add item to cart
function addToCart($pdo, $userId, $productId, $quantity = 1) {
    $cartId = getCartId($pdo, $userId);
    
    // Get product info including stock
    $stmt = $pdo->prepare("SELECT product_id, price, stock FROM products WHERE product_id = ? AND active = TRUE");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found'];
    }
    
    $stock = (int)$product['stock'];
    
    // Check if item already in cart
    $stmt = $pdo->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->execute([$cartId, $productId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update quantity
        $newQty = $existing['quantity'] + $quantity;
        
        // Validate against stock
        if ($newQty > $stock) {
            return ['success' => false, 'message' => "Only $stock items available in stock", 'stock' => $stock];
        }
        
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE cart_item_id = ?");
        $stmt->execute([$newQty, $existing['cart_item_id']]);
    } else {
        // Validate quantity against stock
        if ($quantity > $stock) {
            return ['success' => false, 'message' => "Only $stock items available in stock", 'stock' => $stock];
        }
        
        // Add new item
        $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$cartId, $productId, $quantity]);
    }
    
    return ['success' => true, 'message' => 'Item added to cart'];
}

// Update cart item quantity
function updateCartQuantity($pdo, $userId, $productId, $quantity) {
    $cartId = getCartId($pdo, $userId);
    
    if ($quantity <= 0) {
        // Remove item
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $stmt->execute([$cartId, $productId]);
        return ['success' => true, 'message' => 'Item removed from cart'];
    }
    
    // Get product stock
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE product_id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found'];
    }
    
    $stock = (int)$product['stock'];
    
    // Validate against stock
    if ($quantity > $stock) {
        return ['success' => false, 'message' => "Only $stock items available in stock", 'stock' => $stock];
    }
    
    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE cart_id = ? AND product_id = ?");
    $stmt->execute([$quantity, $cartId, $productId]);
    
    return ['success' => true, 'message' => 'Quantity updated'];
}

// Remove item from cart
function removeFromCartDb($pdo, $userId, $productId) {
    $cartId = getCartId($pdo, $userId);
    
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->execute([$cartId, $productId]);
    
    return ['success' => true, 'message' => 'Item removed from cart'];
}

// Clear entire cart
function clearCart($pdo, $userId) {
    $cartId = getCartId($pdo, $userId);
    
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cartId]);
    
    return ['success' => true, 'message' => 'Cart cleared'];
}

// Get cart count
function getCartCount($pdo, $userId) {
    $cartId = getCartId($pdo, $userId);
    
    $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cartId]);
    $result = $stmt->fetch();
    
    return (int)($result['count'] ?? 0);
}

// Handle API requests
try {
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($action) {
        case 'get':
            if (!isLoggedIn()) {
                $response = ['success' => true, 'items' => [], 'count' => 0, 'loggedIn' => false];
            } else {
                $items = getCartItems($pdo, $_SESSION['user_id']);
                $count = getCartCount($pdo, $_SESSION['user_id']);
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
                    $response = addToCart($pdo, $_SESSION['user_id'], $productId, $quantity);
                    $response['count'] = getCartCount($pdo, $_SESSION['user_id']);
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
                    $response = updateCartQuantity($pdo, $_SESSION['user_id'], $productId, $quantity);
                    $response['count'] = getCartCount($pdo, $_SESSION['user_id']);
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
                    $response = removeFromCartDb($pdo, $_SESSION['user_id'], $productId);
                    $response['count'] = getCartCount($pdo, $_SESSION['user_id']);
                } else {
                    $response = ['success' => false, 'message' => 'Invalid product'];
                }
            }
            break;
            
        case 'clear':
            if (!isLoggedIn()) {
                $response = ['success' => false, 'message' => 'Please login first', 'requireLogin' => true];
            } else {
                $response = clearCart($pdo, $_SESSION['user_id']);
                $response['count'] = 0;
            }
            break;
            
        case 'count':
            if (!isLoggedIn()) {
                $response = ['success' => true, 'count' => 0, 'loggedIn' => false];
            } else {
                $count = getCartCount($pdo, $_SESSION['user_id']);
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
