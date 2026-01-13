<?php
/**
 * Run Order Confirmation Migration
 * 
 * This script adds the order confirmation fields to the database.
 * Run this once to set up the new columns.
 */

require_once __DIR__ . '/../includes/config.php';

echo "Running order confirmation migration...\n\n";

if (!$pdo) {
    die("Error: Database connection failed.\n");
}

try {
    // Check if confirmation_method column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'confirmation_method'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "Migration already applied - confirmation_method column exists.\n";
    } else {
        // Add confirmation_method column
        $pdo->exec("ALTER TABLE orders ADD COLUMN confirmation_method ENUM('sms', 'email') DEFAULT NULL AFTER status");
        echo "✓ Added confirmation_method column\n";
    }
    
    // Check if confirmation_token column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'confirmation_token'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "confirmation_token column already exists.\n";
    } else {
        // Add confirmation_token column
        $pdo->exec("ALTER TABLE orders ADD COLUMN confirmation_token VARCHAR(64) DEFAULT NULL AFTER confirmation_method");
        echo "✓ Added confirmation_token column\n";
    }
    
    // Check if confirmed_at column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'confirmed_at'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "confirmed_at column already exists.\n";
    } else {
        // Add confirmed_at column
        $pdo->exec("ALTER TABLE orders ADD COLUMN confirmed_at TIMESTAMP NULL AFTER confirmation_token");
        echo "✓ Added confirmed_at column\n";
    }
    
    // Add indexes
    try {
        $pdo->exec("CREATE INDEX idx_confirmation_token ON orders(confirmation_token)");
        echo "✓ Added idx_confirmation_token index\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "idx_confirmation_token index already exists.\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_pending_phone ON orders(phone, status)");
        echo "✓ Added idx_pending_phone index\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "idx_pending_phone index already exists.\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n✓ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    die("Migration error: " . $e->getMessage() . "\n");
}
?>
