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

$action = $_POST['action'] ?? '';
$comment_id = intval($_POST['comment_id'] ?? 0);

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF invalid']);
    exit;
}

if (!$comment_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid comment ID']);
    exit;
}

global $mysqli;

if ($action === 'hide') {
    $sql = "UPDATE comments SET is_hidden = 1 WHERE id = ? AND page_id = ?";
    $page_id = $_SESSION['page_id'] ?? '';
    $stmt = db_query($sql, 'is', [&$comment_id, &$page_id]);
    
    if ($stmt) {
        echo json_encode(['success' => true, 'message' => 'Comment hidden']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to hide comment']);
    }
} elseif ($action === 'show') {
    $sql = "UPDATE comments SET is_hidden = 0 WHERE id = ? AND page_id = ?";
    $page_id = $_SESSION['page_id'] ?? '';
    $stmt = db_query($sql, 'is', [&$comment_id, &$page_id]);
    
    if ($stmt) {
        echo json_encode(['success' => true, 'message' => 'Comment made visible']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to show comment']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}

exit;
?>
