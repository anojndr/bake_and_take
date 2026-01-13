<?php
require_once __DIR__ . '/../includes/mailer.php';

header('Content-Type: text/plain');

$to = defined('SMTP_USER') ? SMTP_USER : '';

echo "SMTP_HOST=" . (defined('SMTP_HOST') ? SMTP_HOST : '') . PHP_EOL;
echo "SMTP_PORT=" . (defined('SMTP_PORT') ? SMTP_PORT : '') . PHP_EOL;
echo "SMTP_USER set=" . ((defined('SMTP_USER') && SMTP_USER !== '') ? 'yes' : 'no') . PHP_EOL;
echo "SMTP_PASS set=" . ((defined('SMTP_PASS') && SMTP_PASS !== '') ? 'yes' : 'no') . PHP_EOL;

if ($to === '') {
    echo "\nERROR: SMTP_USER is empty. Configure includes/secrets.php.\n";
    exit(2);
}

$result = sendMail(
    $to,
    'Bake & Take SMTP Test',
    'Test email sent at ' . date('c'),
    false
);

echo "\nResult:\n";
print_r($result);

if (!is_array($result) || empty($result['success'])) {
    exit(1);
}

exit(0);
