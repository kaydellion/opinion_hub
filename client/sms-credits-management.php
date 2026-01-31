<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('client');

$user = getCurrentUser();
$page_title = "SMS Credits Management";

// Get current credits balance (prefer messaging_credits table; fallback to users.sms_credits)
$sms_credits = 0;
$mc = $conn->query("SELECT sms_balance FROM messaging_credits WHERE user_id = " . intval($user['id']));
if ($mc && $mc->num_rows > 0) {
    $sms_credits = intval($mc->fetch_assoc()['sms_balance'] ?? 0);
} else {
    $credits_query = "SELECT sms_credits FROM users WHERE id = " . intval($user['id']);
    $credits_result = $conn->query($credits_query);
    if ($credits_result && $credits_result->num_rows > 0) {
        $sms_credits = intval($credits_result->fetch_assoc()['sms_credits'] ?? 0);
    }
}

// Get usage statistics
$stats_query = "SELECT 
                COUNT(*) as total_sent,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(credits_used) as total_used,
                DATE(MIN(created_at)) as first_sent,
                DATE(MAX(created_at)) as last_sent
                FROM message_logs 
                WHERE user_id = {$user['id']} AND message_type = 'sms'";
$stats_result = $conn->query($stats_query);
$stats = [
    'total_sent' => 0,
    'delivered' => 0,
    'failed' => 0,
    'total_used' => 0,
    'first_sent' => null,
    'last_sent' => null
];
if ($stats_result instanceof mysqli_result) {
    $stats = $stats_result->fetch_assoc();
} else {
    error_log('Failed to load SMS stats: ' . $conn->error);
}

// Pagination for transactions
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 50;
$offset = ($page - 1) * $page_size;

// Get recent transactions with pagination
$transactions_query = "SELECT SQL_CALC_FOUND_ROWS * FROM transactions 
                      WHERE user_id = {$user['id']} 
                      AND type LIKE '%sms%'
                      ORDER BY created_at DESC 
                      LIMIT $offset, $page_size";
$transactions = $conn->query($transactions_query);
$total_rows = $conn->query("SELECT FOUND_ROWS() as total")->fetch_assoc()['total'] ?? 0;
$total_pages = $total_rows > 0 ? ceil($total_rows / $page_size) : 1;

// Get recent usage (last 30 days)
$usage_query = "SELECT DATE(created_at) as date, 
                COUNT(*) as messages, 
                SUM(credits_used) as credits
                FROM message_logs 
                WHERE user_id = {$user['id']} 
                AND message_type = 'sms'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
$usage_history = $conn->query($usage_query);

include_once '../header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-sms"></i> SMS Credits Management</h2>
            <p class="text-muted">Manage your SMS credits balance and track usage</p>
        </div>
    </div>

    <!-- Balance & Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-gradient" style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);">
                <div class="card-body text-white">
                    <h6 class="mb-1"><i class="fas fa-coins"></i> Current Balance</h6>
                    <h2 class="mb-0"><?php echo number_format($sms_credits); ?></h2>
                    <small>SMS Credits Available</small>
                    <div class="mt-3">
                        <a href="buy-credits.php" class="btn btn-light btn-sm">
                            <i class="fas fa-plus-circle"></i> Buy More
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Sent</h6>
                    <h3 class="mb-0 text-primary"><?php echo number_format($stats['total_sent'] ?? 0); ?></h3>
                    <small class="text-muted">All time messages</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Credits Used</h6>
                    <h3 class="mb-0 text-info"><?php echo number_format($stats['total_used'] ?? 0); ?></h3>
                    <small class="text-muted">Total consumed</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Delivery Rate</h6>
                    <h3 class="mb-0 text-success">
                        <?php 
                        $rate = ($stats['total_sent'] ?? 0) > 0 ? round((($stats['delivered'] ?? 0) / $stats['total_sent']) * 100, 1) : 0;
                        echo $rate;
                        ?>%
                    </h3>
                    <small class="text-muted"><?php echo number_format($stats['delivered'] ?? 0); ?> delivered</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="buy-credits.php" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-shopping-cart"></i><br>
                                <span class="small">Buy Credits</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="send-invites.php" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-paper-plane"></i><br>
                                <span class="small">Send SMS</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="sms-delivery-status.php" class="btn btn-info btn-lg w-100">
                                <i class="fas fa-chart-line"></i><br>
                                <span class="small">Delivery Reports</span>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="manage-contacts.php" class="btn btn-outline-primary btn-lg w-100">
                                <i class="fas fa-address-book"></i><br>
                                <span class="small">Manage Contacts</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Usage Chart (Last 30 Days) -->
        <div class="col-md-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Usage History (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <?php if ($usage_history && $usage_history->num_rows > 0): ?>
                    <canvas id="usageChart" height="100"></canvas>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No usage data for the last 30 days</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Credit Packages -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-tags"></i> Credit Packages</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>500 SMS</strong>
                                    <br><small class="text-muted">₦5,000</small>
                                </div>
                                <span class="badge bg-primary">₦10/SMS</span>
                            </div>
                        </div>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>1,000 SMS</strong>
                                    <br><small class="text-muted">₦9,000</small>
                                </div>
                                <span class="badge bg-success">₦9/SMS</span>
                            </div>
                        </div>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>5,000 SMS</strong>
                                    <br><small class="text-muted">₦40,000</small>
                                </div>
                                <span class="badge bg-warning">₦8/SMS</span>
                            </div>
                        </div>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>10,000 SMS</strong>
                                    <br><small class="text-muted">₦70,000</small>
                                </div>
                                <span class="badge bg-danger">₦7/SMS</span>
                            </div>
                        </div>
                    </div>
                    <a href="buy-credits.php" class="btn btn-primary w-100 mt-3">
                        View All Packages
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-receipt"></i> Recent Credit Purchases</h5>
        </div>
        <div class="card-body">
            <?php if ($transactions && $transactions->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($txn = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y h:i A', strtotime($txn['created_at'])); ?></td>
                            <td><code><?php echo htmlspecialchars($txn['reference']); ?></code></td>
                            <td>₦<?php echo number_format($txn['amount'] / 100, 2); ?></td>
                            <td>
                                <?php
                                $badge_class = [
                                    'completed' => 'success',
                                    'pending' => 'warning',
                                    'failed' => 'danger'
                                ][$txn['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $badge_class; ?>">
                                    <?php echo ucfirst($txn['status']); ?>
                                </span>
                            </td>
                            <td><?php echo ucfirst($txn['payment_method'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Transactions pagination">
                <ul class="pagination justify-content-center mt-3">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= max(1, $page-1) ?>">Previous</a>
                    </li>
                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                        <li class="page-item <?= $p == $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a></li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= min($total_pages, $page+1) ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                <p class="text-muted">No credit purchases yet</p>
                <a href="buy-credits.php" class="btn btn-primary">Buy Your First Credits</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tips & Info -->
    <div class="card border-0 shadow-sm bg-light">
        <div class="card-body">
            <h5><i class="fas fa-lightbulb text-warning"></i> Tips for Managing SMS Credits</h5>
            <ul class="mb-0">
                <li><strong>Buy in bulk:</strong> Larger packages offer better rates per SMS</li>
                <li><strong>Monitor delivery:</strong> Check delivery reports to ensure messages reach recipients</li>
                <li><strong>Segment contacts:</strong> Send targeted messages to save credits</li>
                <li><strong>Schedule messages:</strong> Plan campaigns to optimize credit usage</li>
                <li><strong>Track ROI:</strong> Monitor poll responses to measure message effectiveness</li>
            </ul>
        </div>
    </div>
</div>

<?php include_once '../footer.php'; ?>

<?php if ($usage_history && $usage_history->num_rows > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prepare chart data
const usageData = <?php 
$usage_history->data_seek(0);
$dates = [];
$messages = [];
$credits = [];
while ($row = $usage_history->fetch_assoc()) {
    $dates[] = $row['date'];
    $messages[] = $row['messages'];
    $credits[] = $row['credits'];
}
echo json_encode(['dates' => array_reverse($dates), 'messages' => array_reverse($messages), 'credits' => array_reverse($credits)]);
?>;

// Create chart
const ctx = document.getElementById('usageChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: usageData.dates,
        datasets: [{
            label: 'Messages Sent',
            data: usageData.messages,
            borderColor: '#ff6b35',
            backgroundColor: 'rgba(255, 107, 53, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Credits Used',
            data: usageData.credits,
            borderColor: '#f7931e',
            backgroundColor: 'rgba(247, 147, 30, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});
</script>
<?php endif; ?>
