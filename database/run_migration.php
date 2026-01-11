<?php
/**
 * Migration: Remove Payment Method Column
 * Run this file once to update the database
 */

require_once __DIR__ . '/../includes/config.php';

if (!$pdo) {
    die("Error: Could not connect to database.\n");
}

echo "Starting migration: Remove payment_method column...\n\n";

try {
    // Check if column exists first
    $checkStmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
    $column = $checkStmt->fetch();
    
    if ($column) {
        // Run the migration
        $pdo->exec("ALTER TABLE orders DROP COLUMN payment_method");
        
        echo "✓ Successfully removed payment_method column!\n";
        echo "  - All orders are now PayPal only\n\n";
    } else {
        echo "Column 'payment_method' does not exist. Nothing to do.\n\n";
    }
    
    echo "Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
