<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php?page=checkout');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirect('../index.php?page=checkout', 'Invalid request. Please try again.', 'error');
}

// Collect order data
$orderData = [
    'first_name' => sanitize($_POST['first_name'] ?? ''),
    'last_name' => sanitize($_POST['last_name'] ?? ''),
    'email' => sanitize($_POST['email'] ?? ''),
    'phone' => sanitize($_POST['phone'] ?? ''),
    'delivery_method' => sanitize($_POST['delivery_method'] ?? 'delivery'),
    'address' => sanitize($_POST['address'] ?? ''),
    'city' => sanitize($_POST['city'] ?? ''),
    'state' => sanitize($_POST['state'] ?? ''),
    'zip' => sanitize($_POST['zip'] ?? ''),
    'instructions' => sanitize($_POST['instructions'] ?? ''),
    'order_date' => date('Y-m-d H:i:s')
];

// Validate required fields
$required = ['first_name', 'last_name', 'email', 'phone'];
if ($orderData['delivery_method'] === 'delivery') {
    $required = array_merge($required, ['address', 'city', 'state', 'zip']);
}

foreach ($required as $field) {
    if (empty($orderData[$field])) {
        redirect('../index.php?page=checkout', 'Please fill in all required fields.', 'error');
    }
}

if (!isValidEmail($orderData['email'])) {
    redirect('../index.php?page=checkout', 'Please enter a valid email address.', 'error');
}

// In production, you would:
// 1. Process payment
// 2. Save order to database
// 3. Send confirmation email
// 4. Clear cart

// Generate order number
$orderNumber = strtoupper(substr(md5(time() . rand()), 0, 8));
$_SESSION['last_order'] = $orderNumber;

redirect('../index.php?page=order-success');
?>
