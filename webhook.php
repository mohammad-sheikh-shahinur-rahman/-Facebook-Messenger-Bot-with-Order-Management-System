<?php
/**
 * Facebook Automation System - Enhanced Webhook Handler
 * Handles messages, comments, and integrations
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Log incoming request
$input = file_get_contents('php://input');
log_message("Webhook Request: " . substr($input, 0, 500), 'INFO');

$data = json_decode($input, true);

// HTTP Verification (Webhook Setup)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    handle_verification();
} 
// Message/Comment Handling
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handle_webhook_events($data);
}

/**
 * Handle Facebook Webhook Verification
 * Facebook calls this endpoint with verify_token to confirm it's your webhook
 */
function handle_verification() {
    $token = $_GET['hub_verify_token'] ?? null;
    $challenge = $_GET['hub_challenge'] ?? null;
    
    if ($token === FB_VERIFY_TOKEN && $challenge) {
        log_message("Webhook verified successfully", 'INFO');
        echo $challenge;
        exit;
    }
    
    log_message("Webhook verification failed - Invalid token", 'WARNING');
    http_response_code(403);
    exit;
}

/**
 * Handle incoming messages and events
 */
function handle_messages($data) {
    // Verify it's from Facebook
    if ($data['object'] !== 'page') {
        http_response_code(400);
        exit;
    }
    
    // Process each entry
    foreach ($data['entry'] ?? [] as $entry) {
        // Handle messages
        foreach ($entry['messaging'] ?? [] as $messaging) {
            if (isset($messaging['message'])) {
                handle_message($messaging);
            }
            
            // Handle postback (menu clicks)
            if (isset($messaging['postback'])) {
                handle_postback($messaging);
            }
            
            // Handle quick reply
            if (isset($messaging['message']['quick_reply'])) {
                handle_quick_reply($messaging);
            }
        }
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
}

/**
 * Handle regular text messages
 */
function handle_message($messaging) {
    $sender_id = $messaging['sender']['id'];
    $recipient_id = $messaging['recipient']['id'];
    $message = $messaging['message'];
    $text = strtolower(trim($message['text'] ?? ''));
    
    log_message("Message from $sender_id: $text", 'INFO');
    
    // Check if user has an active session (ordering process)
    $session = get_user_session($sender_id);
    
    if ($session) {
        handle_order_flow($sender_id, $text, json_decode($session['session_data'], true));
        return;
    }
    
    // Handle keyword-based responses
    $response = get_keyword_response($text);
    
    if ($response) {
        send_facebook_message($sender_id, $response['type'], $response['data']);
    } else {
        // Default response
        send_facebook_message($sender_id, 'text', [
            'text' => "I didn't understand that. Type 'help' to see what I can do."
        ]);
    }
}

/**
 * Handle postback (persistent menu clicks)
 */
function handle_postback($messaging) {
    $sender_id = $messaging['sender']['id'];
    $payload = $messaging['postback']['payload'];
    
    log_message("Postback from $sender_id: $payload", 'INFO');
    
    switch ($payload) {
        case 'PRODUCTS_MENU':
            show_products($sender_id);
            break;
            
        case 'ORDER_MENU':
            start_order($sender_id);
            break;
            
        case 'SUPPORT_MENU':
            send_facebook_message($sender_id, 'text', [
                'text' => "📞 Contact Support:\n\n" .
                         "Email: support@example.com\n" .
                         "Phone: +1 (555) 123-4567\n" .
                         "Hours: Mon-Fri 9AM-6PM EST\n\n" .
                         "We typically respond within 24 hours."
            ]);
            break;
            
        default:
            send_facebook_message($sender_id, 'text', [
                'text' => "Unknown command. Type 'help' for assistance."
            ]);
    }
}

/**
 * Handle quick reply responses
 */
function handle_quick_reply($messaging) {
    $sender_id = $messaging['sender']['id'];
    $payload = $messaging['message']['quick_reply']['payload'];
    $text = strtolower(trim($messaging['message']['text'] ?? ''));
    
    log_message("Quick Reply from $sender_id: $payload", 'INFO');
    
    // Process based on the quick reply payload
    $session = get_user_session($sender_id);
    
    if ($session) {
        $session_data = json_decode($session['session_data'], true);
        handle_order_flow($sender_id, $text, $session_data, $payload);
    }
}

/**
 * Get response for keywords
 */
function get_keyword_response($text) {
    switch ($text) {
        case 'hi':
        case 'hello':
        case 'hey':
        case 'start':
            return [
                'type' => 'text',
                'data' => ['text' => get_welcome_message()]
            ];
            
        case 'products':
        case 'price':
        case 'menu':
            return null; // Will call show_products
            
        case 'order':
            return null; // Will call start_order
            
        case 'help':
            return [
                'type' => 'text',
                'data' => ['text' => get_help_message()]
            ];
            
        default:
            return null;
    }
}

/**
 * Show available products
 */
function show_products($sender_id) {
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
        [
            'title' => '🌮 Taco Pack (6)',
            'subtitle' => 'Assorted tacos with salsa',
            'price' => '$9.99'
        ],
        [
            'title' => '🍗 Chicken Wings (12)',
            'subtitle' => 'Crispy wings with sauce',
            'price' => '$14.99'
        ]
    ];
    
    $elements = [];
    foreach ($products as $product) {
        $elements[] = [
            'title' => $product['title'],
            'subtitle' => $product['subtitle'],
            'buttons' => [
                [
                    'type' => 'postback',
                    'title' => 'Order - ' . $product['price'],
                    'payload' => 'ORDER_ITEM_' . str_replace(' ', '_', $product['title'])
                ]
            ]
        ];
    }
    
    send_facebook_message($sender_id, 'generic', [
        'elements' => $elements
    ]);
}

/**
 * Start ordering process
 */
function start_order($sender_id) {
    // Initialize order session
    $session_data = [
        'step' => 'name',
        'data' => []
    ];
    
    save_user_session($sender_id, $session_data);
    
    send_facebook_message($sender_id, 'text', [
        'text' => "🛒 Let's start your order!\n\n" .
                 "Step 1 of 5: What's your name?"
    ]);
}

/**
 * Handle the order collection flow
 */
function handle_order_flow($sender_id, $text, $session_data, $payload = null) {
    $step = $session_data['step'] ?? 'name';
    $data = $session_data['data'] ?? [];
    
    switch ($step) {
        case 'name':
            if (strlen($text) < 2) {
                send_facebook_message($sender_id, 'text', [
                    'text' => "Please enter a valid name (at least 2 characters)."
                ]);
                return;
            }
            
            $data['name'] = sanitize($text);
            $session_data = [
                'step' => 'phone',
                'data' => $data
            ];
            
            save_user_session($sender_id, $session_data);
            send_facebook_message($sender_id, 'text', [
                'text' => "Nice to meet you, {$data['name']}! 👋\n\n" .
                         "Step 2 of 5: What's your phone number?\n" .
                         "(e.g., +1-555-1234567 or 555-1234567)"
            ]);
            break;
            
        case 'phone':
            if (!is_valid_phone($text)) {
                send_facebook_message($sender_id, 'text', [
                    'text' => "Please enter a valid phone number."
                ]);
                return;
            }
            
            $data['phone'] = sanitize($text);
            $session_data = [
                'step' => 'address',
                'data' => $data
            ];
            
            save_user_session($sender_id, $session_data);
            send_facebook_message($sender_id, 'text', [
                'text' => "Thanks! 📞\n\n" .
                         "Step 3 of 5: What's your delivery address?\n" .
                         "(e.g., 123 Main St, Apt 4, City, State ZIP)"
            ]);
            break;
            
        case 'address':
            if (strlen($text) < 10) {
                send_facebook_message($sender_id, 'text', [
                    'text' => "Please enter a complete address."
                ]);
                return;
            }
            
            $data['address'] = sanitize($text);
            $session_data = [
                'step' => 'product',
                'data' => $data
            ];
            
            save_user_session($sender_id, $session_data);
            send_facebook_message($sender_id, 'text', [
                'text' => "Perfect! 📍\n\n" .
                         "Step 4 of 5: What product would you like?\n" .
                         "Available options:\n" .
                         "1. Burger Combo - $12.99\n" .
                         "2. Pizza Large - $15.99\n" .
                         "3. Taco Pack - $9.99\n" .
                         "4. Chicken Wings - $14.99\n\n" .
                         "Reply with the product name or number."
            ]);
            break;
            
        case 'product':
            $product_map = [
                '1' => '🍔 Burger Combo',
                '2' => '🍕 Pizza Large',
                '3' => '🌮 Taco Pack (6)',
                '4' => '🍗 Chicken Wings (12)',
                'burger' => '🍔 Burger Combo',
                'pizza' => '🍕 Pizza Large',
                'taco' => '🌮 Taco Pack (6)',
                'wings' => '🍗 Chicken Wings (12)'
            ];
            
            $product = $product_map[$text] ?? null;
            
            if (!$product) {
                send_facebook_message($sender_id, 'text', [
                    'text' => "⚠️ Invalid product. Please choose from the list."
                ]);
                return;
            }
            
            $data['product'] = $product;
            $session_data = [
                'step' => 'quantity',
                'data' => $data
            ];
            
            save_user_session($sender_id, $session_data);
            send_facebook_message($sender_id, 'text', [
                'text' => "Great choice! {$product} ✨\n\n" .
                         "Step 5 of 5: How many would you like?\n" .
                         "(Enter a number: 1-10)"
            ]);
            break;
            
        case 'quantity':
            $qty = intval($text);
            
            if ($qty < 1 || $qty > 10) {
                send_facebook_message($sender_id, 'text', [
                    'text' => "Please enter a quantity between 1 and 10."
                ]);
                return;
            }
            
            $data['quantity'] = $qty;
            
            // Save order to database
            $order_id = create_order(
                $sender_id,
                $data['name'],
                $data['phone'],
                $data['address'],
                $data['product'],
                $qty,
                ""
            );
            
            if ($order_id) {
                log_message("Order created: ID=$order_id, User=$sender_id", 'INFO');
                
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
                
                // Clear session
                delete_customer_session($sender_id);
            } else {
                send_facebook_message($sender_id, 'text', [
                    'text' => "❌ Error creating order. Please try again."
                ]);
            }
            break;
    }
}

/**
 * Delete customer session (helper function)
 */
function delete_customer_session($user_id) {
    $sql = "DELETE FROM customer_sessions WHERE facebook_user_id = ?";
    db_query($sql, 'i', [&$user_id]);
}

?>
