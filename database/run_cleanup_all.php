<?php
/**
 * Run Comprehensive Database Cleanup Migration
 * 
 * This script safely removes all unused tables and columns:
 * - Tables: contact_messages, newsletter_subscribers
 * - Columns: users.address/city/state/zip, cart.session_id, cart_items.price, orders.payment_method/paid_at,
 *            paypal_transactions.request_data/response_data/error_message, categories.description,
 *            sms_log.user_id
 * - Redundant indexes: cart.idx_cart_user, cart_items.idx_cart_items_cart
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

    // Drop legacy cart indexes that are redundant with existing UNIQUE keys
    echo "\n4b. Checking redundant indexes on cart tables...\n";
    $check = $pdo->query("SHOW INDEX FROM cart WHERE Key_name = 'idx_cart_user'");
    if ($check->rowCount() > 0) {
        $pdo->exec("DROP INDEX idx_cart_user ON cart");
        echo "   ✓ Dropped cart.idx_cart_user index\n";
        $changes[] = "Dropped cart.idx_cart_user index";
    } else {
        echo "   - Index cart.idx_cart_user doesn't exist, skipping\n";
    }

    $check = $pdo->query("SHOW INDEX FROM cart_items WHERE Key_name = 'idx_cart_items_cart'");
    if ($check->rowCount() > 0) {
        $pdo->exec("DROP INDEX idx_cart_items_cart ON cart_items");
        echo "   ✓ Dropped cart_items.idx_cart_items_cart index\n";
        $changes[] = "Dropped cart_items.idx_cart_items_cart index";
    } else {
        echo "   - Index cart_items.idx_cart_items_cart doesn't exist, skipping\n";
    }

    // Drop legacy cart_items.price column (price is read from products)
    echo "\n4c. Checking price column in cart_items table...\n";
    $check = $pdo->query("SHOW COLUMNS FROM cart_items LIKE 'price'");
    if ($check->rowCount() > 0) {
        $pdo->exec("ALTER TABLE cart_items DROP COLUMN price");
        echo "   ✓ Dropped cart_items.price column\n";
        $changes[] = "Dropped cart_items.price column";
    } else {
        echo "   - Column cart_items.price doesn't exist, skipping\n";
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
    // STEP 4b: DROP UNUSED COLUMNS FROM CATEGORIES TABLE
    // =====================================================

    echo "\n5b. Checking unused columns in categories table...\n";
    $check = $pdo->query("SHOW COLUMNS FROM categories LIKE 'description'");
    if ($check->rowCount() > 0) {
        $pdo->exec("ALTER TABLE categories DROP COLUMN description");
        echo "   ✓ Dropped categories.description column\n";
        $changes[] = "Dropped categories.description column";
    } else {
        echo "   - Column categories.description doesn't exist, skipping\n";
    }

    // =====================================================
    // STEP 5a: DROP UNUSED COLUMNS FROM SMS_LOG TABLE
    // =====================================================

    echo "\n5c. Checking unused columns in sms_log table...\n";
    $check = $pdo->query("SHOW COLUMNS FROM sms_log LIKE 'user_id'");
    if ($check && $check->rowCount() > 0) {
        // Drop any foreign key that references sms_log.user_id (constraint name varies by DB).
        try {
            $fkStmt = $pdo->query(
                "SELECT CONSTRAINT_NAME " .
                "FROM information_schema.KEY_COLUMN_USAGE " .
                "WHERE TABLE_SCHEMA = DATABASE() " .
                "AND TABLE_NAME = 'sms_log' " .
                "AND COLUMN_NAME = 'user_id' " .
                "AND REFERENCED_TABLE_NAME IS NOT NULL"
            );
            $fkNames = $fkStmt ? $fkStmt->fetchAll(PDO::FETCH_COLUMN) : [];
            foreach ($fkNames as $fkName) {
                if (!empty($fkName)) {
                    $pdo->exec("ALTER TABLE sms_log DROP FOREIGN KEY `{$fkName}`");
                    echo "   ✓ Dropped sms_log foreign key {$fkName}\n";
                    $changes[] = "Dropped sms_log foreign key {$fkName}";
                }
            }
        } catch (PDOException $e) {
            // Non-fatal; proceed to attempt dropping the column anyway.
            echo "   ! Could not inspect/drop sms_log.user_id foreign key (non-fatal): {$e->getMessage()}\n";
        }

        $pdo->exec("ALTER TABLE sms_log DROP COLUMN user_id");
        echo "   ✓ Dropped sms_log.user_id column\n";
        $changes[] = "Dropped sms_log.user_id column";
    } else {
        echo "   - Column sms_log.user_id doesn't exist, skipping\n";
    }

    // =====================================================
    // STEP 5: DROP UNUSED COLUMNS FROM PAYPAL_TRANSACTIONS
    // =====================================================

    echo "\n6. Checking unused columns in paypal_transactions table...\n";

    // These columns were previously present for verbose logging but are not referenced by the codebase.
    $paypalColumns = ['request_data', 'response_data', 'error_message'];

    $tableCheck = $pdo->query("SHOW TABLES LIKE 'paypal_transactions'");
    if ($tableCheck->rowCount() === 0) {
        echo "   - Table paypal_transactions doesn't exist, skipping\n";
    } else {
        foreach ($paypalColumns as $col) {
            $check = $pdo->query("SHOW COLUMNS FROM paypal_transactions LIKE '{$col}'");
            if ($check->rowCount() > 0) {
                $pdo->exec("ALTER TABLE paypal_transactions DROP COLUMN {$col}");
                echo "   ✓ Dropped paypal_transactions.{$col} column\n";
                $changes[] = "Dropped paypal_transactions.{$col} column";
            } else {
                echo "   - Column paypal_transactions.{$col} doesn't exist, skipping\n";
            }
        }
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
