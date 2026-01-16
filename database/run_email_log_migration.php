<?php
/**
 * Run Email Log Migration
 * 
 * This script creates the email_log table for tracking all sent emails.
 * Run this script once to set up email logging functionality.
 */

require_once __DIR__ . '/../includes/config.php';

echo "=== Email Log Migration ===\n\n";

// Check if table already exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'email_log'");
if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
    echo "✓ email_log table already exists.\n";
    echo "\nMigration complete!\n";
    exit(0);
}

// Create the email_log table
$sql = "
CREATE TABLE IF NOT EXISTS email_log (
    email_id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body TEXT NOT NULL,
    is_html BOOLEAN DEFAULT TRUE,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    order_id INT NULL,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_recipient (recipient_email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
)
";

if (mysqli_query($conn, $sql)) {
    echo "✓ Created email_log table successfully.\n";
} else {
    echo "✗ Error creating email_log table: " . mysqli_error($conn) . "\n";
    exit(1);
}

// Add foreign key for order_id if orders table exists
$ordersCheck = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
if ($ordersCheck && mysqli_num_rows($ordersCheck) > 0) {
    $fkSql = "ALTER TABLE email_log ADD CONSTRAINT fk_email_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL";
    if (mysqli_query($conn, $fkSql)) {
        echo "✓ Added foreign key constraint for order_id.\n";
    } else {
        echo "! Warning: Could not add foreign key for order_id: " . mysqli_error($conn) . "\n";
    }
}

// Add foreign key for user_id if users table exists
$usersCheck = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if ($usersCheck && mysqli_num_rows($usersCheck) > 0) {
    $fkSql = "ALTER TABLE email_log ADD CONSTRAINT fk_email_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL";
    if (mysqli_query($conn, $fkSql)) {
        echo "✓ Added foreign key constraint for user_id.\n";
    } else {
        echo "! Warning: Could not add foreign key for user_id: " . mysqli_error($conn) . "\n";
    }
}

echo "\n=== Migration Complete ===\n";
echo "The email_log table has been created. All emails sent through sendMail() will now be logged.\n";
echo "View logs in the admin panel under 'Messages'.\n";
?>
