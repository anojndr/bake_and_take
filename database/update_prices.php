<?php
/**
 * Update Product Prices to Philippine Peso
 * Run this script once to update all product prices in your database
 */

require_once __DIR__ . '/../includes/config.php';
global $conn;

if (!$conn) {
    die("Error: Database connection not available.\n");
}

echo "===========================================\n";
echo "  Updating Product Prices to Philippine Peso\n";
echo "===========================================\n\n";

// Price updates based on market research from Philippine bakeries
$priceUpdates = [
    'sourdough-loaf' => 299.00,
    'butter-croissant' => 85.00,
    'red-velvet-cake' => 1350.00,
    'chocolate-chip-cookies' => 85.00,
    'baguette' => 145.00,
    'pain-au-chocolat' => 145.00,
    'carrot-cake' => 1250.00,
    'macarons-box' => 950.00,
    'ciabatta' => 120.00,
    'danish-pastry' => 140.00,
    'chocolate-truffle-cake' => 1700.00,
    'oatmeal-raisin-cookies' => 75.00,
];

$stmt = mysqli_prepare($conn, "UPDATE products SET price = ? WHERE slug = ?");
$successCount = 0;
$errorCount = 0;

foreach ($priceUpdates as $slug => $price) {
    mysqli_stmt_bind_param($stmt, "ds", $price, $slug);
    if (mysqli_stmt_execute($stmt)) {
        $rowsAffected = mysqli_stmt_affected_rows($stmt);
        
        if ($rowsAffected > 0) {
            echo "✓ Updated '{$slug}' to ₱" . number_format($price, 2) . "\n";
            $successCount++;
        } else {
            echo "⚠ Product '{$slug}' not found in database\n";
        }
    } else {
        echo "✗ Error updating '{$slug}': " . mysqli_stmt_error($stmt) . "\n";
        $errorCount++;
    }
}
mysqli_stmt_close($stmt);

echo "\n===========================================\n";
echo "  Summary\n";
echo "===========================================\n";
echo "Updated: {$successCount} products\n";
echo "Errors: {$errorCount}\n\n";

// Show current prices
echo "Current Product Prices:\n";
echo str_repeat("-", 50) . "\n";

$result = mysqli_query($conn, "SELECT id, name, price FROM products ORDER BY id");
while ($product = mysqli_fetch_assoc($result)) {
    echo sprintf("%-30s ₱%s\n", $product['name'], number_format($product['price'], 2));
}
mysqli_free_result($result);

echo "\n✅ Price update complete!\n";
?>
