<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$page_title = "SMS Delivery Reports";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Filters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Build query
$where_clauses = ["1=1"];

if ($status_filter !== 'all') {
    $where_clauses[] = "delivery_status = '$status_filter'";
}

if ($user_filter > 0) {
    $where_clauses[] = "user_id = $user_filter";
}

if (!empty($date_from)) {
    $where_clauses[] = "DATE(sent_at) >= '$date_from'";
}

if (!empty($date_to)) {
    $where_clauses[] = "DATE(sent_at) <= '$date_to'";
}

$where_sql = implode(' AND ', $where_clauses);

// Check if message_logs table exists
$table_check = $conn->query("SHOW TABLES LIKE 'message_logs'");
$table_exists = $table_check && $table_check->num_rows > 0;

if (!$table_exists) {
    // Table doesn't exist, set default values
    $total_count = 0;
    $total_pages = 0;
    $messages = null;
    $total_messages = 0;
    $delivered = 0;
    $total_credits = 0;
    $active_users_count = 0;
    $delivery_rate = 0;
    $active_users = null;
    $_SESSION['warning'] = 'Message logs table not found. SMS delivery tracking not yet set up.';
} else {
    // Get total count
    $count_result = $conn->query("SELECT COUNT(*) as total FROM message_logs WHERE $where_sql");
    $total_count = $count_result ? $count_result->fetch_assoc()['total'] : 0;
    $total_pages = ceil($total_count / $limit);

    // Get messages with user info
    $query = "SELECT ml.*, u.first_name, u.last_name, u.email, u.role 
              FROM message_logs ml
              LEFT JOIN users u ON ml.user_id = u.id
              WHERE $where_sql 
              ORDER BY ml.sent_at DESC 
              LIMIT $limit OFFSET $offset";
    $messages = $conn->query($query);

    // Get statistics
    $stats = $conn->query("SELECT 
                          delivery_status,
                          COUNT(*) as count,
                          SUM(credits_used) as total_credits
                          FROM message_logs 
                          GROUP BY delivery_status")->fetch_all(MYSQLI_ASSOC);

    $stats_by_status = [];
    foreach ($stats as $row) {
        $stats_by_status[$row['delivery_status']] = $row;
    }

    // Get overall statistics
    $overall = $conn->query("SELECT 
                            COUNT(*) as total_messages,
                            SUM(credits_used) as total_credits_used,
                            COUNT(DISTINCT user_id) as total_users
                            FROM message_logs")->fetch_assoc();

    // Calculate delivery rate
    $total_messages = $overall['total_messages'];
    $total_credits = $overall['total_credits_used'];
    $active_users_count = $overall['total_users'];
    $delivered = $stats_by_status['delivered']['count'] ?? 0;
    $delivery_rate = $total_messages > 0 ? round(($delivered / $total_messages) * 100, 2) : 0;

    // Get active users list for filter
    $active_users = $conn->query("SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.role
                                  FROM users u
                                  INNER JOIN message_logs ml ON u.id = ml.user_id
                                  ORDER BY u.first_name, u.last_name
                                  LIMIT 100");
}

include_once '../header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-paper-plane text-primary"></i> SMS Delivery Reports</h2>
            <p class="text-muted">Monitor SMS delivery status across all users</p>
        </div>
    </div>

    <!-- Overall Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-paper-plane text-primary"></i> Total Messages</h6>
                    <h2 class="mb-0"><?php echo number_format($total_messages); ?></h2>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-check-circle text-success"></i> Delivered</h6>
                    <h2 class="mb-0 text-success"><?php echo number_format($delivered); ?></h2>
                    <small class="text-muted"><?php echo $delivery_rate; ?>% delivery rate</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-coins text-warning"></i> Credits Used</h6>
                    <h2 class="mb-0 text-warning"><?php echo number_format($total_credits); ?></h2>
                    <small class="text-muted">Total platform usage</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-users text-info"></i> Active Users</h6>
                    <h2 class="mb-0 text-info"><?php echo number_format($active_users_count); ?></h2>
                    <small class="text-muted">Sending messages</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Status Breakdown -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="mb-3">Delivery Status Breakdown</h6>
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h5 class="text-muted mb-1">Pending</h5>
                            <h3 class="text-secondary">
                                <?php echo number_format($stats_by_status['pending']['count'] ?? 0); ?>
                            </h3>
                            <small class="text-muted">
                                <?php echo number_format($stats_by_status['pending']['total_credits'] ?? 0); ?> credits
                            </small>
                        </div>
                        <div class="col-md-3">
                            <h5 class="text-muted mb-1">Sent</h5>
                            <h3 class="text-info">
                                <?php echo number_format($stats_by_status['sent']['count'] ?? 0); ?>
                            </h3>
                            <small class="text-muted">
                                <?php echo number_format($stats_by_status['sent']['total_credits'] ?? 0); ?> credits
                            </small>
                        </div>
                        <div class="col-md-3">
                            <h5 class="text-muted mb-1">Delivered</h5>
                            <h3 class="text-success">
                                <?php echo number_format($stats_by_status['delivered']['count'] ?? 0); ?>
                            </h3>
                            <small class="text-muted">
                                <?php echo number_format($stats_by_status['delivered']['total_credits'] ?? 0); ?> credits
                            </small>
                        </div>
                        <div class="col-md-3">
                            <h5 class="text-muted mb-1">Failed</h5>
                            <h3 class="text-danger">
                                <?php echo number_format($stats_by_status['failed']['count'] ?? 0); ?>
                            </h3>
                            <small class="text-muted">
                                <?php echo number_format($stats_by_status['failed']['total_credits'] ?? 0); ?> credits
                            </small>
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
                <div class="col-md-3">
                    <label class="form-label">Delivery Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="sent" <?php echo $status_filter === 'sent' ? 'selected' : ''; ?>>Sent</option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">User</label>
                    <select name="user_id" class="form-select">
                        <option value="0">All Users</option>
                        <?php if ($active_users): ?>
                            <?php while ($user = $active_users->fetch_assoc()): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $user_filter === $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> 
                                    (<?php echo ucfirst($user['role']); ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
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
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="<?php echo SITE_URL; ?>admin/sms-delivery-reports.php" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Messages Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">SMS Delivery Log (<?php echo number_format($total_count); ?> messages)</h5>
                <button class="btn btn-success btn-sm" onclick="exportToCSV()">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if ($messages && $messages->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date & Time</th>
                            <th>User</th>
                            <th>Recipient</th>
                            <th>Message</th>
                            <th>Credits</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($messages && $messages->num_rows > 0): ?>
                            <?php while ($msg = $messages->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo date('M d, Y', strtotime($msg['sent_at'])); ?><br>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($msg['sent_at'])); ?></small>
                            </td>
                            <td>
                                <?php if ($msg['first_name']): ?>
                                    <strong><?php echo htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']); ?></strong><br>
                                    <small class="text-muted">
                                        <span class="badge bg-<?php 
                                            echo $msg['role'] === 'admin' ? 'danger' : 
                                                ($msg['role'] === 'client' ? 'primary' : 
                                                ($msg['role'] === 'agent' ? 'success' : 'secondary')); 
                                        ?>"><?php echo ucfirst($msg['role']); ?></span>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted">System</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($msg['recipient_name'] ?? 'N/A'); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($msg['recipient_phone']); ?></small>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars(substr($msg['message'], 0, 50)); ?>
                                <?php echo strlen($msg['message']) > 50 ? '...' : ''; ?></small>
                            </td>
                            <td>
                                <span class="badge bg-warning"><?php echo $msg['credits_used']; ?></span>
                            </td>
                            <td>
                                <?php
                                $status_badges = [
                                    'pending' => 'secondary',
                                    'sent' => 'info',
                                    'delivered' => 'success',
                                    'failed' => 'danger'
                                ];
                                $badge_class = $status_badges[$msg['delivery_status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $badge_class; ?>">
                                    <?php echo ucfirst($msg['delivery_status']); ?>
                                </span>
                                <?php if ($msg['delivered_at']): ?>
                                    <br><small class="text-muted"><?php echo date('M d, h:i A', strtotime($msg['delivered_at'])); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#messageModal<?php echo $msg['id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Message Details Modal -->
                        <div class="modal fade" id="messageModal<?php echo $msg['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Message Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <h6>Sender Information</h6>
                                        <p>
                                            <strong>Name:</strong> <?php echo htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']); ?><br>
                                            <strong>Email:</strong> <?php echo htmlspecialchars($msg['email']); ?><br>
                                            <strong>Role:</strong> <?php echo ucfirst($msg['role']); ?>
                                        </p>

                                        <hr>

                                        <h6>Recipient Information</h6>
                                        <p>
                                            <strong>Name:</strong> <?php echo htmlspecialchars($msg['recipient_name'] ?? 'N/A'); ?><br>
                                            <strong>Phone:</strong> <?php echo htmlspecialchars($msg['recipient_phone']); ?>
                                        </p>

                                        <hr>

                                        <h6>Message Content</h6>
                                        <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>

                                        <hr>

                                        <h6>Delivery Information</h6>
                                        <p>
                                            <strong>Status:</strong> <span class="badge bg-<?php echo $badge_class; ?>"><?php echo ucfirst($msg['delivery_status']); ?></span><br>
                                            <strong>Sent At:</strong> <?php echo date('F d, Y h:i A', strtotime($msg['sent_at'])); ?><br>
                                            <?php if ($msg['delivered_at']): ?>
                                                <strong>Delivered At:</strong> <?php echo date('F d, Y h:i A', strtotime($msg['delivered_at'])); ?><br>
                                            <?php endif; ?>
                                            <strong>Credits Used:</strong> <?php echo $msg['credits_used']; ?><br>
                                            <?php if ($msg['message_id']): ?>
                                                <strong>Message ID:</strong> <code><?php echo htmlspecialchars($msg['message_id']); ?></code><br>
                                            <?php endif; ?>
                                            <?php if ($msg['failed_reason']): ?>
                                                <strong>Failure Reason:</strong> <span class="text-danger"><?php echo htmlspecialchars($msg['failed_reason']); ?></span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p class="mb-0">
                                        <?php if (!$table_exists): ?>
                                            Message logs table not found. SMS delivery tracking is not yet set up.
                                        <?php else: ?>
                                            No messages found matching your filters.
                                        <?php endif; ?>
                                    </p>
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
                    <ul class="pagination justify-content-center mb-0">
                        <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&user_id=<?php echo $user_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        <?php if ($total_pages > 10): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                            <li class="page-item"><a class="page-link" href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-paper-plane fa-3x text-muted mb-3"></i>
                <p class="text-muted">No messages found</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info Card -->
    <div class="card border-0 shadow-sm bg-light mt-4">
        <div class="card-body">
            <h5><i class="fas fa-info-circle text-primary"></i> About SMS Delivery Reports</h5>
            <ul class="mb-0">
                <li><strong>Pending:</strong> Message queued for sending</li>
                <li><strong>Sent:</strong> Message sent to Termii API</li>
                <li><strong>Delivered:</strong> Message confirmed delivered to recipient</li>
                <li><strong>Failed:</strong> Message delivery failed (check failure reason)</li>
                <li><strong>Credits:</strong> Number of SMS units consumed per message</li>
                <li><strong>Termii Integration:</strong> Real-time delivery status updates from Termii API</li>
            </ul>
        </div>
    </div>
</div>

<script>
function exportToCSV() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '<?php echo SITE_URL; ?>admin/export-sms-reports.php?' + params.toString();
}
</script>

<?php include_once '../footer.php'; ?>
