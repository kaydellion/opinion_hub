<?php
require_once '../connect.php';
require_once '../functions.php';

// Check if user is logged in and is an agent
if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . 'signin.php');
    exit;
}

$user = getCurrentUser();

if ($user['role'] !== 'agent') {
    $_SESSION['errors'] = ["Access Denied: This page is for agents only."];
    header('Location: ' . SITE_URL . 'dashboard.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get filters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build WHERE clause
$where_clauses = ["p.status = 'active'"];

if ($category_filter > 0) {
    $where_clauses[] = "p.category_id = $category_filter";
}

if (!empty($search)) {
    $where_clauses[] = "(p.title LIKE '%$search%' OR p.description LIKE '%$search%')";
}

$where_sql = implode(' AND ', $where_clauses);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM polls p WHERE $where_sql";
$count_result = $conn->query($count_query);
$total_polls = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_polls / $limit);

// Get polls
$polls_query = "SELECT p.*, c.name as category_name, 
                CONCAT(u.first_name, ' ', u.last_name) as creator_name,
                (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as response_count
                FROM polls p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE $where_sql
                ORDER BY p.created_at DESC
                LIMIT $limit OFFSET $offset";
$polls = $conn->query($polls_query);

// Get categories for filter
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

$page_title = "Browse Polls to Share";
include '../header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= SITE_URL ?>dashboards/agent-dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Browse Polls</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4 p-5">
        <div class="col-md-8">
            <h2><i class="fas fa-share-alt me-2"></i>Share Polls & Earn</h2>
            <p class="text-muted">Browse active polls and share them to earn commissions for every completed response.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= SITE_URL ?>agent/my-earnings.php" class="btn btn-success">
                <i class="fas fa-wallet me-2"></i>My Earnings
            </a>
        </div>
    </div>

    <!-- Earning Info Alert -->
    <div class="alert alert-info mb-4 p-55">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-2"><i class="fas fa-lightbulb me-2"></i>How to Earn</h5>
                <p class="mb-0">
                    <strong>1. Complete polls yourself</strong> - Earn the stated amount per poll<br>
                    <strong>2. Share your referral link</strong> - Earn when others complete polls through your link
                </p>
            </div>
            <div class="col-md-4 text-end">
                <div class="display-6 text-primary">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4 p-5">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search polls..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="0">All Categories</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                </div>
                <?php if ($search || $category_filter): ?>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <a href="<?= SITE_URL ?>agent/browse-polls.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Polls Grid -->
    <?php if ($polls && $polls->num_rows > 0): ?>
        <div class="row g-4 mb-4 p-5">
            <?php while ($poll = $polls->fetch_assoc()): 
                $price = floatval($poll['price_per_response'] ?? 0);
                $progress = $poll['target_responders'] > 0 ? ($poll['response_count'] / $poll['target_responders']) * 100 : 0;
                $progress = min(100, $progress);
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm hover-shadow">
                        <?php if ($poll['image']): ?>
                            <img src="<?= SITE_URL . 'uploads/polls/'.$poll['image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($poll['title']) ?>" style="height: 200px; object-fit: cover;">
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-primary"><?= htmlspecialchars($poll['category_name'] ?? 'General') ?></span>
                                <?php if ($price > 0): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-money-bill-wave"></i> ₦<?= number_format($price, 2) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <h5 class="card-title"><?= htmlspecialchars($poll['title']) ?></h5>
                            <p class="card-text text-muted small">
                                <?= htmlspecialchars(substr($poll['description'], 0, 100)) . (strlen($poll['description']) > 100 ? '...' : '') ?>
                            </p>
                            
                            <div class="mb-3">
                                <small class="text-muted">Progress: <?= $poll['response_count'] ?> / <?= $poll['target_responders'] ?> responses</small>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progress ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="<?= SITE_URL ?>view-poll/<?= $poll['slug'] ?>" class="btn btn-outline-primary btn-sm flex-fill" target="_blank">
                                    <i class="fas fa-eye me-1"></i>View
                                </a>
                                <a href="<?= SITE_URL ?>agent/share-poll.php?poll_id=<?= $poll['id'] ?>" class="btn btn-success btn-sm flex-fill">
                                    <i class="fas fa-share-alt me-1"></i>Share & Earn
                                </a>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-light">
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($poll['creator_name']) ?>
                                <span class="ms-2"><i class="fas fa-clock me-1"></i><?= date('M d, Y', strtotime($poll['created_at'])) ?></span>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center">
                <nav>
                    <ul class="pagination">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&category=<?= $category_filter ?>&search=<?= urlencode($search) ?>">
                                Previous
                            </a>
                        </li>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&category=<?= $category_filter ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&category=<?= $category_filter ?>&search=<?= urlencode($search) ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h4>No Polls Found</h4>
                <p class="text-muted mb-0">
                    <?php if ($search || $category_filter): ?>
                        No polls match your search criteria. Try adjusting your filters.
                    <?php else: ?>
                        There are no active polls available at the moment.
                    <?php endif; ?>
                </p>
                <?php if ($search || $category_filter): ?>
                    <a href="<?= SITE_URL ?>agent/browse-polls.php" class="btn btn-primary mt-3">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}
</style>

<?php include '../footer.php'; ?>
