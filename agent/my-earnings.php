<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('agent');

$user = getCurrentUser();
$page_title = "My Earnings";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$type_filter = isset($_GET['type']) ? sanitize($_GET['type']) : 'all';

// Build query
$where_clauses = ["agent_id = " . $user['id']];

if ($status_filter !== 'all') {
    $where_clauses[] = "status = '$status_filter'";
}

if ($type_filter !== 'all') {
    $where_clauses[] = "earning_type = '$type_filter'";
}

$where_sql = implode(' AND ', $where_clauses);

// Get total count
$count_result = $conn->query("SELECT COUNT(*) as total FROM agent_earnings WHERE $where_sql");
$total_earnings_count = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_earnings_count / $limit);

// Get earnings
$query = "SELECT ae.*, p.title as poll_title 
          FROM agent_earnings ae
          LEFT JOIN polls p ON ae.poll_id = p.id
          WHERE $where_sql 
          ORDER BY ae.created_at DESC 
          LIMIT $limit OFFSET $offset";
$earnings = $conn->query($query);

// Get statistics from users table
$user_stats = $conn->query("SELECT total_earnings, pending_earnings, paid_earnings 
                            FROM users WHERE id = {$user['id']}")->fetch_assoc();

// Get breakdown by status
$breakdown = $conn->query("SELECT 
                          status,
                          COUNT(*) as count,
                          SUM(amount) as total
                          FROM agent_earnings 
                          WHERE agent_id = {$user['id']}
                          GROUP BY status")->fetch_all(MYSQLI_ASSOC);

$stats_by_status = [];
foreach ($breakdown as $row) {
    $stats_by_status[$row['status']] = $row;
}

include_once '../header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-money-bill-wave text-success"></i> My Earnings</h2>
            <p class="text-muted">Track your earnings from poll responses and referrals</p>
        </div>
    </div>

    <!-- Earnings Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-gradient" style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);">
                <div class="card-body text-white">
                    <h6 class="mb-1"><i class="fas fa-coins"></i> Total Earnings</h6>
                    <h2 class="mb-0">₦<?php echo number_format($user_stats['total_earnings'] ?? 0, 2); ?></h2>
                    <small>All time earnings</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-clock text-warning"></i> Pending</h6>
                    <h3 class="mb-0 text-warning">₦<?php echo number_format($user_stats['pending_earnings'] ?? 0, 2); ?></h3>
                    <small class="text-muted">Awaiting approval</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-check-circle text-success"></i> Paid Out</h6>
                    <h3 class="mb-0 text-success">₦<?php echo number_format($user_stats['paid_earnings'] ?? 0, 2); ?></h3>
                    <small class="text-muted">Successfully withdrawn</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h6 class="text-muted mb-2">Pending</h6>
                            <h4 class="text-warning">
                                ₦<?php echo number_format($stats_by_status['pending']['total'] ?? 0, 2); ?>
                            </h4>
                            <small class="text-muted"><?php echo $stats_by_status['pending']['count'] ?? 0; ?> transactions</small>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted mb-2">Approved</h6>
                            <h4 class="text-info">
                                ₦<?php echo number_format($stats_by_status['approved']['total'] ?? 0, 2); ?>
                            </h4>
                            <small class="text-muted"><?php echo $stats_by_status['approved']['count'] ?? 0; ?> transactions</small>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted mb-2">Paid</h6>
                            <h4 class="text-success">
                                ₦<?php echo number_format($stats_by_status['paid']['total'] ?? 0, 2); ?>
                            </h4>
                            <small class="text-muted"><?php echo $stats_by_status['paid']['count'] ?? 0; ?> transactions</small>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted mb-2">Average/Transaction</h6>
                            <h4 class="text-primary">
                                ₦<?php echo $total_earnings_count > 0 ? number_format(($user_stats['total_earnings'] ?? 0) / $total_earnings_count, 2) : '0.00'; ?>
                            </h4>
                            <small class="text-muted">Per earning</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="poll_response" <?php echo $type_filter === 'poll_response' ? 'selected' : ''; ?>>Poll Response</option>
                        <option value="poll_share" <?php echo $type_filter === 'poll_share' ? 'selected' : ''; ?>>Poll Share</option>
                        <option value="referral" <?php echo $type_filter === 'referral' ? 'selected' : ''; ?>>Referral</option>
                        <option value="bonus" <?php echo $type_filter === 'bonus' ? 'selected' : ''; ?>>Bonus</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="my-earnings.php" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Earnings Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Earnings History (<?php echo number_format($total_earnings_count); ?> total)</h5>
                <a href="<?php echo SITE_URL; ?>agent/request-payout.php" class="btn btn-success btn-sm">
                    <i class="fas fa-hand-holding-usd"></i> Request Payout
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if ($earnings && $earnings->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Poll</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($earn = $earnings->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo date('M d, Y', strtotime($earn['created_at'])); ?><br>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($earn['created_at'])); ?></small>
                            </td>
                            <td>
                                <?php
                                $type_badges = [
                                    'poll_response' => '<span class="badge bg-primary">Response</span>',
                                    'poll_share' => '<span class="badge bg-info">Share</span>',
                                    'referral' => '<span class="badge bg-success">Referral</span>',
                                    'bonus' => '<span class="badge bg-warning">Bonus</span>',
                                    'other' => '<span class="badge bg-secondary">Other</span>'
                                ];
                                echo $type_badges[$earn['earning_type']] ?? $earn['earning_type'];
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($earn['description']); ?></td>
                            <td>
                                <?php if ($earn['poll_title']): ?>
                                    <a href="<?php echo SITE_URL; ?>view-poll/<?php echo $earn['poll_id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars(substr($earn['poll_title'], 0, 30)); ?>...
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold text-success">₦<?php echo number_format($earn['amount'], 2); ?></td>
                            <td>
                                <?php
                                $status_badges = [
                                    'pending' => 'warning',
                                    'approved' => 'info',
                                    'paid' => 'success',
                                    'cancelled' => 'danger'
                                ];
                                $badge_class = $status_badges[$earn['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $badge_class; ?>">
                                    <?php echo ucfirst($earn['status']); ?>
                                </span>
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
                    <ul class="pagination justify-content-center mb-0">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                <p class="text-muted">No earnings yet</p>
                <p>Start responding to polls to earn money!</p>
                <a href="<?php echo SITE_URL; ?>polls.php" class="btn btn-primary">
                    <i class="fas fa-poll-h"></i> Browse Polls
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info Card -->
    <div class="card border-0 shadow-sm bg-light mt-4">
        <div class="card-body">
            <h5><i class="fas fa-info-circle text-primary"></i> How Earnings Work</h5>
            <ul class="mb-0">
                <li><strong>Poll Response:</strong> Earn ₦1,000 for each poll you complete</li>
                <li><strong>Pending:</strong> Earnings awaiting admin approval</li>
                <li><strong>Approved:</strong> Earnings verified and ready for payout</li>
                <li><strong>Paid:</strong> Successfully withdrawn to your account</li>
                <li><strong>Minimum Payout:</strong> ₦5,000 (Request payout when you reach this amount)</li>
                <li><strong>Payment Method:</strong> Cash, Airtime, or Data (set in your profile)</li>
            </ul>
        </div>
    </div>
</div>

<?php include_once '../footer.php'; ?>
