<?php
$page_title = "All Poll Results - Admin";
include_once '../header.php';

$current_user = getCurrentUser();
if (!$current_user || $current_user['role'] !== 'admin') {
    header('Location: ' . SITE_URL . 'signin.php');
    exit;
}

global $conn;

// Pagination
$page_num = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page_num - 1) * $limit;

// Filters
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build WHERE clause
$where = [];
if ($category > 0) $where[] = "p.category_id = $category";
if ($status) $where[] = "p.status = '$status'";
if ($search) $where[] = "p.title LIKE '%$search%'";
$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get polls
$query = "SELECT p.*, c.name as category_name, u.first_name, u.last_name,
          (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as response_count,
          p.results_for_sale
          FROM polls p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN users u ON p.created_by = u.id
          $where_clause
          ORDER BY p.created_at DESC
          LIMIT $limit OFFSET $offset";

$polls = $conn->query($query);

// Get total count
$total_result = $conn->query("SELECT COUNT(*) as count FROM polls p $where_clause");
$total = $total_result ? $total_result->fetch_assoc()['count'] : 0;
$total_pages = $total > 0 ? ceil($total / $limit) : 0;

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Get stats
$stats = $conn->query("SELECT 
    COUNT(*) as total_polls,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_polls,
    SUM(CASE WHEN status = 'paused' THEN 1 ELSE 0 END) as paused_polls,
    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_polls,
    SUM(CASE WHEN results_for_sale = 1 THEN 1 ELSE 0 END) as for_sale_polls
    FROM polls")->fetch_assoc();
?>

<div class="container-fluid my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-chart-bar text-primary"></i> All Poll Results</h2>
            <p class="text-muted">View and analyze all poll results across the platform (Admin Access)</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?php echo SITE_URL; ?>admin/dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-poll text-primary"></i> Total</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['total_polls']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-check-circle text-success"></i> Active</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['active_polls']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-pause-circle text-warning"></i> Paused</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['paused_polls']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-times-circle text-danger"></i> Closed</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['closed_polls']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-dollar-sign text-info"></i> Results for Sale</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['for_sale_polls']); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search polls..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php if ($categories): ?>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="paused" <?php echo $status == 'paused' ? 'selected' : ''; ?>>Paused</option>
                        <option value="closed" <?php echo $status == 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Polls Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-list"></i> Polls List</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($polls && $polls->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Poll Title</th>
                                <th>Category</th>
                                <th>Creator</th>
                                <th>Responses</th>
                                <th>Status</th>
                                <th>For Sale</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($poll = $polls->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $poll['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($poll['title']); ?></strong><br>
                                    <small class="text-muted"><?php echo substr(htmlspecialchars($poll['description'] ?? ''), 0, 60); ?>...</small>
                                </td>
                                <td><span class="badge bg-primary"><?php echo htmlspecialchars($poll['category_name'] ?? 'N/A'); ?></span></td>
                                <td>
                                    <small><?php echo htmlspecialchars(($poll['first_name'] ?? '') . ' ' . ($poll['last_name'] ?? '')); ?></small>
                                </td>
                                <td><span class="badge bg-info"><?php echo number_format($poll['response_count']); ?></span></td>
                                <td>
                                    <?php
                                    $badge_class = 'secondary';
                                    if ($poll['status'] == 'active') $badge_class = 'success';
                                    elseif ($poll['status'] == 'paused') $badge_class = 'warning';
                                    elseif ($poll['status'] == 'closed') $badge_class = 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?>"><?php echo ucfirst($poll['status']); ?></span>
                                </td>
                                <td>
                                    <?php if ($poll['results_for_sale']): ?>
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Yes</span>
                                        <br><small class="text-success">₦<?php echo number_format($poll['results_sale_price'] ?? 0, 2); ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?php echo date('M d, Y', strtotime($poll['created_at'])); ?></small></td>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>admin/view-poll-result.php?id=<?php echo $poll['id']; ?>" 
                                       class="btn btn-sm btn-primary" 
                                       title="View Results">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                    <?php if ($poll['status'] == 'active'): ?>
                                    <button onclick="pausePoll(<?php echo $poll['id']; ?>)" 
                                            class="btn btn-sm btn-warning" 
                                            title="Pause Poll">
                                        <i class="fas fa-pause"></i>
                                    </button>
                                    <?php elseif ($poll['status'] == 'paused'): ?>
                                    <button onclick="activatePoll(<?php echo $poll['id']; ?>)" 
                                            class="btn btn-sm btn-success" 
                                            title="Activate Poll">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <nav>
                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                            <?php if ($page_num > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page_num - 1; ?>&category=<?php echo $category; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start = max(1, $page_num - 5);
                            $end = min($total_pages, $page_num + 4);
                            for ($i = $start; $i <= $end; $i++): 
                            ?>
                                <li class="page-item <?php echo $i === $page_num ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo $category; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page_num < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page_num + 1; ?>&category=<?php echo $category; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="card-body text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h4>No Polls Found</h4>
                    <p class="text-muted">No polls match your current filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function pausePoll(pollId) {
    if (!confirm('Pause this poll?')) return;
    
    fetch('<?php echo SITE_URL; ?>actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=admin_pause_poll&poll_id=' + pollId
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error pausing poll');
        }
    });
}

function activatePoll(pollId) {
    if (!confirm('Activate this poll?')) return;
    
    fetch('<?php echo SITE_URL; ?>actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=admin_activate_poll&poll_id=' + pollId
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error activating poll');
        }
    });
}
</script>

<?php include_once '../footer.php'; ?>
