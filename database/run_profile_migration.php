<?php
/**
 * Run Profile Verification Migration
 * Adds required columns for email and phone change verification
 */

require_once __DIR__ . '/../includes/config.php';

echo "=== Profile Verification Migration ===\n\n";

if (!$pdo) {
    die("Error: Database connection failed.\n");
}

try {
    echo "Adding profile verification columns...\n\n";
    
    // Check and add pending_email column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'pending_email'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN pending_email VARCHAR(100) NULL");
        echo "✓ Added 'pending_email' column\n";
    } else {
        echo "- 'pending_email' column already exists\n";
    }
    
    // Check and add pending_email_token column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'pending_email_token'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN pending_email_token VARCHAR(255) NULL");
        echo "✓ Added 'pending_email_token' column\n";
    } else {
        echo "- 'pending_email_token' column already exists\n";
    }
    
    // Check and add pending_email_expires column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'pending_email_expires'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN pending_email_expires TIMESTAMP NULL");
        echo "✓ Added 'pending_email_expires' column\n";
    } else {
        echo "- 'pending_email_expires' column already exists\n";
    }

    // Check and add pending_email_old_otp column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'pending_email_old_otp'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN pending_email_old_otp VARCHAR(10) NULL");
        echo "✓ Added 'pending_email_old_otp' column\n";
    } else {
        echo "- 'pending_email_old_otp' column already exists\n";
    }

    // Check and add email_change_step column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email_change_step'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email_change_step VARCHAR(20) NULL");
        echo "✓ Added 'email_change_step' column\n";
    } else {
        echo "- 'email_change_step' column already exists\n";
    }
    
    // Check and add email_change_cancel_token column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email_change_cancel_token'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email_change_cancel_token VARCHAR(255) NULL");
        echo "✓ Added 'email_change_cancel_token' column\n";
    } else {
        echo "- 'email_change_cancel_token' column already exists\n";
    }
    
    // Check and add pending_phone column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'pending_phone'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN pending_phone VARCHAR(20) NULL");
        echo "✓ Added 'pending_phone' column\n";
    } else {
        echo "- 'pending_phone' column already exists\n";
    }
    
    // Check and add pending_phone_otp column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'pending_phone_otp'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN pending_phone_otp VARCHAR(10) NULL");
        echo "✓ Added 'pending_phone_otp' column\n";
    } else {
        echo "- 'pending_phone_otp' column already exists\n";
    }
    
    // Check and add pending_phone_expires column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'pending_phone_expires'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN pending_phone_expires TIMESTAMP NULL");
        echo "✓ Added 'pending_phone_expires' column\n";
    } else {
        echo "- 'pending_phone_expires' column already exists\n";
    }
    
    // Check and add phone_change_step column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone_change_step'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN phone_change_step VARCHAR(20) NULL");
        echo "✓ Added 'phone_change_step' column\n";
    } else {
        echo "- 'phone_change_step' column already exists\n";
    }
    
    // Check and add phone_recovery_token column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone_recovery_token'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN phone_recovery_token VARCHAR(255) NULL");
        echo "✓ Added 'phone_recovery_token' column\n";
    } else {
        echo "- 'phone_recovery_token' column already exists\n";
    }
    
    // Check and add email_verified column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email_verified'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE");
        echo "✓ Added 'email_verified' column\n";
        
        // Set email_verified = TRUE for existing verified users
        $pdo->exec("UPDATE users SET email_verified = TRUE WHERE is_verified = TRUE");
        echo "✓ Updated email_verified for existing verified users\n";
    } else {
        echo "- 'email_verified' column already exists\n";
    }
    
    // Check and add phone_verified column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone_verified'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN phone_verified BOOLEAN DEFAULT FALSE");
        echo "✓ Added 'phone_verified' column\n";
    } else {
        echo "- 'phone_verified' column already exists\n";
    }
    
    // Add indexes (ignore if they already exist)
    echo "\nAdding indexes...\n";
    
    try {
        $pdo->exec("CREATE INDEX idx_pending_email_token ON users(pending_email_token)");
        echo "✓ Added index 'idx_pending_email_token'\n";
    } catch (PDOException $e) {
        echo "- Index 'idx_pending_email_token' already exists\n";
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_pending_phone ON users(pending_phone)");
        echo "✓ Added index 'idx_pending_phone'\n";
    } catch (PDOException $e) {
        echo "- Index 'idx_pending_phone' already exists\n";
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_email_cancel_token ON users(email_change_cancel_token)");
        echo "✓ Added index 'idx_email_cancel_token'\n";
    } catch (PDOException $e) {
        echo "- Index 'idx_email_cancel_token' already exists\n";
    }
    
    echo "\n=== Migration completed successfully! ===\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
