<?php
/**
 * Admin Logout
 */
session_start();

// Clear only admin session variables
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_name']);

header('Location: ../login.php');
exit;
?>
