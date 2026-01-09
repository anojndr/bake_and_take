<?php
/**
 * Bake & Take - Configuration File
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'bake_and_take');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site Configuration
define('SITE_NAME', 'Bake & Take');
define('SITE_URL', 'http://localhost/bake_and_take');
define('SITE_EMAIL', 'hello@bakeandtake.com');

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_FROM_NAME', 'Bake & Take');

// Load secrets
if (file_exists(__DIR__ . '/secrets.php')) {
    require_once __DIR__ . '/secrets.php';
} else {
    define('SMTP_USER', '');
    define('SMTP_PASS', '');
}

// Timezone
date_default_timezone_set('America/New_York');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // For development, we'll continue without database
    $pdo = null;
}

// Product Categories
$CATEGORIES = [
    'breads' => ['name' => 'Artisan Breads', 'icon' => 'bi-basket'],
    'pastries' => ['name' => 'Pastries', 'icon' => 'bi-egg-fried'],
    'cakes' => ['name' => 'Cakes', 'icon' => 'bi-cake2'],
    'cookies' => ['name' => 'Cookies & Treats', 'icon' => 'bi-cookie']
];

// Sample Products (for development without database)
$PRODUCTS = [
    [
        'id' => 1,
        'name' => 'Sourdough Loaf',
        'category' => 'breads',
        'price' => 8.99,
        'description' => 'Traditional sourdough with a crispy crust and chewy interior. Fermented for 24 hours.',
        'image' => 'sourdough.jpg',
        'featured' => true
    ],
    [
        'id' => 2,
        'name' => 'Butter Croissant',
        'category' => 'pastries',
        'price' => 4.50,
        'description' => 'Flaky, buttery layers made with authentic French technique.',
        'image' => 'croissant.jpg',
        'featured' => true
    ],
    [
        'id' => 3,
        'name' => 'Red Velvet Cake',
        'category' => 'cakes',
        'price' => 45.00,
        'description' => 'Classic red velvet with cream cheese frosting. Serves 8-10.',
        'image' => 'red-velvet.jpg',
        'featured' => true
    ],
    [
        'id' => 4,
        'name' => 'Chocolate Chip Cookies',
        'category' => 'cookies',
        'price' => 3.50,
        'description' => 'Chewy cookies loaded with premium dark chocolate chips.',
        'image' => 'chocolate-chip.jpg',
        'featured' => true
    ],
    [
        'id' => 5,
        'name' => 'Baguette',
        'category' => 'breads',
        'price' => 5.99,
        'description' => 'Classic French baguette with a golden crust.',
        'image' => 'baguette.jpg',
        'featured' => false
    ],
    [
        'id' => 6,
        'name' => 'Pain au Chocolat',
        'category' => 'pastries',
        'price' => 4.99,
        'description' => 'Buttery pastry with rich dark chocolate bars inside.',
        'image' => 'pain-chocolat.jpg',
        'featured' => true
    ],
    [
        'id' => 7,
        'name' => 'Carrot Cake',
        'category' => 'cakes',
        'price' => 42.00,
        'description' => 'Moist carrot cake with cream cheese frosting and walnuts.',
        'image' => 'carrot-cake.jpg',
        'featured' => false
    ],
    [
        'id' => 8,
        'name' => 'Macarons Box',
        'category' => 'cookies',
        'price' => 18.00,
        'description' => 'Assorted French macarons in various flavors. Box of 12.',
        'image' => 'macarons.jpg',
        'featured' => true
    ],
    [
        'id' => 9,
        'name' => 'Ciabatta',
        'category' => 'breads',
        'price' => 6.50,
        'description' => 'Italian bread with large air pockets and crispy crust.',
        'image' => 'ciabatta.jpg',
        'featured' => false
    ],
    [
        'id' => 10,
        'name' => 'Danish Pastry',
        'category' => 'pastries',
        'price' => 5.25,
        'description' => 'Fruit-topped danish with vanilla custard.',
        'image' => 'danish.jpg',
        'featured' => false
    ],
    [
        'id' => 11,
        'name' => 'Chocolate Truffle Cake',
        'category' => 'cakes',
        'price' => 55.00,
        'description' => 'Decadent chocolate cake with ganache. Serves 10-12.',
        'image' => 'truffle-cake.jpg',
        'featured' => true
    ],
    [
        'id' => 12,
        'name' => 'Oatmeal Raisin Cookies',
        'category' => 'cookies',
        'price' => 3.25,
        'description' => 'Wholesome oatmeal cookies with plump raisins.',
        'image' => 'oatmeal-raisin.jpg',
        'featured' => false
    ]
];
?>
