<?php
/**
 * Bake & Take - Helper Functions
 */

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatPrice($price) {
    return '$' . number_format($price, 2);
}

function getProductsByCategory($category = null) {
    global $PRODUCTS;
    if ($category === null) return $PRODUCTS;
    return array_filter($PRODUCTS, fn($p) => $p['category'] === $category);
}

function getFeaturedProducts() {
    global $PRODUCTS;
    return array_filter($PRODUCTS, fn($p) => $p['featured'] === true);
}

function getProductById($id) {
    global $PRODUCTS;
    foreach ($PRODUCTS as $p) { if ($p['id'] == $id) return $p; }
    return null;
}

function getCategoryName($slug) {
    global $CATEGORIES;
    return isset($CATEGORIES[$slug]) ? $CATEGORIES[$slug]['name'] : 'All Products';
}

function getProductImage($image) { return "assets/images/products/{$image}"; }

function setFlashMessage($type, $message) { $_SESSION['flash'] = ['type' => $type, 'message' => $message]; }

function getFlashMessage() {
    if (isset($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}

function isLoggedIn() { return isset($_SESSION['user_id']); }

function redirect($url, $message = null, $type = 'info') {
    if ($message) setFlashMessage($type, $message);
    header("Location: {$url}"); exit;
}

function isValidEmail($email) { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) { return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token); }

function getCart() { return $_SESSION['cart'] ?? []; }

function getCartTotal() {
    $total = 0;
    foreach (getCart() as $item) { $p = getProductById($item['product_id']); if ($p) $total += $p['price'] * $item['quantity']; }
    return $total;
}

function getCartItemCount() {
    $count = 0;
    foreach (getCart() as $item) $count += $item['quantity'];
    return $count;
}
?>
