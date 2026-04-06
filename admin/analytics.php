<?php
$page_title = 'Analytics';
require_once __DIR__ . '/header.php';

// Get data for analytics
$total_orders = get_order_count();
$pending_orders = get_order_count('pending');
$confirmed_orders = get_order_count('confirmed');
$delivered_orders = get_order_count('delivered');
$cancelled_orders = get_order_count('cancelled');

// Get last 30 days data
global $mysqli;
$result = db_query("SELECT DATE(created_at) as date, COUNT(*) as count FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at)");

$daily_data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $daily_data[$row['date']] = $row['count'];
    }
    $result->free();
}

// Get top products
$result = db_query("SELECT product, COUNT(*) as count FROM orders GROUP BY product ORDER BY count DESC LIMIT 5");

$top_products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $top_products[] = $row;
    }
    $result->free();
}
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-cubes"></i></div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-number"><?php echo $total_orders; ?></div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-percent" style="color: #0dcaf0;"></i></div>
            <div class="stat-label">Completion Rate</div>
            <div class="stat-number" style="color: #0dcaf0;"><?php echo $total_orders > 0 ? round(($delivered_orders / $total_orders) * 100) : 0; ?>%</div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-label">Unique Customers</div>
            <div class="stat-number">
                <?php
                $result = db_query("SELECT COUNT(DISTINCT facebook_user_id) as count FROM orders");
                if ($result) {
                    $row = $result->fetch_assoc();
                    echo $row['count'];
                    $result->free();
                }
                ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line" style="color: #198754;"></i></div>
            <div class="stat-label">Avg Orders/Day</div>
            <div class="stat-number" style="color: #198754;"><?php echo $total_orders > 0 ? round($total_orders / 30) : 0; ?></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                📊 Orders in Last 30 Days
            </div>
            <div class="card-body">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                📦 Order Status Distribution
            </div>
            <div class="card-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                🏆 Top Products
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Orders</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (empty($top_products)) {
                            echo '<tr><td colspan="3" style="text-align: center; color: #999;">No data yet</td></tr>';
                        } else {
                            foreach ($top_products as $product) {
                                $percentage = ($product['count'] / $total_orders) * 100;
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($product['product']) . '</td>';
                                echo '<td><strong>' . $product['count'] . '</strong></td>';
                                echo '<td><div class="progress" style="height: 20px;"><div class="progress-bar" style="width: ' . $percentage . '%">' . round($percentage, 1) . '%</div></div></td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                📈 Status Breakdown
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <td>Pending Orders</td>
                        <td style="text-align: right;"><strong><?php echo $pending_orders; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Confirmed Orders</td>
                        <td style="text-align: right;"><strong><?php echo $confirmed_orders; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Delivered Orders</td>
                        <td style="text-align: right;"><strong><?php echo $delivered_orders; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Cancelled Orders</td>
                        <td style="text-align: right;"><strong><?php echo $cancelled_orders; ?></strong></td>
                    </tr>
                    <tr style="border-top: 2px solid #ddd; font-weight: bold;">
                        <td>Total</td>
                        <td style="text-align: right;"><strong><?php echo $total_orders; ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Daily Orders Chart
var ctx = document.getElementById('dailyChart').getContext('2d');
var labels = [];
var data = [];

<?php
// Generate last 30 days
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = isset($daily_data[$date]) ? $daily_data[$date] : 0;
    echo "labels.push('" . date('M d', strtotime($date)) . "');\n";
    echo "data.push($count);\n";
}
?>

var dailyChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Orders',
            data: data,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#667eea',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                labels: {
                    padding: 20
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Status Chart
var ctx2 = document.getElementById('statusChart').getContext('2d');
var statusChart = new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Confirmed', 'Delivered', 'Cancelled'],
        datasets: [{
            data: [
                <?php echo $pending_orders; ?>,
                <?php echo $confirmed_orders; ?>,
                <?php echo $delivered_orders; ?>,
                <?php echo $cancelled_orders; ?>
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
                    padding: 15,
                    font: {
                        size: 12
                    }
                }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
