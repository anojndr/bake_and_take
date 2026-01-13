<?php
require_once 'includes/config.php';

echo "=== Recent Orders ===\n";
$stmt = $pdo->query('SELECT id, order_number, phone, status, confirmation_method, confirmation_token, confirmed_at FROM orders ORDER BY id DESC LIMIT 5');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\n=== Recent SMS Logs (Inbound) ===\n";
$stmt = $pdo->query("SELECT * FROM sms_log WHERE direction = 'inbound' ORDER BY id DESC LIMIT 10");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\n=== Phone Number Check ===\n";
require_once 'includes/sms_service.php';
echo "Formatted phone: " . formatPhoneNumber('+639108449984') . "\n";
