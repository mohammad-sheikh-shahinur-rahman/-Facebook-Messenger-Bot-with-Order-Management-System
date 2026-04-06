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

$facebook_user_id = sanitize($_GET['user'] ?? '');
$page_id = $_SESSION['page_id'] ?? '';
$last_id = intval($_GET['last_id'] ?? 0);

if (empty($facebook_user_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user ID']);
    exit;
}

// Get new messages
$sql = "SELECT * FROM messages WHERE page_id = ? AND facebook_user_id = ?";
$types = 'ss';
$params = [&$page_id, &$facebook_user_id];

if ($last_id > 0) {
    $sql .= " AND id > ?";
    $types .= 'i';
    $params[] = &$last_id;
}

$sql .= " ORDER BY created_at ASC LIMIT 50";

$stmt = db_query($sql, $types, $params);
$messages = [];

if ($stmt) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'id' => $row['id'],
            'text' => htmlspecialchars($row['message_text']),
            'type' => $row['message_type'],
            'time' => date('H:i', strtotime($row['created_at']))
        ];
    }
    $stmt->close();
}

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'count' => count($messages)
]);

exit;
?>
