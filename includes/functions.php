<?php
/**
 * Bake & Take - Helper Functions
 * Updated to fetch data from MySQL database using mysqli
 */

function sanitize($input) {
    return htmlspecialchars(trim($input ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatPrice($price) {
    return '₱' . number_format($price, 2);
}

/**
 * Get all products from database
 */
function getAllProducts() {
    global $conn, $PRODUCTS;
    
    if ($conn) {
        $query = "
            SELECT p.*, c.slug as category 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.active = 1 
            ORDER BY p.featured DESC, p.name ASC
        ";
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            $products = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $row['featured'] = (bool) $row['featured'];
                $products[] = $row;
            }
            return $products;
        }
    }
    
    return $PRODUCTS;
}

/**
 * Get products by category from database
 */
function getProductsByCategory($category = null) {
    global $conn, $PRODUCTS;
    
    if ($category === null) {
        return getAllProducts();
    }
    
    if ($conn) {
        $category = mysqli_real_escape_string($conn, $category);
        $query = "
            SELECT p.*, c.slug as category 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE c.slug = '$category' AND p.active = 1 
            ORDER BY p.featured DESC, p.name ASC
        ";
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            $products = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $row['featured'] = (bool) $row['featured'];
                $products[] = $row;
            }
            return $products;
        }
    }
    
    return array_filter($PRODUCTS, fn($p) => $p['category'] === $category);
}

/**
 * Get featured products from database
 */
function getFeaturedProducts() {
    global $conn, $PRODUCTS;
    
    if ($conn) {
        $query = "
            SELECT p.*, c.slug as category 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.featured = 1 AND p.active = 1 
            ORDER BY p.name ASC
        ";
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            $products = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $row['featured'] = true;
                $products[] = $row;
            }
            return $products;
        }
    }
    
    return array_filter($PRODUCTS, fn($p) => $p['featured'] === true);
}

/**
 * Get single product by ID from database
 */
function getProductById($id) {
    global $conn, $PRODUCTS;
    
    if ($conn) {
        $id = (int)$id;
        $query = "
            SELECT p.*, c.slug as category 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.product_id = $id
        ";
        $result = mysqli_query($conn, $query);
        
        if ($result && $product = mysqli_fetch_assoc($result)) {
            $product['featured'] = (bool) $product['featured'];
            return $product;
        }
    }
    
    foreach ($PRODUCTS as $p) { 
        if ($p['product_id'] == $id) return $p; 
    }
    return null;
}

/**
 * Get all categories from database
 */
function getAllCategories() {
    global $conn, $CATEGORIES;
    
    if ($conn) {
        $result = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");
        if ($result) {
            $categories = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $categories[$row['slug']] = [
                    'id' => $row['category_id'],
                    'name' => $row['name'],
                    'icon' => $row['icon']
                ];
            }
            return $categories;
        }
    }
    
    return $CATEGORIES;
}

/**
 * Get category name by slug
 */
function getCategoryName($slug) {
    global $conn, $CATEGORIES;
    
    if ($conn) {
        $slug = mysqli_real_escape_string($conn, $slug);
        $result = mysqli_query($conn, "SELECT name FROM categories WHERE slug = '$slug'");
        if ($result && $category = mysqli_fetch_assoc($result)) {
            return $category['name'];
        }
    }
    
    return isset($CATEGORIES[$slug]) ? $CATEGORIES[$slug]['name'] : 'All Products';
}

/**
 * Get product count by category
 */
function getProductCountByCategory($categorySlug = null) {
    global $conn, $PRODUCTS;
    
    if ($conn) {
        if ($categorySlug === null) {
            $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE active = 1");
        } else {
            $categorySlug = mysqli_real_escape_string($conn, $categorySlug);
            $result = mysqli_query($conn, "
                SELECT COUNT(*) as count 
                FROM products p 
                JOIN categories c ON p.category_id = c.category_id 
                WHERE c.slug = '$categorySlug' AND p.active = 1
            ");
        }
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return (int) $row['count'];
        }
    }
    
    if ($categorySlug === null) {
        return count($PRODUCTS);
    }
    return count(array_filter($PRODUCTS, fn($p) => $p['category'] === $categorySlug));
}

function getProductImage($image) { 
    return "assets/images/products/{$image}"; 
}

function setFlashMessage($type, $message) { 
    $_SESSION['flash'] = ['type' => $type, 'message' => $message]; 
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) { 
        $f = $_SESSION['flash']; 
        unset($_SESSION['flash']); 
        return $f; 
    }
    return null;
}

function isLoggedIn() { 
    return isset($_SESSION['user_id']); 
}

function isAdmin() { 
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true; 
}

function redirect($url, $message = null, $type = 'info') {
    if ($message) setFlashMessage($type, $message);
    header("Location: {$url}"); 
    exit;
}

function isValidEmail($email) { 
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; 
}

/**
 * Get the current site URL dynamically
 * Detects whether we're on localhost or a domain
 */
function getCurrentSiteUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Check if we're on localhost
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        // Extract the path for localhost (e.g., /bake_and_take)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname(dirname($scriptName)); // Go up two levels from includes/
        
        // Clean up the path
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        
        return $protocol . '://' . $host . $basePath;
    } else {
        // For production domain, just use protocol and host
        return $protocol . '://' . $host;
    }
}

/**
 * Get site URL for SMS messages (when called from webhook/CLI context)
 * Uses SITE_URL constant or fallback to config
 */
function getSiteUrlForSMS() {
    // Try to use defined SITE_URL constant first
    if (defined('SITE_URL') && !empty(SITE_URL)) {
        return rtrim(SITE_URL, '/');
    }
    
    // Fallback: Try to get from server if available
    if (isset($_SERVER['HTTP_HOST'])) {
        return getCurrentSiteUrl();
    }
    
    // Default fallback for CLI/webhook context
    return 'http://localhost/bake_and_take';
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) { 
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token); 
}

function getCart() { 
    return $_SESSION['cart'] ?? []; 
}

function getCartTotal() {
    $total = 0;
    foreach (getCart() as $item) { 
        $p = getProductById($item['product_id']); 
        if ($p) $total += $p['price'] * $item['quantity']; 
    }
    return $total;
}

function getDatabaseCartCount($userId) {
    global $conn;
    if (!$conn) return 0;
    
    $userId = (int)$userId;
    
    // Get cart ID
    $result = mysqli_query($conn, "SELECT cart_id FROM cart WHERE user_id = $userId");
    $cart = mysqli_fetch_assoc($result);
    
    if (!$cart) return 0;
    
    $cartId = (int)$cart['cart_id'];
    
    // Get count
    $result = mysqli_query($conn, "SELECT SUM(quantity) as count FROM cart_items WHERE cart_id = $cartId");
    $row = mysqli_fetch_assoc($result);
    
    return (int)($row['count'] ?? 0);
}

function getCartItemCount() {
    if (isLoggedIn()) {
        return getDatabaseCartCount($_SESSION['user_id']);
    }

    $count = 0;
    foreach (getCart() as $item) {
        $count += $item['quantity'];
    }
    return $count;
}
?>
