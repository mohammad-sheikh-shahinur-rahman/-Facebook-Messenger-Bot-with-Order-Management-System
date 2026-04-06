<?php
/**
 * Order Delete Handler
 */

session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$order_id = $_GET['id'] ?? null;
$csrf_token = $_GET['csrf'] ?? null;

// Verify CSRF token
if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
    header('Location: orders.php?error=csrf_invalid');
    exit;
}

if (!$order_id || !is_numeric($order_id)) {
    header('Location: orders.php?error=invalid_order');
    exit;
}

// Verify order exists
$order = get_order($order_id);
if (!$order) {
    header('Location: orders.php?error=order_not_found');
    exit;
}

// Delete order
if (delete_order($order_id)) {
    log_message("Order #$order_id deleted by admin", 'INFO');
    header('Location: orders.php?success=order_deleted');
} else {
    header('Location: orders.php?error=delete_failed');
}
exit;
?>
