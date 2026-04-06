# Quick Reference & API Documentation

Fast lookup guide for common operations.

---

## 📚 Quick Links

| Task | File | Location |
|------|------|----------|
| Edit Database Settings | config.php | Line 15-19 |
| Edit Facebook Credentials | config.php | Line 24-28 |
| Edit Welcome Message | functions.php | `get_welcome_message()` |
| Edit Bot Keywords | webhook.php | `get_keyword_response()` |
| Edit Products List | webhook.php | `show_products()` |
| Admin Login Page | admin/login.php | http://yourdomain.com/admin/login.php |
| Admin Dashboard | admin/dashboard.php | http://yourdomain.com/admin/dashboard.php |

---

## 🔧 Common Customizations

### 1. Add New Product

**In webhook.php, find `show_products()` function:**

```php
function show_products($sender_id) {
    $products = [
        [
            'title' => '🍔 Burger Combo',
            'subtitle' => 'Includes burger, fries & drink',
            'price' => '$12.99'
        ],
        [
            'title' => '🍕 NEW PRODUCT',
            'subtitle' => 'Your description',
            'price' => '$19.99'  // <- ADD HERE
        ],
    ];
    // Rest of function
}
```

**Also update keyboard in `handle_order_flow()` function:**

```php
case 'product':
    $product_map = [
        '1' => '🍔 Burger Combo',
        '5' => '🍕 NEW PRODUCT',  // <- ADD HERE
        // ...
    ];
```

### 2. Change Welcome Message

**In functions.php:**

```php
function get_welcome_message() {
    return "👋 Welcome to MY STORE NAME!\n\n" .
           "What I can help with:\n" .
           "• Product Info\n" .
           "• Place Orders\n\n" .
           "Type 'menu' for more!";
}
```

### 3. Add New Keyword Response

**In webhook.php, find `get_keyword_response()` function:**

```php
function get_keyword_response($text) {
    switch ($text) {
        // ... existing cases ...
        
        case 'hi':
        case 'hello':
            return ['type' => 'text', 'data' => ['text' => get_welcome_message()]];
        
        case 'deals':        // <- ADD NEW KEYWORD
        case 'special':
            return [
                'type' => 'text',
                'data' => ['text' => "🎉 Special Offers:\n100% OFF on first order!\nUse code: WELCOME"]
            ];
            
        default:
            return null;
    }
}
```

### 4. Change Order Status Flow

**In functions.php, `update_order_status()` function:**

```php
$valid_statuses = ['pending', 'confirmed', 'delivered', 'cancelled'];
// Add new status here:
$valid_statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
```

---

## 📊 Database Functions Reference

### Get Orders

```php
// Get all orders (pagination)
$orders = get_all_orders($limit = 50, $offset = 0, $status = '');

// Get single order
$order = get_order($order_id = 1);

// Get order count
$count = get_order_count($status = ''); // $status can be 'pending', 'confirmed', etc
```

### Manage Orders

```php
// Create new order
$order_id = create_order(
    $user_id,           // Facebook User ID
    $customer_name,     // "John Doe"
    $phone,             // "+1-555-1234567"
    $address,           // "123 Main St"
    $product,           // "🍔 Burger Combo"
    $quantity,          // 2
    $notes = ''         // Optional notes
);

// Update order status
update_order_status(
    $order_id = 1,
    $status = 'confirmed'  // 'pending', 'confirmed', 'delivered', 'cancelled'
);

// Delete order
delete_order($order_id = 1);
```

### Session Management

```php
// Get or create session
$session_data = [
    'step' => 'name',
    'data' => []
];
$session_id = save_user_session($facebook_user_id, $session_data);

// Get existing session
$session = get_user_session($session_id);
```

---

## 💬 Facebook Messaging Functions

### Send Messages

```php
// Send text message
send_facebook_message($recipient_id, 'text', [
    'text' => 'Hello user!'
]);

// Send message with quick replies
send_facebook_message($recipient_id, 'quick_reply', [
    'text' => 'Choose one:',
    'quick_replies' => [
        ['content_type' => 'text', 'title' => 'Yes', 'payload' => 'YES'],
        ['content_type' => 'text', 'title' => 'No', 'payload' => 'NO']
    ]
]);

// Send button template
send_facebook_message($recipient_id, 'buttons', [
    'text' => 'What do you want?',
    'buttons' => [
        ['type' => 'postback', 'title' => 'View Products', 'payload' => 'PRODUCTS'],
        ['type' => 'web_url', 'title' => 'Visit Website', 'url' => 'https://example.com']
    ]
]);

// Send product carousel
send_facebook_message($recipient_id, 'generic', [
    'elements' => [
        [
            'title' => 'Product Name',
            'subtitle' => 'Product description',
            'buttons' => [
                ['type' => 'postback', 'title' => 'Order', 'payload' => 'ORDER_1']
            ]
        ]
    ]
]);
```

---

## 🛡️ Utility Functions

### Sanitization & Validation

```php
// Sanitize input
$safe_input = sanitize($user_input);

// Validate email
if (is_valid_email($email)) { }

// Validate phone
if (is_valid_phone($phone)) { }

// Hash password
$hash = hash_password($password);

// Verify password
if (verify_password($input_password, $hash)) { }

// Generate CSRF token
$token = generate_csrf_token();

// Verify CSRF token
if (verify_csrf_token($submitted_token)) { }
```

### Data Export & Analytics

```php
// Get dashboard statistics
$stats = get_dashboard_stats();
// Returns: ['total_orders', 'pending_orders', 'confirmed_orders', 'delivered_orders']

// Export orders to CSV
$csv = export_orders_to_csv($status = '');

// Get status badge CSS class
$badge_class = get_status_badge($status);
// Returns: 'badge-warning', 'badge-info', etc
```

---

## 🔌 Webhook Events

### Incoming Message Event

```json
{
    "object": "page",
    "entry": [{
        "messaging": [{
            "sender": {"id": "USER_ID"},
            "recipient": {"id": "PAGE_ID"},
            "timestamp": 1234567890,
            "message": {
                "mid": "MID123",
                "text": "hello"
            }
        }]
    }]
}
```

### Postback Event (Button Click)

```json
{
    "sender": {"id": "USER_ID"},
    "recipient": {"id": "PAGE_ID"},
    "postback": {
        "title": "Button Title",
        "payload": "BUTTON_PAYLOAD"
    }
}
```

### Quick Reply Event

```json
{
    "message": {
        "mid": "MID123",
        "text": "Yes",
        "quick_reply": {
            "payload": "YES_PAYLOAD"
        }
    }
}
```

---

## 📊 Admin Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| /admin/login.php | GET/POST | Admin login |
| /admin/logout.php | GET | Admin logout |
| /admin/dashboard.php | GET | Dashboard with stats |
| /admin/orders.php | GET | List all orders |
| /admin/order-add.php | GET/POST | Create new order |
| /admin/order-edit.php | GET/POST | Edit order details |
| /admin/order-delete.php | GET | Delete order |
| /admin/analytics.php | GET | Charts and stats |
| /admin/settings.php | GET | Configuration page |
| /admin/export-csv.php | GET | Export to CSV |

---

## 🔐 Security Functions

### Check Admin Login

```php
// In any admin file:
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
```

### Verify CSRF Token

```php
// In form processing:
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $error = 'Security token invalid';
    exit;
}
```

### Prepared Statements

```php
// Safe database query
$sql = "SELECT * FROM orders WHERE id = ? AND status = ?";
$stmt = db_query($sql, 'is', [&$order_id, &$status]);
```

---

## 📝 Database Schema Quick Reference

### Orders Table

```sql
-- Main orders table
orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    facebook_user_id VARCHAR(255),
    customer_name VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    product VARCHAR(255),
    quantity INT,
    status ENUM('pending', 'confirmed', 'delivered', 'cancelled'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)

-- Get orders by status
SELECT * FROM orders WHERE status = 'pending';

-- Get customer history
SELECT * FROM orders WHERE facebook_user_id = '123' ORDER BY created_at DESC;

-- Get orders from last 7 days
SELECT * FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);
```

### Admins Table

```sql
admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE,
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255), -- bcrypt hash
    status ENUM('active', 'inactive'),
    created_at TIMESTAMP
)
```

### Sessions Table

```sql
customer_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    facebook_user_id VARCHAR(255) UNIQUE,
    session_id VARCHAR(255),
    session_data LONGTEXT, -- JSON encoded
    expires_at DATETIME
)
```

---

## 🚨 Error Codes

| Code | Message | Solution |
|------|---------|----------|
| 001 | Database Connection Failed | Check credentials in config.php |
| 002 | Invalid email | Use valid email format |
| 003 | Invalid phone | Phone must have 7+ digits |
| 004 | CSRF token invalid | Refresh page, try again |
| 005 | Webhook verification failed | Check token and URL |
| 006 | Order not found | Check order ID exists |
| 007 | Insufficient permissions | Verify admin login |
| 008 | Session expired | Login again |
| 009 | Message send failed | Check Facebook token |
| 010 | Database error | Check SQL syntax |

---

## 🧪 Testing Commands

### Test Database Connection

```bash
mysql -h localhost -u fb_bot -p fb_messenger_bot -e "SELECT 1;"
```

### Test Webhook Endpoint

```bash
# Verification
curl -X GET "https://yourdomain.com/fb-bot/webhook.php?hub.mode=subscribe&hub.verify_token=your_token&hub.challenge=test"

# Receive message (simulate)
curl -X POST https://yourdomain.com/fb-bot/webhook.php \
  -H "Content-Type: application/json" \
  -d '{"object":"page","entry":[{"messaging":[{"sender":{"id":"123"},"message":{"text":"hello"}}]}]}'
```

### Test Admin Login

```bash
curl -X POST https://yourdomain.com/fb-bot/admin/login.php \
  -d "username=admin&password=admin123" \
  -c cookies.txt

curl -b cookies.txt https://yourdomain.com/fb-bot/admin/dashboard.php
```

---

## 📞 Support

For detailed setup: See SETUP.md
For deployment: See DEPLOYMENT.md
For troubleshooting: See DEPLOYMENT.md Troubleshooting section
