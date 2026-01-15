<?php
/**
 * Schema Update Runner
 *
 * Updates an existing database to match the current expected schema.
 * Safe to run multiple times (idempotent checks for columns/indexes).
 */

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: text/plain');

echo "Running schema update...\n\n";

if (!$pdo) {
    echo "Error: Database connection failed.\n";
    echo "Check includes/config.php (DB_HOST/DB_NAME/DB_USER/DB_PASS).\n";
    exit(1);
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    // Some MariaDB/MySQL configurations don't accept placeholders in SHOW statements.
    $quoted = $pdo->quote($column);
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE {$quoted}");
    return $stmt ? (bool)$stmt->fetch() : false;
}

function indexExists(PDO $pdo, string $table, string $indexName): bool {
    $quoted = $pdo->quote($indexName);
    $stmt = $pdo->query("SHOW INDEX FROM `{$table}` WHERE Key_name = {$quoted}");
    return $stmt ? (bool)$stmt->fetch() : false;
}

function addColumn(PDO $pdo, string $table, string $column, string $definitionSql): void {
    if (columnExists($pdo, $table, $column)) {
        echo "- {$table}.{$column} already exists\n";
        return;
    }

    $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definitionSql}";
    $pdo->exec($sql);
    echo "✓ Added {$table}.{$column}\n";
}

function addIndex(PDO $pdo, string $table, string $indexName, string $columnsSql): void {
    if (indexExists($pdo, $table, $indexName)) {
        echo "- {$table} index {$indexName} already exists\n";
        return;
    }

    $sql = "CREATE INDEX `{$indexName}` ON `{$table}` ({$columnsSql})";
    $pdo->exec($sql);
    echo "✓ Added {$table} index {$indexName}\n";
}

try {
    // USERS: verification columns
    addColumn($pdo, 'users', 'verification_method', "ENUM('email','phone') NULL");
    addColumn($pdo, 'users', 'verification_token', "VARCHAR(255) NULL");
    addColumn($pdo, 'users', 'verification_token_expires_at', "TIMESTAMP NULL");
    addIndex($pdo, 'users', 'idx_verification_token', '`verification_token`');

    // USERS: email verification columns for existing users
    addColumn($pdo, 'users', 'email_verify_token', "VARCHAR(255) NULL");
    addColumn($pdo, 'users', 'email_verify_expires', "DATETIME NULL");

    // USERS: password reset columns
    addColumn($pdo, 'users', 'password_reset_token_hash', "CHAR(64) NULL");
    addColumn($pdo, 'users', 'password_reset_expires_at', "TIMESTAMP NULL");
    addIndex($pdo, 'users', 'idx_password_reset_token_hash', '`password_reset_token_hash`');

    // ORDERS: customer + confirmation columns
    addColumn($pdo, 'orders', 'first_name', "VARCHAR(50) NULL");
    addColumn($pdo, 'orders', 'last_name', "VARCHAR(50) NULL");
    addColumn($pdo, 'orders', 'email', "VARCHAR(100) NULL");
    addColumn($pdo, 'orders', 'phone', "VARCHAR(20) NULL");

    addColumn($pdo, 'orders', 'confirmation_method', "ENUM('sms','email') DEFAULT NULL");
    addColumn($pdo, 'orders', 'confirmation_token', "VARCHAR(64) DEFAULT NULL");
    addColumn($pdo, 'orders', 'confirmed_at', "TIMESTAMP NULL");

    addIndex($pdo, 'orders', 'idx_confirmation_token', '`confirmation_token`');
    addIndex($pdo, 'orders', 'idx_orders_phone_status', '`phone`, `status`');

    // CART: indexes
    // Note: cart.session_id and indexes on user_id/cart_id were removed as redundant/unused.
    addIndex($pdo, 'cart_items', 'idx_cart_items_product', '`product_id`');

    // PAYPAL: ensure transaction_type exists and has a default
    if (!columnExists($pdo, 'paypal_transactions', 'transaction_type')) {
        $pdo->exec("ALTER TABLE `paypal_transactions` ADD COLUMN `transaction_type` ENUM('create_order','capture','refund','webhook') NOT NULL DEFAULT 'capture' AFTER `currency`");
        echo "✓ Added paypal_transactions.transaction_type\n";
    } else {
        // Try to enforce a default so inserts that omit it don't fail.
        // (If your MySQL version doesn't like this exact MODIFY, it will be reported.)
        try {
            $pdo->exec("ALTER TABLE `paypal_transactions` MODIFY COLUMN `transaction_type` ENUM('create_order','capture','refund','webhook') NOT NULL DEFAULT 'capture'");
            echo "✓ Ensured default for paypal_transactions.transaction_type\n";
        } catch (PDOException $e) {
            echo "! Could not modify paypal_transactions.transaction_type (non-fatal): {$e->getMessage()}\n";
        }
    }

    echo "\n✓ Schema update completed.\n";
    exit(0);

} catch (PDOException $e) {
    echo "\nMigration error: {$e->getMessage()}\n";
    exit(1);
}
