<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$page_id = $_SESSION['page_id'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF token invalid';
    } else {
        $rule_name = sanitize($_POST['rule_name'] ?? '');
        $rule_type = sanitize($_POST['rule_type'] ?? '');
        $trigger_keyword = sanitize($_POST['trigger_keyword'] ?? '');
        $response_message = sanitize($_POST['response_message'] ?? '');
        $response_delay = intval($_POST['response_delay'] ?? 2);
        $priority = intval($_POST['priority'] ?? 10);
        $convert_to_message = isset($_POST['convert_to_message']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($rule_name) || empty($rule_type) || empty($trigger_keyword) || empty($response_message)) {
            $error = 'All fields are required';
        } else {
            global $mysqli;
            
            $sql = "INSERT INTO automation_rules (page_id, rule_name, rule_type, trigger_keyword, response_message, response_delay, priority, convert_to_message, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = db_query($sql, 'sssssiii', [
                &$page_id, &$rule_name, &$rule_type, &$trigger_keyword, &$response_message,
                &$response_delay, &$priority, &$convert_to_message, &$is_active
            ]);
            
            if ($stmt) {
                $stmt->close();
                $success = 'Rule created successfully!';
                header('Refresh: 2; automation-rules.php');
            } else {
                $error = 'Failed to create rule';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Automation Rule - Facebook Automation</title>
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
        }
        
        .container-center {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        
        .form-card h2 {
            color: var(--primary);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid var(--primary);
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #0c4a6e;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            cursor: pointer;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(124, 58, 237, 0.3);
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary);
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container-center">
        <div class="form-card">
            <a href="automation-rules.php" class="back-link"><i class="bi bi-arrow-left"></i> Back to Rules</a>
            
            <h2><i class="bi bi-gear"></i> Create Automation Rule</h2>

            <?php if ($success): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="info-box">
                <i class="bi bi-lightbulb"></i> 
                Create rules to automatically respond to Facebook comments and messages based on keywords.
            </div>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="form-group">
                    <label class="form-label">Rule Name *</label>
                    <input type="text" class="form-control" name="rule_name" placeholder="e.g., Pricing Inquiry" required>
                    <small class="text-muted">A descriptive name for this rule</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Rule Type *</label>
                    <select class="form-select" name="rule_type" required>
                        <option value="">-- Select --</option>
                        <option value="message">Messenger Messages</option>
                        <option value="comment">Post Comments</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Trigger Keyword *</label>
                    <input type="text" class="form-control" name="trigger_keyword" placeholder="e.g., price, order, help" required>
                    <small class="text-muted">Auto-reply when message contains this word</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Auto-Reply Message *</label>
                    <textarea class="form-control" name="response_message" rows="4" placeholder="Your automatic reply message..." required></textarea>
                    <small class="text-muted">This message will be sent automatically</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Response Delay (seconds)</label>
                    <input type="number" class="form-control" name="response_delay" value="2" min="0" max="60">
                    <small class="text-muted">Delay before sending reply (makes it seem human-like)</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <input type="number" class="form-control" name="priority" value="10" min="1" max="100">
                    <small class="text-muted">Higher priority rules are checked first</small>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="convertToMessage" name="convert_to_message" value="1">
                    <label for="convertToMessage" class="mb-0">Also send a Messenger message (for comments only)</label>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="isActive" name="is_active" value="1" checked>
                    <label for="isActive" class="mb-0">Active (rule will be applied immediately)</label>
                </div>

                <button type="submit" class="btn-submit mt-4">
                    <i class="bi bi-check"></i> Create Rule
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
