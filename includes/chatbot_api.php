<?php
/**
 * Bake & Take AI Chatbot API
 * Uses Ollama with qwen3:0.6b model
 * Dynamically includes products from database
 */

// Suppress PHP errors from being displayed (they corrupt JSON output)
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

header('Content-Type: application/json');

// Allow CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_end_clean();
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Include config and functions to access database
try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/functions.php';
    
    // Re-suppress errors after config (config.php enables them)
    error_reporting(0);
    ini_set('display_errors', 0);
} catch (Exception $e) {
    // Continue without database - will use fallback products
}

// Get the user message
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($input['message']) ? trim($input['message']) : '';

if (empty($userMessage)) {
    http_response_code(400);
    ob_end_clean();
    echo json_encode(['error' => 'Message is required']);
    exit;
}

/**
 * Build dynamic product information from database
 */
function buildProductsInfo() {
    global $pdo, $PRODUCTS, $CATEGORIES;
    
    $productsInfo = "";
    
    try {
        // Get all categories with their products
        $categories = getAllCategories();
        
        foreach ($categories as $slug => $category) {
            $products = getProductsByCategory($slug);
            if (empty($products)) continue;
            
            $productsInfo .= "\n### " . $category['name'] . "\n";
            
            foreach ($products as $product) {
                $price = formatPrice($product['price']);
                $featured = !empty($product['featured']) ? ' [BESTSELLER]' : '';
                $productsInfo .= "- **{$product['name']}**{$featured}: {$product['description']} - {$price}\n";
            }
        }
    } catch (Exception $e) {
        // Fallback to config products if database fails
        if (isset($PRODUCTS) && is_array($PRODUCTS)) {
            foreach ($PRODUCTS as $product) {
                $price = '₱' . number_format($product['price'], 2);
                $featured = !empty($product['featured']) ? ' [BESTSELLER]' : '';
                $productsInfo .= "- **{$product['name']}**{$featured}: {$product['description']} - {$price}\n";
            }
        }
    }
    
    return $productsInfo;
}

/**
 * Build featured products list from database
 */
function buildFeaturedProductsInfo() {
    global $PRODUCTS;
    
    try {
        $featuredProducts = getFeaturedProducts();
        
        if (empty($featuredProducts)) {
            return "Currently, all our products are equally special!";
        }
        
        $featuredInfo = "";
        foreach ($featuredProducts as $product) {
            $price = formatPrice($product['price']);
            $featuredInfo .= "- **{$product['name']}** - {$product['description']} ({$price})\n";
        }
        
        return $featuredInfo;
    } catch (Exception $e) {
        // Fallback
        if (isset($PRODUCTS) && is_array($PRODUCTS)) {
            $featuredInfo = "";
            foreach ($PRODUCTS as $product) {
                if (!empty($product['featured'])) {
                    $price = '₱' . number_format($product['price'], 2);
                    $featuredInfo .= "- **{$product['name']}** - {$product['description']} ({$price})\n";
                }
            }
            return $featuredInfo ?: "All our products are special!";
        }
        return "All our products are special!";
    }
}

/**
 * Get product count summary
 */
function getProductSummary() {
    global $PRODUCTS, $CATEGORIES;
    
    try {
        $categories = getAllCategories();
        $summary = "";
        $totalProducts = 0;
        
        foreach ($categories as $slug => $category) {
            $count = getProductCountByCategory($slug);
            $totalProducts += $count;
            $summary .= "- {$category['name']}: {$count} products\n";
        }
        
        return "Total Products: {$totalProducts}\n" . $summary;
    } catch (Exception $e) {
        // Fallback
        if (isset($PRODUCTS) && is_array($PRODUCTS)) {
            return "Total Products: " . count($PRODUCTS);
        }
        return "Multiple products available across all categories";
    }
}

// Build dynamic content from database
$productsInfo = buildProductsInfo();
$featuredProductsInfo = buildFeaturedProductsInfo();
$productSummary = getProductSummary();

// System prompt with all Bake & Take information (with dynamic products)
$systemPrompt = <<<EOT
You are the official AI assistant for Bake & Take, an artisan bakery web application. You MUST ONLY respond to questions related to Bake & Take. If a user asks about anything unrelated to Bake & Take, politely decline and redirect them to ask about the bakery.

=== ABOUT BAKE & TAKE ===
Bake & Take is a web application project developed by 5 passionate BSIT (Bachelor of Science in Information Technology) students as their final project for their 3rd year of college at Polytechnic University of the Philippines (PUP) Sto. Tomas, Batangas. The project was created in January 2026.

NOTE: This is an ACADEMIC PROJECT for educational purposes only. It is NOT an actual bakery business. No real transactions are processed.

=== THE DEVELOPMENT TEAM ===
The 5 team members who developed this project are:
1. Jandron Gian Ramos - Developer
2. Benedict Orio - Developer
3. Paulchristian Dimaculangan - Developer
4. Diannedra Halili - Developer
5. Angelene Castillo - Developer

=== TECH STACK ===
- Backend: PHP 7.4+
- Frontend: Bootstrap 5, CSS3, JavaScript (ES6+)
- Database: MySQL/MariaDB
- Icons: Bootstrap Icons
- Fonts: Google Fonts (Playfair Display, Poppins)
- Payment: PayPal REST API integration

=== FEATURES ===
1. Product Catalog - Browse artisan breads, pastries, cakes, and cookies
2. Shopping Cart - Add items, update quantities, and checkout
3. User Authentication - Register and login functionality with email verification
4. Contact Form - Get in touch with the bakery
5. Responsive Design - Works on desktop, tablet, and mobile
6. Modern UI - Beautiful animations and premium design
7. PayPal Integration - Secure payment processing with PayPal Smart Buttons
8. Order History - View past orders
9. AI Chatbot - You! An intelligent assistant to help customers

=== PRODUCT INVENTORY ===
{$productSummary}

=== COMPLETE PRODUCT MENU ===
{$productsInfo}

=== FEATURED/BESTSELLER PRODUCTS ===
{$featuredProductsInfo}

=== CONTACT INFORMATION ===
- Location: Polytechnic University of the Philippines, Sto. Tomas, Batangas, Philippines
- Phone: 09763197468
- Email: anojndr@gmail.com
- Operating Hours: Monday to Saturday: 7:00 AM - 8:00 PM, Sunday: 8:00 AM - 6:00 PM

=== ORDER PROCESS ===
- This is an "online order then pickup at store only" system (no delivery)
- Customers can browse products, add to cart, and checkout online
- Payment is processed securely through PayPal
- After payment, customers pick up their orders at the store

=== WHY CHOOSE BAKE & TAKE ===
1. Fresh Daily - Everything is baked fresh every morning using traditional recipes
2. Organic Ingredients - Only the finest organic and locally-sourced ingredients
3. Easy Pickup - Order online and pick up at your convenience from the store
4. Made with Love - Every item is crafted with passion by skilled bakers

=== PROJECT GOALS ===
1. Clean Code - Writing maintainable, well-structured PHP code with proper separation of concerns
2. Modern Design - Creating a visually stunning and responsive interface using Bootstrap 5
3. User Experience - Building an intuitive shopping experience with functional cart and checkout

=== IMPORTANT REMINDERS ===
- You are Bake & Take's AI assistant
- ONLY answer questions about Bake & Take
- If asked about anything unrelated, politely say: "I'm sorry, I can only help with questions about Bake & Take bakery. Is there anything about our products, services, or ordering process I can help you with?"
- Be friendly, helpful, and professional
- Use emojis sparingly to be welcoming 🍞🥐🎂
- Keep responses concise but informative
- When asked about products, provide accurate prices from the database
- If asked about a product not in our menu, let them know we don't carry it but suggest similar items we do have
EOT;

// Ollama API configuration - using native endpoint for think control
$ollamaUrl = 'http://localhost:11434/api/chat';
$model = 'qwen3:0.6b';

// Prepare the request to Ollama
// Setting 'think' to false disables reasoning trace for faster responses
$requestData = [
    'model' => $model,
    'messages' => [
        [
            'role' => 'system',
            'content' => $systemPrompt
        ],
        [
            'role' => 'user',
            'content' => $userMessage
        ]
    ],
    'think' => false,
    'stream' => false,
    'options' => [
        'temperature' => 0.7
    ]
];

// Initialize cURL
$ch = curl_init($ollamaUrl);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($requestData),
    CURLOPT_TIMEOUT => 120,  // Increased timeout for slower responses
    CURLOPT_CONNECTTIMEOUT => 10
]);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

// Clear any buffered output
ob_end_clean();

// Handle errors
if ($curlError) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to connect to AI service',
        'details' => $curlError,
        'message' => "I'm having trouble connecting to the AI service. Please make sure Ollama is running with: ollama serve"
    ]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'AI service returned an error',
        'httpCode' => $httpCode,
        'details' => $response,
        'message' => "The AI service returned an error. Please check if the qwen3:0.6b model is installed."
    ]);
    exit;
}

// Parse the response (native Ollama API format)
$responseData = json_decode($response, true);

// Native Ollama API returns message.content (not choices[0].message.content)
if (!isset($responseData['message']['content'])) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid response from AI service',
        'details' => $responseData,
        'message' => "Received an unexpected response from the AI. Please try again."
    ]);
    exit;
}

$assistantMessage = $responseData['message']['content'];

// Clean up the response - remove thinking tags if present (qwen3 sometimes includes these)
$assistantMessage = preg_replace('/<think>.*?<\/think>/s', '', $assistantMessage);
$assistantMessage = trim($assistantMessage);

// Return the response
echo json_encode([
    'success' => true,
    'message' => $assistantMessage
]);
