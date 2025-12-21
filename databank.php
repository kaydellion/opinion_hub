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

// Build WHERE clause - ONLY show polls marked for sale
$where = ["p.results_for_sale = 1", "p.status = 'active'"];
if ($category > 0) $where[] = "p.category_id = $category";
if ($search) $where[] = "p.title LIKE '%$search%'";
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
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search polls..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-5">
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
                <div class="col-md-2">
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
                                <a href="<?php echo SITE_URL; ?>view-purchased-result.php?id=<?php echo $poll['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            <?php elseif ($current_user): ?>
                                <button onclick="purchasePollResults(<?php echo $poll['id']; ?>, <?php echo $poll['results_sale_price'] ?? 0; ?>, '<?php echo htmlspecialchars(addslashes($poll['title'])); ?>')" 
                                        class="btn btn-primary">
                                    <i class="fas fa-shopping-cart"></i> Purchase
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
                        <a class="page-link" href="?page=<?php echo $page_num - 1; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                    </li>
                <?php endif; ?>
                
                <?php
                $start = max(1, $page_num - 5);
                $end = min($total_pages, $page_num + 4);
                for ($i = $start; $i <= $end; $i++): 
                ?>
                    <li class="page-item <?php echo $i === $page_num ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page_num < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page_num + 1; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>">Next</a>
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
</div>

<?php if ($current_user): ?>
<script>
function purchasePollResults(pollId, amount, title) {
    if (!confirm('Purchase "' + title + '" for ₦' + amount.toLocaleString() + '?')) {
        return;
    }
    
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
            window.location.href = '<?php echo SITE_URL; ?>vpay-callback.php?reference=' + reference + '&type=databank_access&poll_id=' + pollId + '&amount=' + amount;
        },
        onExit: function(response) {
            console.log('Payment cancelled');
        }
    };
    
    try {
        const { open, exit } = VPayDropin.create(options);
        open();
    } catch (error) {
        console.error('VPayDropin error:', error);
        alert('Payment initialization failed: ' + error.message);
    }
}
</script>
<?php endif; ?>

<?php include_once 'footer.php'; ?>
