<?php
/**
 * Cleanup Unused Database Columns Migration
 * 
 * This script removes unused columns from the database:
 * - orders.payment_method (PayPal is the only payment method)
 * - orders.paid_at (not used anywhere in the codebase)
 * 
 * Run this script from the command line:
 * php database/run_cleanup_migration.php
 */

require_once __DIR__ . '/../includes/config.php';

echo "===========================================\n";
echo "  Cleanup Unused Database Columns\n";
echo "===========================================\n\n";

if (!$pdo) {
    die("Error: Database connection failed.\n");
}

$columnsRemoved = 0;

// 1. Remove payment_method column
try {
    $checkStmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
    if ($checkStmt->rowCount() > 0) {
        echo "Removing 'payment_method' column from orders table...\n";
        $pdo->exec("ALTER TABLE orders DROP COLUMN payment_method");
        echo "✓ Successfully removed payment_method column!\n\n";
        $columnsRemoved++;
    } else {
        echo "Column 'payment_method' does not exist. Skipping.\n\n";
    }
} catch (PDOException $e) {
    echo "✗ Error removing payment_method: " . $e->getMessage() . "\n\n";
}

// 2. Remove paid_at column
try {
    $checkStmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'paid_at'");
    if ($checkStmt->rowCount() > 0) {
        echo "Removing 'paid_at' column from orders table...\n";
        $pdo->exec("ALTER TABLE orders DROP COLUMN paid_at");
        echo "✓ Successfully removed paid_at column!\n\n";
        $columnsRemoved++;
    } else {
        echo "Column 'paid_at' does not exist. Skipping.\n\n";
    }
} catch (PDOException $e) {
    echo "✗ Error removing paid_at: " . $e->getMessage() . "\n\n";
}

// Show current orders table structure
echo "-------------------------------------------\n";
echo "Current 'orders' table structure:\n";
echo "-------------------------------------------\n";
try {
    $columns = $pdo->query("SHOW COLUMNS FROM orders");
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
} catch (PDOException $e) {
    echo "Error fetching table structure: " . $e->getMessage() . "\n";
}

echo "\n===========================================\n";
echo "  Migration Complete!\n";
echo "  Columns removed: $columnsRemoved\n";
echo "===========================================\n";
?>
