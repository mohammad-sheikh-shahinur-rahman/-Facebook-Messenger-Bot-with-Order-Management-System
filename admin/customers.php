<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$page_id = $_SESSION['page_id'] ?? '';
$filter_tag = $_GET['tag'] ?? '';
$search = $_GET['search'] ?? '';

// Get customers
global $mysqli;
$sql = "SELECT * FROM customers WHERE page_id = ?";
$types = 's';
$params = [&$page_id];

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $types .= 'sss';
    $search_param = '%' . $search . '%';
    $params[] = &$search_param;
    $params[] = &$search_param;
    $params[] = &$search_param;
}

$sql .= " ORDER BY last_message_at DESC LIMIT 100";

$stmt = db_query($sql, $types, $params);
$customers = [];

if ($stmt) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    $stmt->close();
}

$stats = get_dashboard_stats($page_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer CRM - Facebook Automation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --primary: #7c3aed;
            --secondary: #a78bfa;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
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
            padding: 30px;
        }
        
        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: white;
        }
        
        .customer-row {
            border-left: 4px solid var(--primary);
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .customer-row:hover {
            box-shadow: 0 5px 15px rgba(124, 58, 237, 0.15);
            transform: translateX(5px);
        }
        
        .tag-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            margin-right: 5px;
            margin-bottom: 5px;
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
        
        .stat-box {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .stat-box h6 {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .stat-box h3 {
            margin: 0;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h5 class="mb-4"><i class="bi bi-robot"></i> Facebook Bot</h5>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a href="messages.php"><i class="bi bi-chat-dots"></i> Messages</a></li>
            <li><a href="comments.php"><i class="bi bi-chat-left-text"></i> Comments</a></li>
            <li><a href="orders.php"><i class="bi bi-bag"></i> Orders</a></li>
            <li><a href="automation-rules.php"><i class="bi bi-gear"></i> Automation</a></li>
            <li><a href="customers.php" class="active"><i class="bi bi-people"></i> Customers</a></li>
            <li><a href="analytics.php"><i class="bi bi-graph-up"></i> Analytics</a></li>
            <li><a href="settings.php"><i class="bi bi-sliders"></i> Settings</a></li>
            <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-people"></i> Customer CRM</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="bi bi-download"></i> Export
                </button>
            </div>

            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-box">
                        <h6>Total Customers</h6>
                        <h3><?php echo count($customers); ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box">
                        <h6>New This Month</h6>
                        <h3><?php
                            $new = db_query("SELECT COUNT(*) as count FROM customers WHERE page_id = ? AND DATE(created_at) > DATE_SUB(NOW(), INTERVAL 30 DAY)", 's', [&$page_id]);
                            $n = $new->fetch_assoc();
                            echo $n['count'] ?? 0;
                        ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box">
                        <h6>Repeat Customers</h6>
                        <h3><?php
                            $repeat = db_query("SELECT COUNT(*) as count FROM customers WHERE page_id = ? AND total_orders > 1", 's', [&$page_id]);
                            $rp = $repeat->fetch_assoc();
                            echo $rp['count'] ?? 0;
                        ?></h3>
                    </div>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="search" placeholder="Search by name, phone, or email..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Customers List -->
            <div class="card card-custom">
                <div class="card-body">
                    <?php if (empty($customers)): ?>
                        <div class="alert alert-info text-center">
                            <i class="bi bi-info-circle"></i> No customers found.
                        </div>
                    <?php else: ?>
                        <table class="table table-hover" id="customersTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Messages</th>
                                    <th>Orders</th>
                                    <th>Status</th>
                                    <th>Last Activity</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($customer['name'] ?? 'Unknown'); ?></strong>
                                            <?php if ($customer['tags']): ?>
                                                <br>
                                                <?php foreach (explode(',', $customer['tags']) as $tag): ?>
                                                    <span class="badge bg-info tag-badge"><?php echo htmlspecialchars($tag); ?></span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($customer['phone']): ?>
                                                <i class="bi bi-phone"></i> <?php echo htmlspecialchars($customer['phone']); ?><br>
                                            <?php endif; ?>
                                            <?php if ($customer['email']): ?>
                                                <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $customer['total_messages'] ?? 0; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?php echo $customer['total_orders'] ?? 0; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($customer['total_orders'] > 5): ?>
                                                <span class="badge bg-warning">⭐ VIP</span>
                                            <?php elseif ($customer['total_orders'] > 1): ?>
                                                <span class="badge bg-info">Repeat</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">New</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('M d, H:i', strtotime($customer['last_message_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <a href="../messages.php?user=<?php echo urlencode($customer['facebook_user_id']); ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-chat"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Customers</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="customer-export.php">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="format" id="csv" value="csv" checked>
                            <label class="form-check-label" for="csv">CSV (Excel)</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="format" id="json" value="json">
                            <label class="form-check-label" for="json">JSON</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-download"></i> Export Now
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#customersTable').DataTable({
                pageLength: 25,
                order: [[5, 'desc']]
            });
        });
    </script>
</body>
</html>
