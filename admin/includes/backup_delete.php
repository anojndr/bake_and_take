<?php
/**
 * Delete Database Backup
 */
session_start();
header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Validate filename
if (!isset($_POST['filename']) || empty($_POST['filename'])) {
    echo json_encode(['success' => false, 'error' => 'No file specified']);
    exit;
}

// Prevent directory traversal attacks
$filename = basename($_POST['filename']);

// Validate file extension
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if ($extension !== 'sql') {
    echo json_encode(['success' => false, 'error' => 'Invalid file type']);
    exit;
}

// Backup directory
$backupDir = dirname(__DIR__, 2) . '/backups';
$filepath = $backupDir . '/' . $filename;

// Check if file exists
if (!file_exists($filepath)) {
    echo json_encode(['success' => false, 'error' => 'File not found']);
    exit;
}

// Delete file
if (unlink($filepath)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete file']);
}
?>
