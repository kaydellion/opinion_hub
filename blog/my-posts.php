<?php
session_start();
require_once '../connect.php';
require_once '../functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = "My Blog Posts";

// Get filter from URL
$filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query based on filter
$query = "SELECT bp.*, 
          (SELECT COUNT(*) FROM blog_likes WHERE post_id = bp.id) as like_count,
          (SELECT COUNT(*) FROM blog_comments WHERE post_id = bp.id) as comment_count,
          (SELECT COUNT(*) FROM blog_shares WHERE post_id = bp.id) as share_count
          FROM blog_posts bp 
          WHERE bp.user_id = ?";

if ($filter !== 'all') {
    $query .= " AND bp.status = ?";
}

$query .= " ORDER BY bp.created_at DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

if ($filter !== 'all') {
    $stmt->bind_param("is", $user_id, $filter);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$posts = $result->fetch_all(MYSQLI_ASSOC);

// Get counts for each status
$count_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM blog_posts WHERE user_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$counts = $count_stmt->get_result()->fetch_assoc();

include_once '../header.php';
?>

<style>
.nav-pills .nav-link.active {
    background-color: var(--primary);
    color: #ffffff !important;
}
.nav-pills .nav-link {
    color: var(--gray-600);
}
</style>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-blog me-2"></i>My Blog Posts</h2>
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>New Post
                </a>
            </div>

            <!-- Filter Tabs -->
            <ul class="nav nav-pills mb-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" 
                       href="my-posts.php?status=all">
                        All (<?php echo $counts['total']; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'draft' ? 'active' : ''; ?>" 
                       href="my-posts.php?status=draft">
                        <i class="fas fa-file-alt me-1"></i>Drafts (<?php echo $counts['draft_count']; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'pending' ? 'active' : ''; ?>" 
                       href="my-posts.php?status=pending">
                        <i class="fas fa-clock me-1"></i>Pending (<?php echo $counts['pending_count']; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'approved' ? 'active' : ''; ?>" 
                       href="my-posts.php?status=approved">
                        <i class="fas fa-check-circle me-1"></i>Approved (<?php echo $counts['approved_count']; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'rejected' ? 'active' : ''; ?>" 
                       href="my-posts.php?status=rejected">
                        <i class="fas fa-times-circle me-1"></i>Rejected (<?php echo $counts['rejected_count']; ?>)
                    </a>
                </li>
            </ul>

            <?php if (empty($posts)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No posts found. <a href="create.php">Create a blog post</a>!
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Stats</th>
                                <th>Created</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td>
                                        <?php if ($post['featured_image']): ?>
                                            <img src="<?php echo SITE_URL . $post['featured_image']; ?>" 
                                                 alt="Featured" 
                                                 class="rounded me-2" 
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php endif; ?>
                                        <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                        <?php if ($post['excerpt']): ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars(substr($post['excerpt'], 0, 80)); ?>...
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = [
                                            'draft' => 'secondary',
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $badge = $badge_class[$post['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $badge; ?>">
                                            <?php echo ucfirst($post['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fas fa-heart text-danger"></i> <?php echo $post['like_count']; ?>
                                            <i class="fas fa-comment text-primary ms-2"></i> <?php echo $post['comment_count']; ?>
                                            <i class="fas fa-share text-success ms-2"></i> <?php echo $post['share_count']; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($post['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($post['updated_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($post['status'] === 'approved'): ?>
                                                <a href="view.php?slug=<?php echo urlencode($post['slug']); ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (in_array($post['status'], ['draft', 'rejected'])): ?>
                                                <a href="edit.php?id=<?php echo $post['id']; ?>" 
                                                   class="btn btn-outline-warning" 
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (in_array($post['status'], ['draft', 'rejected'])): ?>
                                                <button onclick="deletePost(<?php echo $post['id']; ?>)" 
                                                        class="btn btn-outline-danger" 
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deletePost(postId) {
    if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
        window.location.href = 'delete.php?id=' + postId;
    }
}
</script>

<?php include_once '../footer.php'; ?>
