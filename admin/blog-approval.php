<?php
session_start();
require_once '../connect.php';
require_once '../functions.php';

// Check if user is admin
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . SITE_URL);
    exit();
}

$page_title = "Blog Approval";

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $post_id = intval($_POST['post_id']);
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $update = $conn->prepare("UPDATE blog_posts SET status = 'approved', approved_at = NOW(), approved_by = ? WHERE id = ?");
        if (!$update) {
            error_log('Blog approval update prepare failed: ' . $conn->error);
            $_SESSION['error'] = 'Database error while approving post.';
        } else {
            $update->bind_param("ii", $_SESSION['user_id'], $post_id);

            if ($update->execute()) {
                // Get post author for notification
                $author_stmt = $conn->prepare("SELECT user_id, title FROM blog_posts WHERE id = ?");
                if (!$author_stmt) {
                    error_log('Blog approval author select prepare failed: ' . $conn->error);
                } else {
                    $author_stmt->bind_param("i", $post_id);
                    $author_stmt->execute();
                    $post_data = $author_stmt->get_result()->fetch_assoc();

                    // Create notification
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) 
                                                  VALUES (?, 'blog_approved', 'Blog Post Approved!', ?, ?)");
                    if (!$notif_stmt) {
                        error_log('Blog approval notif insert prepare failed: ' . $conn->error);
                    } else {
                        $message = "Your blog post '{$post_data['title']}' has been approved and is now live!";
                        $link = "blog/view.php?id=" . $post_id;
                        $notif_stmt->bind_param("iss", $post_data['user_id'], $message, $link);
                        $notif_stmt->execute();
                    }

                    $_SESSION['success'] = "Post approved successfully!";
                }
            }
        }
    } elseif ($action === 'reject') {
        $rejection_reason = trim($_POST['rejection_reason'] ?? '');
        
        $update = $conn->prepare("UPDATE blog_posts SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        if (!$update) {
            error_log('Blog rejection update prepare failed: ' . $conn->error);
            $_SESSION['error'] = 'Database error while rejecting post.';
        } else {
            $update->bind_param("si", $rejection_reason, $post_id);

            if ($update->execute()) {
                // Get post author for notification
                $author_stmt = $conn->prepare("SELECT user_id, title FROM blog_posts WHERE id = ?");
                if (!$author_stmt) {
                    error_log('Blog rejection author select prepare failed: ' . $conn->error);
                } else {
                    $author_stmt->bind_param("i", $post_id);
                    $author_stmt->execute();
                    $post_data = $author_stmt->get_result()->fetch_assoc();

                    // Create notification
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) 
                                                  VALUES (?, 'blog_rejected', 'Blog Post Needs Revision', ?, ?)");
                    if (!$notif_stmt) {
                        error_log('Blog rejection notif insert prepare failed: ' . $conn->error);
                    } else {
                        $message = "Your blog post '{$post_data['title']}' needs revision. Reason: {$rejection_reason}";
                        $link = "blog/edit.php?id=" . $post_id;
                        $notif_stmt->bind_param("iss", $post_data['user_id'], $message, $link);
                        $notif_stmt->execute();
                    }

                    $_SESSION['success'] = "Post rejected with feedback.";
                }
            }
        }
    } elseif ($action === 'cancel' || $action === 'unpublish') {
        $cancel_reason = trim($_POST['cancel_reason'] ?? 'Post cancelled by admin');
        
        $update = $conn->prepare("UPDATE blog_posts SET status = 'cancelled', rejection_reason = ? WHERE id = ?");
        if (!$update) {
            error_log('Blog cancel update prepare failed: ' . $conn->error);
            $_SESSION['error'] = 'Database error while cancelling post.';
        } else {
            $update->bind_param("si", $cancel_reason, $post_id);

            if ($update->execute()) {
                // Get post author for notification
                $author_stmt = $conn->prepare("SELECT user_id, title FROM blog_posts WHERE id = ?");
                if (!$author_stmt) {
                    error_log('Blog cancel author select prepare failed: ' . $conn->error);
                } else {
                    $author_stmt->bind_param("i", $post_id);
                    $author_stmt->execute();
                    $post_data = $author_stmt->get_result()->fetch_assoc();

                    // Create notification
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) 
                                                  VALUES (?, 'blog_cancelled', 'Blog Post Cancelled', ?, ?)");
                    if (!$notif_stmt) {
                        error_log('Blog cancel notif insert prepare failed: ' . $conn->error);
                    } else {
                        $message = "Your blog post '{$post_data['title']}' has been cancelled/unpublished. Reason: {$cancel_reason}";
                        $link = "blog/my-posts.php";
                        $notif_stmt->bind_param("iss", $post_data['user_id'], $message, $link);
                        $notif_stmt->execute();
                    }

                    $_SESSION['success'] = "Post cancelled/unpublished successfully.";
                }
            }
        }
    }
    
    header("Location: blog-approval.php");
    exit();
}

// Get filter
$filter = isset($_GET['status']) ? $_GET['status'] : 'pending';

// Pagination
$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Search functionality
$search_term = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = $conn->real_escape_string(trim($_GET['search']));
}

// Count total posts for pagination
$count_query = "SELECT COUNT(*) as total
                FROM blog_posts bp
                JOIN users u ON bp.user_id = u.id
                WHERE bp.status = ?";
if (!empty($search_term)) {
    $count_query .= " AND (bp.title LIKE '%$search_term%' 
                           OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search_term%' 
                           OR u.email LIKE '%$search_term%'
                           OR bp.content LIKE '%$search_term%')";
}

$count_stmt = $conn->prepare($count_query);
if (!$count_stmt) {
    error_log('Blog count prepare failed: ' . $conn->error);
    die('Database error preparing blog count. Check error log.');
}
$count_stmt->bind_param("s", $filter);
$count_stmt->execute();
$total_posts = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $per_page);

// Build query
$query = "SELECT bp.*, CONCAT(u.first_name, ' ', u.last_name) as author_name, u.email as author_email,
          (SELECT COUNT(*) FROM blog_likes WHERE post_id = bp.id) as like_count,
          (SELECT COUNT(*) FROM blog_comments WHERE post_id = bp.id) as comment_count
          FROM blog_posts bp
          JOIN users u ON bp.user_id = u.id
          WHERE bp.status = ?";
if (!empty($search_term)) {
    $query .= " AND (bp.title LIKE '%$search_term%' 
                     OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search_term%' 
                     OR u.email LIKE '%$search_term%'
                     OR bp.content LIKE '%$search_term%')";
}
$query .= " ORDER BY bp.created_at DESC LIMIT $per_page OFFSET $offset";

$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log('Blog approval prepare failed: ' . $conn->error);
    die('Database error preparing blog list. Check error log.');
}
$stmt->bind_param("s", $filter);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include_once '../header.php';
?>

<style>
/* Fix active nav-link text color */
.nav-pills .nav-link.active {
    color: #fff !important;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-tasks me-2"></i>Blog Approval Management</h2>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            <!-- Status Filter Tabs -->
            <ul class="nav nav-pills mb-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'pending' ? 'active' : ''; ?>" 
                       href="?status=pending">
                        <i class="fas fa-clock me-1"></i>Pending Review
                        <?php
                        $pending_count = $conn->query("SELECT COUNT(*) as count FROM blog_posts WHERE status = 'pending'")->fetch_assoc();
                        echo " ({$pending_count['count']})";
                        ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'approved' ? 'active' : ''; ?>" 
                       href="?status=approved">
                        <i class="fas fa-check-circle me-1"></i>Approved
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'rejected' ? 'active' : ''; ?>" 
                       href="?status=rejected">
                        <i class="fas fa-times-circle me-1"></i>Rejected
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'cancelled' ? 'active' : ''; ?>" 
                       href="?status=cancelled">
                        <i class="fas fa-ban me-1"></i>Cancelled
                    </a>
                </li>
            </ul>

            <!-- Search Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="blog-approval.php" class="row g-3">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter); ?>">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by title, author name, email, or content..." 
                                   value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                        <?php if (!empty($search_term)): ?>
                        <div class="col-12">
                            <a href="?status=<?php echo htmlspecialchars($filter); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear Search
                            </a>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if (empty($posts)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No <?php echo $filter; ?> posts found.
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <!-- Featured Image -->
                                <?php if ($post['featured_image']): ?>
                                    <div class="col-md-3">
                                        <img src="<?php echo SITE_URL . $post['featured_image']; ?>" 
                                             alt="Featured" 
                                             class="img-fluid rounded"
                                             style="max-height: 200px; width: 100%; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Post Details -->
                                <div class="col-md-<?php echo $post['featured_image'] ? '6' : '9'; ?>">
                                    <h4><?php echo htmlspecialchars($post['title']); ?></h4>
                                    
                                    <div class="text-muted mb-2">
                                        <small>
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($post['author_name']); ?>
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('M d, Y g:i A', strtotime($post['created_at'])); ?>
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-heart text-danger me-1"></i>
                                            <?php echo $post['like_count']; ?>
                                            <i class="fas fa-comment text-primary ms-2 me-1"></i>
                                            <?php echo $post['comment_count']; ?>
                                        </small>
                                    </div>

                                    <?php if ($post['excerpt']): ?>
                                        <p class="text-muted mb-2">
                                            <?php echo htmlspecialchars($post['excerpt']); ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Content Preview -->
                                    <div class="border-start border-3 border-primary ps-3 mb-3">
                                        <small class="text-muted">Content Preview:</small>
                                        <div style="max-height: 100px; overflow: hidden;">
                                            <?php echo substr(strip_tags($post['content']), 0, 300); ?>...
                                        </div>
                                    </div>

                                    <!-- Rejection Reason (if rejected) -->
                                    <?php if ($post['status'] === 'rejected' && $post['rejection_reason']): ?>
                                        <div class="alert alert-warning mb-0">
                                            <strong>Rejection Reason:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($post['rejection_reason'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Actions -->
                                <div class="col-md-3">
                                    <div class="d-grid gap-2">
                                        <!-- Preview Button -->
                                        <button class="btn btn-outline-primary btn-sm" 
                                                onclick="showPreview(<?php echo $post['id']; ?>)">
                                            <i class="fas fa-eye me-2"></i>Preview Full Content
                                        </button>

                                        <?php if ($filter === 'pending'): ?>
                                            <!-- Approve Button -->
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-success btn-sm w-100 text-white" style="color:#fff !important;"
                                                        onclick="return confirm('Approve this post?')">
                                                    <i class="fas fa-check me-2"></i>Approve
                                                </button>
                                            </form>

                                            <!-- Reject Button -->
                                            <button class="btn btn-danger btn-sm" 
                                                    onclick="showRejectModal(<?php echo $post['id']; ?>, '<?php echo htmlspecialchars($post['title']); ?>')">
                                                <i class="fas fa-times me-2"></i>Reject
                                            </button>
                                        <?php elseif ($filter === 'approved'): ?>
                                            <a href="<?php echo SITE_URL; ?>blog/view.php?slug=<?php echo urlencode($post['slug']); ?>" 
                                               class="btn btn-primary btn-sm" 
                                               target="_blank">
                                                <i class="fas fa-external-link-alt me-2"></i>View Live
                                            </a>
                                            
                                            <!-- Cancel/Unpublish Button -->
                                            <button class="btn btn-warning btn-sm text-white" style="color:#fff !important;"
                                                    onclick="showCancelModal(<?php echo $post['id']; ?>, '<?php echo htmlspecialchars($post['title']); ?>')">
                                                <i class="fas fa-ban me-2"></i>Cancel/Unpublish
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div>
                        <p class="text-muted mb-0">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_posts); ?> of <?php echo $total_posts; ?> posts
                        </p>
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?status=<?php echo htmlspecialchars($filter); ?>&page=<?php echo $page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?status=<?php echo htmlspecialchars($filter); ?>&page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?status=<?php echo htmlspecialchars($filter); ?>&page=<?php echo $page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Content Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent" style="max-height: 70vh; overflow-y: auto;">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Blog Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="post_id" id="reject-post-id">
                    <input type="hidden" name="action" value="reject">
                    
                    <p>You are about to reject: <strong id="reject-post-title"></strong></p>
                    
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason *</label>
                        <textarea class="form-control" 
                                  name="rejection_reason" 
                                  id="rejection_reason" 
                                  rows="4"
                                  required
                                  placeholder="Provide clear feedback to help the author improve their post..."></textarea>
                        <div class="form-text">Be specific about what needs to be changed.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Reject Post
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel/Unpublish Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Cancel/Unpublish Blog Post</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="post_id" id="cancel-post-id">
                    <input type="hidden" name="action" value="cancel">
                    
                    <p>You are about to cancel/unpublish: <strong id="cancel-post-title"></strong></p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> This post will be removed from public view immediately.</p>
                    
                    <div class="mb-3">
                        <label for="cancel_reason" class="form-label">Cancellation Reason *</label>
                        <textarea class="form-control" 
                                  name="cancel_reason" 
                                  id="cancel_reason" 
                                  rows="4"
                                  required
                                  placeholder="Provide reason for cancelling/unpublishing this post..."></textarea>
                        <div class="form-text">The author will be notified.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-white" style="color:#fff !important;">
                        <i class="fas fa-ban me-2"></i>Cancel/Unpublish
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showPreview(postId) {
    // Fetch post content
    fetch('../blog/get-post.php?id=' + postId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('previewContent').innerHTML = data.content;
                new bootstrap.Modal(document.getElementById('previewModal')).show();
            }
        });
}

function showRejectModal(postId, postTitle) {
    document.getElementById('reject-post-id').value = postId;
    document.getElementById('reject-post-title').textContent = postTitle;
    document.getElementById('rejection_reason').value = '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function showCancelModal(postId, postTitle) {
    document.getElementById('cancel-post-id').value = postId;
    document.getElementById('cancel-post-title').textContent = postTitle;
    document.getElementById('cancel_reason').value = '';
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}
</script>

<?php include_once '../footer.php'; ?>
