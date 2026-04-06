<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/header.php';

// Get statistics
$stats = get_dashboard_stats();
?>

<div class="row">
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-cubes"></i>
            </div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock text-warning"></i>
            </div>
            <div class="stat-label">Pending Orders</div>
            <div class="stat-number" style="color: #ffc107;"><?php echo $stats['pending_orders']; ?></div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle text-info"></i>
            </div>
            <div class="stat-label">Confirmed Orders</div>
            <div class="stat-number" style="color: #0dcaf0;"><?php echo $stats['confirmed_orders']; ?></div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-truck text-success"></i>
            </div>
            <div class="stat-label">Delivered Orders</div>
            <div class="stat-number" style="color: #198754;"><?php echo $stats['delivered_orders']; ?></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-line"></i> Orders by Status
            </div>
            <div class="card-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Quick Stats
            </div>
            <div class="card-body">
                <div style="margin-bottom: 15px;">
                    <p style="margin: 0; color: #666; font-size: 14px;">
                        <i class="fas fa-calendar"></i> Today's Orders: <strong>
                        <?php
                        global $mysqli;
                        $result = db_query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
                        if ($result) {
                            $row = $result->fetch_assoc();
                            echo $row['count'];
                            $result->free();
                        }
                        ?>
                        </strong>
                    </p>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <p style="margin: 0; color: #666; font-size: 14px;">
                        <i class="fas fa-user-circle"></i> Total Customers: <strong>
                        <?php
                        $result = db_query("SELECT COUNT(DISTINCT facebook_user_id) as count FROM orders");
                        if ($result) {
                            $row = $result->fetch_assoc();
                            echo $row['count'];
                            $result->free();
                        }
                        ?>
                        </strong>
                    </p>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <p style="margin: 0; color: #666; font-size: 14px;">
                        <i class="fas fa-hourglass-end"></i> Completion Rate: <strong>
                        <?php
                        $total = get_order_count();
                        $delivered = get_order_count('delivered');
                        $rate = $total > 0 ? round(($delivered / $total) * 100) : 0;
                        echo $rate . '%';
                        ?>
                        </strong>
                    </p>
                </div>
                
                <hr>
                
                <a href="orders.php" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-eye"></i> View All Orders
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> Recent Orders
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_orders = get_all_orders(10, 0);
                        
                        if (empty($recent_orders)) {
                            echo '<tr><td colspan="8" style="text-align: center; color: #999;">No orders yet</td></tr>';
                        } else {
                            foreach ($recent_orders as $order) {
                                $status_badge = get_status_badge($order['status']);
                                echo '
                                    <tr>
                                        <td><strong>#' . htmlspecialchars($order['id']) . '</strong></td>
                                        <td>' . htmlspecialchars($order['customer_name']) . '</td>
                                        <td>' . htmlspecialchars($order['phone']) . '</td>
                                        <td>' . htmlspecialchars($order['product']) . '</td>
                                        <td>' . htmlspecialchars($order['quantity']) . '</td>
                                        <td><span class="badge ' . $status_badge . '">' . ucfirst($order['status']) . '</span></td>
                                        <td>' . date('M d, Y H:i', strtotime($order['created_at'])) . '</td>
                                        <td>
                                            <a href="order-edit.php?id=' . $order['id'] . '" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
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
    </div>
</div>

<script>
// Orders by Status Chart
var ctx = document.getElementById('statusChart').getContext('2d');
var statusChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Confirmed', 'Delivered', 'Cancelled'],
        datasets: [{
            data: [
                <?php echo $stats['pending_orders']; ?>,
                <?php echo $stats['confirmed_orders']; ?>,
                <?php echo $stats['delivered_orders']; ?>,
                <?php echo get_order_count('cancelled'); ?>
            ],
            backgroundColor: [
                '#ffc107',
                '#0dcaf0',
                '#198754',
                '#dc3545'
            ],
            borderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    font: {
                        size: 13
                    }
                }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
