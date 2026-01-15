<?php
/**
 * Create Database Backup
 */
session_start();
header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

require_once '../../includes/config.php';

global $conn;

// Backup directory
$backupDir = dirname(__DIR__, 2) . '/backups';

// Create backup directory if it doesn't exist
if (!file_exists($backupDir)) {
    if (!mkdir($backupDir, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'Could not create backup directory']);
        exit;
    }
}

// Get options
$includeDropTables = isset($_POST['include_drop']) && $_POST['include_drop'] === '1';
$includeCreateDb = isset($_POST['include_create_db']) && $_POST['include_create_db'] === '1';

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$backup = '';
$timestamp = date('Y-m-d_H-i-s');
$filename = 'backup_' . $timestamp . '.sql';
$filepath = $backupDir . '/' . $filename;

// Header
$backup .= "-- Bake & Take Database Backup\n";
$backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$backup .= "-- Database: " . DB_NAME . "\n";
$backup .= "-- ----------------------------------------\n\n";

// Create database statement
if ($includeCreateDb) {
    $backup .= "-- Create database\n";
    $backup .= "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
    $backup .= "USE `" . DB_NAME . "`;\n\n";
}

// Disable foreign key checks during restore
$backup .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$backup .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
$backup .= "SET AUTOCOMMIT = 0;\n";
$backup .= "START TRANSACTION;\n\n";

// Get all tables
$result = mysqli_query($conn, "SHOW TABLES");
if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

$tables = [];
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}
mysqli_free_result($result);

foreach ($tables as $table) {
    // Drop table if exists
    if ($includeDropTables) {
        $backup .= "-- ----------------------------\n";
        $backup .= "-- Drop table if exists: `$table`\n";
        $backup .= "-- ----------------------------\n";
        $backup .= "DROP TABLE IF EXISTS `$table`;\n\n";
    }
    
    // Get create table statement
    $result = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
        exit;
    }
    $row = mysqli_fetch_assoc($result);
    $createTable = $row['Create Table'];
    mysqli_free_result($result);
    
    $backup .= "-- ----------------------------\n";
    $backup .= "-- Table structure for `$table`\n";
    $backup .= "-- ----------------------------\n";
    $backup .= $createTable . ";\n\n";
    
    // Get table data
    $result = mysqli_query($conn, "SELECT * FROM `$table`");
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
        exit;
    }
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_free_result($result);
    
    if (!empty($rows)) {
        $backup .= "-- ----------------------------\n";
        $backup .= "-- Records of `$table`\n";
        $backup .= "-- ----------------------------\n";
        
        // Get column names
        $columns = array_keys($rows[0]);
        $columnList = '`' . implode('`, `', $columns) . '`';
        
        // Build insert statements (batched for efficiency)
        $batchSize = 100;
        $batch = [];
        
        foreach ($rows as $index => $row) {
            $values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = "'" . mysqli_real_escape_string($conn, $value) . "'";
                }
            }
            $batch[] = '(' . implode(', ', $values) . ')';
            
            // Write batch
            if (count($batch) >= $batchSize || $index === count($rows) - 1) {
                $backup .= "INSERT INTO `$table` ($columnList) VALUES\n";
                $backup .= implode(",\n", $batch) . ";\n";
                $batch = [];
            }
        }
        
        $backup .= "\n";
    }
}

// Commit transaction and enable foreign key checks
$backup .= "COMMIT;\n";
$backup .= "SET FOREIGN_KEY_CHECKS = 1;\n";
$backup .= "\n-- End of backup\n";

// Write to file
if (file_put_contents($filepath, $backup) === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to write backup file']);
    exit;
}

echo json_encode([
    'success' => true,
    'filename' => $filename,
    'size' => filesize($filepath),
    'tables' => count($tables)
]);
?>
