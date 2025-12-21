<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');
$page_title = "All Transactions";

// Filters
$type_filter = $_GET['type'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where_clauses = [];

if ($type_filter !== 'all') {
    $where_clauses[] = "type = '$type_filter'";
}

if ($status_filter !== 'all') {
    $where_clauses[] = "status = '$status_filter'";
}

if ($date_from) {
    $where_clauses[] = "DATE(created_at) >= '$date_from'";
}

if ($date_to) {
    $where_clauses[] = "DATE(created_at) <= '$date_to'";
}

if ($search) {
    $where_clauses[] = "(reference LIKE '%$search%' OR user_id IN (SELECT id FROM users WHERE email LIKE '%$search%' OR first_name LIKE '%$search%' OR last_name LIKE '%$search%'))";
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get total count
$count_result = $conn->query("SELECT COUNT(*) as total FROM transactions $where_sql");
$total_transactions = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_transactions / $limit);

// Get transactions
$query = "SELECT t.*, u.first_name, u.last_name, u.email 
          FROM transactions t
          LEFT JOIN users u ON t.user_id = u.id
          $where_sql
          ORDER BY t.created_at DESC
          LIMIT $limit OFFSET $offset";
$transactions = $conn->query($query);

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total_count,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status = 'completed' THEN admin_commission ELSE 0 END) as total_profit,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
                FROM transactions
                $where_sql";
$stats = $conn->query($stats_query)->fetch_assoc();

include_once '../header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-receipt"></i> All Transactions</h2>
            <p class="text-muted">View and manage all payment transactions</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total Revenue</h6>
                    <h3 class="mb-0 text-success">₦<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Platform Profit</h6>
                    <h3 class="mb-0 text-primary">₦<?php echo number_format($stats['total_profit'] ?? 0, 2); ?></h3>
                    <small class="text-muted">From commissions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total Transactions</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['total_count'] ?? 0); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Pending / Failed</h6>
                    <h3 class="mb-0">
                        <span class="text-warning"><?php echo $stats['pending_count'] ?? 0; ?></span> /
                        <span class="text-danger"><?php echo $stats['failed_count'] ?? 0; ?></span>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="all">All Types</option>
                        <option value="poll_payment" <?php echo $type_filter === 'poll_payment' ? 'selected' : ''; ?>>Poll Payment</option>
                        <option value="sms_credits" <?php echo $type_filter === 'sms_credits' ? 'selected' : ''; ?>>SMS Credits</option>
                        <option value="subscription" <?php echo $type_filter === 'subscription' ? 'selected' : ''; ?>>Subscription</option>
                        <option value="advertisement" <?php echo $type_filter === 'advertisement' ? 'selected' : ''; ?>>Advertisement</option>
                        <option value="databank" <?php echo $type_filter === 'databank' ? 'selected' : ''; ?>>Databank</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all">All Status</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Reference, email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <?php if ($transactions && $transactions->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Commission</th>
                            <th>Status</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($txn = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <small><?php echo date('M d, Y', strtotime($txn['created_at'])); ?></small><br>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($txn['created_at'])); ?></small>
                            </td>
                            <td>
                                <small class="font-monospace"><?php echo htmlspecialchars(substr($txn['reference'], 0, 20)); ?>...</small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($txn['first_name'] . ' ' . $txn['last_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($txn['email']); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo ucwords(str_replace('_', ' ', $txn['type'])); ?></span>
                                <?php if ($txn['poll_id']): ?>
                                    <br><small class="text-muted">Poll #<?php echo $txn['poll_id']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><strong>₦<?php echo number_format($txn['amount'], 2); ?></strong></td>
                            <td>
                                <?php if ($txn['admin_commission'] > 0): ?>
                                    <span class="text-success">₦<?php echo number_format($txn['admin_commission'], 2); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badge_class = [
                                    'completed' => 'bg-success',
                                    'pending' => 'bg-warning',
                                    'failed' => 'bg-danger'
                                ][$txn['status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo ucfirst($txn['status']); ?>
                                </span>
                            </td>
                            <td><?php echo strtoupper($txn['payment_method'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <p class="text-muted mb-0">
                    Showing <?php echo (($page - 1) * $limit) + 1; ?> to <?php echo min($page * $limit, $total_transactions); ?> of <?php echo number_format($total_transactions); ?>
                </p>
                <nav>
                    <ul class="pagination mb-0">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                <p class="text-muted">No transactions found</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../footer.php'; ?>
