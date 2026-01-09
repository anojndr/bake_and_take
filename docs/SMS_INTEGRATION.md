# SMS Integration Guide for Bake & Take

## Overview

This document explains how to set up and use the SMS integration feature in your Bake & Take application. The system uses:

1. **SMSGate Android App** - For sending outbound SMS messages
2. **Incoming SMS to URL Forwarder** - For receiving inbound SMS messages via webhook
3. **PHP Webhook** - To process received SMS messages

## Architecture

```
┌─────────────────┐     HTTP POST      ┌─────────────────┐
│   Your PHP      │ ◄─────────────────► │  SMSGate App    │
│   Application   │   (Send SMS API)   │  (Android)      │
└────────┬────────┘                     └────────┬────────┘
         │                                       │
         │                                       │  Carrier
         │                                       │  Network
         │                                       ▼
         │                              ┌─────────────────┐
         │                              │   Customer      │
         │                              │   Phone         │
         │                              └────────┬────────┘
         │                                       │
         │     HTTP POST (Webhook)               │ SMS Reply
         ◄───────────────────────────────────────┤
         │                                       │
┌────────▼────────┐                     ┌────────▼────────┐
│  SMS Webhook    │ ◄─────────────────── │  SMS Forwarder  │
│  (sms_webhook.php)                    │  (Android App)  │
└─────────────────┘                     └─────────────────┘
```

## Setup Instructions

### 1. Install Required Android Apps

#### SMSGate (Outbound SMS)
- Download from: https://github.com/capcom6/android-sms-gateway
- Install on an Android phone with an active SIM card
- Open the app and configure:
  - Enable the SMS gateway service
  - Note the local IP address shown (e.g., `192.168.1.100`)
  - Set authentication credentials (username/password)
  - Keep the phone connected to the same WiFi network as your server

#### Incoming SMS to URL Forwarder (Inbound SMS)
- Download from: https://github.com/bogkonstantin/android_income_sms_gateway_webhook
- Alternative: Search "SMS Forwarder" on Play Store
- Configure:
  - Set webhook URL: `http://YOUR_SERVER_IP/bake_and_take/includes/sms_webhook.php`
  - Set the secret token (must match your PHP configuration)
  - Enable SMS forwarding

### 2. Configure PHP Application

Edit `includes/sms_config.php`:

```php
// SMSGate API endpoint - your Android device's IP
define('SMS_GATEWAY_URL', 'http://192.168.1.100:8080');

// Authentication credentials (match SMSGate app settings)
define('SMS_GATEWAY_USERNAME', 'admin');
define('SMS_GATEWAY_PASSWORD', 'your-password');

// Webhook security token (match SMS Forwarder app)
define('SMS_WEBHOOK_SECRET', 'your-secret-token-here');

// Country code for phone formatting
define('SMS_DEFAULT_COUNTRY_CODE', '+63'); // Philippines
```

### 3. Database Setup

Run the migration SQL:
```sql
-- Run this in phpMyAdmin or MySQL CLI
SOURCE database/migration_add_sms.sql;
```

Or use the command:
```bash
mysql -u root < database/migration_add_sms.sql
```

### 4. Network Configuration

Ensure all devices are on the same network:
- Android phone (SMSGate): Connected to WiFi
- XAMPP Server: Running on your computer
- Both should be on the same subnet (e.g., 192.168.1.x)

For external access (outside local network):
- Configure port forwarding on your router
- Use ngrok for testing: `ngrok http 80`
- Update webhook URL in SMS Forwarder app

## Features

### Outbound SMS
- **Order Confirmation**: Automatically sent when a new order is placed
- **Order Status Updates**: Sent when order status changes (confirmed, preparing, ready, etc.)
- **OTP Verification**: Send verification codes to customers
- **Manual SMS**: Send custom messages from the admin panel

### Inbound SMS
- **OTP Verification**: Customers can reply with OTP codes
- **Order Commands**: 
  - Reply `CONFIRM` to confirm pending orders
  - Reply `CANCEL` to cancel orders  
  - Reply `STATUS` to check order status
  - Reply `HELP` for available commands

### SMS Templates

Templates are defined in `includes/sms_config.php`:

```php
// Order confirmation
define('SMS_TEMPLATE_ORDER_CONFIRM', 
    '{store_name}: Hi {name}! Your order #{order_number} for ${total} has been received.'
);

// Order ready notification
define('SMS_TEMPLATE_ORDER_READY', 
    '{store_name}: Great news {name}! Your order #{order_number} is ready for pickup.'
);

// OTP message
define('SMS_TEMPLATE_OTP', 
    '{store_name}: Your verification code is {otp}. Valid for {expires} minutes.'
);
```

## Admin Panel

Access the SMS Gateway section in your admin panel:
`http://localhost/bake_and_take/admin/index.php?page=sms`

Features:
- View SMS statistics (sent, failed, received)
- Check gateway status
- View SMS log with filtering
- Send manual SMS
- Retry failed messages

## API Reference

### Send SMS
```php
require_once 'includes/sms_service.php';

// Send a simple SMS
$result = sendSMS('+639123456789', 'Hello, this is a test message!');

// Send with order reference
$result = sendSMS($phone, $message, $orderId, $userId);
```

### Send OTP
```php
// Generate and send OTP
$result = sendOTP('+639123456789', 'phone_verify');

// Verify OTP
$verified = verifyOTP('+639123456789', '123456');
```

### Order Notifications
```php
// Send order confirmation
sendOrderConfirmationSMS([
    'first_name' => 'John',
    'phone' => '+639123456789',
    'order_number' => 'ABC12345',
    'total' => 123.50,
    'order_id' => 1
]);

// Send order ready notification
sendOrderReadySMS($orderData);

// Send status update
sendOrderStatusSMS($orderData, 'preparing');
```

## Troubleshooting

### SMS not sending
1. Check if SMSGate app is running on Android
2. Verify network connectivity (same WiFi network)
3. Check `SMS_GATEWAY_URL` matches Android device IP
4. Verify authentication credentials
5. Check error logs in `logs/sms_webhook.log`

### Webhook not receiving
1. Ensure SMS Forwarder app is configured correctly
2. Check webhook URL is accessible from Android device
3. Verify `SMS_WEBHOOK_SECRET` matches
4. Check Apache/PHP logs for errors

### Phone number formatting issues
- Always use international format: `+639123456789`
- Numbers starting with `0` are auto-converted
- Country code defaults to `+63` (Philippines)

## Security Considerations

1. **Webhook Authentication**: Use a strong secret token
2. **IP Whitelisting**: Restrict webhook access to known IPs
3. **HTTPS**: Use HTTPS for external access (ngrok provides this)
4. **Rate Limiting**: Consider implementing rate limits for OTP
5. **Logging**: Monitor SMS logs for suspicious activity

## Files Reference

```
includes/
├── sms_config.php      # SMS configuration
├── sms_service.php     # SMS functions (send, OTP, etc.)
└── sms_webhook.php     # Webhook for receiving SMS

admin/
├── pages/sms.php       # Admin SMS dashboard
└── includes/
    ├── send_sms.php    # Manual SMS sending
    └── retry_sms.php   # Retry failed SMS

database/
└── migration_add_sms.sql  # Database tables for SMS

logs/
└── sms_webhook.log     # Webhook activity log
```

## Cost Considerations

This SMS integration uses your personal SIM card through the Android device, meaning:
- **No subscription fees** for SMS gateway services
- **Standard carrier rates** apply for each SMS sent
- **Free incoming** messages (depending on carrier)
- Ideal for small to medium businesses

Consider carrier SMS bundles or unlimited plans for high-volume usage.
