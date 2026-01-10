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

// ============================================
// PAYPAL CONFIGURATION
// ============================================
// Get your credentials from: https://developer.paypal.com/
// 1. Log in and go to Developer Dashboard
// 2. Go to Apps & Credentials > Create App
// 3. Copy Client ID and Secret
//
// For testing, use Sandbox credentials
// For production, switch to Live credentials and set PAYPAL_SANDBOX to false

define('PAYPAL_CLIENT_ID', 'your-paypal-client-id-here');
define('PAYPAL_CLIENT_SECRET', 'your-paypal-client-secret-here');
define('PAYPAL_SANDBOX', true); // Set to false for production

// ============================================
// SANDBOX TEST ACCOUNTS (for development only)
// ============================================
// Create sandbox accounts at: https://developer.paypal.com/dashboard/accounts
// 
// Example Sandbox Buyer:
// Email: sb-buyer@personal.example.com
// Password: (set in PayPal Dashboard)
//
// Example Sandbox Merchant:
// Email: sb-merchant@business.example.com
?>
