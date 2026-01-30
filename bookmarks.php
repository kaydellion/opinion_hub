<?php
require_once 'connect.php';
require_once 'functions.php';

$page_title = "My Bookmarked Polls";
include_once 'header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Handle unbookmark action
if (isset($_POST['action']) && $_POST['action'] === 'unbookmark') {
    $poll_id = (int)$_POST['poll_id'];
    $stmt = $conn->prepare("DELETE FROM user_bookmarks WHERE user_id = ? AND poll_id = ?");
    $stmt->bind_param("ii", $current_user_id, $poll_id);
    $stmt->execute();
    
    $_SESSION['success'] = "Poll removed from bookmarks";
    header("Location: bookmarks.php");
    exit;
}

// Check if bookmarks table exists, create if not
$table_check = $conn->query("SHOW TABLES LIKE 'user_bookmarks'");
if (!$table_check || $table_check->num_rows === 0) {
    // Create bookmarks table if it doesn't exist
    $create_sql = "CREATE TABLE user_bookmarks (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        poll_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
        UNIQUE KEY unique_bookmark (user_id, poll_id),
        INDEX(user_id),
        INDEX(poll_id),
        INDEX(created_at)
    )";
    $conn->query($create_sql);
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get total bookmarked polls
$total_query = $conn->prepare("SELECT COUNT(*) as total FROM user_bookmarks ub 
                               JOIN polls p ON ub.poll_id = p.id 
                               WHERE ub.user_id = ? AND p.status = 'active'");
if ($total_query) {
    $total_query->bind_param("i", $current_user_id);
    $total_query->execute();
    $total_result = $total_query->get_result();
    $total_bookmarks = $total_result->fetch_assoc()['total'];
    $total_pages = ceil($total_bookmarks / $per_page);
} else {
    $total_bookmarks = 0;
    $total_pages = 0;
}

// Get bookmarked polls with pagination
$bookmarks_query = $conn->prepare("SELECT p.*, ub.created_at as bookmarked_at, 
                                   u.username as creator_username, u.first_name as creator_first_name,
                                   c.name as category_name
                                   FROM user_bookmarks ub 
                                   JOIN polls p ON ub.poll_id = p.id 
                                   LEFT JOIN users u ON p.created_by = u.id
                                   LEFT JOIN categories c ON p.category_id = c.id
                                   WHERE ub.user_id = ? AND p.status = 'active'
                                   ORDER BY ub.created_at DESC 
                                   LIMIT ? OFFSET ?");
if ($bookmarks_query) {
    $bookmarks_query->bind_param("iii", $current_user_id, $per_page, $offset);
    $bookmarks_query->execute();
    $bookmarked_polls = $bookmarks_query->get_result();
} else {
    $bookmarked_polls = false;
}

$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors']);
unset($_SESSION['success']);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-bookmark me-2"></i>My Bookmarked Polls</h2>
                    <p class="text-muted mb-0">Polls you've saved for later</p>
                </div>
                <div>
                    <span class="badge bg-primary fs-6"><?= $total_bookmarks ?> Bookmarked</span>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php foreach ($errors as $error): ?>
                        <?= $error ?><br>
                    <?php endforeach; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($bookmarked_polls && $bookmarked_polls->num_rows > 0): ?>
                <div class="row">
                    <?php while ($poll = $bookmarked_polls->fetch_assoc()): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 border-0 shadow-sm hover-shadow">
                                <?php if (!empty($poll['image'])): ?>
                                    <div class="card-img-top position-relative overflow-hidden" style="height: 200px;">
                                        <img src="<?= SITE_URL ?>uploads/polls/<?= htmlspecialchars($poll['image']) ?>" 
                                             alt="<?= htmlspecialchars($poll['title']) ?>" 
                                             class="w-100 h-100 object-fit-cover"
                                             onerror="this.src='<?= SITE_URL ?>assets/images/default-poll.jpg'">
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <span class="badge bg-<?= $poll['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?= ucfirst($poll['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">
                                            <a href="<?= SITE_URL ?>view-poll/<?= htmlspecialchars($poll['slug']) ?>" 
                                               class="text-decoration-none text-dark">
                                                <?= htmlspecialchars(substr($poll['title'], 0, 60)) ?>
                                                <?= strlen($poll['title']) > 60 ? '...' : '' ?>
                                            </a>
                                        </h6>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form method="POST" onsubmit="return confirm('Remove this poll from bookmarks?');">
                                                        <input type="hidden" name="action" value="unbookmark">
                                                        <input type="hidden" name="poll_id" value="<?= $poll['id'] ?>">
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-bookmark me-2"></i>Remove Bookmark
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <p class="card-text text-muted small mb-2">
                                        <?= htmlspecialchars(substr(strip_tags($poll['description']), 0, 100)) ?>...
                                    </p>
                                    
                                    <div class="d-flex justify-content-between align-items-center text-muted small mb-2">
                                        <span>
                                            <i class="fas fa-user me-1"></i>
                                            <?= htmlspecialchars($poll['creator_first_name'] ?: $poll['creator_username'] ?: 'Anonymous') ?>
                                        </span>
                                        <?php if (!empty($poll['category_name'])): ?>
                                            <span class="badge bg-light text-dark">
                                                <?= htmlspecialchars($poll['category_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
                                        <span>
                                            <i class="fas fa-chart-bar me-1"></i>
                                            <?= number_format($poll['total_responses']) ?> responses
                                        </span>
                                        <span>
                                            <i class="fas fa-clock me-1"></i>
                                            <?= timeAgo($poll['bookmarked_at']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="<?= SITE_URL ?>view-poll/<?= htmlspecialchars($poll['slug']) ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-poll me-1"></i>Take Poll
                                        </a>
                                        <span class="text-warning">
                                            <i class="fas fa-bookmark"></i>
                                            <small>Bookmarked</small>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Bookmarks pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <li class="page-item active">
                                        <span class="page-link"><?= $i ?></span>
                                    </li>
                                <?php elseif (abs($i - $page) <= 2 || $i == 1 || $i == $total_pages): ?>
                                    <li class="page-item">
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

            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-bookmark fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Bookmarked Polls Yet</h5>
                    <p class="text-muted">Start exploring and bookmark polls that interest you!</p>
                    <a href="<?= SITE_URL ?>polls.php" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Browse Polls
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}

.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.card-img-top {
    border-top-left-radius: 0.375rem;
    border-top-right-radius: 0.375rem;
}

.object-fit-cover {
    object-fit: cover;
}
</style>

<?php include_once 'footer.php'; ?>
