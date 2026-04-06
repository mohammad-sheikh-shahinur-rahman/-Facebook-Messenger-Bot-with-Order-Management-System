<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$page_id = $_SESSION['page_id'] ?? '';
$filter_status = $_GET['status'] ?? '';
$selected_user = $_GET['user'] ?? '';

// Get recent conversations (unique facebook_user_ids)
global $mysqli;
$sql = "SELECT 
    DISTINCT m.facebook_user_id,
    c.name,
    c.avatar_url,
    MAX(m.created_at) as last_message_at,
    (SELECT COUNT(*) FROM messages WHERE facebook_user_id = m.facebook_user_id AND page_id = ? AND status = 'new') as unread_count,
    (SELECT message_text FROM messages WHERE facebook_user_id = m.facebook_user_id AND page_id = ? ORDER BY created_at DESC LIMIT 1) as last_message
FROM messages m
LEFT JOIN customers c ON m.page_id = c.page_id AND m.facebook_user_id = c.facebook_user_id
WHERE m.page_id = ?
ORDER BY last_message_at DESC
LIMIT 50";

$stmt = db_query($sql, 'sss', [&$page_id, &$page_id, &$page_id]);
$conversations = [];

if ($stmt) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $conversations[] = $row;
    }
    $stmt->close();
}

// Get messages for selected user
$selected_messages = [];
if ($selected_user) {
    $selected_messages = get_conversation($page_id, $selected_user, 100);
}

// Get stats
$stats = get_dashboard_stats($page_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages & Chat - Facebook Automation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #7c3aed;
            --secondary: #a78bfa;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            min-height: 100vh;
            padding: 20px;
            position: fixed;
            width: 250px;
            left: 0;
            top: 0;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .chat-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            height: calc(100vh - 120px);
        }
        
        .conversations-panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .conversations-header {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }
        
        .conversations-list {
            overflow-y: auto;
            flex: 1;
        }
        
        .conversation-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .conversation-item:hover {
            background-color: #f9fafb;
        }
        
        .conversation-item.active {
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.1) 0%, rgba(167, 139, 250, 0.1) 100%);
            border-left: 4px solid var(--primary);
            padding-left: 11px;
        }
        
        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        
        .conversation-info {
            flex: 1;
            min-width: 0;
        }
        
        .conversation-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.95rem;
        }
        
        .conversation-preview {
            font-size: 0.85rem;
            color: #6b7280;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .unread-badge {
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .chat-panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f9fafb;
        }
        
        .message-group {
            margin-bottom: 15px;
            display: flex;
        }
        
        .message-group.incoming {
            justify-content: flex-start;
        }
        
        .message-group.outgoing {
            justify-content: flex-end;
        }
        
        .message-bubble {
            max-width: 60%;
            padding: 12px 15px;
            border-radius: 12px;
            word-wrap: break-word;
            font-size: 0.95rem;
            line-height: 1.4;
        }
        
        .message-bubble.incoming {
            background: #e5e7eb;
            color: #1f2937;
        }
        
        .message-bubble.outgoing {
            background: var(--primary);
            color: white;
        }
        
        .message-bubble.bot {
            background: #dbeafe;
            color: #0c4a6e;
            border-left: 3px solid #0284c7;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 4px;
            text-align: center;
        }
        
        .chat-input-area {
            padding: 15px 20px;
            border-top: 1px solid #e5e7eb;
            background: white;
            display: flex;
            gap: 10px;
        }
        
        .chat-input-area textarea {
            flex: 1;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            padding: 10px;
            resize: none;
            font-size: 0.95rem;
            font-family: inherit;
        }
        
        .chat-input-area textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        
        .btn-send {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .btn-send:hover {
            background: #6d28d9;
            transform: translateY(-2px);
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #9ca3af;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-nav li {
            margin: 10px 0;
        }
        
        .sidebar-nav a {
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            display: block;
            transition: all 0.3s ease;
        }
        
        .sidebar-nav a:hover {
            background-color: rgba(255,255,255,0.2);
        }
        
        .sidebar-nav a.active {
            background-color: white;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h5 class="mb-4"><i class="bi bi-robot"></i> Facebook Bot</h5>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a href="messages.php" class="active"><i class="bi bi-chat-dots"></i> Messages</a></li>
            <li><a href="comments.php"><i class="bi bi-chat-left-text"></i> Comments</a></li>
            <li><a href="orders.php"><i class="bi bi-bag"></i> Orders</a></li>
            <li><a href="automation-rules.php"><i class="bi bi-gear"></i> Automation</a></li>
            <li><a href="customers.php"><i class="bi bi-people"></i> Customers</a></li>
            <li><a href="analytics.php"><i class="bi bi-graph-up"></i> Analytics</a></li>
            <li><a href="settings.php"><i class="bi bi-sliders"></i> Settings</a></li>
            <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div style="margin-bottom: 20px;">
            <h4><i class="bi bi-chat-dots"></i> Real-Time Messages</h4>
            <small class="text-muted">View and reply to customer messages</small>
        </div>

        <div class="chat-container">
            <!-- Conversations List -->
            <div class="conversations-panel">
                <div class="conversations-header">
                    <h6 class="mb-0"><i class="bi bi-chat-left-dots"></i> Conversations (<?php echo count($conversations); ?>)</h6>
                </div>
                <div class="conversations-list">
                    <?php if (empty($conversations)): ?>
                        <div class="empty-state">
                            <p class="mb-0">No conversations</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                            <div class="conversation-item <?php echo $selected_user === $conv['facebook_user_id'] ? 'active' : ''; ?>" 
                                 onclick="selectConversation('<?php echo htmlspecialchars($conv['facebook_user_id']); ?>')">
                                <div class="avatar">
                                    <?php echo strtoupper(substr($conv['name'] ?? 'U', 0, 1)); ?>
                                </div>
                                <div class="conversation-info">
                                    <div class="conversation-name"><?php echo htmlspecialchars($conv['name'] ?? 'Unknown User'); ?></div>
                                    <div class="conversation-preview"><?php echo htmlspecialchars(substr($conv['last_message'] ?? '', 0, 40)); ?></div>
                                </div>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <div class="unread-badge"><?php echo $conv['unread_count']; ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Panel -->
            <div class="chat-panel">
                <?php if (!$selected_user): ?>
                    <div class="empty-state">
                        <i class="bi bi-chat-dots" style="font-size: 3rem; color: #d1d5db;"></i>
                        <p class="mt-3">Select a conversation to start chatting</p>
                    </div>
                <?php else: ?>
                    <?php
                    $selected_customer = get_customer($page_id, $selected_user);
                    ?>
                    
                    <div class="chat-header">
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($selected_customer['name'] ?? 'Customer'); ?></h6>
                            <small><?php echo htmlspecialchars($selected_customer['phone'] ?? 'No phone'); ?></small>
                        </div>
                        <div>
                            <span class="badge bg-light text-dark"><?php echo $selected_customer['total_messages'] ?? 0; ?> messages</span>
                        </div>
                    </div>

                    <div class="chat-messages" id="chatMessages">
                        <?php foreach ($selected_messages as $msg): ?>
                            <div class="message-group <?php echo $msg['message_type'] === 'incoming' ? 'incoming' : ($msg['message_type'] === 'bot_reply' ? 'incoming' : 'outgoing'); ?>">
                                <div>
                                    <div class="message-bubble <?php echo $msg['message_type'] === 'bot_reply' ? 'bot' : ($msg['message_type'] === 'incoming' ? 'incoming' : 'outgoing'); ?>">
                                        <?php echo nl2br(htmlspecialchars($msg['message_text'])); ?>
                                    </div>
                                    <div class="message-time"><?php echo date('H:i', strtotime($msg['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form class="chat-input-area" method="POST" action="send-message.php" onsubmit="sendMessage(event)">
                        <textarea name="message_text" placeholder="Type a message..." rows="1" required></textarea>
                        <input type="hidden" name="facebook_user_id" value="<?php echo htmlspecialchars($selected_user); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <button type="submit" class="btn-send">Send</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectConversation(userId) {
            window.location.href = '?user=' + encodeURIComponent(userId);
        }

        function sendMessage(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            fetch('send-message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    event.target.querySelector('textarea').value = '';
                    // Reload messages
                    location.reload();
                } else {
                    alert('Error sending message: ' + data.error);
                }
            })
            .catch(err => console.error('Error:', err));
        }

        // Auto-refresh chat messages every 3 seconds
        setInterval(() => {
            const user = new URLSearchParams(window.location.search).get('user');
            if (user) {
                fetch('get-messages.php?user=' + encodeURIComponent(user))
                    .then(response => response.json())
                    .then(data => {
                        if (data.messages) {
                            const chatMessages = document.getElementById('chatMessages');
                            if (chatMessages) {
                                // Update if new messages exist
                                const lastTime = chatMessages.querySelector('.message-time:last-child')?.textContent;
                                // Simple comparison - could be improved
                            }
                        }
                    });
            }
        }, 3000);
    </script>
</body>
</html>
