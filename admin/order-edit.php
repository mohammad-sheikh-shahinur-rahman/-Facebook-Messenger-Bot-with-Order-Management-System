<?php
$page_title = 'Edit Order';
require_once __DIR__ . '/header.php';

$order_id = $_GET['id'] ?? null;
$error = '';
$success = '';

if (!$order_id || !is_numeric($order_id)) {
    header('Location: orders.php');
    exit;
}

$order = get_order($order_id);

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid. Please try again.';
    } else {
        $customer_name = sanitize($_POST['customer_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $product = sanitize($_POST['product'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');
        
        if (empty($customer_name) || empty($phone) || empty($address) || empty($product) || $quantity < 1) {
            $error = 'All fields are required and quantity must be at least 1.';
        } else {
            global $mysqli;
            
            $sql = "UPDATE orders SET customer_name = ?, phone = ?, address = ?, product = ?, quantity = ?, status = ?, updated_at = NOW() WHERE id = ?";
            $stmt = db_query($sql, 'sssssii', [&$customer_name, &$phone, &$address, &$product, &$quantity, &$status, &$order_id]);
            
            if ($stmt) {
                $stmt->close();
                $success = 'Order updated successfully!';
                $order = get_order($order_id);
                log_message("Order #$order_id updated by admin", 'INFO');
            } else {
                $error = 'Failed to update order.';
            }
        }
    }
}

$products = ['🍔 Burger Combo', '🍕 Pizza Large', '🌮 Taco Pack (6)', '🍗 Chicken Wings (12)'];
$statuses = ['pending', 'confirmed', 'delivered', 'cancelled'];
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-edit"></i> Order #<?php echo htmlspecialchars($order['id']); ?>
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
                                <input type="text" class="form-control" name="customer_name" value="<?php echo htmlspecialchars($order['customer_name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($order['phone']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Delivery Address *</label>
                        <textarea class="form-control" name="address" rows="3" required><?php echo htmlspecialchars($order['address']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Product *</label>
                                <select class="form-control" name="product" required>
                                    <option value="">Select Product</option>
                                    <?php
                                    foreach ($products as $prod) {
                                        $selected = $prod === $order['product'] ? 'selected' : '';
                                        echo "<option value=\"" . htmlspecialchars($prod) . "\" $selected>" . htmlspecialchars($prod) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Quantity *</label>
                                <input type="number" class="form-control" name="quantity" min="1" max="10" value="<?php echo htmlspecialchars($order['quantity']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Status *</label>
                                <select class="form-control" name="status" required>
                                    <?php
                                    foreach ($statuses as $stat) {
                                        $selected = $stat === $order['status'] ? 'selected' : '';
                                        echo "<option value=\"$stat\" $selected>" . ucfirst($stat) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
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
                <i class="fas fa-info-circle"></i> Order Information
            </div>
            <div class="card-body">
                <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['id']); ?></p>
                <p><strong>Status:</strong> <span class="badge <?php echo get_status_badge($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span></p>
                <p><strong>Created:</strong> <?php echo date('M d, Y H:i:s', strtotime($order['created_at'])); ?></p>
                <p><strong>Updated:</strong> <?php echo date('M d, Y H:i:s', strtotime($order['updated_at'])); ?></p>
                <p><strong>Facebook User ID:</strong> <code><?php echo htmlspecialchars($order['facebook_user_id']); ?></code></p>
                
                <hr>
                
                <a href="order-delete.php?id=<?php echo $order['id']; ?>&csrf=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-danger w-100" onclick="return confirmDelete();">
                    <i class="fas fa-trash"></i> Delete Order
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
