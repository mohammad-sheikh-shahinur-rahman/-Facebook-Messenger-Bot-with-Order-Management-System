<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$page_id = $_SESSION['page_id'] ?? '';
$filter_type = $_GET['type'] ?? '';

// Get all automation rules
global $mysqli;
$sql = "SELECT * FROM automation_rules WHERE page_id = ?";
$types = 's';
$params = [&$page_id];

if (!empty($filter_type)) {
    $sql .= " AND rule_type = ?";
    $types .= 's';
    $params[] = &$filter_type;
}

$sql .= " ORDER BY rule_type, priority DESC";

$stmt = db_query($sql, $types, $params);
$rules = [];

if ($stmt) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rules[] = $row;
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
    <title>Automation Rules - Facebook Automation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #7c3aed;
            --secondary: #a78bfa;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        
        .rule-card {
            border-left: 4px solid var(--primary);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .rule-card:hover {
            box-shadow: 0 5px 20px rgba(124, 58, 237, 0.2);
            transform: translateY(-2px);
        }
        
        .rule-type-badge {
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
        }
        
        .rule-type-message { background-color: #dbeafe; color: #0c4a6e; }
        .rule-type-comment { background-color: #fef3c7; color: #78350f; }
        
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
        
        .btn-action {
            padding: 6px 12px;
            font-size: 0.85rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
        }
        
        .stat-card {
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            color: white;
            margin-bottom: 20px;
        }
        
        .stat-card.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
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
            <li><a href="automation-rules.php" class="active"><i class="bi bi-gear"></i> Automation</a></li>
            <li><a href="customers.php"><i class="bi bi-people"></i> Customers</a></li>
            <li><a href="analytics.php"><i class="bi bi-graph-up"></i> Analytics</a></li>
            <li><a href="settings.php"><i class="bi bi-sliders"></i> Settings</a></li>
            <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-gear"></i> Automation Rules</h1>
                <a href="automation-add.php" class="btn btn-primary">
                    <i class="bi bi-plus"></i> Create Rule
                </a>
            </div>

            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card primary">
                        <h6>Total Rules</h6>
                        <h3><?php echo count($rules); ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card primary">
                        <h6>Active Rules</h6>
                        <h3><?php echo count(array_filter($rules, fn($r) => $r['is_active'])); ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card warning">
                        <h6>Message Rules</h6>
                        <h3><?php echo count(array_filter($rules, fn($r) => $r['rule_type'] === 'message')); ?></h3>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item"><a class="nav-link <?php echo empty($filter_type) ? 'active' : ''; ?>" href="?">All Rules</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $filter_type === 'message' ? 'active' : ''; ?>" href="?type=message">Message Rules</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $filter_type === 'comment' ? 'active' : ''; ?>" href="?type=comment">Comment Rules</a></li>
            </ul>

            <!-- Rules List -->
            <?php if (empty($rules)): ?>
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i> No automation rules created yet. <a href="automation-add.php">Create one now</a>
                </div>
            <?php else: ?>
                <?php foreach ($rules as $rule): ?>
                    <div class="card card-custom rule-card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <span class="rule-type-badge rule-type-<?php echo $rule['rule_type']; ?>">
                                            <?php echo ucfirst($rule['rule_type']); ?>
                                        </span>
                                        <h5 class="mb-0"><?php echo htmlspecialchars($rule['rule_name']); ?></h5>
                                        <?php if ($rule['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </div>

                                    <p class="mb-3 text-muted small">
                                        <strong>Trigger:</strong> When message contains "<code><?php echo htmlspecialchars($rule['trigger_keyword']); ?></code>"
                                    </p>

                                    <div class="bg-light p-2 rounded mb-2">
                                        <strong>Response:</strong> 
                                        <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars(substr($rule['response_message'], 0, 150))); ?>...</p>
                                    </div>

                                    <div class="small text-muted">
                                        <i class="bi bi-clock"></i> Delay: <?php echo $rule['response_delay']; ?> seconds
                                        <?php if ($rule['convert_to_message']): ?>
                                            | <i class="bi bi-chat-dots"></i> Send to Messenger
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="d-grid gap-2">
                                        <a href="automation-edit.php?id=<?php echo $rule['id']; ?>" class="btn btn-outline-primary btn-action">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="automation-delete.php?id=<?php echo $rule['id']; ?>" class="btn btn-outline-danger btn-action" onclick="return confirm('Delete this rule?')">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                        <button class="btn btn-outline-secondary btn-action" onclick="toggleActive(<?php echo $rule['id']; ?>, <?php echo $rule['is_active'] ? 0 : 1; ?>)">
                                            <i class="bi <?php echo $rule['is_active'] ? 'bi-pause' : 'bi-play'; ?>"></i> 
                                            <?php echo $rule['is_active'] ? 'Disable' : 'Enable'; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleActive(ruleId, newStatus) {
            fetch('automation-toggle.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'rule_id=' + ruleId + '&is_active=' + newStatus + '&csrf_token=<?php echo generate_csrf_token(); ?>'
            }).then(() => location.reload());
        }
    </script>
</body>
</html>
