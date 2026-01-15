<?php
/**
 * Admin Login Processor
 * Only allows admin accounts to login
 */
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    header('Location: ../login.php?error=' . urlencode('Invalid request. Please try again.'));
    exit;
}

$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: ../login.php?error=' . urlencode('Please enter both email and password.'));
    exit;
}

if (!isValidEmail($email)) {
    header('Location: ../login.php?error=' . urlencode('Please enter a valid email address.'));
    exit;
}

global $conn;
if (!$conn) {
    header('Location: ../login.php?error=' . urlencode('Database connection error.'));
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT user_id, first_name, last_name, email, password, is_admin FROM users WHERE email = ? AND is_admin = 1");
if (!$stmt) {
    header('Location: ../login.php?error=' . urlencode('Login failed. Please try again.'));
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user || !password_verify($password, $user['password'])) {
    header('Location: ../login.php?error=' . urlencode('Invalid admin credentials.'));
    exit;
}

// Set admin session variables (separate from main site)
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = $user['user_id'];
$_SESSION['admin_email'] = $user['email'];
$_SESSION['admin_name'] = $user['first_name'] . ' ' . $user['last_name'];

header('Location: ../index.php');
exit;
?>
