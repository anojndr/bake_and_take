<?php
require_once __DIR__ . '/../includes/config.php';

global $conn;

// Check if table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'paypal_transactions'");
if (mysqli_num_rows($result) > 0) {
    echo "Table paypal_transactions exists\n";
    
    // Show columns
    $cols = mysqli_query($conn, "DESCRIBE paypal_transactions");
    $existingCols = [];
    echo "Existing columns:\n";
    while ($row = mysqli_fetch_assoc($cols)) {
        $existingCols[] = $row['Field'];
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // Add missing columns
    $alterations = [
        'paypal_capture_id' => 'ALTER TABLE paypal_transactions ADD COLUMN paypal_capture_id VARCHAR(100) AFTER paypal_order_id',
        'paypal_payer_id' => 'ALTER TABLE paypal_transactions ADD COLUMN paypal_payer_id VARCHAR(100) AFTER paypal_capture_id',
        'amount' => 'ALTER TABLE paypal_transactions ADD COLUMN amount DECIMAL(10, 2) AFTER paypal_payer_id',
        'currency' => 'ALTER TABLE paypal_transactions ADD COLUMN currency VARCHAR(10) DEFAULT "PHP" AFTER amount',
        'raw_response' => 'ALTER TABLE paypal_transactions ADD COLUMN raw_response TEXT AFTER status'
    ];
    
    echo "\nAdding missing columns:\n";
    foreach ($alterations as $col => $sql) {
        if (!in_array($col, $existingCols)) {
            if (mysqli_query($conn, $sql)) {
                echo "  Added: $col\n";
            } else {
                echo "  Error adding $col: " . mysqli_error($conn) . "\n";
            }
        } else {
            echo "  Skipped: $col (already exists)\n";
        }
    }
    
} else {
    echo "Table paypal_transactions does NOT exist. Creating...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS paypal_transactions (
        transaction_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        paypal_order_id VARCHAR(100),
        paypal_capture_id VARCHAR(100),
        paypal_payer_id VARCHAR(100),
        amount DECIMAL(10, 2),
        currency VARCHAR(10) DEFAULT 'PHP',
        transaction_type ENUM('create_order', 'capture', 'refund', 'webhook') NOT NULL DEFAULT 'capture',
        status VARCHAR(50),
        raw_response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_order_id (order_id)
    )";
    
    if (mysqli_query($conn, $sql)) {
        echo "Table created successfully!\n";
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "\n";
    }
}

echo "\nDone!\n";
?>
