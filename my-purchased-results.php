<?php
$page_title = "My Purchased Results";
include_once 'header.php';

if (!isLoggedIn()) {
    $_SESSION['error'] = "Please login to view your purchased results.";
    header('Location: ' . SITE_URL . 'signin.php');
    exit;
}

global $conn;
$current_user = getCurrentUser();

// Pagination
$page_num = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page_num - 1) * $limit;

// Get user's purchased results
$query = "SELECT p.*, c.name as category_name, u.first_name, u.last_name,
          pra.purchased_at, pra.amount_paid,
          (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as response_count
          FROM poll_results_access pra
          INNER JOIN polls p ON pra.poll_id = p.id
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN users u ON p.created_by = u.id
          WHERE pra.user_id = {$current_user['id']}
          ORDER BY pra.purchased_at DESC
          LIMIT $limit OFFSET $offset";
$purchases = $conn->query($query);

// Get total count and spending
$stats_result = $conn->query("SELECT COUNT(*) as total, SUM(amount_paid) as total_spent 
                              FROM poll_results_access 
                              WHERE user_id = {$current_user['id']}");
$stats = $stats_result ? $stats_result->fetch_assoc() : ['total' => 0, 'total_spent' => 0];

$total_count = $stats['total'] ?? 0;
$total_spent = $stats['total_spent'] ?? 0;
$total_pages = $total_count > 0 ? ceil($total_count / $limit) : 0;

?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-folder-open text-success"></i> My Purchased Results</h2>
            <p class="text-muted">Access all your purchased poll results anytime, forever</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?php echo SITE_URL; ?>databank.php" class="btn btn-primary">
                <i class="fas fa-shopping-cart"></i> Purchase More
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-database text-primary"></i> Total Purchased</h6>
                    <h2 class="mb-0"><?php echo number_format($total_count); ?></h2>
                    <small class="text-muted">Poll results owned</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-money-bill-wave text-success"></i> Total Spent</h6>
                    <h2 class="mb-0">₦<?php echo number_format($total_spent, 2); ?></h2>
                    <small class="text-muted">On databank purchases</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchased Results List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-list"></i> Your Purchased Poll Results</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($purchases && $purchases->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Poll Title</th>
                                <th>Category</th>
                                <th>Creator</th>
                                <th>Responses</th>
                                <th>Purchased</th>
                                <th>Amount Paid</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($purchase = $purchases->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($purchase['title']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($purchase['category_name']); ?></span>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($purchase['first_name'] . ' ' . $purchase['last_name']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo number_format($purchase['response_count']); ?></span>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($purchase['purchased_at'])); ?><br>
                                    <small class="text-muted"><?php echo date('h:i A', strtotime($purchase['purchased_at'])); ?></small>
                                </td>
                                <td>
                                    <strong class="text-success">₦<?php echo number_format($purchase['amount_paid'], 2); ?></strong>
                                </td>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>view-purchased-result.php?id=<?php echo $purchase['id']; ?>" class="btn btn-sm btn-primary me-1">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>view-purchased-result.php?id=<?php echo $purchase['id']; ?>&export=pdf" class="btn btn-sm btn-success" target="_blank">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>
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
                                    <a class="page-link" href="?page=<?php echo $page_num - 1; ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start = max(1, $page_num - 5);
                            $end = min($total_pages, $page_num + 4);
                            
                            for ($i = $start; $i <= $end; $i++): 
                            ?>
                                <li class="page-item <?php echo $i === $page_num ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page_num < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page_num + 1; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="card-body text-center py-5">
                    <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                    <h4>No Purchased Results Yet</h4>
                    <p class="text-muted mb-4">You haven't purchased any poll results from the databank yet.</p>
                    <a href="<?php echo SITE_URL; ?>databank.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i> Browse Databank
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info Card -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> About Your Purchased Results</h5>
                <ul class="mb-0">
                    <li><strong>Lifetime Access:</strong> Once purchased, you have permanent access to the results</li>
                    <li><strong>PDF Export:</strong> Download results as PDF for offline viewing and sharing</li>
                    <li><strong>Always Updated:</strong> Results reflect the latest data from the poll</li>
                    <li><strong>Detailed Insights:</strong> Full analytics including charts, breakdowns, and demographics</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
