<?php
/**
 * Quick check of email_log table
 */
require_once __DIR__ . '/../includes/config.php';

echo "=== Email Log Check ===\n\n";

// Check table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'email_log'");
if (!$tableCheck || mysqli_num_rows($tableCheck) === 0) {
    echo "ERROR: email_log table does not exist!\n";
    exit(1);
}
echo "✓ email_log table exists\n";

// Count entries
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM email_log");
$row = mysqli_fetch_assoc($result);
echo "  Entries: " . $row['count'] . "\n\n";

// Show table structure
echo "Table Structure:\n";
$result = mysqli_query($conn, "DESCRIBE email_log");
while ($r = mysqli_fetch_assoc($result)) {
    echo "  - {$r['Field']} ({$r['Type']})\n";
}

echo "\n=== Check Complete ===\n";
?>
