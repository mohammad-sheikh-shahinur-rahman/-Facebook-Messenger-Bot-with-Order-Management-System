<?php
/**
 * Facebook Automation System - Main Dashboard
 * Real-time management of comments, messages, and orders
 */

$page_title = 'Automation Dashboard';
require_once __DIR__ . '/header.php';

$stats = get_dashboard_stats();
?>

<style>
    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
    }
    
    .stat-box {
        background: white;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #667eea;
        margin-bottom: 15px;
    }
    
    .stat-box.comments {
        border-left-color: #17a2b8;
    }
    
    .stat-box.messages {
        border-left-color: #ffc107;
    }
    
    .stat-box.orders {
        border-left-color: #28a745;
    }
    
    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #333;
    }
    
    .stat-label {
        font-size: 14px;
        color: #999;
        margin-top: 5px;
    }
    
    .live-indicator {
        display: inline-block;
        width: 8px;
        height: 8px;
        background: #28a745;
        border-radius: 50%;
        animation: pulse 2s infinite;
        margin-right: 8px;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>

<div class="dashboard-header">
    <h1><i class="fas fa-robot"></i> Facebook Automation Dashboard</h1>
    <p>Real-time management of comments, messages & orders</p>
    <span class="live-indicator"></span> <small>System Online</small>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="stat-box comments">
            <div class="stat-value">
                <?php 
                $comments_count = get_comments_count();
                echo $comments_count;
                ?>
            </div>
            <div class="stat-label">
                <i class="fas fa-comments"></i> Pending Comments
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-box messages">
            <div class="stat-value">
                <?php 
                $messages_count = get_messages_count('pending');
                echo $messages_count;
                ?>
            </div>
            <div class="stat-label">
                <i class="fas fa-envelope"></i> Unread Messages
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-box orders">
            <div class="stat-value">
                <?php echo get_order_count('pending'); ?>
            </div>
            <div class="stat-label">
                <i class="fas fa-shopping-cart"></i> Pending Orders
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-box">
            <div class="stat-value">
                <?php 
                $auto_replied = get_auto_replied_count();
                echo $auto_replied;
                ?>
            </div>
            <div class="stat-label">
                <i class="fas fa-magic"></i> Auto-Replied Today
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-comments"></i> Recent Comments
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php
                $comments = get_recent_comments(5);
                
                if (empty($comments)) {
                    echo '<p style="color: #999; text-align: center;">No comments yet</p>';
                } else {
                    foreach ($comments as $comment) {
                        echo '
                        <div style="padding: 10px; border-bottom: 1px solid #eee;">
                            <small style="color: #999;">' . date('H:i', strtotime($comment['created_at'])) . '</small><br>
                            <strong>' . htmlspecialchars($comment['from_id']) . '</strong><br>
                            <small>' . htmlspecialchars($comment['comment_text']) . '</small><br>
                            <span class="badge ' . get_comment_status_badge($comment['status']) . '">' . ucfirst($comment['status']) . '</span>
                        </div>
                        ';
                    }
                }
                ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-envelope"></i> Recent Messages
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php
                $messages = get_recent_messages(5);
                
                if (empty($messages)) {
                    echo '<p style="color: #999; text-align: center;">No messages yet</p>';
                } else {
                    foreach ($messages as $message) {
                        $icon = $message['message_type'] === 'incoming' ? '📨' : '📤';
                        echo '
                        <div style="padding: 10px; border-bottom: 1px solid #eee;">
                            <small style="color: #999;">' . date('H:i', strtotime($message['created_at'])) . ' ' . $icon . '</small><br>
                            <small>' . htmlspecialchars(substr($message['message_text'], 0, 60)) . '...</small><br>
                            <span class="badge badge-info">' . ucfirst($message['status']) . '</span>
                        </div>
                        ';
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                📊 Today's Activity
            </div>
            <div class="card-body">
                <canvas id="activityChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3">
        <a href="comments.php" class="btn btn-primary btn-lg w-100">
            <i class="fas fa-comments"></i><br>Manage Comments
        </a>
    </div>
    <div class="col-md-3">
        <a href="messages.php" class="btn btn-info btn-lg w-100">
            <i class="fas fa-envelope"></i><br>Manage Messages
        </a>
    </div>
    <div class="col-md-3">
        <a href="orders.php" class="btn btn-success btn-lg w-100">
            <i class="fas fa-shopping-cart"></i><br>Manage Orders
        </a>
    </div>
    <div class="col-md-3">
        <a href="automation-settings.php" class="btn btn-warning btn-lg w-100">
            <i class="fas fa-cog"></i><br>Automation Settings
        </a>
    </div>
</div>

<script>
// Activity chart
var ctx = document.getElementById('activityChart').getContext('2d');
var activityChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Messages', 'Comments', 'Orders', 'Auto-Replies'],
        datasets: [{
            label: 'Activity Count',
            data: [
                <?php echo get_messages_count(); ?>,
                <?php echo get_comments_count(); ?>,
                <?php echo get_order_count(); ?>,
                <?php echo get_auto_replied_count(); ?>
            ],
            backgroundColor: [
                '#ffc107',
                '#17a2b8',
                '#28a745',
                '#667eea'
            ],
            borderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        indexAxis: 'x',
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
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
