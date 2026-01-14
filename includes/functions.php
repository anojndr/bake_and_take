<?php
/**
 * Bake & Take - Helper Functions
 * Updated to fetch data from MySQL database
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
    global $pdo, $PRODUCTS;
    
    if ($pdo) {
        try {
            $stmt = $pdo->query("
                SELECT p.*, c.slug as category 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.active = 1 
                ORDER BY p.featured DESC, p.name ASC
            ");
            $products = $stmt->fetchAll();
            
            // Convert 'featured' to boolean for consistency
            foreach ($products as &$product) {
                $product['featured'] = (bool) $product['featured'];
            }
            
            return $products;
        } catch (PDOException $e) {
            // Fallback to static array if query fails
            return $PRODUCTS;
        }
    }
    
    return $PRODUCTS;
}

/**
 * Get products by category from database
 */
function getProductsByCategory($category = null) {
    global $pdo, $PRODUCTS;
    
    if ($category === null) {
        return getAllProducts();
    }
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT p.*, c.slug as category 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE c.slug = ? AND p.active = 1 
                ORDER BY p.featured DESC, p.name ASC
            ");
            $stmt->execute([$category]);
            $products = $stmt->fetchAll();
            
            foreach ($products as &$product) {
                $product['featured'] = (bool) $product['featured'];
            }
            
            return $products;
        } catch (PDOException $e) {
            // Fallback to static array
            return array_filter($PRODUCTS, fn($p) => $p['category'] === $category);
        }
    }
    
    return array_filter($PRODUCTS, fn($p) => $p['category'] === $category);
}

/**
 * Get featured products from database
 */
function getFeaturedProducts() {
    global $pdo, $PRODUCTS;
    
    if ($pdo) {
        try {
            $stmt = $pdo->query("
                SELECT p.*, c.slug as category 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.featured = 1 AND p.active = 1 
                ORDER BY p.name ASC
            ");
            $products = $stmt->fetchAll();
            
            foreach ($products as &$product) {
                $product['featured'] = true;
            }
            
            return $products;
        } catch (PDOException $e) {
            return array_filter($PRODUCTS, fn($p) => $p['featured'] === true);
        }
    }
    
    return array_filter($PRODUCTS, fn($p) => $p['featured'] === true);
}

/**
 * Get single product by ID from database
 */
function getProductById($id) {
    global $pdo, $PRODUCTS;
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT p.*, c.slug as category 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            
            if ($product) {
                $product['featured'] = (bool) $product['featured'];
                return $product;
            }
        } catch (PDOException $e) {
            // Fallback to static array
        }
    }
    
    foreach ($PRODUCTS as $p) { 
        if ($p['id'] == $id) return $p; 
    }
    return null;
}

/**
 * Get all categories from database
 */
function getAllCategories() {
    global $pdo, $CATEGORIES;
    
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
            $categories = [];
            while ($row = $stmt->fetch()) {
                $categories[$row['slug']] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'icon' => $row['icon']
                ];
            }
            return $categories;
        } catch (PDOException $e) {
            return $CATEGORIES;
        }
    }
    
    return $CATEGORIES;
}

/**
 * Get category name by slug
 */
function getCategoryName($slug) {
    global $pdo, $CATEGORIES;
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT name FROM categories WHERE slug = ?");
            $stmt->execute([$slug]);
            $category = $stmt->fetch();
            if ($category) {
                return $category['name'];
            }
        } catch (PDOException $e) {
            // Fallback to static array
        }
    }
    
    return isset($CATEGORIES[$slug]) ? $CATEGORIES[$slug]['name'] : 'All Products';
}

/**
 * Get product count by category
 */
function getProductCountByCategory($categorySlug = null) {
    global $pdo, $PRODUCTS;
    
    if ($pdo) {
        try {
            if ($categorySlug === null) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE active = 1");
            } else {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM products p 
                    JOIN categories c ON p.category_id = c.id 
                    WHERE c.slug = ? AND p.active = 1
                ");
                $stmt->execute([$categorySlug]);
            }
            $result = $stmt->fetch();
            return (int) $result['count'];
        } catch (PDOException $e) {
            // Fallback
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
    global $pdo;
    if (!$pdo) return 0;
    
    // Get cart ID
    $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cart = $stmt->fetch();
    
    if (!$cart) return 0;
    
    $cartId = $cart['id'];
    
    // Get count
    $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cartId]);
    $result = $stmt->fetch();
    
    return (int)($result['count'] ?? 0);
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
