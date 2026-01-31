<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$page_title = "Subscription Clients";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$plan_filter = isset($_GET['plan']) ? sanitize($_GET['plan']) : 'all';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'active';

// Build query
$where_clauses = ["us.plan_id IS NOT NULL"];

if ($plan_filter !== 'all') {
    $where_clauses[] = "sp.type = '$plan_filter'";
}

if ($status_filter === 'active') {
    $where_clauses[] = "us.status = 'active' AND (us.end_date IS NULL OR us.end_date > NOW())";
} elseif ($status_filter === 'expired') {
    $where_clauses[] = "(us.status = 'expired' OR us.end_date < NOW())";
} elseif ($status_filter === 'cancelled') {
    $where_clauses[] = "us.status = 'cancelled'";
}

$where_sql = implode(' AND ', $where_clauses);

// Get total count
$count_result = $conn->query("SELECT COUNT(DISTINCT u.id) as total 
                              FROM users u
                              INNER JOIN user_subscriptions us ON u.id = us.user_id
                              INNER JOIN subscription_plans sp ON us.plan_id = sp.id
                              WHERE $where_sql");
$total_count = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_count / $limit);

// Get subscription clients
$query = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at,
                 sp.name as plan_name, sp.type as plan_type, sp.monthly_price, sp.annual_price,
                 us.start_date, us.end_date, us.status,
                 us.amount_paid,
                 us.start_date as subscribed_at,
                 CASE WHEN us.amount_paid = sp.monthly_price THEN 'monthly' ELSE 'annual' END as billing_cycle,
                 0 as auto_renew,
                 (SELECT COUNT(*) FROM polls WHERE created_by = u.id) as total_polls,
                 (SELECT COUNT(*) FROM poll_responses pr 
                  INNER JOIN polls p ON pr.poll_id = p.id 
                  WHERE p.created_by = u.id) as total_responses
          FROM users u
          INNER JOIN user_subscriptions us ON u.id = us.user_id
          INNER JOIN subscription_plans sp ON us.plan_id = sp.id
          WHERE $where_sql 
          ORDER BY us.start_date DESC 
          LIMIT $limit OFFSET $offset";
$clients = $conn->query($query);

// Get statistics
$stats_result = $conn->query("SELECT 
                      COUNT(DISTINCT us.user_id) as total_subscribers,
                      SUM(us.amount_paid) as total_revenue,
                      SUM(CASE WHEN us.status = 'active' AND (us.end_date IS NULL OR us.end_date > NOW()) THEN us.amount_paid ELSE 0 END) as active_revenue
                      FROM user_subscriptions us
                      WHERE us.plan_id IS NOT NULL");
$stats = $stats_result && $stats_result->num_rows > 0 ? $stats_result->fetch_assoc() : [
    'total_subscribers' => 0,
    'total_revenue' => 0,
    'active_revenue' => 0
];

// Revenue by plan
$revenue_result = $conn->query("SELECT sp.name, sp.type, 
                                 COUNT(us.id) as subscribers,
                                 SUM(us.amount_paid) as revenue
                                 FROM user_subscriptions us
                                 INNER JOIN subscription_plans sp ON us.plan_id = sp.id
                                 WHERE us.status = 'active' 
                                 AND (us.end_date IS NULL OR us.end_date > NOW())
                                 GROUP BY sp.id
                                 ORDER BY revenue DESC");
$revenue_by_plan = $revenue_result && $revenue_result->num_rows > 0 ? $revenue_result->fetch_all(MYSQLI_ASSOC) : [];

include_once '../header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-crown text-warning"></i> Subscription Clients</h2>
            <p class="text-muted">Manage and monitor clients with active subscription plans</p>
        </div>
    </div>

    <!-- Overall Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-users text-primary"></i> Total Subscribers</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['total_subscribers']); ?></h2>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-money-bill-wave text-warning"></i> Total Revenue</h6>
                    <h2 class="mb-0 text-warning">₦<?php echo number_format($stats['total_revenue'], 2); ?></h2>
                    <small class="text-success">₦<?php echo number_format($stats['active_revenue'], 2); ?> active</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue by Plan -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Revenue by Plan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($revenue_by_plan as $plan): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <div class="mb-2">
                                        <span class="badge bg-<?php 
                                            echo $plan['type'] === 'free' ? 'secondary' : 
                                                ($plan['type'] === 'basic' ? 'info' : 
                                                ($plan['type'] === 'classic' ? 'primary' : 'warning')); 
                                        ?> fs-6"><?php echo ucfirst($plan['type']); ?></span>
                                    </div>
                                    <h4 class="text-muted mb-1"><?php echo $plan['subscribers']; ?></h4>
                                    <small class="text-muted">subscribers</small>
                                    <h3 class="text-success mt-2 mb-0">₦<?php echo number_format($plan['revenue'], 2); ?></h3>
                                    <small class="text-muted">revenue</small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Plan Type</label>
                    <select name="plan" class="form-select">
                        <option value="all" <?php echo $plan_filter === 'all' ? 'selected' : ''; ?>>All Plans</option>
                        <option value="basic" <?php echo $plan_filter === 'basic' ? 'selected' : ''; ?>>Basic</option>
                        <option value="classic" <?php echo $plan_filter === 'classic' ? 'selected' : ''; ?>>Classic</option>
                        <option value="enterprise" <?php echo $plan_filter === 'enterprise' ? 'selected' : ''; ?>>Enterprise</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Billing Cycle</label>
                    <select name="billing" class="form-select">
                        <option value="all" <?php echo $billing_filter === 'all' ? 'selected' : ''; ?>>All Billing</option>
                        <option value="monthly" <?php echo $billing_filter === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        <option value="annual" <?php echo $billing_filter === 'annual' ? 'selected' : ''; ?>>Annual</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Clients Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-table"></i> Subscription Clients (<?php echo number_format($total_count); ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Client</th>
                            <th>Plan</th>
                            <th>Billing</th>
                            <th>Amount Paid</th>
                            <th>Subscribed</th>
                            <th>Expires</th>
                            <th>Activity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($clients && $clients->num_rows > 0): ?>
                            <?php while ($client = $clients->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($client['email']); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $client['plan_type'] === 'free' ? 'secondary' : 
                                        ($client['plan_type'] === 'basic' ? 'info' : 
                                        ($client['plan_type'] === 'classic' ? 'primary' : 'warning')); 
                                ?> fs-6">
                                    <?php echo ucfirst($client['plan_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo ($client['billing_cycle'] ?? 'monthly') === 'monthly' ? 'info' : 'success'; ?>">
                                    <?php echo ucfirst($client['billing_cycle'] ?? 'monthly'); ?>
                                </span>
                                <?php if ($client['auto_renew'] ?? false): ?>
                                    <br><small class="text-success"><i class="fas fa-sync-alt"></i> Auto-renew</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>₦<?php echo number_format($client['amount_paid'], 2); ?></strong>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($client['subscribed_at'])); ?><br>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($client['subscribed_at'])); ?></small>
                            </td>
                            <td>
                                <?php if ($client['end_date']): ?>
                                    <?php 
                                    $expires = strtotime($client['end_date']);
                                    $now = time();
                                    $is_expiring_soon = ($expires - $now) < (7 * 24 * 60 * 60); // 7 days
                                    ?>
                                    <span class="<?php echo $is_expiring_soon ? 'text-warning' : 'text-muted'; ?>">
                                        <?php echo date('M d, Y', $expires); ?>
                                    </span>
                                    <?php if ($is_expiring_soon && $client['status'] === 'active'): ?>
                                        <br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Expiring soon</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">No expiry</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small>
                                    <i class="fas fa-poll"></i> <?php echo number_format($client['total_polls']); ?> polls<br>
                                    <i class="fas fa-chart-bar"></i> <?php echo number_format($client['total_responses']); ?> responses
                                </small>
                            </td>
                            <td>
                                <?php
                                if ($client['status'] === 'active' && (!$client['end_date'] || strtotime($client['end_date']) > time())) {
                                    echo '<span class="badge bg-success">Active</span>';
                                } elseif ($client['status'] === 'cancelled') {
                                    echo '<span class="badge bg-danger">Cancelled</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">Expired</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-crown fa-3x mb-3"></i>
                                    <p class="mb-0">No subscription clients found matching your filters.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&plan=<?php echo $plan_filter; ?>&billing=<?php echo $billing_filter; ?>&status=<?php echo $status_filter; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start = max(1, $page - 5);
                        $end = min($total_pages, $page + 4);
                        
                        for ($i = $start; $i <= $end; $i++): 
                        ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&plan=<?php echo $plan_filter; ?>&billing=<?php echo $billing_filter; ?>&status=<?php echo $status_filter; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&plan=<?php echo $plan_filter; ?>&billing=<?php echo $billing_filter; ?>&status=<?php echo $status_filter; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../footer.php'; ?>
