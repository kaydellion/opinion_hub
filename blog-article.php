<?php
require_once 'connect.php';
require_once 'functions.php';

$slug = sanitize($_GET['slug'] ?? '');

if (empty($slug)) {
    header('Location: blog.php');
    exit;
}

// Get article
$stmt = $conn->prepare("SELECT a.*, u.name as author_name, c.name as category_name 
                        FROM blog_articles a 
                        JOIN users u ON a.author_id = u.id 
                        LEFT JOIN categories c ON a.category_id = c.id 
                        WHERE a.slug = ? AND a.status = 'published'");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: blog.php');
    exit;
}

$article = $result->fetch_assoc();

// Increment views
$conn->query("UPDATE blog_articles SET views = views + 1 WHERE id = {$article['id']}");

$page_title = $article['title'];
include 'header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <article>
                <?php if ($article['featured_image']): ?>
                    <img src="<?= $article['featured_image'] ?>" class="img-fluid rounded mb-4" alt="<?= htmlspecialchars($article['title']) ?>">
                <?php endif; ?>
                
                <h1 class="mb-3"><?= htmlspecialchars($article['title']) ?></h1>
                
                <div class="text-muted mb-4">
                    <i class="fas fa-user me-2"></i><?= htmlspecialchars($article['author_name']) ?>
                    <span class="mx-2">|</span>
                    <i class="fas fa-calendar me-2"></i><?= date('F j, Y', strtotime($article['published_at'])) ?>
                    <span class="mx-2">|</span>
                    <i class="fas fa-eye me-2"></i><?= number_format($article['views']) ?> views
                    <?php if ($article['category_name']): ?>
                        <span class="mx-2">|</span>
                        <i class="fas fa-folder me-2"></i><?= htmlspecialchars($article['category_name']) ?>
                    <?php endif; ?>
                </div>
                
                <div class="article-content mb-4" style="line-height:1.8;">
                    <?= $article['content'] ?>
                </div>
                
                <?php if ($article['tags']): ?>
                    <div class="mb-4">
                        <?php $tags = json_decode($article['tags'], true); ?>
                        <?php foreach ($tags as $tag): ?>
                            <span class="badge bg-secondary me-1">#<?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="text-center">
                    <a href="blog.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Blog
                    </a>
                </div>
            </article>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
