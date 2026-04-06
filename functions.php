<?php
/**
 * Merged Helper Functions - Facebook Automation System with Order Management
 * Combines messaging, comments, orders, CRM, and automation capabilities
 */

require_once __DIR__ . '/config.php';

// ╔════════════════════════════════════╗
// ║   DATABASE FUNCTIONS              ║
// ╚════════════════════════════════════╝

/**
 * Execute a database query
 */
function db_query($sql, $types = '', $params = []) {
    global $mysqli;
    
    // In development mode without database, return a stub result
    if (defined('DEV_MODE_NO_DB') || !$mysqli) {
        log_message("Database unavailable (dev mode): " . substr($sql, 0, 50) . "...", 'DEBUG');
        return false;
    }
    
    try {
        if (!empty($types)) {
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $mysqli->error);
            }
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            return $stmt;
        } else {
            $result = $mysqli->query($sql);
            if (!$result && $mysqli->errno) {
                throw new Exception("Query failed: " . $mysqli->error);
            }
            return $result;
        }
    } catch (Exception $e) {
        log_message("Database Error: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Get all orders
 */
function get_all_orders($limit = 50, $offset = 0, $status = '') {
    global $mysqli;
    
    $sql = "SELECT * FROM orders";
    
    if (!empty($status)) {
        $sql .= " WHERE status = '" . $mysqli->real_escape_string($status) . "'";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
    
    $result = db_query($sql);
    $orders = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $result->free();
    }
    
    return $orders;
}

/**
 * Get single order by ID
 */
function get_order($order_id) {
    global $mysqli;
    
    $sql = "SELECT * FROM orders WHERE id = ?";
    $stmt = db_query($sql, 'i', [&$order_id]);
    
    if ($stmt) {
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        return $order;
    }
    
    return null;
}

/**
 * Create a new order
 */
function create_order($user_id, $customer_name, $phone, $address, $product, $quantity, $notes = '') {
    global $mysqli;
    
    $sql = "INSERT INTO orders (facebook_user_id, customer_name, phone, address, product, quantity, notes, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    
    $stmt = db_query($sql, 'issssss', [&$user_id, &$customer_name, &$phone, &$address, &$product, &$quantity, &$notes]);
    
    if ($stmt) {
        $order_id = $mysqli->insert_id;
        $stmt->close();
        return $order_id;
    }
    
    return false;
}

/**
 * Update order status
 */
function update_order_status($order_id, $status) {
    global $mysqli;
    
    $valid_statuses = ['pending', 'confirmed', 'delivered', 'cancelled'];
    
    if (!in_array($status, $valid_statuses)) {
        return false;
    }
    
    $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = db_query($sql, 'si', [&$status, &$order_id]);
    
    if ($stmt) {
        $stmt->close();
        return true;
    }
    
    return false;
}

/**
 * Delete order
 */
function delete_order($order_id) {
    global $mysqli;
    
    $sql = "DELETE FROM orders WHERE id = ?";
    $stmt = db_query($sql, 'i', [&$order_id]);
    
    if ($stmt) {
        $stmt->close();
        return true;
    }
    
    return false;
}

/**
 * Get order count
 */
function get_order_count($status = '') {
    global $mysqli;
    
    $sql = "SELECT COUNT(*) as count FROM orders";
    
    if (!empty($status)) {
        $sql .= " WHERE status = '" . $mysqli->real_escape_string($status) . "'";
    }
    
    $result = db_query($sql);
    
    if ($result) {
        $row = $result->fetch_assoc();
        $result->free();
        return $row['count'];
    }
    
    return 0;
}

/**
 * Get user session
 */
function get_user_session($session_id) {
    global $mysqli;
    
    $sql = "SELECT * FROM customer_sessions WHERE session_id = ? AND expires_at > NOW()";
    $stmt = db_query($sql, 's', [&$session_id]);
    
    if ($stmt) {
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();
        return $session;
    }
    
    return null;
}

/**
 * Create or update user session
 */
function save_user_session($user_id, $session_data) {
    global $mysqli;
    
    $session_id = bin2hex(random_bytes(16));
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $data_json = json_encode($session_data);
    
    $sql = "INSERT INTO customer_sessions (facebook_user_id, session_id, session_data, expires_at)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE session_id = VALUES(session_id), session_data = VALUES(session_data), expires_at = VALUES(expires_at)";
    
    $stmt = db_query($sql, 'isss', [&$user_id, &$session_id, &$data_json, &$expires_at]);
    
    if ($stmt) {
        $stmt->close();
        return $session_id;
    }
    
    return false;
}

// ╔════════════════════════════════════╗
// ║   FACEBOOK MESSAGING FUNCTIONS    ║
// ╚════════════════════════════════════╝

/**
 * Send message to user via Facebook
 */
function send_facebook_message($recipient_id, $message_type = 'text', $message_data = []) {
    $url = FB_GRAPH_URL . '/me/messages';
    
    $payload = [
        'recipient' => [
            'id' => $recipient_id
        ],
        'access_token' => FB_PAGE_ACCESS_TOKEN
    ];
    
    // Build message field based on type
    switch ($message_type) {
        case 'text':
            $payload['message'] = [
                'text' => $message_data['text'] ?? ''
            ];
            break;
            
        case 'quick_reply':
            $payload['message'] = [
                'text' => $message_data['text'] ?? '',
                'quick_replies' => $message_data['quick_replies'] ?? []
            ];
            break;
            
        case 'buttons':
            $payload['message'] = [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text' => $message_data['text'] ?? '',
                        'buttons' => $message_data['buttons'] ?? []
                    ]
                ]
            ];
            break;
            
        case 'generic':
            $payload['message'] = [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'generic',
                        'elements' => $message_data['elements'] ?? []
                    ]
                ]
            ];
            break;
    }
    
    return send_http_request($url, $payload);
}

/**
 * Send HTTP Request for Facebook API
 */
function send_http_request($url, $payload) {
    $ch = curl_init($url);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    curl_close($ch);
    
    if ($curl_error) {
        log_message("cURL Error: " . $curl_error, 'ERROR');
        return false;
    }
    
    if ($http_code !== 200) {
        log_message("HTTP Error $http_code: " . $response, 'ERROR');
        return false;
    }
    
    log_message("Message sent successfully to recipient");
    return json_decode($response, true);
}

/**
 * Set Persistent Menu
 */
function set_persistent_menu() {
    $url = FB_GRAPH_URL . '/me/messenger_profile';
    
    $payload = [
        'access_token' => FB_PAGE_ACCESS_TOKEN,
        'persistent_menu' => [
            [
                'locale' => 'default',
                'composer_input_disabled' => false,
                'call_to_actions' => [
                    [
                        'title' => '📦 View Products',
                        'type' => 'postback',
                        'payload' => 'PRODUCTS_MENU'
                    ],
                    [
                        'title' => '🛒 Place Order',
                        'type' => 'postback',
                        'payload' => 'ORDER_MENU'
                    ],
                    [
                        'title' => '💬 Contact Support',
                        'type' => 'postback',
                        'payload' => 'SUPPORT_MENU'
                    ]
                ]
            ]
        ]
    ];
    
    send_http_request($url, $payload);
}

/**
 * Get welcome message
 */
function get_welcome_message() {
    return "👋 Welcome to our store!\n\n" .
           "I'm your automated shopping assistant. I can help you:\n" .
           "• View our products\n" .
           "• Place an order\n" .
           "• Track your orders\n\n" .
           "Type 'menu' to see all options or ask me anything!";
}

/**
 * Get help message
 */
function get_help_message() {
    return "🆘 Here's what I can help you with:\n\n" .
           "📌 Commands:\n" .
           "• 'hi' or 'hello' - Get a welcome message\n" .
           "• 'products' or 'price' - See our product list\n" .
           "• 'order' - Start a new order\n" .
           "• 'help' - Show this message\n\n" .
           "❓ Need more help? Contact our support team!";
}

// ╔════════════════════════════════════╗
// ║   UTILITY FUNCTIONS               ║
// ╚════════════════════════════════════╝

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone (basic international format)
 */
function is_valid_phone($phone) {
    $phone = preg_replace('/[^0-9+\-\(\) ]/', '', $phone);
    return strlen($phone) >= 7;
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Hash password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Get order status badge color
 */
function get_status_badge($status) {
    $badges = [
        'pending' => 'badge-warning',
        'confirmed' => 'badge-info',
        'processing' => 'badge-primary',
        'shipped' => 'badge-purple',
        'delivered' => 'badge-success',
        'cancelled' => 'badge-danger',
        'open' => 'badge-info',
        'closed' => 'badge-success',
        'waiting' => 'badge-warning'
    ];
    return $badges[$status] ?? 'badge-secondary';
}

/**
 * Get dashboard stats (enhanced with multi-page support)
 */
function get_dashboard_stats($page_id = '') {
    global $mysqli;
    
    $stats = [
        'total_messages' => 0,
        'today_messages' => 0,
        'total_comments' => 0,
        'today_comments' => 0,
        'total_orders' => 0,
        'today_orders' => 0,
        'pending_orders' => 0,
        'total_customers' => 0,
        'revenue_today' => 0
    ];
    
    if (empty($page_id)) {
        // Fallback for simple case
        return [
            'total_orders' => get_order_count(),
            'pending_orders' => get_order_count('pending'),
            'confirmed_orders' => get_order_count('confirmed'),
            'delivered_orders' => get_order_count('delivered')
        ];
    }
    
    // Messages
    $result = db_query("SELECT COUNT(*) as count FROM messages WHERE page_id = ?", 's', [&$page_id]);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_messages'] = $row['count'];
        $result->free();
    }
    
    $result = db_query("SELECT COUNT(*) as count FROM messages WHERE page_id = ? AND DATE(created_at) = CURDATE()", 's', [&$page_id]);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['today_messages'] = $row['count'];
        $result->free();
    }
    
    // Comments
    $result = db_query("SELECT COUNT(*) as count FROM comments WHERE page_id = ?", 's', [&$page_id]);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_comments'] = $row['count'];
        $result->free();
    }
    
    $result = db_query("SELECT COUNT(*) as count FROM comments WHERE page_id = ? AND DATE(created_at) = CURDATE()", 's', [&$page_id]);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['today_comments'] = $row['count'];
        $result->free();
    }
    
    // Orders
    $result = db_query("SELECT COUNT(*) as count FROM orders WHERE page_id = ?", 's', [&$page_id]);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_orders'] = $row['count'];
        $result->free();
    }
    
    $result = db_query("SELECT COUNT(*) as count FROM orders WHERE page_id = ? AND DATE(created_at) = CURDATE()", 's', [&$page_id]);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['today_orders'] = $row['count'];
        $result->free();
    }
    
    $result = db_query("SELECT COUNT(*) as count FROM orders WHERE page_id = ? AND status = 'pending'", 's', [&$page_id]);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['pending_orders'] = $row['count'];
        $result->free();
    }
    
    // Customers
    $result = db_query("SELECT COUNT(*) as count FROM customers WHERE page_id = ?", 's', [&$page_id]);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_customers'] = $row['count'];
        $result->free();
    }
    
    return $stats;
}

/**
 * Export orders to CSV
 */
function export_orders_to_csv($status = '') {
    $orders = get_all_orders(PHP_INT_MAX, 0, $status);
    
    $csv = "Order ID,Customer Name,Phone,Address,Product,Quantity,Status,Created At\n";
    
    foreach ($orders as $order) {
        $csv .= sprintf(
            '"%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
            $order['id'],
            $order['customer_name'],
            $order['phone'],
            $order['address'],
            $order['product'],
            $order['quantity'],
            $order['status'],
            $order['created_at']
        );
    }
    
    return $csv;
}

// ╔════════════════════════════════════╗
// ║   MESSAGE FUNCTIONS (ENHANCED)    ║
// ╚════════════════════════════════════╝

function save_incoming_message($page_id, $sender_id, $message_text, $meta = []) {
    $msg_type = 'incoming';
    $source = 'messenger';
    $meta_json = json_encode($meta);
    
    $sql = "INSERT INTO messages (page_id, facebook_user_id, message_text, message_type, source, meta)
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = db_query($sql, 'ssssss', [&$page_id, &$sender_id, &$message_text, &$msg_type, &$source, &$meta_json]);
    
    if ($stmt) {
        global $mysqli;
        $message_id = $mysqli->insert_id;
        $stmt->close();
        if (function_exists('update_customer_last_message')) {
            update_customer_last_message($page_id, $sender_id);
        }
        return $message_id;
    }
    
    return false;
}

function save_outgoing_message($page_id, $recipient_id, $message_text, $type = 'bot_reply', $source = 'messenger') {
    global $mysqli;
    
    $meta_json = json_encode(['sent_at' => date('Y-m-d H:i:s')]);
    
    $sql = "INSERT INTO messages (page_id, facebook_user_id, message_text, message_type, source, meta)
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = db_query($sql, 'ssssss', [&$page_id, &$recipient_id, &$message_text, &$type, &$source, &$meta_json]);
    
    if ($stmt) {
        $message_id = $mysqli->insert_id;
        $stmt->close();
        return $message_id;
    }
    
    return false;
}

function get_messages($page_id, $limit = 50, $offset = 0, $status = '', $assigned_to = null) {
    global $mysqli;
    
    $sql = "SELECT * FROM messages WHERE page_id = ?";
    $types = 's';
    $params = [&$page_id];
    
    if (!empty($status)) {
        $sql .= " AND status = ?";
        $types .= 's';
        $params[] = &$status;
    }
    
    if ($assigned_to) {
        $sql .= " AND assigned_to = ?";
        $types .= 'i';
        $params[] = &$assigned_to;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $types .= 'ii';
    $params[] = &$limit;
    $params[] = &$offset;
    
    $stmt = db_query($sql, $types, $params);
    $messages = [];
    
    if ($stmt) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();
    }
    
    return $messages;
}

function get_conversation($page_id, $facebook_user_id, $limit = 50) {
    global $mysqli;
    
    $sql = "SELECT m.*, c.name as customer_name, c.phone as customer_phone 
            FROM messages m
            LEFT JOIN customers c ON m.page_id = c.page_id AND m.facebook_user_id = c.facebook_user_id
            WHERE m.page_id = ? AND m.facebook_user_id = ?
            ORDER BY m.created_at ASC
            LIMIT ?";
    
    $stmt = db_query($sql, 'ssi', [&$page_id, &$facebook_user_id, &$limit]);
    $messages = [];
    
    if ($stmt) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();
    }
    
    return $messages;
}

function assign_message_to_agent($message_id, $admin_id) {
    $sql = "UPDATE messages SET assigned_to = ?, status = 'replied' WHERE id = ?";
    $stmt = db_query($sql, 'ii', [&$admin_id, &$message_id]);
    
    if ($stmt) {
        $stmt->close();
        return true;
    }
    
    return false;
}

function send_messenger_message($page_id, $recipient_id, $message_text) {
    $url = FB_GRAPH_URL . '/me/messages';
    
    $payload = [
        'recipient' => ['id' => $recipient_id],
        'message' => ['text' => $message_text],
        'access_token' => FB_PAGE_ACCESS_TOKEN
    ];
    
    return send_http_request($url, $payload);
}

// ╔════════════════════════════════════╗
// ║   COMMENT FUNCTIONS (ENHANCED)    ║
// ╚════════════════════════════════════╝

function save_comment($page_id, $post_id, $comment_id, $commenter_id, $commenter_name, $comment_text, $sentiment = 'neutral') {
    global $mysqli;
    
    $sql = "INSERT INTO comments (page_id, post_id, facebook_comment_id, commenter_id, commenter_name, comment_text, sentiment)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = db_query($sql, 'sssssss', [&$page_id, &$post_id, &$comment_id, &$commenter_id, &$commenter_name, &$comment_text, &$sentiment]);
    
    if ($stmt) {
        $comment_db_id = $mysqli->insert_id;
        $stmt->close();
        return $comment_db_id;
    }
    
    return false;
}

function get_comments($page_id, $limit = 50, $offset = 0, $sentiment = '', $replied = null) {
    global $mysqli;
    
    $sql = "SELECT * FROM comments WHERE page_id = ?";
    $types = 's';
    $params = [&$page_id];
    
    if (!empty($sentiment) && $sentiment !== 'all') {
        $sql .= " AND sentiment = ?";
        $types .= 's';
        $params[] = &$sentiment;
    }
    
    if ($replied !== null) {
        if ($replied) {
            $sql .= " AND (auto_reply_sent = 1 OR manual_reply_sent = 1)";
        } else {
            $sql .= " AND auto_reply_sent = 0 AND manual_reply_sent = 0";
        }
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $types .= 'ii';
    $params[] = &$limit;
    $params[] = &$offset;
    
    $stmt = db_query($sql, $types, $params);
    $comments = [];
    
    if ($stmt) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        $stmt->close();
    }
    
    return $comments;
}

function reply_to_comment($comment_id, $reply_text, $reply_type = 'auto', $admin_id = null) {
    $sql = "UPDATE comments SET ";
    
    if ($reply_type === 'auto') {
        $sql .= "auto_reply_sent = 1, auto_reply_text = ? ";
    } else {
        $sql .= "manual_reply_sent = 1, manual_reply_text = ?, replied_by = ? ";
    }
    
    $sql .= "WHERE id = ?";
    
    if ($reply_type === 'auto') {
        $stmt = db_query($sql, 'si', [&$reply_text, &$comment_id]);
    } else {
        $stmt = db_query($sql, 'sii', [&$reply_text, &$admin_id, &$comment_id]);
    }
    
    if ($stmt) {
        $stmt->close();
        return true;
    }
    
    return false;
}

function hide_comment($comment_id, $reason = 'negative') {
    $sql = "UPDATE comments SET is_hidden = 1 WHERE id = ?";
    $stmt = db_query($sql, 'i', [&$comment_id]);
    
    if ($stmt) {
        $stmt->close();
        return true;
    }
    
    return false;
}

function sentiment_analysis($text) {
    $text = strtolower($text);
    $negative_count = 0;
    $positive_count = 0;
    
    $negative_words = ['bad', 'worst', 'scam', 'fraud', 'waste', 'terrible', 'awful', 'horrible'];
    $positive_words = ['good', 'great', 'excellent', 'love', 'awesome', 'amazing', 'best'];
    
    foreach ($negative_words as $word) {
        if (strpos($text, $word) !== false) $negative_count++;
    }
    
    foreach ($positive_words as $word) {
        if (strpos($text, $word) !== false) $positive_count++;
    }
    
    if ($negative_count > $positive_count) return 'negative';
    if ($positive_count > $negative_count) return 'positive';
    return 'neutral';
}

function post_comment_reply($page_id, $comment_id, $reply_text) {
    $url = FB_GRAPH_URL . '/' . $comment_id . '/private_replies';
    
    $payload = [
        'message' => $reply_text,
        'access_token' => FB_PAGE_ACCESS_TOKEN
    ];
    
    $result = send_http_request($url, $payload);
    return $result !== false;
}

// ╔════════════════════════════════════╗
// ║   CUSTOMER/CRM FUNCTIONS          ║
// ╚════════════════════════════════════╝

function create_or_update_customer($page_id, $facebook_user_id, $name = '', $phone = '', $email = '', $address = '') {
    global $mysqli;
    
    $existing = get_customer($page_id, $facebook_user_id);
    
    if ($existing) {
        $sql = "UPDATE customers SET ";
        $updates = [];
        $types = '';
        $params = [];
        
        if (!empty($name)) { $updates[] = "name = ?"; $types .= 's'; $params[] = &$name; }
        if (!empty($phone)) { $updates[] = "phone = ?"; $types .= 's'; $params[] = &$phone; }
        if (!empty($email)) { $updates[] = "email = ?"; $types .= 's'; $params[] = &$email; }
        if (!empty($address)) { $updates[] = "address = ?"; $types .= 's'; $params[] = &$address; }
        
        if (!empty($updates)) {
            $sql .= implode(", ", $updates) . " WHERE page_id = ? AND facebook_user_id = ?";
            $types .= 'ss';
            $params[] = &$page_id;
            $params[] = &$facebook_user_id;
            
            $stmt = db_query($sql, $types, $params);
            if ($stmt) $stmt->close();
        }
    } else {
        $sql = "INSERT INTO customers (page_id, facebook_user_id, name, phone, email, address)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = db_query($sql, 'ssssss', [&$page_id, &$facebook_user_id, &$name, &$phone, &$email, &$address]);
        if ($stmt) $stmt->close();
    }
    
    return get_customer($page_id, $facebook_user_id);
}

function get_customer($page_id, $facebook_user_id) {
    $sql = "SELECT * FROM customers WHERE page_id = ? AND facebook_user_id = ?";
    $stmt = db_query($sql, 'ss', [&$page_id, &$facebook_user_id]);
    
    if ($stmt) {
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $stmt->close();
        return $customer;
    }
    
    return null;
}

function update_customer_last_message($page_id, $facebook_user_id) {
    $sql = "UPDATE customers SET last_message_at = NOW(), total_messages = total_messages + 1 
            WHERE page_id = ? AND facebook_user_id = ?";
    db_query($sql, 'ss', [&$page_id, &$facebook_user_id]);
}

function update_customer_order_count($page_id, $facebook_user_id) {
    $sql = "UPDATE customers SET total_orders = total_orders + 1 WHERE page_id = ? AND facebook_user_id = ?";
    db_query($sql, 'ss', [&$page_id, &$facebook_user_id]);
}

function add_customer_tag($page_id, $facebook_user_id, $tag) {
    $customer = get_customer($page_id, $facebook_user_id);
    
    if (!$customer) return false;
    
    $tags = !empty($customer['tags']) ? explode(',', $customer['tags']) : [];
    
    if (!in_array($tag, $tags)) {
        $tags[] = $tag;
    }
    
    $tags_str = implode(',', $tags);
    
    $sql = "UPDATE customers SET tags = ? WHERE id = ?";
    $stmt = db_query($sql, 'si', [&$tags_str, &$customer['id']]);
    
    return $stmt ? true : false;
}

// ╔════════════════════════════════════╗
// ║   AUTOMATION RULES FUNCTIONS      ║
// ╚════════════════════════════════════╝

function get_automation_rules($page_id, $rule_type = '') {
    global $mysqli;
    
    $sql = "SELECT * FROM automation_rules WHERE page_id = ? AND is_active = 1";
    $types = 's';
    $params = [&$page_id];
    
    if (!empty($rule_type)) {
        $sql .= " AND rule_type = ?";
        $types .= 's';
        $params[] = &$rule_type;
    }
    
    $sql .= " ORDER BY priority DESC";
    
    $stmt = db_query($sql, $types, $params);
    $rules = [];
    
    if ($stmt) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rules[] = $row;
        }
        $stmt->close();
    }
    
    return $rules;
}

function check_keyword_match($text, $keyword) {
    $text = strtolower($text);
    $keyword = strtolower($keyword);
    
    return strpos($text, $keyword) !== false;
}

function get_auto_reply($page_id, $text, $rule_type = 'message') {
    $rules = get_automation_rules($page_id, $rule_type);
    
    foreach ($rules as $rule) {
        if (check_keyword_match($text, $rule['trigger_keyword'])) {
            return $rule;
        }
    }
    
    return null;
}

?>

