<?php
/**
 * Run Comprehensive Database Cleanup Migration
 * 
 * This script safely removes all unused tables and columns:
 * - Tables: contact_messages, newsletter_subscribers
 * - Columns: users.address/city/state/zip, cart.session_id, orders.payment_method/paid_at
 * 
 * Run from command line: php run_cleanup_all.php
 */

require_once __DIR__ . '/../includes/config.php';

echo "===========================================\n";
echo "Bake & Take - Comprehensive Database Cleanup\n";
echo "===========================================\n\n";

if (!$pdo) {
    die("ERROR: Database connection failed.\n");
}

$changes = [];

try {
    // =====================================================
    // STEP 1: DROP UNUSED TABLES
    // =====================================================
    
    // Drop contact_messages table
    echo "1. Checking contact_messages table...\n";
    $check = $pdo->query("SHOW TABLES LIKE 'contact_messages'");
    if ($check->rowCount() > 0) {
        $pdo->exec("DROP TABLE contact_messages");
        echo "   ✓ Dropped contact_messages table\n";
        $changes[] = "Dropped contact_messages table";
    } else {
        echo "   - Table doesn't exist, skipping\n";
    }
    
    // Drop newsletter_subscribers table
    echo "2. Checking newsletter_subscribers table...\n";
    $check = $pdo->query("SHOW TABLES LIKE 'newsletter_subscribers'");
    if ($check->rowCount() > 0) {
        $pdo->exec("DROP TABLE newsletter_subscribers");
        echo "   ✓ Dropped newsletter_subscribers table\n";
        $changes[] = "Dropped newsletter_subscribers table";
    } else {
        echo "   - Table doesn't exist, skipping\n";
    }
    
    // =====================================================
    // STEP 2: DROP UNUSED COLUMNS FROM USERS TABLE
    // =====================================================
    
    $userColumns = ['address', 'city', 'state', 'zip'];
    echo "\n3. Checking unused columns in users table...\n";
    
    foreach ($userColumns as $col) {
        $check = $pdo->query("SHOW COLUMNS FROM users LIKE '$col'");
        if ($check->rowCount() > 0) {
            $pdo->exec("ALTER TABLE users DROP COLUMN $col");
            echo "   ✓ Dropped users.$col column\n";
            $changes[] = "Dropped users.$col column";
        } else {
            echo "   - Column users.$col doesn't exist, skipping\n";
        }
    }
    
    // =====================================================
    // STEP 3: DROP UNUSED COLUMNS FROM CART TABLE
    // =====================================================
    
    echo "\n4. Checking session_id column in cart table...\n";
    $check = $pdo->query("SHOW COLUMNS FROM cart LIKE 'session_id'");
    if ($check->rowCount() > 0) {
        $pdo->exec("ALTER TABLE cart DROP COLUMN session_id");
        echo "   ✓ Dropped cart.session_id column\n";
        $changes[] = "Dropped cart.session_id column";
    } else {
        echo "   - Column doesn't exist, skipping\n";
    }
    
    // =====================================================
    // STEP 4: DROP UNUSED COLUMNS FROM ORDERS TABLE
    // =====================================================
    
    echo "\n5. Checking unused columns in orders table...\n";
    
    $check = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
    if ($check->rowCount() > 0) {
        $pdo->exec("ALTER TABLE orders DROP COLUMN payment_method");
        echo "   ✓ Dropped orders.payment_method column\n";
        $changes[] = "Dropped orders.payment_method column";
    } else {
        echo "   - Column orders.payment_method doesn't exist, skipping\n";
    }
    
    $check = $pdo->query("SHOW COLUMNS FROM orders LIKE 'paid_at'");
    if ($check->rowCount() > 0) {
        $pdo->exec("ALTER TABLE orders DROP COLUMN paid_at");
        echo "   ✓ Dropped orders.paid_at column\n";
        $changes[] = "Dropped orders.paid_at column";
    } else {
        echo "   - Column orders.paid_at doesn't exist, skipping\n";
    }
    
    // =====================================================
    // SUMMARY
    // =====================================================
    
    echo "\n===========================================\n";
    echo "CLEANUP COMPLETE!\n";
    echo "===========================================\n";
    
    if (count($changes) > 0) {
        echo "\nChanges made:\n";
        foreach ($changes as $change) {
            echo "  • $change\n";
        }
    } else {
        echo "\nNo changes were needed - database is already clean.\n";
    }
    
    echo "\nTotal changes: " . count($changes) . "\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
