<?php
/**
 * Schema Update Runner
 *
 * Updates an existing database to match the current expected schema.
 * Safe to run multiple times (idempotent checks for columns/indexes).
 */

require_once __DIR__ . '/../includes/config.php';
global $conn;

header('Content-Type: text/plain');

echo "Running schema update...\n\n";

if (!$conn) {
    echo "Error: Database connection failed.\n";
    echo "Check includes/config.php (DB_HOST/DB_NAME/DB_USER/DB_PASS).\n";
    exit(1);
}

function columnExists(mysqli $conn, string $table, string $column): bool {
    $escaped = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `{$table}` LIKE '{$escaped}'");
    $exists = $result ? (bool)mysqli_fetch_assoc($result) : false;
    if ($result) mysqli_free_result($result);
    return $exists;
}

function indexExists(mysqli $conn, string $table, string $indexName): bool {
    $escaped = mysqli_real_escape_string($conn, $indexName);
    $result = mysqli_query($conn, "SHOW INDEX FROM `{$table}` WHERE Key_name = '{$escaped}'");
    $exists = $result ? (bool)mysqli_fetch_assoc($result) : false;
    if ($result) mysqli_free_result($result);
    return $exists;
}

function addColumn(mysqli $conn, string $table, string $column, string $definitionSql): void {
    if (columnExists($conn, $table, $column)) {
        echo "- {$table}.{$column} already exists\n";
        return;
    }

    $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definitionSql}";
    if (mysqli_query($conn, $sql)) {
        echo "✓ Added {$table}.{$column}\n";
    } else {
        echo "✗ Failed to add {$table}.{$column}: " . mysqli_error($conn) . "\n";
    }
}

function addIndex(mysqli $conn, string $table, string $indexName, string $columnsSql): void {
    if (indexExists($conn, $table, $indexName)) {
        echo "- {$table} index {$indexName} already exists\n";
        return;
    }

    $sql = "CREATE INDEX `{$indexName}` ON `{$table}` ({$columnsSql})";
    if (mysqli_query($conn, $sql)) {
        echo "✓ Added {$table} index {$indexName}\n";
    } else {
        echo "✗ Failed to add {$table} index {$indexName}: " . mysqli_error($conn) . "\n";
    }
}

// USERS: verification columns
addColumn($conn, 'users', 'verification_method', "ENUM('email','phone') NULL");
addColumn($conn, 'users', 'verification_token', "VARCHAR(255) NULL");
addColumn($conn, 'users', 'verification_token_expires_at', "TIMESTAMP NULL");
addIndex($conn, 'users', 'idx_verification_token', '`verification_token`');

// USERS: email verification columns for existing users
addColumn($conn, 'users', 'email_verify_token', "VARCHAR(255) NULL");
addColumn($conn, 'users', 'email_verify_expires', "DATETIME NULL");

// USERS: password reset columns
addColumn($conn, 'users', 'password_reset_token_hash', "CHAR(64) NULL");
addColumn($conn, 'users', 'password_reset_expires_at', "TIMESTAMP NULL");
addIndex($conn, 'users', 'idx_password_reset_token_hash', '`password_reset_token_hash`');

// ORDERS: customer + confirmation columns
addColumn($conn, 'orders', 'first_name', "VARCHAR(50) NULL");
addColumn($conn, 'orders', 'last_name', "VARCHAR(50) NULL");
addColumn($conn, 'orders', 'email', "VARCHAR(100) NULL");
addColumn($conn, 'orders', 'phone', "VARCHAR(20) NULL");

addColumn($conn, 'orders', 'confirmation_method', "ENUM('sms','email') DEFAULT NULL");
addColumn($conn, 'orders', 'confirmation_token', "VARCHAR(64) DEFAULT NULL");
addColumn($conn, 'orders', 'confirmed_at', "TIMESTAMP NULL");

addIndex($conn, 'orders', 'idx_confirmation_token', '`confirmation_token`');
addIndex($conn, 'orders', 'idx_orders_phone_status', '`phone`, `status`');

// CART: indexes
// Note: cart.session_id and indexes on user_id/cart_id were removed as redundant/unused.
addIndex($conn, 'cart_items', 'idx_cart_items_product', '`product_id`');

// PAYPAL: ensure transaction_type exists and has a default
if (!columnExists($conn, 'paypal_transactions', 'transaction_type')) {
    if (mysqli_query($conn, "ALTER TABLE `paypal_transactions` ADD COLUMN `transaction_type` ENUM('create_order','capture','refund','webhook') NOT NULL DEFAULT 'capture' AFTER `currency`")) {
        echo "✓ Added paypal_transactions.transaction_type\n";
    } else {
        echo "✗ Failed to add paypal_transactions.transaction_type: " . mysqli_error($conn) . "\n";
    }
} else {
    // Try to enforce a default so inserts that omit it don't fail.
    if (mysqli_query($conn, "ALTER TABLE `paypal_transactions` MODIFY COLUMN `transaction_type` ENUM('create_order','capture','refund','webhook') NOT NULL DEFAULT 'capture'")) {
        echo "✓ Ensured default for paypal_transactions.transaction_type\n";
    } else {
        echo "! Could not modify paypal_transactions.transaction_type (non-fatal): " . mysqli_error($conn) . "\n";
    }
}

echo "\n✓ Schema update completed.\n";
exit(0);

