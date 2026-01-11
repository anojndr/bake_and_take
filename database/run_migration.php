<?php
/**
 * Migration: Remove Cash Payment Method
 * Run this file once to update the database
 */

require_once __DIR__ . '/../includes/config.php';

if (!$pdo) {
    die("Error: Could not connect to database.\n");
}

echo "Starting migration: Remove cash payment method...\n\n";

try {
    // Check for any existing cash orders
    $checkStmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE payment_method = 'cash'");
    $cashCount = $checkStmt->fetch()['count'];
    
    if ($cashCount > 0) {
        echo "Warning: Found {$cashCount} orders with 'cash' payment method.\n";
        echo "These will be converted to 'paypal'.\n\n";
    }
    
    // Run the migration
    $pdo->exec("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('paypal', 'gcash') DEFAULT 'paypal'");
    
    echo "✓ Successfully updated payment_method column!\n";
    echo "  - Removed 'cash' from allowed payment methods\n";
    echo "  - Only 'paypal' and 'gcash' are now valid options\n\n";
    
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
