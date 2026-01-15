<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php?page=forgot-password');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirect('../index.php?page=forgot-password', 'Invalid request. Please try again.', 'error');
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($token)) {
    redirect('../index.php?page=forgot-password', 'Invalid or missing reset token. Please request a new link.', 'error');
}

if (strlen($password) < 8) {
    redirect('../index.php?page=reset-password&token=' . urlencode($token), 'Password must be at least 8 characters.', 'error');
}

if ($password !== $confirmPassword) {
    redirect('../index.php?page=reset-password&token=' . urlencode($token), 'Passwords do not match.', 'error');
}

global $conn;
if (!$conn) {
    redirect('../index.php?page=login', 'Database connection error. Please try again later.', 'error');
}

$tokenHash = hash('sha256', $token);

$stmt = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE password_reset_token_hash = ? AND password_reset_expires_at IS NOT NULL AND password_reset_expires_at > NOW() LIMIT 1');
if (!$stmt) {
    error_log('Reset password error: ' . mysqli_error($conn));
    redirect('../index.php?page=forgot-password', 'Failed to reset password. Please try again.', 'error');
}
mysqli_stmt_bind_param($stmt, "s", $tokenHash);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    redirect('../index.php?page=forgot-password', 'This reset link is invalid or expired. Please request a new one.', 'error');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$update = mysqli_prepare($conn, 'UPDATE users SET password = ?, password_reset_token_hash = NULL, password_reset_expires_at = NULL WHERE user_id = ?');
if (!$update) {
    error_log('Reset password error: ' . mysqli_error($conn));
    redirect('../index.php?page=forgot-password', 'Failed to reset password. Please try again.', 'error');
}
mysqli_stmt_bind_param($update, "si", $hashedPassword, $user['user_id']);
mysqli_stmt_execute($update);
mysqli_stmt_close($update);

redirect('../index.php?page=login', 'Your password has been updated. You can now sign in.', 'success');
