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
global $conn;

echo "===========================================\n";
echo "Bake & Take - Comprehensive Database Cleanup\n";
echo "===========================================\n\n";

if (!$conn) {
    die("ERROR: Database connection failed.\n");
}

$changes = [];

// =====================================================
// STEP 1: DROP UNUSED TABLES
// =====================================================

// Drop contact_messages table
echo "1. Checking contact_messages table...\n";
$check = mysqli_query($conn, "SHOW TABLES LIKE 'contact_messages'");
if (mysqli_num_rows($check) > 0) {
    if (mysqli_query($conn, "DROP TABLE contact_messages")) {
        echo "   ✓ Dropped contact_messages table\n";
        $changes[] = "Dropped contact_messages table";
    } else {
        echo "   ✗ Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "   - Table doesn't exist, skipping\n";
}
mysqli_free_result($check);

// Drop newsletter_subscribers table
echo "2. Checking newsletter_subscribers table...\n";
$check = mysqli_query($conn, "SHOW TABLES LIKE 'newsletter_subscribers'");
if (mysqli_num_rows($check) > 0) {
    if (mysqli_query($conn, "DROP TABLE newsletter_subscribers")) {
        echo "   ✓ Dropped newsletter_subscribers table\n";
        $changes[] = "Dropped newsletter_subscribers table";
    } else {
        echo "   ✗ Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "   - Table doesn't exist, skipping\n";
}
mysqli_free_result($check);

// =====================================================
// STEP 2: DROP UNUSED COLUMNS FROM USERS TABLE
// =====================================================

$userColumns = ['address', 'city', 'state', 'zip'];
echo "\n3. Checking unused columns in users table...\n";

foreach ($userColumns as $col) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE '$col'");
    if (mysqli_num_rows($check) > 0) {
        if (mysqli_query($conn, "ALTER TABLE users DROP COLUMN $col")) {
            echo "   ✓ Dropped users.$col column\n";
            $changes[] = "Dropped users.$col column";
        } else {
            echo "   ✗ Error dropping users.$col: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "   - Column users.$col doesn't exist, skipping\n";
    }
    mysqli_free_result($check);
}

// =====================================================
// STEP 3: DROP UNUSED COLUMNS FROM CART TABLE
// =====================================================

echo "\n4. Checking session_id column in cart table...\n";
$check = mysqli_query($conn, "SHOW COLUMNS FROM cart LIKE 'session_id'");
if (mysqli_num_rows($check) > 0) {
    if (mysqli_query($conn, "ALTER TABLE cart DROP COLUMN session_id")) {
        echo "   ✓ Dropped cart.session_id column\n";
        $changes[] = "Dropped cart.session_id column";
    } else {
        echo "   ✗ Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "   - Column doesn't exist, skipping\n";
}
mysqli_free_result($check);

// Drop legacy cart indexes that are redundant with existing UNIQUE keys
echo "\n4b. Checking redundant indexes on cart tables...\n";
$check = mysqli_query($conn, "SHOW INDEX FROM cart WHERE Key_name = 'idx_cart_user'");
if (mysqli_num_rows($check) > 0) {
    if (mysqli_query($conn, "DROP INDEX idx_cart_user ON cart")) {
        echo "   ✓ Dropped cart.idx_cart_user index\n";
        $changes[] = "Dropped cart.idx_cart_user index";
    } else {
        echo "   ✗ Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "   - Index cart.idx_cart_user doesn't exist, skipping\n";
}
mysqli_free_result($check);

$check = mysqli_query($conn, "SHOW INDEX FROM cart_items WHERE Key_name = 'idx_cart_items_cart'");
if (mysqli_num_rows($check) > 0) {
    if (mysqli_query($conn, "DROP INDEX idx_cart_items_cart ON cart_items")) {
        echo "   ✓ Dropped cart_items.idx_cart_items_cart index\n";
        $changes[] = "Dropped cart_items.idx_cart_items_cart index";
    } else {
        echo "   ✗ Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "   - Index cart_items.idx_cart_items_cart doesn't exist, skipping\n";
}
mysqli_free_result($check);

// Drop legacy cart_items.price column (price is read from products)
echo "\n4c. Checking price column in cart_items table...\n";
$check = mysqli_query($conn, "SHOW COLUMNS FROM cart_items LIKE 'price'");
if (mysqli_num_rows($check) > 0) {
    if (mysqli_query($conn, "ALTER TABLE cart_items DROP COLUMN price")) {
        echo "   ✓ Dropped cart_items.price column\n";
        $changes[] = "Dropped cart_items.price column";
    } else {
        echo "   ✗ Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "   - Column cart_items.price doesn't exist, skipping\n";
}
mysqli_free_result($check);

// =====================================================
// STEP 4: DROP UNUSED COLUMNS FROM ORDERS TABLE
// =====================================================

echo "\n5. Checking unused columns in orders table...\n";

$check = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'payment_method'");
if (mysqli_num_rows($check) > 0) {
    if (mysqli_query($conn, "ALTER TABLE orders DROP COLUMN payment_method")) {
        echo "   ✓ Dropped orders.payment_method column\n";
        $changes[] = "Dropped orders.payment_method column";
    } else {
        echo "   ✗ Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "   - Column orders.payment_method doesn't exist, skipping\n";
}
mysqli_free_result($check);

$check = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'paid_at'");
if (mysqli_num_rows($check) > 0) {
    if (mysqli_query($conn, "ALTER TABLE orders DROP COLUMN paid_at")) {
        echo "   ✓ Dropped orders.paid_at column\n";
        $changes[] = "Dropped orders.paid_at column";
    } else {
        echo "   ✗ Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "   - Column orders.paid_at doesn't exist, skipping\n";
}
mysqli_free_result($check);

// =====================================================
// STEP 4b: DROP UNUSED COLUMNS FROM CATEGORIES TABLE
// =====================================================

echo "\n5b. Checking unused columns in categories table...\n";
$check = mysqli_query($conn, "SHOW COLUMNS FROM categories LIKE 'description'");
if (mysqli_num_rows($check) > 0) {
    if (mysqli_query($conn, "ALTER TABLE categories DROP COLUMN description")) {
        echo "   ✓ Dropped categories.description column\n";
        $changes[] = "Dropped categories.description column";
    } else {
        echo "   ✗ Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "   - Column categories.description doesn't exist, skipping\n";
}
mysqli_free_result($check);

// =====================================================
// STEP 5a: DROP UNUSED COLUMNS FROM SMS_LOG TABLE
// =====================================================

echo "\n5c. Checking unused columns in sms_log table...\n";
$check = mysqli_query($conn, "SHOW COLUMNS FROM sms_log LIKE 'user_id'");
if ($check && mysqli_num_rows($check) > 0) {
    // Drop any foreign key that references sms_log.user_id (constraint name varies by DB).
    $fkStmt = mysqli_query(
        $conn,
        "SELECT CONSTRAINT_NAME " .
        "FROM information_schema.KEY_COLUMN_USAGE " .
        "WHERE TABLE_SCHEMA = DATABASE() " .
        "AND TABLE_NAME = 'sms_log' " .
        "AND COLUMN_NAME = 'user_id' " .
        "AND REFERENCED_TABLE_NAME IS NOT NULL"
    );
    if ($fkStmt) {
        while ($fkRow = mysqli_fetch_assoc($fkStmt)) {
            $fkName = $fkRow['CONSTRAINT_NAME'];
            if (!empty($fkName)) {
                if (mysqli_query($conn, "ALTER TABLE sms_log DROP FOREIGN KEY `{$fkName}`")) {
                    echo "   ✓ Dropped sms_log foreign key {$fkName}\n";
                    $changes[] = "Dropped sms_log foreign key {$fkName}";
                } else {
                    echo "   ! Could not drop sms_log foreign key {$fkName}: " . mysqli_error($conn) . "\n";
                }
            }
        }
        mysqli_free_result($fkStmt);
    }

    if (mysqli_query($conn, "ALTER TABLE sms_log DROP COLUMN user_id")) {
        echo "   ✓ Dropped sms_log.user_id column\n";
        $changes[] = "Dropped sms_log.user_id column";
    } else {
        echo "   ✗ Error dropping sms_log.user_id: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "   - Column sms_log.user_id doesn't exist, skipping\n";
}
if ($check) mysqli_free_result($check);

// =====================================================
// STEP 5: DROP UNUSED COLUMNS FROM PAYPAL_TRANSACTIONS
// =====================================================

echo "\n6. Checking unused columns in paypal_transactions table...\n";

// These columns were previously present for verbose logging but are not referenced by the codebase.
$paypalColumns = ['request_data', 'response_data', 'error_message'];

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'paypal_transactions'");
if (mysqli_num_rows($tableCheck) === 0) {
    echo "   - Table paypal_transactions doesn't exist, skipping\n";
} else {
    foreach ($paypalColumns as $col) {
        $check = mysqli_query($conn, "SHOW COLUMNS FROM paypal_transactions LIKE '{$col}'");
        if (mysqli_num_rows($check) > 0) {
            if (mysqli_query($conn, "ALTER TABLE paypal_transactions DROP COLUMN {$col}")) {
                echo "   ✓ Dropped paypal_transactions.{$col} column\n";
                $changes[] = "Dropped paypal_transactions.{$col} column";
            } else {
                echo "   ✗ Error: " . mysqli_error($conn) . "\n";
            }
        } else {
            echo "   - Column paypal_transactions.{$col} doesn't exist, skipping\n";
        }
        mysqli_free_result($check);
    }
}
mysqli_free_result($tableCheck);

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
?>
