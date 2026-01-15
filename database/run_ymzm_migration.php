<?php
/**
 * Run YMZM Migration
 * Adds ymzm_user_id column to users table for YMZM login integration
 */

require_once __DIR__ . '/../includes/config.php';

echo "Running YMZM Integration Migration...\n";
echo "====================================\n\n";

if (!$pdo) {
    die("Error: Database connection not available.\n");
}

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'ymzm_user_id'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "Column 'ymzm_user_id' already exists in users table.\n";
        echo "Migration already applied. Skipping...\n";
    } else {
        // Add the column
        echo "Adding 'ymzm_user_id' column to users table...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN ymzm_user_id INT NULL AFTER is_admin");
        echo "Column added successfully!\n";
        
        // Add unique index
        echo "Adding unique index for ymzm_user_id...\n";
        $pdo->exec("ALTER TABLE users ADD UNIQUE INDEX idx_ymzm_user_id (ymzm_user_id)");
        echo "Index added successfully!\n";
    }
    
    echo "\n====================================\n";
    echo "YMZM Integration Migration Complete!\n";
    echo "\nYou can now use 'Login with YMZM' on the login page.\n";
    echo "Make sure to configure YMZM_API_URL in includes/secrets.php\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
