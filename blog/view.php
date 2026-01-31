<?php
session_start();
require_once '../connect.php';
require_once '../functions.php';

// Get post slug from URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header("Location: " . SITE_URL . "blog.php");
    exit();
}

// Get post with author details
$stmt = $conn->prepare("SELECT bp.*, CONCAT(u.first_name, ' ', u.last_name) as author_name, u.email as author_email,
                        (SELECT COUNT(*) FROM blog_likes WHERE post_id = bp.id) as like_count,
                        (SELECT COUNT(*) FROM blog_comments WHERE post_id = bp.id) as comment_count,
                        (SELECT COUNT(*) FROM blog_shares WHERE post_id = bp.id) as share_count
                        FROM blog_posts bp
                        JOIN users u ON bp.user_id = u.id
                        WHERE bp.slug = ? AND bp.status = 'approved'");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    $_SESSION['error'] = "Post not found or not yet approved.";
    header("Location: " . SITE_URL . "blog.php");
    exit();
}

// Check if current user has liked this post
$user_has_liked = false;
if (isLoggedIn()) {
    $like_stmt = $conn->prepare("SELECT id FROM blog_likes WHERE post_id = ? AND user_id = ?");
    $like_stmt->bind_param("ii", $post['id'], $_SESSION['user_id']);
    $like_stmt->execute();
    $user_has_liked = $like_stmt->get_result()->num_rows > 0;
}

// Get latest 3 polls
$latest_polls = $conn->query("SELECT p.*, c.name as category_name,
                              (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as total_responses
                              FROM polls p
                              LEFT JOIN categories c ON p.category_id = c.id
                              WHERE p.status = 'active'
                              ORDER BY p.created_at DESC
                              LIMIT 3");

// Get latest 3 datasets from databank
$latest_datasets = $conn->query("SELECT p.*, c.name as category_name,
                                 (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as response_count
                                 FROM polls p
                                 LEFT JOIN categories c ON p.category_id = c.id
                                 WHERE p.results_for_sale = 1 AND p.status = 'active'
                                 ORDER BY p.created_at DESC
                                 LIMIT 3");

// Get comments (only top-level, replies will be fetched via nested query)
$comments_stmt = $conn->prepare("SELECT bc.*, CONCAT(u.first_name, ' ', u.last_name) as commenter_name
                                 FROM blog_comments bc
                                 JOIN users u ON bc.user_id = u.id
                                 WHERE bc.post_id = ? AND bc.parent_id IS NULL
                                 ORDER BY bc.created_at DESC");
$comments_stmt->bind_param("i", $post['id']);
$comments_stmt->execute();
$comments = $comments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Function to get replies for a comment
function getReplies($conn, $comment_id) {
    $stmt = $conn->prepare("SELECT bc.*, CONCAT(u.first_name, ' ', u.last_name) as commenter_name
                           FROM blog_comments bc
                           JOIN users u ON bc.user_id = u.id
                           WHERE bc.parent_id = ?
                           ORDER BY bc.created_at ASC");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$page_title = $post['title'];
include_once '../header.php';
?>

<!-- SEO Meta Tags -->
<meta name="description" content="<?php echo htmlspecialchars($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 160)); ?>">
<meta property="og:title" content="<?php echo htmlspecialchars($post['title']); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($post['excerpt']); ?>">
<?php if ($post['featured_image']): ?>
<meta property="og:image" content="<?php echo SITE_URL . $post['featured_image']; ?>">
<?php endif; ?>

<div class="container py-5">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <article class="blog-post">
                <!-- Featured Image -->
                <?php if ($post['featured_image']): ?>
                    <img src="<?php echo SITE_URL . $post['featured_image']; ?>" 
                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                         class="img-fluid rounded mb-4 w-100"
                         style="max-height: 500px; object-fit: cover;">
                <?php endif; ?>

                <!-- Post Header -->
                <h1 class="mb-3"><?php echo htmlspecialchars($post['title']); ?></h1>
                
                <div class="d-flex align-items-center text-muted mb-4">
                    <div class="me-4">
                        <i class="fas fa-user-circle me-2"></i>
                        <strong><?php echo htmlspecialchars($post['author_name']); ?></strong>
                    </div>
                    <div class="me-4">
                        <i class="fas fa-calendar me-2"></i>
                        <?php echo date('F d, Y', strtotime($post['created_at'])); ?>
                    </div>
                    <div>
                        <i class="fas fa-clock me-2"></i>
                        <?php 
                        $read_time = ceil(str_word_count(strip_tags($post['content'])) / 200);
                        echo $read_time . ' min read';
                        ?>
                    </div>
                </div>

                <!-- Excerpt -->
                <?php if ($post['excerpt']): ?>
                    <div class="alert alert-light border-start border-primary border-4 mb-4">
                        <p class="mb-0 lead"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Post Content -->
                <div class="post-content mb-5">
                    <?php echo $post['content']; ?>
                </div>

                <!-- Post Actions -->
                <div class="d-flex justify-content-between align-items-center border-top border-bottom py-3 mb-5">
                    <div>
                        <button onclick="toggleLike(<?php echo $post['id']; ?>)" 
                                class="btn btn-outline-danger btn-sm me-2 <?php echo $user_has_liked ? 'active' : ''; ?>"
                                id="like-btn"
                                <?php echo !isLoggedIn() ? 'disabled title="Login to like"' : ''; ?>>
                            <i class="fas fa-heart me-1"></i>
                            <span id="like-count"><?php echo $post['like_count']; ?></span> Likes
                        </button>
                        <button class="btn btn-outline-primary btn-sm me-2"
                                onclick="document.getElementById('comment-form').scrollIntoView({ behavior: 'smooth' })">
                            <i class="fas fa-comment me-1"></i>
                            <?php echo $post['comment_count']; ?> Comments
                        </button>
                        <span class="text-muted">
                            <i class="fas fa-share me-1"></i>
                            <?php echo $post['share_count']; ?> Shares
                        </span>
                    </div>
                    <div class="btn-group">
                        <button onclick="sharePost('facebook')" class="btn btn-sm btn-outline-primary" title="Share on Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </button>
                        <button onclick="sharePost('twitter')" class="btn btn-sm btn-outline-info" title="Share on Twitter">
                            <i class="fab fa-twitter"></i>
                        </button>
                        <button onclick="sharePost('whatsapp')" class="btn btn-sm btn-outline-success" title="Share on WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </button>
                        <button onclick="sharePost('email')" class="btn btn-sm btn-outline-secondary" title="Share via Email">
                            <i class="fas fa-envelope"></i>
                        </button>
                    </div>
                </div>

                <!-- Latest Polls and Datasets Section -->
                <div class="row mt-5">
                    <!-- Latest Polls -->
                    <div class="col-lg-6 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-poll me-2"></i>Latest Polls</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($latest_polls && $latest_polls->num_rows > 0): ?>
                                    <div class="row">
                                        <?php while ($poll = $latest_polls->fetch_assoc()): ?>
                                            <div class="col-md-12 mb-3">
                                                <div class="d-flex align-items-start">
                                                    <?php if (!empty($poll['image'])): ?>
                                                        <img src="<?php echo SITE_URL; ?>uploads/polls/<?php echo $poll['image']; ?>"
                                                             class="rounded me-3" alt="Poll" style="width: 60px; height: 60px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center me-3"
                                                             style="width: 60px; height: 60px;">
                                                            <i class="fas fa-poll fa-lg"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">
                                                            <a href="<?php echo SITE_URL; ?>view-poll/<?php echo $poll['slug']; ?>"
                                                               class="text-decoration-none text-dark">
                                                                <?php echo htmlspecialchars(substr($poll['title'], 0, 50)); ?>
                                                                <?php if (strlen($poll['title']) > 50) echo '...'; ?>
                                                            </a>
                                                        </h6>
                                                        <p class="text-muted small mb-1">
                                                            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($poll['category_name'] ?? 'General'); ?>
                                                        </p>
                                                        <p class="text-muted small mb-0">
                                                            <i class="fas fa-users"></i> <?php echo $poll['total_responses']; ?> responses
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="<?php echo SITE_URL; ?>polls.php" class="btn btn-outline-primary btn-sm">
                                            View All Polls <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-poll fa-3x mb-3"></i>
                                        <p>No polls available yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Latest Datasets from Databank -->
                    <div class="col-lg-6 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-database me-2"></i>Latest Datasets</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($latest_datasets && $latest_datasets->num_rows > 0): ?>
                                    <div class="row">
                                        <?php while ($dataset = $latest_datasets->fetch_assoc()): ?>
                                            <div class="col-md-12 mb-3">
                                                <div class="d-flex align-items-start">
                                                    <?php if (!empty($dataset['image'])): ?>
                                                        <img src="<?php echo SITE_URL; ?>uploads/polls/<?php echo $dataset['image']; ?>"
                                                             class="rounded me-3" alt="Dataset" style="width: 60px; height: 60px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-success text-white rounded d-flex align-items-center justify-content-center me-3"
                                                             style="width: 60px; height: 60px;">
                                                            <i class="fas fa-database fa-lg"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">
                                                            <a href="<?php echo SITE_URL; ?>databank.php"
                                                               class="text-decoration-none text-dark">
                                                                <?php echo htmlspecialchars(substr($dataset['title'], 0, 50)); ?>
                                                                <?php if (strlen($dataset['title']) > 50) echo '...'; ?>
                                                            </a>
                                                        </h6>
                                                        <p class="text-muted small mb-1">
                                                            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($dataset['category_name'] ?? 'General'); ?>
                                                        </p>
                                                        <p class="text-muted small mb-0">
                                                            <i class="fas fa-users"></i> <?php echo $dataset['response_count']; ?> responses
                                                            <span class="badge bg-success ms-2">₦<?php echo number_format($dataset['results_sale_price'] ?? 0); ?></span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="<?php echo SITE_URL; ?>databank.php" class="btn btn-outline-success btn-sm">
                                            Browse Databank <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-database fa-3x mb-3"></i>
                                        <p>No datasets available yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="comments-section">
                    <h3 class="mb-4">
                        <i class="fas fa-comments me-2"></i>
                        Comments (<?php echo $post['comment_count']; ?>)
                    </h3>

                    <!-- Comment Form -->
                    <?php if (isLoggedIn()): ?>
                        <div class="card mb-4" id="comment-form">
                            <div class="card-body">
                                <form onsubmit="submitComment(event)">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <input type="hidden" name="parent_id" id="parent-id" value="">
                                    <div class="mb-3">
                                        <label class="form-label" id="comment-label">Leave a comment</label>
                                        <textarea class="form-control" 
                                                  name="comment" 
                                                  id="comment-text"
                                                  rows="3" 
                                                  required 
                                                  placeholder="Share your thoughts..."></textarea>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-sm btn-secondary" id="cancel-reply" style="display: none;" onclick="cancelReply()">
                                            Cancel Reply
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-2"></i>Post Comment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Please <a href="<?php echo SITE_URL; ?>signin.php">sign in</a> to leave a comment.
                        </div>
                    <?php endif; ?>

                    <!-- Comments List -->
                    <div id="comments-list">
                        <?php if (empty($comments)): ?>
                            <p class="text-muted">No comments yet. Be the first to comment!</p>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <?php
                                $replies = getReplies($conn, $comment['id']);
                                ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?php echo htmlspecialchars($comment['commenter_name']); ?></strong>
                                                <small class="text-muted ms-2">
                                                    <?php echo date('M d, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                                                </small>
                                            </div>
                                            <?php if (isLoggedIn()): ?>
                                                <button class="btn btn-sm btn-link text-primary" 
                                                        onclick="replyToComment(<?php echo $comment['id']; ?>, '<?php echo htmlspecialchars($comment['commenter_name']); ?>')">
                                                    <i class="fas fa-reply me-1"></i>Reply
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                        
                                        <!-- Replies -->
                                        <?php if (!empty($replies)): ?>
                                            <div class="ms-4 mt-3">
                                                <?php foreach ($replies as $reply): ?>
                                                    <div class="card bg-light mb-2">
                                                        <div class="card-body py-2">
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($reply['commenter_name']); ?></strong>
                                                                <small class="text-muted ms-2">
                                                                    <?php echo date('M d, Y \a\t g:i A', strtotime($reply['created_at'])); ?>
                                                                </small>
                                                            </div>
                                                            <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($reply['comment'])); ?></p>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">About the Author</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                             style="width: 60px; height: 60px; font-size: 24px;">
                            <?php echo strtoupper(substr($post['author_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($post['author_name']); ?></h6>
                            <small class="text-muted">Author</small>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // Get related posts from same author
            $related_stmt = $conn->prepare("SELECT id, title, slug, featured_image, created_at
                                           FROM blog_articles
                                           WHERE author_id = ? AND id != ? AND status = 'published'
                                           ORDER BY created_at DESC
                                           LIMIT 5");
            $related_stmt->bind_param("ii", $post['user_id'], $post['id']);
            $related_stmt->execute();
            $related_posts = $related_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            ?>

            <?php if (!empty($related_posts)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">More from this Author</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($related_posts as $related): ?>
                            <a href="view.php?slug=<?php echo urlencode($related['slug']); ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex align-items-center">
                                    <?php if ($related['featured_image']): ?>
                                        <img src="<?php echo SITE_URL . $related['featured_image']; ?>" 
                                             alt="" 
                                             class="rounded me-3"
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($related['title']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y', strtotime($related['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const postId = <?php echo $post['id']; ?>;
const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;

function toggleLike(postId) {
    if (!isLoggedIn) {
        alert('Please login to like posts');
        return;
    }

    fetch('like.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'post_id=' + postId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('like-count').textContent = data.like_count;
            const likeBtn = document.getElementById('like-btn');
            if (data.liked) {
                likeBtn.classList.add('active');
            } else {
                likeBtn.classList.remove('active');
            }
        }
    });
}

function sharePost(platform) {
    const url = window.location.href;
    const title = <?php echo json_encode($post['title']); ?>;
    
    let shareUrl = '';
    switch(platform) {
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
            break;
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
            break;
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`;
            break;
        case 'email':
            shareUrl = `mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(url)}`;
            break;
    }
    
    // Track share
    fetch('share.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `post_id=${postId}&platform=${platform}`
    });
    
    if (platform !== 'email') {
        window.open(shareUrl, '_blank', 'width=600,height=400');
    } else {
        window.location.href = shareUrl;
    }
}

function submitComment(event) {
    event.preventDefault();
    
    if (!isLoggedIn) {
        alert('Please login to comment');
        return;
    }
    
    const formData = new FormData(event.target);
    
    fetch('comment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error posting comment');
        }
    });
}

function replyToComment(commentId, commenterName) {
    document.getElementById('parent-id').value = commentId;
    document.getElementById('comment-label').textContent = `Reply to ${commenterName}`;
    document.getElementById('cancel-reply').style.display = 'block';
    document.getElementById('comment-text').focus();
    document.getElementById('comment-form').scrollIntoView({ behavior: 'smooth' });
}

function cancelReply() {
    document.getElementById('parent-id').value = '';
    document.getElementById('comment-label').textContent = 'Leave a comment';
    document.getElementById('cancel-reply').style.display = 'none';
}
</script>

<style>
.post-content {
    font-size: 1.1rem;
    line-height: 1.8;
}
.post-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 20px 0;
}
.post-content h2, .post-content h3, .post-content h4 {
    margin-top: 30px;
    margin-bottom: 15px;
}
.post-content p {
    margin-bottom: 20px;
}
</style>

<?php include_once '../footer.php'; ?>
