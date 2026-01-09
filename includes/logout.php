<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Clear session
session_destroy();

header('Location: ../index.php');
exit;
?>
