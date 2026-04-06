<?php
/**
 * Comments Management Page
 * View, reply, and manage Facebook post comments
 */

$page_title = 'Manage Comments';
require_once __DIR__ . '/header.php';

$status_filter = $_GET['status'] ?? '';
$comments = get_all_comments(100, 0, $status_filter);
?>

<style>
    .comment-item {
        background: #f9f9f9;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        transition: all 0.3s;
    }
    
    .comment-item:hover {
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .comment-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .comment-author {
        font-weight: 600;
        color: #333;
    }
    
    .comment-time {
        font-size: 12px;
        color: #999;
    }
    
    .comment-text {
        color: #555;
        margin: 10px 0;
        padding: 10px;
        background: white;
        border-left: 3px solid #667eea;
    }
    
    .comment-actions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
</style>

<div class="mb-4">
    <div class="row">
        <div class="col-md-8">
            <a href="comments.php" class="btn btn-outline-secondary">
                <i class="fas fa-list"></i> All
            </a>
            <a href="?status=pending" class="btn btn-outline-warning">
                <i class="fas fa-clock"></i> Pending
            </a>
            <a href="?status=auto_replied" class="btn btn-outline-info">
                <i class="fas fa-magic"></i> Auto-Replied
            </a>
            <a href="?status=manual_replied" class="btn btn-outline-success">
                <i class="fas fa-user"></i> Replied
            </a>
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" id="searchComments" placeholder="Search comments...">
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-comments"></i> Facebook Comments (<?php echo count($comments); ?>)
    </div>
    <div class="card-body">
        <?php
        if (empty($comments)) {
            echo '<div style="text-align: center; color: #999; padding: 40px;">
                    <i class="fas fa-inbox" style="font-size: 40px; color: #ddd;"></i>
                    <p>No comments found</p>
                  </div>';
        } else {
            foreach ($comments as $comment) {
                $badge_class = 'badge-secondary';
                if ($comment['status'] === 'auto_replied') $badge_class = 'badge-info';
                elseif ($comment['status'] === 'manual_replied') $badge_class = 'badge-success';
                elseif ($comment['status'] === 'pending') $badge_class = 'badge-warning';
                
                echo '
                <div class="comment-item">
                    <div class="comment-header">
                        <div>
                            <span class="comment-author">' . htmlspecialchars($comment['from_id']) . '</span>
                            <span class="comment-time">' . date('M d, H:i', strtotime($comment['created_at'])) . '</span>
                        </div>
                        <span class="badge ' . $badge_class . '">' . ucfirst(str_replace('_', ' ', $comment['status'])) . '</span>
                    </div>
                    
                    <div class="comment-text">
                        ' . htmlspecialchars($comment['comment_text']) . '
                    </div>
                    
                    <div class="comment-actions">
                        <button class="btn btn-sm btn-primary" onclick="showReplyModal(' . $comment['id'] . ', \'' . addslashes($comment['comment_id']) . '\')">
                            <i class="fas fa-reply"></i> Reply
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="hideComment(' . $comment['id'] . ')">
                            <i class="fas fa-eye-slash"></i> Hide
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteComment(' . $comment['id'] . ')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
                ';
            }
        }
        ?>
    </div>
</div>

<!-- Reply Modal -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reply to Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="replyForm" method="POST" action="comment-reply.php">
                <div class="modal-body">
                    <input type="hidden" id="comment_id" name="comment_id">
                    <input type="hidden" id="facebook_comment_id" name="facebook_comment_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Reply Type</label>
                        <div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="reply_type" value="public" id="public" checked>
                                <label class="form-check-label" for="public">
                                    <i class="fas fa-globe"></i> Public Reply (on comment)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="reply_type" value="private" id="private">
                                <label class="form-check-label" for="private">
                                    <i class="fas fa-envelope"></i> Private Message
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Your Reply *</label>
                        <textarea class="form-control" name="reply_text" rows="4" placeholder="Type your reply..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showReplyModal(commentId, facebookCommentId) {
    document.getElementById('comment_id').value = commentId;
    document.getElementById('facebook_comment_id').value = facebookCommentId;
    new bootstrap.Modal(document.getElementById('replyModal')).show();
}

function hideComment(commentId) {
    if (confirm('Hide this comment?')) {
        fetch('comment-action.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=hide&comment_id=' + commentId
        }).then(r => r.json()).then(d => {
            if (d.success) location.reload();
        });
    }
}

function deleteComment(commentId) {
    if (confirm('Delete this comment from your system? (Not from Facebook)')) {
        fetch('comment-action.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=delete&comment_id=' + commentId
        }).then(r => r.json()).then(d => {
            if (d.success) location.reload();
        });
    }
}

// Search functionality
document.getElementById('searchComments')?.addEventListener('keyup', function(e) {
    const search = e.target.value.toLowerCase();
    document.querySelectorAll('.comment-item').forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(search) ? 'block' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
