<?php
/**
 * Test SMS Webhook - Simulates an incoming SMS
 * 
 * This script simulates what the SMS Forwarder app would send
 * when a customer replies to the confirmation SMS.
 * 
 * Usage: php test_sms_webhook.php [phone_number] [message]
 * Example: php test_sms_webhook.php "+639108449984" "CONFIRM"
 */

// Get phone number and message from command line args
$phoneNumber = $argv[1] ?? '+639108449984';
$message = $argv[2] ?? 'CONFIRM';

echo "Testing SMS Webhook...\n";
echo "Phone: $phoneNumber\n";
echo "Message: $message\n\n";

// Simulate the payload from SMS Forwarder app
$payload = json_encode([
    'from' => $phoneNumber,
    'text' => $message,
    'sentStamp' => time(),
    'receivedStamp' => time(),
    'sim' => 'SIM1'
]);

// Get the webhook URL
$webhookUrl = 'http://localhost/bake_and_take/includes/sms_webhook.php';

// If running on a live server, use actual URL
if (php_sapi_name() === 'cli') {
    // Direct include approach for CLI testing
    echo "Using direct include approach...\n\n";
    
    // Simulate $_SERVER and input
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    
    // Create a temporary input stream
    $tempFile = tempnam(sys_get_temp_dir(), 'sms_test_');
    file_put_contents($tempFile, $payload);
    
    // We need to directly call the processing function
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/sms_config.php';
    require_once __DIR__ . '/includes/sms_service.php';
    
    // Format phone number
    $formattedPhone = formatPhoneNumber($phoneNumber);
    echo "Formatted phone: $formattedPhone\n";
    
    // Check for pending orders
    if ($pdo) {
        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE phone = ? AND status = 'pending' AND confirmation_method = 'sms'
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$formattedPhone]);
        $pendingOrder = $stmt->fetch();
        
        if ($pendingOrder) {
            echo "Found pending order: #{$pendingOrder['order_number']}\n";
            
            // Check if message is CONFIRM
            if (strtoupper(trim($message)) === 'CONFIRM') {
                echo "Message is CONFIRM - updating order status...\n";
                
                // Update order status
                $updateStmt = $pdo->prepare("
                    UPDATE orders 
                    SET status = 'confirmed', 
                        confirmed_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$pendingOrder['id']]);
                
                echo "✓ Order #{$pendingOrder['order_number']} status updated to 'confirmed'\n";
                
                // Send confirmation SMS
                $result = sendOrderConfirmedSMS([
                    'phone' => $formattedPhone,
                    'order_number' => $pendingOrder['order_number'],
                    'order_id' => $pendingOrder['id'],
                    'user_id' => $pendingOrder['user_id']
                ]);
                
                echo "SMS send result: " . ($result['success'] ? 'Success' : 'Failed - ' . $result['message']) . "\n";
                
            } else {
                echo "Message is not CONFIRM - ignoring\n";
            }
        } else {
            echo "No pending orders found for phone: $formattedPhone\n";
            
            // Check if there are any orders at all
            $stmt = $pdo->prepare("SELECT order_number, status, confirmation_method FROM orders WHERE phone = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$formattedPhone]);
            $anyOrder = $stmt->fetch();
            
            if ($anyOrder) {
                echo "Latest order for this phone: #{$anyOrder['order_number']} (status: {$anyOrder['status']}, method: {$anyOrder['confirmation_method']})\n";
            }
        }
    } else {
        echo "Database connection failed!\n";
    }
    
    // Clean up
    unlink($tempFile);
}

echo "\nDone.\n";
