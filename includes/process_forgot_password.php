<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php?page=forgot-password');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirect('../index.php?page=forgot-password', 'Invalid request. Please try again.', 'error');
}

$email = sanitize($_POST['email'] ?? '');

if (empty($email) || !isValidEmail($email)) {
    redirect('../index.php?page=forgot-password', 'Please enter a valid email address.', 'error');
}

// Always respond with the same message to prevent account enumeration.
$genericMessage = 'If an account exists for that email, we\'ll send a password reset link.';

if (!$pdo) {
    redirect('../index.php?page=login', $genericMessage, 'info');
}

try {
    $stmt = $pdo->prepare('SELECT user_id, first_name, email, is_admin FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        redirect('../index.php?page=login', $genericMessage, 'info');
    }

    // Generate token and store only a hash.
    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+60 minutes'));

    $update = $pdo->prepare('UPDATE users SET password_reset_token_hash = ?, password_reset_expires_at = ? WHERE user_id = ?');
    $update->execute([$tokenHash, $expiresAt, $user['user_id']]);

    // Build reset link
    $siteUrl = function_exists('getCurrentSiteUrl') ? getCurrentSiteUrl() : (defined('SITE_URL') ? SITE_URL : '');
    $resetLink = rtrim($siteUrl, '/') . '/index.php?page=reset-password&token=' . urlencode($rawToken);

    $firstName = htmlspecialchars($user['first_name'] ?? 'there', ENT_QUOTES, 'UTF-8');

    $emailBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #8B4513;'>Reset Your Password</h2>
            <p>Hi {$firstName},</p>
            <p>We received a request to reset your password. Click the button below to set a new password:</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$resetLink}'
                   style='background: linear-gradient(135deg, #E8B482 0%, #D4A574 100%);
                          color: white;
                          padding: 14px 32px;
                          text-decoration: none;
                          border-radius: 8px;
                          font-weight: bold;
                          display: inline-block;'>
                    Reset Password
                </a>
            </div>
            <p style='color: #666;'>Or copy and paste this link in your browser:</p>
            <p style='word-break: break-all; color: #8B4513;'>{$resetLink}</p>
            <p style='color: #999; font-size: 12px; margin-top: 30px;'>This link will expire in 60 minutes.</p>
            <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
            <p style='color: #999; font-size: 12px;'>If you didn't request this, you can ignore this email.</p>
        </div>
    ";

    $mailResult = sendMail($user['email'], 'Password Reset - ' . (defined('SITE_NAME') ? SITE_NAME : 'Bake & Take'), $emailBody);

    if (!$mailResult['success']) {
        error_log('Password reset email failed for user_id=' . $user['user_id'] . ': ' . ($mailResult['message'] ?? 'unknown error'));
    }

    // Lightweight local log for debugging mail delivery without leaking tokens.
    // This is safe to keep in production since it contains no reset token.
    try {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $line = sprintf(
            "[%s] password_reset_mail user_id=%s to=%s success=%s message=%s\n",
            date('c'),
            (string)$user['user_id'],
            (string)$user['email'],
            !empty($mailResult['success']) ? '1' : '0',
            str_replace(["\r", "\n"], ' ', (string)($mailResult['message'] ?? ''))
        );
        @file_put_contents($logDir . '/password_reset_mail.log', $line, FILE_APPEND);
    } catch (Throwable $t) {
        // ignore
    }

    redirect('../index.php?page=login', $genericMessage, 'info');

} catch (Exception $e) {
    error_log('Forgot password error: ' . $e->getMessage());
    redirect('../index.php?page=login', $genericMessage, 'info');
}
