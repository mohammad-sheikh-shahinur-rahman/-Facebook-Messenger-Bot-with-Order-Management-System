<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$page_id = $_SESSION['page_id'] ?? '';
$rule_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$rule_id) {
    header('Location: automation-rules.php');
    exit;
}

// Get rule
global $mysqli;
$sql = "SELECT * FROM automation_rules WHERE id = ? AND page_id = ?";
$stmt = db_query($sql, 'is', [&$rule_id, &$page_id]);

if (!$stmt) {
    $_SESSION['error'] = 'Rule not found';
    header('Location: automation-rules.php');
    exit;
}

$result = $stmt->get_result();
$rule = $result->fetch_assoc();
$stmt->close();

if (!$rule) {
    $_SESSION['error'] = 'Rule not found';
    header('Location: automation-rules.php');
    exit;
}

$error = '';
$success = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF token invalid';
    } else {
        $rule_name = sanitize($_POST['rule_name'] ?? '');
        $trigger_keyword = sanitize($_POST['trigger_keyword'] ?? '');
        $response_message = sanitize($_POST['response_message'] ?? '');
        $response_delay = intval($_POST['response_delay'] ?? 2);
        $priority = intval($_POST['priority'] ?? 10);
        $convert_to_message = isset($_POST['convert_to_message']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($rule_name) || empty($trigger_keyword) || empty($response_message)) {
            $error = 'All fields are required';
        } else {
            $sql = "UPDATE automation_rules SET 
                    rule_name = ?, trigger_keyword = ?, response_message = ?,
                    response_delay = ?, priority = ?, convert_to_message = ?, is_active = ?
                    WHERE id = ? AND page_id = ?";
            
            $stmt = db_query($sql, 'sssiiisi', [
                &$rule_name, &$trigger_keyword, &$response_message,
                &$response_delay, &$priority, &$convert_to_message, &$is_active,
                &$rule_id, &$page_id
            ]);
            
            if ($stmt) {
                $stmt->close();
                $success = 'Rule updated successfully!';
                header('Refresh: 2; automation-rules.php');
                
                // Re-fetch updated rule
                $sql = "SELECT * FROM automation_rules WHERE id = ?";
                $rs = db_query($sql, 'i', [&$rule_id]);
                $rule = $rs->get_result()->fetch_assoc();
                $rs->close();
            } else {
                $error = 'Failed to update rule';
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
    <title>Edit Automation Rule - Facebook Automation</title>
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
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
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
            
            <h2><i class="bi bi-pencil"></i> Edit Automation Rule</h2>

            <?php if ($success): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="id" value="<?php echo $rule_id; ?>">

                <div class="info-box">
                    <i class="bi bi-info-circle"></i> 
                    Rule Type: <strong><?php echo ucfirst($rule['rule_type']); ?></strong>
                </div>

                <div class="form-group">
                    <label class="form-label">Rule Name *</label>
                    <input type="text" class="form-control" name="rule_name" value="<?php echo htmlspecialchars($rule['rule_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Trigger Keyword *</label>
                    <input type="text" class="form-control" name="trigger_keyword" value="<?php echo htmlspecialchars($rule['trigger_keyword']); ?>" required>
                    <small class="text-muted">Update will apply to new messages/comments</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Auto-Reply Message *</label>
                    <textarea class="form-control" name="response_message" rows="4" required><?php echo htmlspecialchars($rule['response_message']); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Response Delay (seconds)</label>
                    <input type="number" class="form-control" name="response_delay" value="<?php echo $rule['response_delay']; ?>" min="0" max="60">
                </div>

                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <input type="number" class="form-control" name="priority" value="<?php echo $rule['priority']; ?>" min="1" max="100">
                </div>

                <?php if ($rule['rule_type'] === 'comment'): ?>
                    <div class="checkbox-group">
                        <input type="checkbox" id="convertToMessage" name="convert_to_message" value="1" <?php echo $rule['convert_to_message'] ? 'checked' : ''; ?>>
                        <label for="convertToMessage" class="mb-0">Send Messenger message (for comments)</label>
                    </div>
                <?php endif; ?>

                <div class="checkbox-group">
                    <input type="checkbox" id="isActive" name="is_active" value="1" <?php echo $rule['is_active'] ? 'checked' : ''; ?>>
                    <label for="isActive" class="mb-0">Active</label>
                </div>

                <button type="submit" class="btn-submit w-100 mt-4">
                    <i class="bi bi-check"></i> Update Rule
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
