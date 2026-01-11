<?php
/**
 * PayPal Create Order API
 * 
 * This endpoint creates a PayPal order and returns the order ID
 * for the frontend to use with PayPal Smart Buttons.
 */

header('Content-Type: application/json');
session_start();

require_once 'config.php';
require_once 'functions.php';
require_once 'secrets.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the request body
$input = json_decode(file_get_contents('php://input'), true);

// Validate cart data
$cartData = $input['cart'] ?? [];
if (empty($cartData)) {
    http_response_code(400);
    echo json_encode(['error' => 'Cart is empty']);
    exit;
}

// Calculate totals
$subtotal = 0;
foreach ($cartData as $item) {
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}
$tax = $subtotal * 0.08;
$total = $subtotal + $tax;

// Generate access token
$accessToken = getPayPalAccessToken();
if (!$accessToken) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to authenticate with PayPal']);
    exit;
}

// Create order with PayPal
$orderData = [
    'intent' => 'CAPTURE',
    'purchase_units' => [
        [
            'reference_id' => 'bake_and_take_order_' . time(),
            'description' => 'Bake & Take Order',
            'amount' => [
                'currency_code' => 'PHP',
                'value' => number_format($total, 2, '.', ''),
                'breakdown' => [
                    'item_total' => [
                        'currency_code' => 'PHP',
                        'value' => number_format($subtotal, 2, '.', '')
                    ],
                    'tax_total' => [
                        'currency_code' => 'PHP',
                        'value' => number_format($tax, 2, '.', '')
                    ]
                ]
            ],
            'items' => array_map(function($item) {
                return [
                    'name' => substr($item['name'], 0, 127), // PayPal limits name to 127 chars
                    'quantity' => strval(intval($item['quantity'])),
                    'unit_amount' => [
                        'currency_code' => 'PHP',
                        'value' => number_format(floatval($item['price']), 2, '.', '')
                    ]
                ];
            }, $cartData)
        ]
    ],
    'application_context' => [
        'brand_name' => 'Bake & Take Bakery',
        'landing_page' => 'NO_PREFERENCE',
        'user_action' => 'PAY_NOW',
        'return_url' => SITE_URL . '/index.php?page=order-success',
        'cancel_url' => SITE_URL . '/index.php?page=checkout'
    ]
];

// Make request to PayPal
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, getPayPalApiUrl() . '/v2/checkout/orders');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $accessToken
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode >= 200 && $httpCode < 300 && isset($result['id'])) {
    // Store cart data in session for later use
    $_SESSION['pending_paypal_order'] = [
        'paypal_order_id' => $result['id'],
        'cart' => $cartData,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'total' => $total,
        'created_at' => time()
    ];
    
    echo json_encode([
        'id' => $result['id'],
        'status' => $result['status']
    ]);
} else {
    error_log('PayPal Create Order Error: ' . $response);
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to create PayPal order',
        'details' => $result['message'] ?? 'Unknown error'
    ]);
}

/**
 * Get PayPal OAuth2 Access Token
 */
function getPayPalAccessToken() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, getPayPalApiUrl() . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300 && isset($result['access_token'])) {
        return $result['access_token'];
    }
    
    error_log('PayPal Auth Error: ' . $response);
    return null;
}

/**
 * Get PayPal API URL based on environment
 */
function getPayPalApiUrl() {
    // Check if using sandbox or live credentials
    // For production, change to 'https://api-m.paypal.com'
    return defined('PAYPAL_SANDBOX') && !PAYPAL_SANDBOX 
        ? 'https://api-m.paypal.com' 
        : 'https://api-m.sandbox.paypal.com';
}
?>
