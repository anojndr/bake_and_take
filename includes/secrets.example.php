<?php
/**
 * Secrets Configuration - EXAMPLE FILE
 * 
 * Copy this file to secrets.php and fill in your actual values.
 * The secrets.php file is gitignored for security.
 */

// ============================================
// EMAIL CONFIGURATION (Gmail SMTP)
// ============================================
// Create an App Password at: https://myaccount.google.com/apppasswords
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password-here');

// ============================================
// SMS GATEWAY CONFIGURATION (SMSGate Android)
// ============================================
// These are configured in includes/sms_config.php
// Here you can override for local environment:
// 
// define('SMS_GATEWAY_URL', 'http://192.168.1.100:8080');
// define('SMS_GATEWAY_USERNAME', 'admin');
// define('SMS_GATEWAY_PASSWORD', 'your-password');
// define('SMS_WEBHOOK_SECRET', 'your-secret-token');
?>
