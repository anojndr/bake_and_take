<?php
/**
 * Download Database Backup
 */
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    die('Unauthorized access');
}

// Validate filename
if (!isset($_GET['file']) || empty($_GET['file'])) {
    http_response_code(400);
    die('No file specified');
}

// Prevent directory traversal attacks
$filename = basename($_GET['file']);

// Validate file extension
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if ($extension !== 'sql') {
    http_response_code(400);
    die('Invalid file type');
}

// Backup directory
$backupDir = dirname(__DIR__, 2) . '/backups';
$filepath = $backupDir . '/' . $filename;

// Check if file exists
if (!file_exists($filepath)) {
    http_response_code(404);
    die('File not found');
}

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));

// Clear output buffer
ob_clean();
flush();

// Output file
readfile($filepath);
exit;
?>
