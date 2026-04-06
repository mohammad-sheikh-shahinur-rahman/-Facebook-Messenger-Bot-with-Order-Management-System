<?php
$page_title = 'Settings';
require_once __DIR__ . '/header.php';

$success = '';
$error = '';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-cog"></i> Facebook Bot Configuration
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Configuration Guide:</strong> Update these settings in your <code>config.php</code> file in the root directory.
                </div>
                
                <table class="table table-striped">
                    <tr>
                        <td><strong>Setting</strong></td>
                        <td><strong>Value</strong></td>
                        <td><strong>Status</strong></td>
                    </tr>
                    <tr>
                        <td>
                            <i class="fas fa-check"></i> Page ID
                        </td>
                        <td><code><?php echo FB_PAGE_ID === 'YOUR_PAGE_ID_HERE' ? '❌ NOT SET' : '✓ SET'; ?></code></td>
                        <td>
                            <?php echo FB_PAGE_ID === 'YOUR_PAGE_ID_HERE' ? '<span class="badge bg-danger">Required</span>' : '<span class="badge bg-success">Configured</span>'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <i class="fas fa-check"></i> Access Token
                        </td>
                        <td><code><?php echo FB_PAGE_ACCESS_TOKEN === 'YOUR_PAGE_ACCESS_TOKEN_HERE' ? '❌ NOT SET' : '✓ SET'; ?></code></td>
                        <td>
                            <?php echo FB_PAGE_ACCESS_TOKEN === 'YOUR_PAGE_ACCESS_TOKEN_HERE' ? '<span class="badge bg-danger">Required</span>' : '<span class="badge bg-success">Configured</span>'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <i class="fas fa-check"></i> Verify Token
                        </td>
                        <td><code><?php echo FB_VERIFY_TOKEN === 'YOUR_VERIFY_TOKEN_HERE' ? '❌ NOT SET' : htmlspecialchars(FB_VERIFY_TOKEN); ?></code></td>
                        <td>
                            <?php echo FB_VERIFY_TOKEN === 'YOUR_VERIFY_TOKEN_HERE' ? '<span class="badge bg-danger">Required</span>' : '<span class="badge bg-success">Configured</span>'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <i class="fas fa-globe"></i> Webhook URL
                        </td>
                        <td><code><?php echo htmlspecialchars(WEBHOOK_URL); ?></code></td>
                        <td><span class="badge bg-info">Copy to Facebook</span></td>
                    </tr>
                    <tr>
                        <td>
                            <i class="fas fa-database"></i> Database
                        </td>
                        <td><code><?php echo htmlspecialchars(DB_NAME); ?></code></td>
                        <td><span class="badge bg-success">Connected</span></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-download"></i> Export Orders
            </div>
            <div class="card-body">
                <p>Export all orders to CSV format for backup or analysis:</p>
                
                <div class="button-group" role="group">
                    <a href="export-csv.php" class="btn btn-primary">
                        <i class="fas fa-download"></i> Export All Orders
                    </a>
                    <a href="export-csv.php?status=pending" class="btn btn-warning">
                        <i class="fas fa-download"></i> Export Pending
                    </a>
                    <a href="export-csv.php?status=delivered" class="btn btn-success">
                        <i class="fas fa-download"></i> Export Delivered
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-database"></i> Database Info
            </div>
            <div class="card-body">
                <p><strong>Host:</strong> <code><?php echo htmlspecialchars(DB_HOST); ?></code></p>
                <p><strong>Port:</strong> <code><?php echo htmlspecialchars(DB_PORT); ?></code></p>
                <p><strong>Database:</strong> <code><?php echo htmlspecialchars(DB_NAME); ?></code></p>
                
                <?php
                global $mysqli;
                $result = db_query("SELECT table_name, table_rows FROM information_schema.tables WHERE table_schema = DATABASE()");
                
                if ($result) {
                    echo '<p><strong>Database Tables:</strong></p>';
                    echo '<ul>';
                    while ($row = $result->fetch_assoc()) {
                        echo '<li><code>' . htmlspecialchars($row['table_name']) . '</code> (' . $row['table_rows'] . ' rows)</li>';
                    }
                    echo '</ul>';
                    $result->free();
                }
                ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-lightbulb"></i> Setup Instructions
            </div>
            <div class="card-body" style="font-size: 13px;">
                <p><strong>📝 Step 1: Configure config.php</strong></p>
                <ol>
                    <li>Open <code>config.php</code></li>
                    <li>Update all Facebook credentials</li>
                    <li>Set your app URL</li>
                </ol>
                
                <hr>
                
                <p><strong>⚙️ Step 2: Set Webhook</strong></p>
                <ol>
                    <li>Go to Facebook Developers</li>
                    <li>Set webhook callback URL to:</li>
                    <li><code><?php echo htmlspecialchars(WEBHOOK_URL); ?></code></li>
                    <li>Enter verify token</li>
                </ol>
                
                <hr>
                
                <p><strong>🔒 Step 3: Subscribe to Messages</strong></p>
                <ol>
                    <li>In Facebook Developers</li>
                    <li>Subscribe to <em>messages</em> event</li>
                    <li>Update admin password</li>
                </ol>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <i class="fas fa-user"></i> Admin Account
            </div>
            <div class="card-body">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'N/A'); ?></p>
                
                <button class="btn btn-warning btn-sm w-100 mt-3" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updatePassword()">Update</button>
            </div>
        </div>
    </div>
</div>

<script>
function updatePassword() {
    var form = document.getElementById('changePasswordForm');
    var newPassword = form.new_password.value;
    var confirmPassword = form.confirm_password.value;
    
    if (newPassword !== confirmPassword) {
        alert('Passwords do not match!');
        return;
    }
    
    if (newPassword.length < 8) {
        alert('Password must be at least 8 characters long!');
        return;
    }
    
    alert('Password change feature needs to be implemented via change-password.php');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
