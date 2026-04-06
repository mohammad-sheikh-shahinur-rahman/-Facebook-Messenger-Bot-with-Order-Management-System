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

$message_text = sanitize($_POST['message_text'] ?? '');
$facebook_user_id = sanitize($_POST['facebook_user_id'] ?? '');
$page_id = $_SESSION['page_id'] ?? '';
$admin_id = $_SESSION['admin_id'];

if (empty($message_text) || empty($facebook_user_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Save outgoing message
$db_msg_id = save_outgoing_message($page_id, $facebook_user_id, $message_text, 'agent_reply', 'messenger');

if (!$db_msg_id) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save message']);
    exit;
}

// Send to Facebook Messenger
$sent = send_messenger_message($page_id, $facebook_user_id, $message_text);

if ($sent) {
    // Update message status
    $sql = "UPDATE messages SET status = 'sent' WHERE id = ?";
    db_query($sql, 'i', [&$db_msg_id]);
    
    echo json_encode(['success' => true, 'message_id' => $db_msg_id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send to Facebook']);
}

exit;
?>
