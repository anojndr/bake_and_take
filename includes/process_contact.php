<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php?page=contact');
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirect('../index.php?page=contact', 'Invalid request. Please try again.', 'error');
}

// Sanitize inputs
$firstName = sanitize($_POST['first_name'] ?? '');
$lastName = sanitize($_POST['last_name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$subject = sanitize($_POST['subject'] ?? '');
$message = sanitize($_POST['message'] ?? '');

// Validate required fields
if (empty($firstName) || empty($lastName) || empty($email) || empty($subject) || empty($message)) {
    redirect('../index.php?page=contact', 'Please fill in all required fields.', 'error');
}

// Validate email
if (!isValidEmail($email)) {
    redirect('../index.php?page=contact', 'Please enter a valid email address.', 'error');
}

require_once 'mailer.php';

// 1. Send email to Admin
$adminBody = "
    <h2>New Contact Message</h2>
    <p><strong>Name:</strong> {$firstName} {$lastName}</p>
    <p><strong>Email:</strong> {$email}</p>
    <p><strong>Phone:</strong> {$phone}</p>
    <p><strong>Subject:</strong> {$subject}</p>
    <p><strong>Message:</strong><br>{$message}</p>
";

// We send the notification to the configured SMTP_USER
// In a real scenario, you might want to send this to a specific admin email
$result = sendMail(SMTP_USER, "New Contact: $subject", $adminBody);

if ($result['success']) {
    // 2. Send confirmation to User
    $userBody = "
        <p>Hi {$firstName},</p>
        <p>Thank you for contacting Bake & Take. We have received your message regarding '{$subject}' and will get back to you shortly.</p>
        <br>
        <p>Best regards,<br>Bake & Take Team</p>
    ";
    sendMail($email, "We received your message", $userBody);

    redirect('../index.php?page=contact', 'Thank you for your message! We sent you a confirmation email.', 'success');
} else {
    // If sending fails (e.g. config not set), notify user but don't crash
    // In production you might want to log this error
    redirect('../index.php?page=contact', 'Message received, but email notification failed. Please check SMTP configuration.', 'warning');
}
?>
