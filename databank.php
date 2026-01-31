<?php
$page_title = "Databank - Purchase Poll Results";
include_once 'header.php';

global $conn;
$current_user = isLoggedIn() ? getCurrentUser() : null;

// Pagination
$page_num = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page_num - 1) * $limit;

// Filters
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$location = isset($_GET['location']) ? sanitize($_GET['location']) : '';

// Build WHERE clause - ONLY show polls marked for sale
$where = ["p.results_for_sale = 1", "p.status = 'active'"];
if ($category > 0) $where[] = "p.category_id = $category";
if ($search) $where[] = "p.title LIKE '%$search%'";
if (!empty($location)) $where[] = "(p.agent_state_criteria = '$location' OR p.agent_location_all = 1)";
$where_clause = implode(' AND ', $where);

// Get polls for sale with access check
$query = "SELECT p.*, c.name as category_name, u.first_name, u.last_name,
          (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as response_count";

if ($current_user) {
    $query .= ", (SELECT id FROM poll_results_access WHERE user_id = {$current_user['id']} AND poll_id = p.id) as has_access";
}

$query .= " FROM polls p
           LEFT JOIN categories c ON p.category_id = c.id
           LEFT JOIN users u ON p.created_by = u.id
           WHERE $where_clause
           ORDER BY response_count DESC, p.created_at DESC
           LIMIT $limit OFFSET $offset";

$polls = $conn->query($query);

// Get total count
$total_result = $conn->query("SELECT COUNT(*) as count FROM polls p WHERE $where_clause");
$total = $total_result ? $total_result->fetch_assoc()['count'] : 0;
$total_pages = $total > 0 ? ceil($total / $limit) : 0;

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Get stats
$stats_query = $conn->query("SELECT 
    COUNT(*) as total_polls,
    AVG(results_sale_price) as avg_price
    FROM polls WHERE results_for_sale = 1 AND status = 'active'");
$stats = $stats_query ? $stats_query->fetch_assoc() : ['total_polls' => 0, 'avg_price' => 0];

$responses_query = $conn->query("SELECT SUM((SELECT COUNT(*) FROM poll_responses WHERE poll_id = polls.id)) as total_responses
                                 FROM polls WHERE results_for_sale = 1 AND status = 'active'");
$stats['total_responses'] = $responses_query ? ($responses_query->fetch_assoc()['total_responses'] ?? 0) : 0;

// Get latest 6 blog articles
$latest_articles = $conn->query("SELECT bp.*, CONCAT(u.first_name, ' ', u.last_name) as author_name,
                                 (SELECT COUNT(*) FROM blog_likes WHERE post_id = bp.id) as like_count,
                                 (SELECT COUNT(*) FROM blog_comments WHERE post_id = bp.id) as comment_count
                                 FROM blog_posts bp
                                 JOIN users u ON bp.user_id = u.id
                                 WHERE bp.status = 'approved'
                                 ORDER BY bp.created_at DESC
                                 LIMIT 6");
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-database text-primary"></i> Poll Results Databank</h1>
            <p class="text-muted">Purchase one-time access to comprehensive poll results</p>
        </div>
        <div class="col-md-4 text-end">
            <?php if ($current_user): ?>
                <a href="<?php echo SITE_URL; ?>my-purchased-results.php" class="btn btn-success">
                    <i class="fas fa-folder-open"></i> My Purchases
                </a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>signin.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-poll text-primary"></i> Available</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['total_polls'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-users text-success"></i> Responses</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['total_responses'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-money-bill-wave text-warning"></i> Avg Price</h6>
                    <h2 class="mb-0">₦<?php echo number_format($stats['avg_price'] ?? 0, 2); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
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
                    <select class="form-select" name="location">
                        <option value="">All Locations</option>
                        <?php
                        $nigerian_states = [
                            'Abia',
                            'Adamawa',
                            'Akwa Ibom',
                            'Anambra',
                            'Bauchi',
                            'Bayelsa',
                            'Benue',
                            'Borno',
                            'Cross River',
                            'Delta',
                            'Ebonyi',
                            'Edo',
                            'Ekiti',
                            'Enugu',
                            'FCT',
                            'Gombe',
                            'Imo',
                            'Jigawa',
                            'Kaduna',
                            'Kano',
                            'Katsina',
                            'Kebbi',
                            'Kogi',
                            'Kwara',
                            'Lagos',
                            'Nasarawa',
                            'Niger',
                            'Ogun',
                            'Ondo',
                            'Osun',
                            'Oyo',
                            'Plateau',
                            'Rivers',
                            'Sokoto',
                            'Taraba',
                            'Yobe',
                            'Zamfara'
                        ];
                        foreach ($nigerian_states as $state): ?>
                            <option value="<?php echo $state; ?>" <?php echo $location === $state ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($state); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Polls Grid -->
    <?php if ($polls && $polls->num_rows > 0): ?>
        <div class="row">
            <?php while ($poll = $polls->fetch_assoc()):
                $has_access = isset($poll['has_access']) && $poll['has_access'];
            ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="mb-3">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($poll['category_name'] ?? 'Uncategorized'); ?></span>
                                <?php if ($has_access): ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle"></i> Owned</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-lock"></i> For Sale</span>
                                <?php endif; ?>
                            </div>

                            <h5 class="card-title"><?php echo htmlspecialchars($poll['title']); ?></h5>
                            <p class="card-text text-muted flex-grow-1">
                                <?php echo substr(htmlspecialchars($poll['description'] ?? ''), 0, 100); ?>...
                            </p>

                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars(($poll['first_name'] ?? '') . ' ' . ($poll['last_name'] ?? '')); ?>
                                </small><br>
                                <small class="text-muted">
                                    <i class="fas fa-users"></i> <?php echo number_format($poll['response_count'] ?? 0); ?> responses
                                </small>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="mb-0 text-success">₦<?php echo number_format($poll['results_sale_price'] ?? 0, 2); ?></h4>

                                <?php if ($has_access): ?>
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo SITE_URL; ?>view-purchased-result.php?id=<?php echo $poll['id']; ?>&format=combined" class="btn btn-success btn-sm">
                                            <i class="fas fa-chart-line"></i> Combined
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>view-purchased-result.php?id=<?php echo $poll['id']; ?>&format=single" class="btn btn-info btn-sm">
                                            <i class="fas fa-users"></i> Single
                                        </a>
                                    </div>
                                <?php elseif ($current_user): ?>
                                    <button onclick="purchaseDataset(<?php echo $poll['id']; ?>, <?php echo $poll['results_sale_price'] ?? 0; ?>, '<?php echo htmlspecialchars(addslashes($poll['title'])); ?>')"
                                        class="btn btn-primary">
                                        <i class="fas fa-shopping-cart"></i> Purchase Dataset
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo SITE_URL; ?>signin.php" class="btn btn-outline-primary">
                                        Sign In
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page_num > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page_num - 1; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>&location=<?php echo urlencode($location); ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page_num - 5);
                    $end = min($total_pages, $page_num + 4);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <li class="page-item <?php echo $i === $page_num ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>&location=<?php echo urlencode($location); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page_num < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page_num + 1; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>&location=<?php echo urlencode($location); ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info text-center py-5">
            <i class="fas fa-info-circle fa-3x mb-3"></i>
            <h4>No Poll Results for Sale</h4>
            <p class="mb-0">No poll results are available for purchase at the moment.</p>
        </div>
    <?php endif; ?>

    <!-- How It Works -->
    <div class="row mt-5">
        <div class="col-md-12">
            <div class="card border-0 bg-light">
                <div class="card-body p-4">
                    <h4 class="mb-4"><i class="fas fa-question-circle"></i> How It Works</h4>
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <i class="fas fa-search fa-3x text-primary mb-3"></i>
                            <h5>1. Browse</h5>
                            <p class="text-muted">Find poll results</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <i class="fas fa-shopping-cart fa-3x text-success mb-3"></i>
                            <h5>2. Purchase</h5>
                            <p class="text-muted">One-time payment</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <i class="fas fa-chart-bar fa-3x text-warning mb-3"></i>
                            <h5>3. Access Forever</h5>
                            <p class="text-muted">Lifetime access</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i>
                            <h5>4. Export PDF</h5>
                            <p class="text-muted">Download anytime</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Blog Articles -->
    <?php if ($latest_articles && $latest_articles->num_rows > 0): ?>
        <div class="row mt-5">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-blog me-2"></i> Latest Blog Articles</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php while ($article = $latest_articles->fetch_assoc()): ?>
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card h-100 border-0 shadow-sm hover-shadow">
                                        <div class="card-body">
                                            <img src="<?= SITE_URL ?>uploads/blog/<?= htmlspecialchars($article['featured_image']) ?>"
                                                class="card-img-top"
                                                alt="<?= htmlspecialchars($article['title']) ?>"
                                                style="height: 220px; object-fit: cover;"
                                                onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIyMCIgdmlld0JveD0iMCAwIDQwMCAyMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSI0MDAiIGhlaWdodD0iMjIwIiBmaWxsPSIjNjY3Ii8+CjxwYXRoIGQ9Ik0xNTAgNzBWMTUwSDI1MFY3MEgxNTBaIiBmaWxsPSIjOTk5Ii8+CjxwYXRoIGQ9Ik0xODAgMTAwSDIyMFYxMjBIMTgwVjEwMFoiIGZpbGw9IiNDQ0MiLz4+Cjwvc3ZnPg=='">
                                            <h6 class="card-title">
                                                <a href="<?php echo SITE_URL; ?>blog/view.php?slug=<?php echo urlencode($article['slug']); ?>"
                                                    class="text-decoration-none text-dark">
                                                    <?php echo htmlspecialchars(substr($article['title'], 0, 60)); ?>
                                                    <?php if (strlen($article['title']) > 60) echo '...'; ?>
                                                </a>
                                            </h6>
                                            <p class="card-text text-muted small mb-2">
                                                <?php echo htmlspecialchars(substr(strip_tags($article['content']), 0, 100)); ?>...
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center text-muted small">
                                                <span><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($article['author_name']); ?></span>
                                                <span><i class="fas fa-calendar me-1"></i> <?php echo date('M d, Y', strtotime($article['created_at'])); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <div class="text-muted small">
                                                    <i class="fas fa-heart text-danger me-1"></i> <?php echo $article['like_count']; ?>
                                                    <i class="fas fa-comment text-primary ms-2 me-1"></i> <?php echo $article['comment_count']; ?>
                                                </div>
                                                <a href="<?php echo SITE_URL; ?>blog/view.php?slug=<?php echo urlencode($article['slug']); ?>"
                                                    class="btn btn-outline-primary btn-sm">
                                                    Read More <i class="fas fa-arrow-right ms-1"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo SITE_URL; ?>blog.php" class="btn btn-primary">
                                <i class="fas fa-blog me-2"></i> View All Articles
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Dataset Format Selection Modal -->
<div class="modal fade" id="formatSelectionModal" tabindex="-1" aria-labelledby="formatSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="purchaseModalLabel">Purchase Dataset Access</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <h6>Confirm Your Purchase</h6>
                </div>

                <div class="alert alert-success">
                    <h6 class="alert-heading"><i class="fas fa-check-circle me-2"></i>What You Get:</h6>
                    <ul class="mb-0">
                        <li><strong>COMBINED Format:</strong> Aggregated responses with trend analysis and patterns</li>
                        <li><strong>SINGLE Format:</strong> Individual responses from each participant</li>
                        <li><strong>Charts & Analytics:</strong> Pie charts, bar charts, trend analysis (Daily/Weekly/Monthly/Annually)</li>
                        <li><strong>Export Options:</strong> PDF, CSV, Excel formats</li>
                        <li><strong>Lifetime Access:</strong> Access to both formats forever</li>
                    </ul>
                </div>

                <div class="text-center">
                    <h5>Poll: <span id="confirmTitle"></span></h5>
                    <h4 class="text-primary">Price: ₦<span id="confirmAmount"></span></h4>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmPurchaseBtn" onclick="confirmPurchase()">
                    <i class="fas fa-credit-card me-2"></i>Proceed to Payment
                </button>
            </div>
        </div>
    </div>
</div>
</div>

<?php if ($current_user): ?>
    <script>
        // Global variables for format selection
        let selectedPollId = 0;
        let selectedAmount = 0;
        let selectedTitle = '';

        function purchaseDataset(pollId, amount, title) {
            selectedPollId = pollId;
            selectedAmount = amount;
            selectedTitle = title;

            // Set modal content
            document.getElementById('confirmTitle').textContent = title;
            document.getElementById('confirmAmount').textContent = amount.toLocaleString();

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('formatSelectionModal'));
            modal.show();
        }

        function confirmPurchase() {
            if (!confirm('Purchase "' + selectedTitle + '" for ₦' + selectedAmount.toLocaleString() + '?\n\nYou will get access to BOTH COMBINED and SINGLE formats with charts and analytics.')) {
                return;
            }

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('formatSelectionModal'));
            modal.hide();

            // Proceed with payment (no format parameter - grants access to both)
            proceedToPayment(selectedPollId, selectedAmount, selectedTitle);
        }

        function proceedToPayment(pollId, amount, title) {
            const reference = 'DATABANK_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9).toUpperCase();

            // Check if VPayDropin is loaded
            if (!window.VPayDropin) {
                console.error('VPayDropin not found. Current window properties:', Object.keys(window).filter(k => k.toLowerCase().includes('vpay')));
                alert('Payment system not loaded. Please refresh the page and try again.');
                return;
            }

            const options = {
                amount: amount,
                currency: 'NGN',
                domain: window.VPAY_CONFIG.domain,
                key: window.VPAY_CONFIG.key,
                email: '<?php echo $current_user["email"] ?? ""; ?>',
                transactionref: reference,
                customer_logo: window.VPAY_CONFIG.customerLogo,
                customer_service_channel: window.VPAY_CONFIG.customerService,
                txn_charge: 0,
                txn_charge_type: 'flat',
                onSuccess: function(response) {
                    console.log('Payment successful:', response);
                    // Remove format parameter - grants access to both formats
                    window.location.href = '<?php echo SITE_URL; ?>vpay-callback.php?reference=' + reference + '&type=databank_access&poll_id=' + pollId + '&amount=' + amount;
                },
                onExit: function(response) {
                    console.log('Payment cancelled');
                }
            };

            try {
                const {
                    open,
                    exit
                } = VPayDropin.create(options);
                open();
            } catch (error) {
                console.error('VPayDropin error:', error);
                alert('Payment initialization failed: ' + error.message);
            }
        }

        // Legacy function for backward compatibility
        function purchasePollResults(pollId, amount, title) {
            showFormatSelection(pollId, amount, title);
        }
    </script>
<?php endif; ?>

<?php include_once 'footer.php'; ?>