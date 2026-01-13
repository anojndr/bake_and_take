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

if (!$pdo) {
    redirect('../index.php?page=login', 'Database connection error. Please try again later.', 'error');
}

try {
    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare('SELECT id FROM users WHERE password_reset_token_hash = ? AND password_reset_expires_at IS NOT NULL AND password_reset_expires_at > NOW() LIMIT 1');
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch();

    if (!$user) {
        redirect('../index.php?page=forgot-password', 'This reset link is invalid or expired. Please request a new one.', 'error');
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $update = $pdo->prepare('UPDATE users SET password = ?, password_reset_token_hash = NULL, password_reset_expires_at = NULL WHERE id = ?');
    $update->execute([$hashedPassword, $user['id']]);

    redirect('../index.php?page=login', 'Your password has been updated. You can now sign in.', 'success');

} catch (PDOException $e) {
    error_log('Reset password error: ' . $e->getMessage());
    redirect('../index.php?page=forgot-password', 'Failed to reset password. Please try again.', 'error');
}
