<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: comments.php');
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token invalid']);
    exit;
}

$comment_id = intval($_POST['comment_id'] ?? 0);
$reply_text = sanitize($_POST['reply_text'] ?? '');
$send_message = isset($_POST['send_message']) ? 1 : 0;

if (!$comment_id || empty($reply_text)) {
    $_SESSION['error'] = 'Missing required fields';
    header('Location: comments.php');
    exit;
}

// Get comment details
global $mysqli;
$sql = "SELECT * FROM comments WHERE id = ? AND page_id = ?";
$page_id = $_SESSION['page_id'] ?? '';
$stmt = db_query($sql, 'is', [&$comment_id, &$page_id]);

if (!$stmt) {
    $_SESSION['error'] = 'Comment not found';
    header('Location: comments.php');
    exit;
}

$result = $stmt->get_result();
$comment = $result->fetch_assoc();
$stmt->close();

if (!$comment) {
    $_SESSION['error'] = 'Comment not found';
    header('Location: comments.php');
    exit;
}

// Post reply to Facebook
if ($comment['facebook_comment_id']) {
    $reply_success = post_comment_reply($page_id, $comment['facebook_comment_id'], $reply_text);
    
    if (!$reply_success) {
        $_SESSION['warning'] = 'Could not post to Facebook, but reply saved locally.';
    }
}

// Save manual reply in database
$saved = reply_to_comment($comment_id, $reply_text, 'manual', $_SESSION['admin_id']);

if ($saved) {
    // Send message if checkbox is checked
    if ($send_message && $comment['commenter_id']) {
        send_messenger_message($page_id, $comment['commenter_id'], "Thanks for your comment! " . $reply_text);
    }
    
    $_SESSION['success'] = 'Reply sent successfully!';
} else {
    $_SESSION['error'] = 'Failed to save reply';
}

header('Location: comments.php');
exit;
?>
