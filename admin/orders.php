<?php
$page_title = 'Manage Orders';
require_once __DIR__ . '/header.php';

$status_filter = $_GET['status'] ?? '';
$orders = get_all_orders(100, 0, $status_filter);
?>

<div class="mb-4">
    <div class="row">
        <div class="col-md-6">
            <a href="order-add.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Add New Order
            </a>
            <a href="?status=pending" class="btn btn-outline-warning">
                <i class="fas fa-clock"></i> Pending
            </a>
            <a href="?status=confirmed" class="btn btn-outline-info">
                <i class="fas fa-check-circle"></i> Confirmed
            </a>
            <a href="?status=delivered" class="btn btn-outline-success">
                <i class="fas fa-truck"></i> Delivered
            </a>
            <a href="orders.php" class="btn btn-outline-secondary">
                <i class="fas fa-list"></i> All Orders
            </a>
        </div>
        <div class="col-md-6" style="text-align: right;">
            <button onclick="exportToCSV('orders.csv')" class="btn btn-primary">
                <i class="fas fa-download"></i> Export to CSV
            </button>
        </div>
    </div>
</div>

<?php if ($status_filter): ?>
    <div class="alert alert-info">
        <i class="fas fa-filter"></i> 
        Showing orders with status: <strong><?php echo ucfirst($status_filter); ?></strong>
        <a href="orders.php" style="margin-left: 10px;">Clear filter</a>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-shopping-cart"></i> All Orders (<?php echo count($orders); ?>)
    </div>
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (empty($orders)) {
                    echo '<tr><td colspan="9" style="text-align: center; color: #999;">No orders found</td></tr>';
                } else {
                    foreach ($orders as $order) {
                        $status_badge = get_status_badge($order['status']);
                        echo '
                            <tr>
                                <td><strong>#' . htmlspecialchars($order['id']) . '</strong></td>
                                <td>' . htmlspecialchars($order['customer_name']) . '</td>
                                <td>' . htmlspecialchars($order['phone']) . '</td>
                                <td><small>' . htmlspecialchars($order['address']) . '</small></td>
                                <td>' . htmlspecialchars($order['product']) . '</td>
                                <td>' . htmlspecialchars($order['quantity']) . '</td>
                                <td><span class="badge ' . $status_badge . '">' . ucfirst($order['status']) . '</span></td>
                                <td><small>' . date('M d, Y H:i', strtotime($order['created_at'])) . '</small></td>
                                <td>
                                    <a href="order-edit.php?id=' . $order['id'] . '" class="btn btn-sm btn-info" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="order-delete.php?id=' . $order['id'] . '&csrf=' . $_SESSION['csrf_token'] . '" class="btn btn-sm btn-danger" onclick="return confirmDelete();" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        ';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
