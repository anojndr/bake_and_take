<?php
/**
 * SMS Gateway Configuration
 * 
 * This file contains all configuration settings for the SMS integration
 * using SMSGate Android app and Incoming SMS Webhook Forwarder
 * 
 * SMSGate App: https://github.com/capcom6/android-sms-gateway
 * SMS Forwarder: https://github.com/bogkonstantin/android_income_sms_gateway_webhook
 */

// ============================================
// OUTBOUND SMS SETTINGS (SMSGate Cloud)
// ============================================

// SMSGate Cloud API endpoint
// Using cloud server instead of local server for better reliability
// Cloud: https://api.sms-gate.app | Local (old): http://192.168.100.159:8080
define('SMS_GATEWAY_URL', 'https://api.sms-gate.app');

// SMSGate API endpoint path for sending messages
define('SMS_GATEWAY_SEND_PATH', '/3rdparty/v1/message');

// Authentication credentials for SMSGate Cloud
// These are configured in the SMSGate Android app under Cloud server settings
define('SMS_GATEWAY_USERNAME', 'DA3ODN');
define('SMS_GATEWAY_PASSWORD', 'hgrbu5t-dapmxq');

// Device ID for cloud server (required for cloud API)
define('SMS_GATEWAY_DEVICE_ID', 'FXSuQT_Re95AwJbTI41uZ');

// Alternative: API Key authentication (if using token-based auth)
define('SMS_GATEWAY_API_KEY', '');

// Request timeout in seconds
define('SMS_GATEWAY_TIMEOUT', 30);

// ============================================
// INBOUND SMS SETTINGS (Webhook)
// ============================================

// Webhook authentication token
// Configure the same token in the SMS Forwarder Android app
// This secures your webhook from unauthorized access
define('SMS_WEBHOOK_SECRET', 'your-secret-token-here');

// Allowed IP addresses for webhook (leave empty to allow all)
// Add your Android device's IP(s) for security
define('SMS_WEBHOOK_ALLOWED_IPS', [
    // '192.168.1.100',
    // '192.168.1.101',
]);

// ============================================
// SMS SETTINGS
// ============================================

// Store name for SMS messages
define('SMS_SENDER_NAME', 'Bake & Take');

// Maximum message length (SMS standard is 160 chars, 70 for Unicode)
define('SMS_MAX_LENGTH', 160);

// Enable/disable SMS features
define('SMS_ENABLED', true);
define('SMS_OTP_ENABLED', true);
define('SMS_ORDER_NOTIFICATIONS_ENABLED', true);

// OTP Settings
define('SMS_OTP_LENGTH', 6);
define('SMS_OTP_EXPIRY_MINUTES', 10);
define('SMS_OTP_MAX_ATTEMPTS', 3);

// Country code (for phone number formatting)
define('SMS_DEFAULT_COUNTRY_CODE', '+63'); // Philippines

// ============================================
// NOTIFICATION TEMPLATES
// ============================================

// Order confirmation SMS template
// Available placeholders: {name}, {order_number}, {total}, {store_name}
define('SMS_TEMPLATE_ORDER_CONFIRM', 
    '{store_name}: Hi {name}! Your order #{order_number} for ${total} has been received. We\'ll notify you when it\'s ready for pickup.'
);

// Order ready for pickup SMS template
define('SMS_TEMPLATE_ORDER_READY', 
    '{store_name}: Great news {name}! Your order #{order_number} is ready for pickup. See you soon!'
);

// OTP verification SMS template
// Available placeholders: {otp}, {store_name}, {purpose}, {expires}
define('SMS_TEMPLATE_OTP', 
    '{store_name}: Your verification code is {otp}. Valid for {expires} minutes. Do not share this code.'
);

// Order status update template
define('SMS_TEMPLATE_ORDER_STATUS', 
    '{store_name}: Order #{order_number} status: {status}'
);
?>
