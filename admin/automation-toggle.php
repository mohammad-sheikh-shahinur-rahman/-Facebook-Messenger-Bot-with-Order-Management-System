<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF invalid']);
    exit;
}

$rule_id = intval($_POST['rule_id'] ?? 0);
$is_active = intval($_POST['is_active'] ?? 0);

if (!$rule_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid rule ID']);
    exit;
}

$sql = "UPDATE automation_rules SET is_active = ? WHERE id = ? AND page_id = ?";
$page_id = $_SESSION['page_id'] ?? '';
$stmt = db_query($sql, 'iis', [&$is_active, &$rule_id, &$page_id]);

if ($stmt) {
    echo json_encode(['success' => true, 'message' => 'Rule updated']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update rule']);
}

exit;
?>
