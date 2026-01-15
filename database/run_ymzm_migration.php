<?php
/**
 * Run YMZM Migration
 * Adds ymzm_user_id column to users table for YMZM login integration
 */

require_once __DIR__ . '/../includes/config.php';
global $conn;

echo "Running YMZM Integration Migration...\n";
echo "====================================\n\n";

if (!$conn) {
    die("Error: Database connection not available.\n");
}

// Check if column already exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'ymzm_user_id'");
$columnExists = mysqli_fetch_assoc($result);
mysqli_free_result($result);

if ($columnExists) {
    echo "Column 'ymzm_user_id' already exists in users table.\n";
    echo "Migration already applied. Skipping...\n";
} else {
    // Add the column
    echo "Adding 'ymzm_user_id' column to users table...\n";
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN ymzm_user_id INT NULL AFTER is_admin")) {
        echo "Column added successfully!\n";
    } else {
        echo "Error adding column: " . mysqli_error($conn) . "\n";
        exit(1);
    }
    
    // Add unique index
    echo "Adding unique index for ymzm_user_id...\n";
    if (mysqli_query($conn, "ALTER TABLE users ADD UNIQUE INDEX idx_ymzm_user_id (ymzm_user_id)")) {
        echo "Index added successfully!\n";
    } else {
        echo "Error adding index: " . mysqli_error($conn) . "\n";
        exit(1);
    }
}

echo "\n====================================\n";
echo "YMZM Integration Migration Complete!\n";
echo "\nYou can now use 'Login with YMZM' on the login page.\n";
echo "Make sure to configure YMZM_API_URL in includes/secrets.php\n";
?>
