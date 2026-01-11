<?php
/**
 * Migration: Remove GCash Payment Method
 * Run this file once to update the database
 */

require_once __DIR__ . '/../includes/config.php';

if (!$pdo) {
    die("Error: Could not connect to database.\n");
}

echo "Starting migration: Remove GCash payment method...\n\n";

try {
    // Check for any existing GCash orders
    $checkStmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE payment_method = 'gcash'");
    $gcashCount = $checkStmt->fetch()['count'];
    
    if ($gcashCount > 0) {
        echo "Warning: Found {$gcashCount} orders with 'gcash' payment method.\n";
        echo "These will be converted to 'paypal'.\n\n";
    }
    
    // Run the migration
    $pdo->exec("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('paypal') DEFAULT 'paypal'");
    
    echo "✓ Successfully updated payment_method column!\n";
    echo "  - Removed 'gcash' from allowed payment methods\n";
    echo "  - Only 'paypal' is now a valid option\n\n";
    
    // Verify the change
    $verifyStmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
    $column = $verifyStmt->fetch();
    echo "Verification:\n";
    echo "  Column Type: " . $column['Type'] . "\n";
    echo "  Default: " . $column['Default'] . "\n\n";
    
    echo "Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
