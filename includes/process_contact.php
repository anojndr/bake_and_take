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

// In production, you would:
// 1. Save to database
// 2. Send email notification
// 3. Send confirmation email to user

// For now, we'll just simulate success
redirect('../index.php?page=contact', 'Thank you for your message! We\'ll get back to you within 24 hours.', 'success');
?>
