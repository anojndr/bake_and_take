<?php
/**
 * Run Order Confirmation Migration
 * 
 * This script adds the order confirmation fields to the database.
 * Run this once to set up the new columns.
 */

require_once __DIR__ . '/../includes/config.php';
global $conn;

echo "Running order confirmation migration...\n\n";

if (!$conn) {
    die("Error: Database connection failed.\n");
}

// Check if confirmation_method column exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'confirmation_method'");
$exists = mysqli_fetch_assoc($result);
mysqli_free_result($result);

if ($exists) {
    echo "Migration already applied - confirmation_method column exists.\n";
} else {
    // Add confirmation_method column
    if (mysqli_query($conn, "ALTER TABLE orders ADD COLUMN confirmation_method ENUM('sms', 'email') DEFAULT NULL AFTER status")) {
        echo "✓ Added confirmation_method column\n";
    } else {
        die("Error adding confirmation_method: " . mysqli_error($conn) . "\n");
    }
}

// Check if confirmation_token column exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'confirmation_token'");
$exists = mysqli_fetch_assoc($result);
mysqli_free_result($result);

if ($exists) {
    echo "confirmation_token column already exists.\n";
} else {
    // Add confirmation_token column
    if (mysqli_query($conn, "ALTER TABLE orders ADD COLUMN confirmation_token VARCHAR(64) DEFAULT NULL AFTER confirmation_method")) {
        echo "✓ Added confirmation_token column\n";
    } else {
        die("Error adding confirmation_token: " . mysqli_error($conn) . "\n");
    }
}

// Check if confirmed_at column exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'confirmed_at'");
$exists = mysqli_fetch_assoc($result);
mysqli_free_result($result);

if ($exists) {
    echo "confirmed_at column already exists.\n";
} else {
    // Add confirmed_at column
    if (mysqli_query($conn, "ALTER TABLE orders ADD COLUMN confirmed_at TIMESTAMP NULL AFTER confirmation_token")) {
        echo "✓ Added confirmed_at column\n";
    } else {
        die("Error adding confirmed_at: " . mysqli_error($conn) . "\n");
    }
}

// Add indexes
if (mysqli_query($conn, "CREATE INDEX idx_confirmation_token ON orders(confirmation_token)")) {
    echo "✓ Added idx_confirmation_token index\n";
} else {
    $error = mysqli_error($conn);
    if (strpos($error, 'Duplicate key name') !== false) {
        echo "idx_confirmation_token index already exists.\n";
    } else {
        die("Error adding index: " . $error . "\n");
    }
}

if (mysqli_query($conn, "CREATE INDEX idx_pending_phone ON orders(phone, status)")) {
    echo "✓ Added idx_pending_phone index\n";
} else {
    $error = mysqli_error($conn);
    if (strpos($error, 'Duplicate key name') !== false) {
        echo "idx_pending_phone index already exists.\n";
    } else {
        die("Error adding index: " . $error . "\n");
    }
}

echo "\n✓ Migration completed successfully!\n";
?>
