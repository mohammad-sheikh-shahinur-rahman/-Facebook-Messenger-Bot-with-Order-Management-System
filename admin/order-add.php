<?php
$page_title = 'Add New Order';
require_once __DIR__ . '/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid. Please try again.';
    } else {
        $customer_name = sanitize($_POST['customer_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $product = sanitize($_POST['product'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $facebook_user_id = sanitize($_POST['facebook_user_id'] ?? 'manual_' . time());
        
        if (empty($customer_name) || empty($phone) || empty($address) || empty($product) || $quantity < 1) {
            $error = 'All fields are required and quantity must be at least 1.';
        } else {
            $order_id = create_order($facebook_user_id, $customer_name, $phone, $address, $product, $quantity);
            
            if ($order_id) {
                $success = "Order created successfully! Order ID: #$order_id";
                log_message("Order #$order_id created manually by admin", 'INFO');
            } else {
                $error = 'Failed to create order.';
            }
        }
    }
}

$products = ['🍔 Burger Combo', '🍕 Pizza Large', '🌮 Taco Pack (6)', '🍗 Chicken Wings (12)'];
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus"></i> Create New Order
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Customer Name *</label>
                                <input type="text" class="form-control" name="customer_name" placeholder="Enter customer name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" name="phone" placeholder="+1-555-1234567" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Delivery Address *</label>
                        <textarea class="form-control" name="address" rows="3" placeholder="123 Main St, City, State ZIP" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Product *</label>
                                <select class="form-control" name="product" required>
                                    <option value="">Select Product</option>
                                    <?php
                                    foreach ($products as $prod) {
                                        echo "<option value=\"" . htmlspecialchars($prod) . "\">" . htmlspecialchars($prod) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantity *</label>
                                <input type="number" class="form-control" name="quantity" min="1" max="10" placeholder="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Create Order
                        </button>
                        <a href="orders.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-lightbulb"></i> Tips
            </div>
            <div class="card-body">
                <p><strong>📝 How to add orders:</strong></p>
                <ul>
                    <li>Enter customer details</li>
                    <li>Select product from list</li>
                    <li>Set quantity (1-10)</li>
                    <li>Click "Create Order"</li>
                </ul>
                <hr>
                <p><strong>💡 Note:</strong> Orders can also be created automatically through the Messenger bot.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
