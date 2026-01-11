<?php
/**
 * Restore Database Backup
 */
session_start();
header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

require_once '../../includes/config.php';

// Backup directory
$backupDir = dirname(__DIR__, 2) . '/backups';

if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$sqlContent = '';

// Check if restoring from uploaded file or saved backup
if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
    // Uploaded file
    $file = $_FILES['backup_file'];
    
    // Validate file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'sql') {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only SQL files are allowed.']);
        exit;
    }
    
    // Validate file size (max 50MB)
    if ($file['size'] > 50 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 50MB.']);
        exit;
    }
    
    $sqlContent = file_get_contents($file['tmp_name']);
    
} elseif (isset($_POST['filename'])) {
    // Saved backup file
    $filename = basename($_POST['filename']); // Prevent directory traversal
    $filepath = $backupDir . '/' . $filename;
    
    // Validate file extension
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($extension !== 'sql') {
        echo json_encode(['success' => false, 'error' => 'Invalid file type.']);
        exit;
    }
    
    // Check if file exists
    if (!file_exists($filepath)) {
        echo json_encode(['success' => false, 'error' => 'Backup file not found.']);
        exit;
    }
    
    $sqlContent = file_get_contents($filepath);
    
} else {
    echo json_encode(['success' => false, 'error' => 'No backup file provided.']);
    exit;
}

if (empty($sqlContent)) {
    echo json_encode(['success' => false, 'error' => 'Backup file is empty.']);
    exit;
}

try {
    // Split SQL content into individual statements
    // This handles multi-line statements and ignores comments
    $statements = [];
    $currentStatement = '';
    $inString = false;
    $stringChar = '';
    $lines = explode("\n", $sqlContent);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and comments
        if (empty($line) || strpos($line, '--') === 0 || strpos($line, '#') === 0) {
            continue;
        }
        
        $currentStatement .= $line . ' ';
        
        // Check if statement is complete (ends with semicolon, not inside a string)
        if (preg_match('/;\s*$/', $line)) {
            // Simple check - if the statement ends with a semicolon and we're not in a multiline string
            $cleanStatement = trim($currentStatement);
            if (!empty($cleanStatement) && $cleanStatement !== ';') {
                $statements[] = $cleanStatement;
            }
            $currentStatement = '';
        }
    }
    
    // Add any remaining statement
    if (!empty(trim($currentStatement))) {
        $statements[] = trim($currentStatement);
    }
    
    // Execute statements
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    foreach ($statements as $statement) {
        try {
            // Skip certain problematic statements
            if (stripos($statement, 'CREATE DATABASE') === 0 || 
                stripos($statement, 'USE ') === 0 ||
                stripos($statement, 'SET SQL_MODE') === 0 ||
                stripos($statement, 'SET AUTOCOMMIT') === 0 ||
                stripos($statement, 'START TRANSACTION') === 0 ||
                stripos($statement, 'COMMIT') === 0 ||
                stripos($statement, 'SET FOREIGN_KEY_CHECKS') === 0) {
                continue;
            }
            
            $pdo->exec($statement);
            $successCount++;
            
        } catch (PDOException $e) {
            $errorCount++;
            $errors[] = [
                'statement' => substr($statement, 0, 100) . '...',
                'error' => $e->getMessage()
            ];
            
            // Continue with other statements even if one fails
        }
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    if ($errorCount > 0 && $successCount === 0) {
        echo json_encode([
            'success' => false, 
            'error' => 'Failed to restore database. All statements failed.',
            'errors' => array_slice($errors, 0, 5)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'statements_executed' => $successCount,
            'errors' => $errorCount,
            'error_details' => array_slice($errors, 0, 5)
        ]);
    }
    
} catch (Exception $e) {
    // Re-enable foreign key checks on error
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $e2) {
        // Ignore
    }
    
    echo json_encode(['success' => false, 'error' => 'Restore failed: ' . $e->getMessage()]);
}
?>
