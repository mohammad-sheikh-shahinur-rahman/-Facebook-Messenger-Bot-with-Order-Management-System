# Customization Guide

How to customize the bot for your specific business needs.

---

## 🎨 Branding & Text Customization

### Change Bot Welcome Message

**File:** `functions.php`
**Function:** `get_welcome_message()`

Current:
```php
function get_welcome_message() {
    return "👋 Welcome to our store!\n\n" .
           "I'm your automated shopping assistant. I can help you:\n" .
           "• View our products\n" .
           "• Place an order\n" .
           "• Track your orders\n\n" .
           "Type 'menu' to see all options or ask me anything!";
}
```

**To customize:**
1. Open `functions.php`
2. Find the `get_welcome_message()` function
3. Change the text inside the return statement
4. Use `\n` for line breaks
5. Use emoji for visual appeal

Example:
```php
function get_welcome_message() {
    return "👋 Welcome to ACME Pizza!\n\n" .
           "We deliver hot pizza to your door!\n" .
           "• 🍕 View Menu\n" .
           "• 📦 Place Order\n" .
           "• 📞 Call Support\n\n" .
           "What can I help you with?";
}
```

### Change Help Message

**File:** `functions.php`
**Function:** `get_help_message()`

```php
function get_help_message() {
    return "🆘 Here's what I can help you with:\n\n" .
           "📌 Commands:\n" .
           "• 'menu' - See products\n" .
           "• 'order' - Place order\n\n" .
           "Email: support@example.com";
}
```

---

## 🛍️ Customize Products

### Add/Remove Products

**File:** `webhook.php`
**Function:** `show_products()`

Current structure:
```php
$products = [
    [
        'title' => '🍔 Burger Combo',
        'subtitle' => 'Includes burger, fries & drink',
        'price' => '$12.99'
    ],
    [
        'title' => '🍕 Pizza Large',
        'subtitle' => 'Cheese & toppings included',
        'price' => '$15.99'
    ],
    // Add more here
];
```

**To add new product:**

1. Add new array to the $products array:
```php
[
    'title' => '🍗 Fried Chicken Pack',
    'subtitle' => '8 pieces with sauce',
    'price' => '$18.99'
],
```

**IMPORTANT: Also update order mapping**

In same file, find `handle_order_flow()` → `case 'product':` section:

```php
$product_map = [
    '1' => '🍔 Burger Combo',
    '2' => '🍕 Pizza Large',
    '3' => '🌮 Taco Pack (6)',
    '4' => '🍗 Chicken Wings (12)',
    'burger' => '🍔 Burger Combo',
    'pizza' => '🍕 Pizza Large',
    'taco' => '🌮 Taco Pack (6)',
    'wings' => '🍗 Chicken Wings (12)',
    // Add mapping for new product:
    '5' => '🍗 Fried Chicken Pack',
    'chicken' => '🍗 Fried Chicken Pack'
];
```

---

## 🤖 Customize Keywords & Commands

### Add New Keyword Response

**File:** `webhook.php`
**Function:** `get_keyword_response()`

Example - Add "weather" keyword:

```php
function get_keyword_response($text) {
    switch ($text) {
        // ... existing cases ...
        
        case 'weather':           // <- NEW
        case 'forecast':          // <- Alternative keyword
            return [
                'type' => 'text',
                'data' => ['text' => "🌤️ Weather Info:\n\nCheck local weather at weather.com"]
            ];
        
        case 'hours':
        case 'time':
            return [
                'type' => 'text',
                'data' => ['text' => "⏰ Business Hours:\nMon-Fri: 10am - 10pm\nWeekend: 11am - 11pm"]
            ];
        
        default:
            return null;
    }
}
```

### Change Support Information

**File:** `webhook.php`
**Function:** `handle_postback()` - Look for 'SUPPORT_MENU' case:

Current:
```php
case 'SUPPORT_MENU':
    send_facebook_message($sender_id, 'text', [
        'text' => "📞 Contact Support:\n\n" .
                 "Email: support@example.com\n" .
                 "Phone: +1 (555) 123-4567\n" .
                 "Hours: Mon-Fri 9AM-6PM EST\n\n" .
                 "We typically respond within 24 hours."
    ]);
    break;
```

Customize with your info:
```php
case 'SUPPORT_MENU':
    send_facebook_message($sender_id, 'text', [
        'text' => "📞 Contact US PIZZA:\n\n" .
                 "📧 Email: info@uspizza.com\n" .
                 "📱 Phone: +1 (212) 555-0123\n" .
                 "⏰ Hours: Daily 9AM-11PM\n" .
                 "🏠 Address: 123 Pizza Lane, NY\n\n" .
                 "Quick response guaranteed!"
    ]);
    break;
```

---

## 🎯 Customize Persistent Menu

**File:** `functions.php`
**Function:** `set_persistent_menu()`

Current:
```php
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
```

**To customize menu:**

```php
'call_to_actions' => [
    [
        'title' => '🍕 Pizza Menu',
        'type' => 'postback',
        'payload' => 'PRODUCTS_MENU'
    ],
    [
        'title' => '🛵 Fast Order',
        'type' => 'postback',
        'payload' => 'ORDER_MENU'
    ],
    [
        'title' => '📞 Call Us',
        'type' => 'postback',
        'payload' => 'SUPPORT_MENU'
    ],
    [
        'title' => '⭐ Reviews',
        'type' => 'web_url',
        'url' => 'https://facebook.com/yourpage'
    ]
]
```

Then handle new payload in `handle_postback()`:
```php
case 'REVIEWS':
    send_facebook_message($sender_id, 'text', [
        'text' => "⭐ Check our reviews on our Facebook page!"
    ]);
    break;
```

---

## 📝 Order Customization

### Change Order Confirmation Message

**File:** `webhook.php`
**Function:** `handle_order_flow()` - Find the confirmation section:

Current:
```php
send_facebook_message($sender_id, 'text', [
    'text' => "✅ Order Confirmed!\n\n" .
             "Order ID: #$order_id\n" .
             "Name: {$data['name']}\n" .
             "Product: {$data['product']}\n" .
             "Quantity: {$qty}\n" .
             "Address: {$data['address']}\n\n" .
             "We'll process your order soon! You'll receive updates via Messenger.\n\n" .
             "Thank you for your order! 🎉"
]);
```

Customize:
```php
send_facebook_message($sender_id, 'text', [
    'text' => "✅ 您的订单已确认！\n\n" .
             "订单号: #$order_id\n" .
             "姓名: {$data['name']}\n" .
             "商品: {$data['product']}\n" .
             "数量: {$qty}\n\n" .
             "我们会立即处理您的订单。\n" .
             "预计送达时间: 30分钟\n\n" .
             "感谢您的购买！🎉"
]);
```

### Change Order Max Steps

The bot currently follows 5 steps. Add a 6th step (notes):

```php
case 'quantity':
    // ... existing code ...
    $data['quantity'] = $qty;
    
    $session_data = [
        'step' => 'notes',  // <- NEW STEP
        'data' => $data
    ];
    save_user_session($sender_id, $session_data);
    send_facebook_message($sender_id, 'text', [
        'text' => "Any special notes or dietary restrictions?\n(Or type 'none')"
    ]);
    break;
    
case 'notes':
    $data['notes'] = sanitize($text);
    
    // Create order...
    $order_id = create_order(
        $sender_id,
        $data['name'],
        $data['phone'],
        $data['address'],
        $data['product'],
        $data['quantity'],
        $data['notes']  // <- ADD NOTES
    );
    break;
```

---

## 💾 Database Customization

### Add Custom Order Fields

Add to `database.sql`:
```sql
ALTER TABLE orders ADD COLUMN delivery_date DATE;
ALTER TABLE orders ADD COLUMN special_instructions TEXT;
ALTER TABLE orders ADD COLUMN estimated_cost DECIMAL(10,2);
```

Then update PHP functions to include these fields:

```php
// In functions.php, create_order() function:
$sql = "INSERT INTO orders (
    facebook_user_id, customer_name, phone, address, product, quantity, 
    notes, delivery_date, special_instructions, status, created_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

$stmt = db_query($sql, 'isssssssss', [
    &$user_id, &$customer_name, &$phone, &$address, &$product, 
    &$quantity, &$notes, &$delivery_date, &$special_instructions
]);
```

---

## 🎨 Admin Dashboard Customization

### Change Dashboard Title

**File:** `admin/header.php`

Find:
```php
<h3><i class="fas fa-robot"></i> FB Bot</h3>
```

Change to:
```php
<h3><i class="fas fa-pizza-slice"></i> Pizza Store Bot</h3>
```

### Change Colors

All CSS is in `admin/header.php` - Find the `<style>` section:

```css
/* Change gradient color */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
/* To: */
background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); /* Orange theme */

/* Change text colors */
color: #667eea;
/* To: */
color: #ff6b35;
```

### Add Custom Admin Page

Create `admin/custom-page.php`:
```php
<?php $page_title = 'Custom Page'; require_once __DIR__ . '/header.php'; ?>

<div class="card">
    <div class="card-header">My Custom Section</div>
    <div class="card-body">
        <p>Your content here</p>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
```

Then add to menu in `header.php`:
```php
<li>
    <a href="custom-page.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'custom-page.php' ? 'active' : ''; ?>">
        <i class="fas fa-star"></i>
        <span>Custom Page</span>
    </a>
</li>
```

---

## 🌐 Multi-Language Support

Add language detection:

**File:** `config.php`

```php
// Add after database config
define('BOT_LANGUAGE', 'en'); // 'en', 'es', 'zh', 'fr', etc

function translate($key) {
    $translations = [
        'en' => [
            'welcome' => 'Welcome to our store!',
            'order' => 'Place Order',
            'products' => 'View Products'
        ],
        'es' => [
            'welcome' => '¡Bienvenido a nuestra tienda!',
            'order' => 'Hacer Pedido',
            'products' => 'Ver Productos'
        ],
        'zh' => [
            'welcome' => '欢迎来到我们的商店！',
            'order' => '下单',
            'products' => '查看产品'
        ]
    ];
    
    return $translations[BOT_LANGUAGE][$key] ?? $key;
}
```

Then use in messages:
```php
'text' => translate('welcome')
```

---

## 📧 Email Integration

Add order confirmation emails:

**File:** `functions.php`

```php
function send_order_confirmation_email($customer_email, $order) {
    $subject = "Order Confirmation #" . $order['id'];
    
    $message = "Thank you for your order!\n\n";
    $message .= "Order ID: #" . $order['id'] . "\n";
    $message .= "Product: " . $order['product'] . "\n";
    $message .= "Quantity: " . $order['quantity'] . "\n";
    $message .= "Status: " . ucfirst($order['status']) . "\n";
    
    mail($customer_email, $subject, $message);
}
```

Use in webhook:
```php
send_order_confirmation_email("customer@example.com", [
    'id' => $order_id,
    'product' => $data['product'],
    'quantity' => $qty,
    'status' => 'pending'
]);
```

---

## 🎁 Add Coupon/Discount System

Add to database:
```sql
CREATE TABLE coupons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE,
    discount_percent INT,
    valid_until DATE,
    used INT DEFAULT 0
);
```

Add to order flow:
```php
case 'coupon':
    // Validate coupon
    $sql = "SELECT discount_percent FROM coupons WHERE code = ? AND valid_until >= CURDATE()";
    $stmt = db_query($sql, 's', [&$text]);
    $result = $stmt->get_result();
    $coupon = $result->fetch_assoc();
    
    if ($coupon) {
        $data['discount'] = $coupon['discount_percent'];
        send_facebook_message($sender_id, 'text', [
            'text' => "✅ Coupon applied! " . $coupon['discount_percent'] . "% OFF"
        ]);
    } else {
        send_facebook_message($sender_id, 'text', [
            'text' => "❌ Invalid coupon code"
        ]);
    }
    break;
```

---

## 📱 Notifications

Add order notifications to admin:

**File:** `functions.php`

```php
function notify_admin_new_order($order_id) {
    $order = get_order($order_id);
    
    $message = "New Order #$order_id\n";
    $message .= "Customer: {$order['customer_name']}\n";
    $message .= "Product: {$order['product']} x {$order['quantity']}\n";
    
    // Send email
    mail('admin@example.com', 'New Order Alert', $message);
    
    // Or send to Telegram bot, Slack, etc
}
```

Call after creating order:
```php
if ($order_id) {
    notify_admin_new_order($order_id);
}
```

---

## 🔒 Add Password Reset

Create `admin/forgot-password.php`

---

## 📊 Custom Reports

Create `admin/reports.php` with:
- Orders by time range
- Revenue reports
- Top customers
- Repeat customer analysis

---

Need More Help?
- Check API_REFERENCE.md for function documentation
- See DEPLOYMENT.md for production tips
- Review webhook.php for event handling examples
