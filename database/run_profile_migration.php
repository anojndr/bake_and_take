<?php
/**
 * Run Profile Verification Migration
 * Adds required columns for email and phone change verification
 */

require_once __DIR__ . '/../includes/config.php';
global $conn;

echo "=== Profile Verification Migration ===\n\n";

if (!$conn) {
    die("Error: Database connection failed.\n");
}

echo "Adding profile verification columns...\n\n";

// Check and add pending_email column
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'pending_email'");
if (mysqli_num_rows($result) === 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN pending_email VARCHAR(100) NULL")) {
        echo "✓ Added 'pending_email' column\n";
    } else {
        echo "✗ Error adding 'pending_email': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- 'pending_email' column already exists\n";
}
mysqli_free_result($result);

// Check and add pending_email_token column
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'pending_email_token'");
if (mysqli_num_rows($result) === 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN pending_email_token VARCHAR(255) NULL")) {
        echo "✓ Added 'pending_email_token' column\n";
    } else {
        echo "✗ Error adding 'pending_email_token': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- 'pending_email_token' column already exists\n";
}
mysqli_free_result($result);

// Check and add pending_email_expires column
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'pending_email_expires'");
if (mysqli_num_rows($result) === 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN pending_email_expires TIMESTAMP NULL")) {
        echo "✓ Added 'pending_email_expires' column\n";
    } else {
        echo "✗ Error adding 'pending_email_expires': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- 'pending_email_expires' column already exists\n";
}
mysqli_free_result($result);

// Check and add pending_email_old_otp column
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'pending_email_old_otp'");
if (mysqli_num_rows($result) === 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN pending_email_old_otp VARCHAR(10) NULL")) {
        echo "✓ Added 'pending_email_old_otp' column\n";
    } else {
        echo "✗ Error adding 'pending_email_old_otp': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- 'pending_email_old_otp' column already exists\n";
}
mysqli_free_result($result);

// Check and add pending_email_new_otp column
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'pending_email_new_otp'");
if (mysqli_num_rows($result) === 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN pending_email_new_otp VARCHAR(6) NULL")) {
        echo "✓ Added 'pending_email_new_otp' column\n";
    } else {
        echo "✗ Error adding 'pending_email_new_otp': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- 'pending_email_new_otp' column already exists\n";
}
mysqli_free_result($result);

// Check and add email_change_step column
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'email_change_step'");
if (mysqli_num_rows($result) === 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN email_change_step VARCHAR(20) NULL")) {
        echo "✓ Added 'email_change_step' column\n";
    } else {
        echo "✗ Error adding 'email_change_step': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- 'email_change_step' column already exists\n";
}
mysqli_free_result($result);

// Check and add email_change_cancel_token column
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'email_change_cancel_token'");
if (mysqli_num_rows($result) === 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN email_change_cancel_token VARCHAR(255) NULL")) {
        echo "✓ Added 'email_change_cancel_token' column\n";
    } else {
        echo "✗ Error adding 'email_change_cancel_token': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- 'email_change_cancel_token' column already exists\n";
}
mysqli_free_result($result);

// Check and add pending_phone column
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'pending_phone'");
if (mysqli_num_rows($result) === 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN pending_phone VARCHAR(20) NULL")) {
        echo "✓ Added 'pending_phone' column\n";
    } else {
        echo "✗ Error adding 'pending_phone': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- 'pending_phone' column already exists\n";
}
mysqli_free_result($result);

// Check and add pending_phone_otp column
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'pending_phone_otp'");
if (mysqli_num_rows($result) === 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN pending_phone_otp VARCHAR(10) NULL")) {
        echo "✓ Added 'pending_phone_otp' column\n";
    } else {
        echo "✗ Error adding 'pending_phone_otp': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- 'pending_phone_otp' column already exists\n";
}
mysqli_free_result($result);

// Check and add pending_phone_expires column
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'pending_phone_expires'");
if (mysqli_num_rows($result) === 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN pending_phone_expires TIMESTAMP NULL")) {
        echo "✓ Added 'pending_phone_expires' column\n";
    } else {
        echo "✗ Error adding 'pending_phone_expires': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- 'pending_phone_expires' column already exists\n";
}
mysqli_free_result($result);

// Check and add phone_change_step column
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'phone_change_step'");
if (mysqli_num_rows($result) === 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN phone_change_step VARCHAR(20) NULL")) {
        echo "✓ Added 'phone_change_step' column\n";
    } else {
        echo "✗ Error adding 'phone_change_step': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- 'phone_change_step' column already exists\n";
}
mysqli_free_result($result);

// Check and add phone_recovery_token column
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'phone_recovery_token'");
if (mysqli_num_rows($result) === 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN phone_recovery_token VARCHAR(255) NULL")) {
        echo "✓ Added 'phone_recovery_token' column\n";
    } else {
        echo "✗ Error adding 'phone_recovery_token': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- 'phone_recovery_token' column already exists\n";
}
mysqli_free_result($result);

// Check and add email_verified column
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'email_verified'");
if (mysqli_num_rows($result) === 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE")) {
        echo "✓ Added 'email_verified' column\n";
        
        // Set email_verified = TRUE for existing verified users
        if (mysqli_query($conn, "UPDATE users SET email_verified = TRUE WHERE is_verified = TRUE")) {
            echo "✓ Updated email_verified for existing verified users\n";
        } else {
            echo "✗ Error updating email_verified: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "✗ Error adding 'email_verified': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- 'email_verified' column already exists\n";
}
mysqli_free_result($result);

// Check and add phone_verified column
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'phone_verified'");
if (mysqli_num_rows($result) === 0) {
    if (mysqli_query($conn, "ALTER TABLE users ADD COLUMN phone_verified BOOLEAN DEFAULT FALSE")) {
        echo "✓ Added 'phone_verified' column\n";
    } else {
        echo "✗ Error adding 'phone_verified': " . mysqli_error($conn) . "\n";
    }
} else {
    echo "- 'phone_verified' column already exists\n";
}
mysqli_free_result($result);

// Add indexes
echo "\nAdding indexes...\n";

if (mysqli_query($conn, "CREATE INDEX idx_pending_email_token ON users(pending_email_token)")) {
    echo "✓ Added index 'idx_pending_email_token'\n";
} else {
    if (strpos(mysqli_error($conn), 'Duplicate key name') !== false) {
        echo "- Index 'idx_pending_email_token' already exists\n";
    } else {
        echo "✗ Error adding index: " . mysqli_error($conn) . "\n";
    }
}

if (mysqli_query($conn, "CREATE INDEX idx_pending_phone ON users(pending_phone)")) {
    echo "✓ Added index 'idx_pending_phone'\n";
} else {
    if (strpos(mysqli_error($conn), 'Duplicate key name') !== false) {
        echo "- Index 'idx_pending_phone' already exists\n";
    } else {
        echo "✗ Error adding index: " . mysqli_error($conn) . "\n";
    }
}

if (mysqli_query($conn, "CREATE INDEX idx_email_cancel_token ON users(email_change_cancel_token)")) {
    echo "✓ Added index 'idx_email_cancel_token'\n";
} else {
    if (strpos(mysqli_error($conn), 'Duplicate key name') !== false) {
        echo "- Index 'idx_email_cancel_token' already exists\n";
    } else {
        echo "✗ Error adding index: " . mysqli_error($conn) . "\n";
    }
}

echo "\n=== Migration completed successfully! ===\n";
?>
