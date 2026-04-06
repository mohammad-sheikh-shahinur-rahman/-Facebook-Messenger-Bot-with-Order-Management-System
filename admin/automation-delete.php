<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$rule_id = intval($_GET['id'] ?? 0);
$page_id = $_SESSION['page_id'] ?? '';

if (!$rule_id) {
    $_SESSION['error'] = 'Invalid rule ID';
    header('Location: automation-rules.php');
    exit;
}

// Get rule
global $mysqli;
$sql = "SELECT * FROM automation_rules WHERE id = ? AND page_id = ?";
$stmt = db_query($sql, 'is', [&$rule_id, &$page_id]);

if (!$stmt) {
    $_SESSION['error'] = 'Rule not found';
    header('Location: automation-rules.php');
    exit;
}

$result = $stmt->get_result();
$rule = $result->fetch_assoc();
$stmt->close();

if (!$rule) {
    $_SESSION['error'] = 'Rule not found';
    header('Location: automation-rules.php');
    exit;
}

// Delete rule
$delete_sql = "DELETE FROM automation_rules WHERE id = ? AND page_id = ?";
$delete_stmt = db_query($delete_sql, 'is', [&$rule_id, &$page_id]);

if ($delete_stmt) {
    $_SESSION['success'] = 'Rule deleted successfully';
} else {
    $_SESSION['error'] = 'Failed to delete rule';
}

header('Location: automation-rules.php');
exit;
?>
