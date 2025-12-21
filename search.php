<?php
session_start();
require_once 'connect.php';
require_once 'functions.php';

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Pagination
$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

$results = [
    'polls' => [],
    'blog_posts' => [],
    'users' => [],
    'agents' => []
];

$total_results = 0;

if (!empty($search_query)) {
    $search_term = $conn->real_escape_string($search_query);
    
    // Search Polls
    if ($search_type === 'all' || $search_type === 'polls') {
        $poll_query = "SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) as creator_name,
                       (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as response_count
                       FROM polls p
                       JOIN users u ON p.created_by = u.id
                       WHERE p.status = 'active' 
                       AND (p.title LIKE '%$search_term%' OR p.description LIKE '%$search_term%')
                       ORDER BY p.created_at DESC
                       LIMIT $per_page OFFSET $offset";
        $poll_result = $conn->query($poll_query);
        if ($poll_result) {
            $results['polls'] = $poll_result->fetch_all(MYSQLI_ASSOC);
            $total_results += count($results['polls']);
        }
    }
    
    // Search Blog Posts
    if ($search_type === 'all' || $search_type === 'blog') {
        $blog_query = "SELECT bp.*, CONCAT(u.first_name, ' ', u.last_name) as author_name,
                       (SELECT COUNT(*) FROM blog_likes WHERE post_id = bp.id) as like_count,
                       (SELECT COUNT(*) FROM blog_comments WHERE post_id = bp.id) as comment_count
                       FROM blog_posts bp
                       JOIN users u ON bp.user_id = u.id
                       WHERE bp.status = 'approved' 
                       AND (bp.title LIKE '%$search_term%' OR bp.content LIKE '%$search_term%' OR bp.excerpt LIKE '%$search_term%')
                       ORDER BY bp.created_at DESC
                       LIMIT $per_page OFFSET $offset";
        $blog_result = $conn->query($blog_query);
        if ($blog_result) {
            $results['blog_posts'] = $blog_result->fetch_all(MYSQLI_ASSOC);
            $total_results += count($results['blog_posts']);
        }
    }
    
    // Search Users (only if admin)
    if (isLoggedIn() && getCurrentUser()['role'] === 'admin') {
        if ($search_type === 'all' || $search_type === 'users') {
            $user_query = "SELECT id, username, email, first_name, last_name, role, created_at,
                           CONCAT(first_name, ' ', last_name) as full_name
                           FROM users
                           WHERE (first_name LIKE '%$search_term%' 
                                 OR last_name LIKE '%$search_term%' 
                                 OR email LIKE '%$search_term%' 
                                 OR username LIKE '%$search_term%')
                           ORDER BY created_at DESC
                           LIMIT $per_page OFFSET $offset";
            $user_result = $conn->query($user_query);
            if ($user_result) {
                $results['users'] = $user_result->fetch_all(MYSQLI_ASSOC);
                $total_results += count($results['users']);
            }
        }
    }
}

$page_title = !empty($search_query) ? "Search Results for \"$search_query\"" : "Search";
include_once 'header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <!-- Search Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="mb-4"><i class="fas fa-search me-2"></i>Search Opinion Hub NG</h3>
                    <form method="GET" action="search.php">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <input type="text" name="q" class="form-control form-control-lg" 
                                       placeholder="Search polls, blog posts, and more..." 
                                       value="<?php echo htmlspecialchars($search_query); ?>" 
                                       required>
                            </div>
                            <div class="col-md-4">
                                <select name="type" class="form-select form-select-lg">
                                    <option value="all" <?php echo $search_type === 'all' ? 'selected' : ''; ?>>All Content</option>
                                    <option value="polls" <?php echo $search_type === 'polls' ? 'selected' : ''; ?>>Polls Only</option>
                                    <option value="blog" <?php echo $search_type === 'blog' ? 'selected' : ''; ?>>Blog Posts Only</option>
                                    <?php if (isLoggedIn() && getCurrentUser()['role'] === 'admin'): ?>
                                        <option value="users" <?php echo $search_type === 'users' ? 'selected' : ''; ?>>Users Only</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($search_query)): ?>
                <!-- Results Summary -->
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Found <strong><?php echo $total_results; ?></strong> result(s) for "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
                </div>

                <!-- Poll Results -->
                <?php if (!empty($results['polls'])): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-poll me-2 text-primary"></i>Polls (<?php echo count($results['polls']); ?>)</h4>
                        </div>
                        <div class="card-body">
                            <?php foreach ($results['polls'] as $poll): ?>
                                <div class="border-bottom pb-3 mb-3">
                                    <h5>
                                        <a href="<?php echo SITE_URL; ?>view-poll/<?php echo $poll['slug']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($poll['title']); ?>
                                        </a>
                                    </h5>
                                    <p class="text-muted mb-2"><?php echo htmlspecialchars(substr($poll['description'], 0, 200)); ?>...</p>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($poll['creator_name']); ?> •
                                        <i class="fas fa-chart-bar me-1"></i><?php echo $poll['response_count']; ?> responses •
                                        <i class="fas fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($poll['created_at'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Blog Post Results -->
                <?php if (!empty($results['blog_posts'])): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-blog me-2 text-success"></i>Blog Posts (<?php echo count($results['blog_posts']); ?>)</h4>
                        </div>
                        <div class="card-body">
                            <?php foreach ($results['blog_posts'] as $post): ?>
                                <div class="border-bottom pb-3 mb-3">
                                    <h5>
                                        <a href="<?php echo SITE_URL; ?>blog/view.php?slug=<?php echo $post['slug']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h5>
                                    <p class="text-muted mb-2"><?php echo htmlspecialchars(substr(strip_tags($post['excerpt'] ?: $post['content']), 0, 200)); ?>...</p>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($post['author_name']); ?> •
                                        <i class="fas fa-heart me-1"></i><?php echo $post['like_count']; ?> likes •
                                        <i class="fas fa-comment me-1"></i><?php echo $post['comment_count']; ?> comments •
                                        <i class="fas fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- User Results (Admin Only) -->
                <?php if (isLoggedIn() && getCurrentUser()['role'] === 'admin' && !empty($results['users'])): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-users me-2 text-info"></i>Users (<?php echo count($results['users']); ?>)</h4>
                        </div>
                        <div class="card-body">
                            <?php foreach ($results['users'] as $u): ?>
                                <div class="border-bottom pb-3 mb-3">
                                    <h5><?php echo htmlspecialchars($u['full_name']); ?> <span class="badge bg-secondary"><?php echo ucfirst($u['role']); ?></span></h5>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($u['email']); ?> •
                                        <i class="fas fa-user me-1"></i>@<?php echo htmlspecialchars($u['username']); ?> •
                                        <i class="fas fa-calendar me-1"></i>Joined <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- No Results -->
                <?php if ($total_results === 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No results found for "<strong><?php echo htmlspecialchars($search_query); ?></strong>". Try different keywords or search type.
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-4x text-muted mb-3"></i>
                    <h3>Enter a search term to get started</h3>
                    <p class="text-muted">Search across polls, blog posts, and more</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
