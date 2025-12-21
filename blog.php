<?php
session_start();
require_once 'connect.php';
require_once 'functions.php';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

// Get total count
$count_query = "SELECT COUNT(*) as total FROM blog_posts WHERE status = 'approved'";
$count_result = $conn->query($count_query);
$total_posts = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $per_page);

// Get approved blog posts with stats
$articles_query = "SELECT bp.*, 
                   CONCAT(u.first_name, ' ', u.last_name) as author_name,
                   (SELECT COUNT(*) FROM blog_likes WHERE post_id = bp.id) as like_count,
                   (SELECT COUNT(*) FROM blog_comments WHERE post_id = bp.id) as comment_count,
                   (SELECT COUNT(*) FROM blog_shares WHERE post_id = bp.id) as share_count
                   FROM blog_posts bp 
                   JOIN users u ON bp.user_id = u.id 
                   WHERE bp.status = 'approved' 
                   ORDER BY bp.created_at DESC
                   LIMIT ? OFFSET ?";

$stmt = $conn->prepare($articles_query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$articles = $stmt->get_result();

$page_title = 'Blog - Opinion Hub NG';
include 'header.php';
?>

<div class="container py-5">
    <!-- Header -->
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold mb-3">
            <i class="fas fa-blog text-primary me-3"></i>Our Blog
        </h1>
        <p class="lead text-muted">
            Insights, stories, and updates from the Opinion Hub NG community
        </p>
        <?php if (isLoggedIn()): ?>
            <a href="blog/create.php" class="btn btn-primary btn-lg mt-3">
                <i class="fas fa-pen me-2"></i>Write a Post
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Blog Posts Grid -->
    <div class="row g-4">
        <?php if ($articles && $articles->num_rows > 0): ?>
            <?php while ($article = $articles->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm hover-shadow transition">
                        <?php if ($article['featured_image']): ?>
                            <a href="blog/view.php?slug=<?= urlencode($article['slug']) ?>">
                                <img src="<?= SITE_URL . $article['featured_image'] ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($article['title']) ?>" 
                                     style="height: 220px; object-fit: cover;">
                            </a>
                        <?php else: ?>
                            <div class="card-img-top bg-gradient-primary d-flex align-items-center justify-content-center" 
                                 style="height: 220px;">
                                <i class="fas fa-file-alt fa-4x text-white opacity-50"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title mb-3">
                                <a href="blog/view.php?slug=<?= urlencode($article['slug']) ?>" 
                                   class="text-decoration-none text-dark stretched-link">
                                    <?= htmlspecialchars($article['title']) ?>
                                </a>
                            </h5>
                            
                            <?php if ($article['excerpt']): ?>
                                <p class="card-text text-muted mb-3">
                                    <?= htmlspecialchars(substr($article['excerpt'], 0, 120)) ?>...
                                </p>
                            <?php else: ?>
                                <p class="card-text text-muted mb-3">
                                    <?= substr(strip_tags($article['content']), 0, 120) ?>...
                                </p>
                            <?php endif; ?>
                            
                            <div class="mt-auto">
                                <!-- Author & Date -->
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                                         style="width: 32px; height: 32px; font-size: 14px;">
                                        <?= strtoupper(substr($article['author_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <small class="text-dark fw-bold d-block">
                                            <?= htmlspecialchars($article['author_name']) ?>
                                        </small>
                                        <small class="text-muted">
                                            <?= date('M j, Y', strtotime($article['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Stats -->
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>
                                        <i class="fas fa-heart text-danger"></i> <?= $article['like_count'] ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-comment text-primary"></i> <?= $article['comment_count'] ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-share text-success"></i> <?= $article['share_count'] ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-clock"></i> 
                                        <?php 
                                        $read_time = ceil(str_word_count(strip_tags($article['content'])) / 200);
                                        echo $read_time . ' min';
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center py-5">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <h4>No blog posts yet</h4>
                    <p class="mb-0">Be the first to share your insights!</p>
                    <?php if (isLoggedIn()): ?>
                        <a href="blog/create.php" class="btn btn-primary mt-3">
                            <i class="fas fa-pen me-2"></i>Create a Blog Post
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Blog pagination" class="mt-5">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == 1 || $i == $total_pages || abs($i - $page) <= 2): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php elseif (abs($i - $page) == 3): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
}
.bg-gradient-primary {
    background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-primary-dark, #0056b3) 100%);
}
</style>

<?php include 'footer.php'; ?>
