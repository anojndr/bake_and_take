<?php
/**
 * Fix Database Primary Keys
 * Renames table_name_id to id for consistency
 */

require_once __DIR__ . '/../includes/config.php';
global $conn;

// Check if run from browser or CLI
$isCli = (php_sapi_name() === 'cli');
$eol = $isCli ? "\n" : "<br>";

if ($isCli) {
    echo "Starting Primary Key Fixer..." . $eol;
} else {
    echo "<html><body style='font-family: monospace; background: #1a1a1a; color: #0f0; padding: 20px;'>";
    echo "<h3>Starting Primary Key Fixer...</h3>";
}

if (!$conn) {
    die("Error: Could not connect to database.$eol");
}

// Disable FK checks to allow modifications
if (mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0")) {
    echo "Foreign key checks disabled.$eol";
} else {
    die("Error disabling FK checks: " . mysqli_error($conn) . $eol);
}

// 1. Rename Primary Keys to 'id'
$tables = [
    'users', 'categories', 'products', 'orders', 
    'order_items', 'cart', 'cart_items', 
    'paypal_transactions', 'sms_log', 'sms_otp'
];

foreach ($tables as $table) {
    // Check if table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (!$result || mysqli_num_rows($result) == 0) {
        if ($result) mysqli_free_result($result);
        continue;
    }
    mysqli_free_result($result);

    // Get current PK
    $result = mysqli_query($conn, "SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
    $pkRow = mysqli_fetch_assoc($result);
    $pk = $pkRow['Column_name'] ?? null;
    mysqli_free_result($result);

    if (!$pk) {
        echo "Skipping $table: No primary key found.$eol";
        continue;
    }

    if ($pk === 'id') {
        echo "Table $table: Already uses 'id'.$eol";
        continue;
    }

    echo "Table $table: Renaming $pk -> id... ";
    
    // Get column details to preserve attributes (Type, Extra like auto_increment)
    $colResult = mysqli_query($conn, "SHOW COLUMNS FROM `$table` WHERE Field = '$pk'");
    $colData = mysqli_fetch_assoc($colResult);
    $type = $colData['Type'];
    $extra = $colData['Extra']; // e.g. auto_increment
    mysqli_free_result($colResult);
    
    if (mysqli_query($conn, "ALTER TABLE `$table` CHANGE `$pk` `id` $type $extra")) {
        echo "Success!$eol";
    } else {
        echo "Failed: " . mysqli_error($conn) . $eol;
    }
}

echo "$eol--- updating foreign key references ---$eol$eol";

// 2. Refresh Foreign Keys
// We scan for any FKs that are NOT pointing to 'id' column in the referenced table
// (excluding cases where the referenced column really shouldn't be 'id', but here all parent PKs are 'id')

$stmt = mysqli_prepare($conn, "
    SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME IS NOT NULL
    AND REFERENCED_COLUMN_NAME != 'id'
");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($fk = mysqli_fetch_assoc($result)) {
    $table = $fk['TABLE_NAME'];
    $constraint = $fk['CONSTRAINT_NAME'];
    $col = $fk['COLUMN_NAME'];
    $refTable = $fk['REFERENCED_TABLE_NAME'];
    
    echo "Fixing FK $constraint ($table.$col -> $refTable)... ";

    // Determine correct ON DELETE rule based on schema knowledge
    $onDelete = 'RESTRICT'; // Default safe fallback
    
    // Rules from schema.sql
    if ($refTable === 'users' && in_array($table, ['orders', 'sms_log', 'products'])) $onDelete = 'SET NULL';
    if ($refTable === 'users' && $table === 'cart') $onDelete = 'CASCADE';
    
    if ($refTable === 'orders' && in_array($table, ['paypal_transactions', 'sms_log'])) $onDelete = 'SET NULL';
    if ($refTable === 'orders' && $table === 'order_items') $onDelete = 'CASCADE';
    
    if ($refTable === 'products' && $table === 'order_items') $onDelete = 'SET NULL';
    if ($refTable === 'products' && $table === 'cart_items') $onDelete = 'CASCADE';
    
    if ($refTable === 'cart' && $table === 'cart_items') $onDelete = 'CASCADE';
    if ($refTable === 'categories' && $table === 'products') $onDelete = 'SET NULL';

    // Drop old FK
    if (mysqli_query($conn, "ALTER TABLE `$table` DROP FOREIGN KEY `$constraint`")) {
        // Add new FK pointing to 'id'
        if (mysqli_query($conn, "ALTER TABLE `$table` ADD CONSTRAINT `$constraint` 
                   FOREIGN KEY (`$col`) REFERENCES `$refTable`(`id`) ON DELETE $onDelete")) {
            echo "Done (ON DELETE $onDelete)$eol";
        } else {
            echo "Error adding FK: " . mysqli_error($conn) . $eol;
        }
    } else {
        echo "Error dropping FK: " . mysqli_error($conn) . $eol;
    }
}
mysqli_free_result($result);
mysqli_stmt_close($stmt);

// Re-enable FK checks
if (mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1")) {
    echo "$eolComplete! All primary keys should now be 'id'.$eol";
} else {
    echo "$eolWarning: Could not re-enable FK checks: " . mysqli_error($conn) . $eol;
}
?>
