<?php
/**
 * Cleanup Migration: Remove unused database tables and columns
 * 
 * Removes:
 * 1. newsletter_subscribers table (unused)
 * 2. users table columns: address, city, state, zip (unused, never populated)
 * 3. cart table column: session_id (unused)
 */

require_once __DIR__ . '/../includes/config.php';

if (!$pdo) {
    die("Error: Could not connect to database.\n");
}

echo "Starting cleanup migration...\n\n";

try {
    // 1. Drop newsletter_subscribers table
    echo "1. Checking newsletter_subscribers table...\n";
    $pdo->exec("DROP TABLE IF EXISTS newsletter_subscribers");
    echo "   ✓ Dropped or verified nonexistent\n\n";

    // 2. Drop unused columns from users table
    $userColumns = ['address', 'city', 'state', 'zip'];
    echo "2. Cleaning users table...\n";
    
    foreach ($userColumns as $col) {
        $check = $pdo->query("SHOW COLUMNS FROM users LIKE '$col'");
        if ($check->fetch()) {
            $pdo->exec("ALTER TABLE users DROP COLUMN $col");
            echo "   ✓ Dropped column: $col\n";
        } else {
            echo "   - Column $col already gone\n";
        }
    }
    echo "\n";

    // 3. Drop unused column from cart table
    echo "3. Cleaning cart table...\n";
    $check = $pdo->query("SHOW COLUMNS FROM cart LIKE 'session_id'");
    if ($check->fetch()) {
        $pdo->exec("ALTER TABLE cart DROP COLUMN session_id");
        echo "   ✓ Dropped column: session_id\n";
    } else {
        echo "   - Column session_id already gone\n";
    }
    echo "\n";

    echo "Cleanup completed successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
