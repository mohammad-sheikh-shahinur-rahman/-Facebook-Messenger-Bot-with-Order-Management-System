<?php
/**
 * Logout Handler
 */

session_start();

require_once __DIR__ . '/../config.php';

if (isset($_SESSION['admin_id'])) {
    log_message("Admin logout: " . $_SESSION['admin_username'], 'INFO');
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: login.php');
exit;
?>
