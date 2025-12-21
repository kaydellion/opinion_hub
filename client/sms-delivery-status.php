<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('client');

$user = getCurrentUser();
$page_title = "SMS Delivery Status";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Filters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Build query
$where_clauses = ["user_id = " . $user['id'], "message_type = 'sms'"];

if ($status_filter !== 'all') {
    $where_clauses[] = "status = '$status_filter'";
}

if ($date_from) {
    $where_clauses[] = "DATE(created_at) >= '$date_from'";
}

if ($date_to) {
    $where_clauses[] = "DATE(created_at) <= '$date_to'";
}

$where_sql = implode(' AND ', $where_clauses);

// Check if message_logs table exists
$table_check = $conn->query("SHOW TABLES LIKE 'message_logs'");
$table_exists = $table_check && $table_check->num_rows > 0;

if (!$table_exists) {
    // Table doesn't exist, set default values
    $total_messages = 0;
    $total_pages = 0;
    $messages = null;
    $total_sent = 0;
    $delivered = 0;
    $failed = 0;
    $pending = 0;
    $total_credits = 0;
    $delivery_rate = 0;
    $_SESSION['warning'] = 'Message logs table not found. SMS delivery tracking is not yet set up.';
} else {
    // Get total count
    $count_result = $conn->query("SELECT COUNT(*) as total FROM message_logs WHERE $where_sql");
    $total_messages = $count_result ? $count_result->fetch_assoc()['total'] : 0;
    $total_pages = ceil($total_messages / $limit);

    // Get messages
    $query = "SELECT * FROM message_logs 
              WHERE $where_sql 
              ORDER BY created_at DESC 
              LIMIT $limit OFFSET $offset";
    $messages = $conn->query($query);

    // Get statistics
    $stats_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(credits_used) as total_credits_used
                    FROM message_logs 
                    WHERE user_id = {$user['id']} AND message_type = 'sms'";
    $stats_result = $conn->query($stats_query);
    
    if (!$stats_result) {
        error_log("SMS Delivery Status - Stats Query Failed: " . $conn->error);
        $stats = ['total' => 0, 'delivered' => 0, 'sent' => 0, 'failed' => 0, 'pending' => 0, 'total_credits_used' => 0];
    } else {
        $stats = $stats_result->fetch_assoc();
    }

    $total_sent = $stats['total'] ?? 0;
    $delivered = $stats['delivered'] ?? 0;
    $failed = $stats['failed'] ?? 0;
    $pending = $stats['pending'] ?? 0;
    $total_credits = $stats['total_credits_used'] ?? 0;
    $delivery_rate = $stats['total'] > 0 ? round(($stats['delivered'] / $stats['total']) * 100, 1) : 0;
}

include_once '../header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-sms"></i> SMS Delivery Status</h2>
            <p class="text-muted">Track delivery status of all SMS messages sent</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Sent</h6>
                            <h3 class="mb-0"><?php echo number_format($total_sent); ?></h3>
                        </div>
                        <i class="fas fa-paper-plane fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Delivered</h6>
                            <h3 class="mb-0 text-success"><?php echo number_format($delivered); ?></h3>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                    <small class="text-muted"><?php echo $delivery_rate; ?>% delivery rate</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Failed</h6>
                            <h3 class="mb-0 text-danger"><?php echo number_format($failed); ?></h3>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Credits Used</h6>
                            <h3 class="mb-0"><?php echo number_format($total_credits); ?></h3>
                        </div>
                        <i class="fas fa-coins fa-2x text-warning"></i>
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
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="sent" <?php echo $status_filter === 'sent' ? 'selected' : ''; ?>>Sent</option>
                        <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="sms-delivery-status.php" class="btn btn-outline-secondary">
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
            <h5 class="mb-0">Delivery Reports (<?php echo number_format($total_messages); ?> total)</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($messages && $messages->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date/Time</th>
                            <th>Recipient</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Credits</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($messages && $messages->num_rows > 0): ?>
                            <?php while ($msg = $messages->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <small><?php echo date('M d, Y', strtotime($msg['created_at'])); ?></small><br>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($msg['recipient']); ?></td>
                            <td>
                                <small><?php echo htmlspecialchars(substr($msg['message_content'] ?? '', 0, 50)); ?><?php echo strlen($msg['message_content'] ?? '') > 50 ? '...' : ''; ?></small>
                            </td>
                            <td>
                                <?php
                                $status = $msg['status'] ?? 'unknown';
                                $badge_class = [
                                    'delivered' => 'bg-success',
                                    'sent' => 'bg-info',
                                    'failed' => 'bg-danger',
                                    'pending' => 'bg-warning',
                                    'unknown' => 'bg-secondary'
                                ][$status] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                                <br><small class="text-muted"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></small>
                            </td>
                            <td><?php echo $msg['credits_used']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="showDetails(<?php echo htmlspecialchars(json_encode($msg), ENT_QUOTES); ?>)">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p class="mb-0">
                                        <?php if (!$table_exists): ?>
                                            Message logs table not found. SMS delivery tracking is not yet set up.
                                        <?php else: ?>
                                            No messages found. Start sending SMS to see delivery status here.
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
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="text-muted mb-0">
                            Showing <?php echo (($page - 1) * $limit) + 1; ?> to <?php echo min($page * $limit, $total_messages); ?> of <?php echo number_format($total_messages); ?> messages
                        </p>
                    </div>
                    <div class="col-md-6">
                        <nav>
                            <ul class="pagination justify-content-end mb-0">
                                <!-- Previous Button -->
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                                
                                <?php
                                // Smart pagination - show first page, last page, and pages around current
                                $show_pages = [];
                                
                                // Always show first page
                                $show_pages[] = 1;
                                
                                // Show pages around current page
                                $range = 2; // Show 2 pages before and after current
                                for ($i = max(2, $page - $range); $i <= min($total_pages - 1, $page + $range); $i++) {
                                    $show_pages[] = $i;
                                }
                                
                                // Always show last page
                                if ($total_pages > 1) {
                                    $show_pages[] = $total_pages;
                                }
                                
                                // Remove duplicates and sort
                                $show_pages = array_unique($show_pages);
                                sort($show_pages);
                                
                                // Display pages with ellipsis
                                $prev_page = 0;
                                foreach ($show_pages as $p):
                                    // Show ellipsis if there's a gap
                                    if ($p - $prev_page > 1):
                                ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                
                                <li class="page-item <?php echo $page === $p ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $p; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                        <?php echo $p; ?>
                                    </a>
                                </li>
                                
                                <?php
                                    $prev_page = $p;
                                endforeach;
                                ?>
                                
                                <!-- Next Button -->
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">No SMS messages found</p>
                <a href="<?php echo SITE_URL; ?>client/send-invites.php" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send SMS Invites
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Message Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="fw-bold">Recipient:</label>
                    <p id="detail-recipient"></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Message:</label>
                    <p id="detail-message"></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Sent At:</label>
                    <p id="detail-sent"></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Delivery Status:</label>
                    <p id="detail-status"></p>
                </div>
                <div class="mb-3" id="detail-delivered-container" style="display:none;">
                    <label class="fw-bold">Delivered At:</label>
                    <p id="detail-delivered"></p>
                </div>
                <div class="mb-3" id="detail-failed-container" style="display:none;">
                    <label class="fw-bold">Failure Reason:</label>
                    <p id="detail-failed" class="text-danger"></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Credits Used:</label>
                    <p id="detail-credits"></p>
                </div>
                <div class="mb-3" id="detail-provider-container" style="display:none;">
                    <label class="fw-bold">Provider Response:</label>
                    <p id="detail-provider"><small class="text-muted"></small></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../footer.php'; ?>

<script>
function showDetails(msg) {
    document.getElementById('detail-recipient').textContent = msg.recipient;
    document.getElementById('detail-message').textContent = msg.message_content || '';
    document.getElementById('detail-sent').textContent = new Date(msg.created_at).toLocaleString();
    document.getElementById('detail-status').innerHTML = '<span class="badge bg-primary">' + (msg.status || 'unknown') + '</span>';
    document.getElementById('detail-credits').textContent = msg.credits_used || 0;
    
    // Hide delivered time since we don't have that column
    document.getElementById('detail-delivered-container').style.display = 'none';
    
    if (msg.failed_reason) {
        document.getElementById('detail-failed-container').style.display = 'block';
        document.getElementById('detail-failed').textContent = msg.failed_reason;
    } else {
        document.getElementById('detail-failed-container').style.display = 'none';
    }
    
    if (msg.provider_response) {
        document.getElementById('detail-provider-container').style.display = 'block';
        document.getElementById('detail-provider').querySelector('small').textContent = msg.provider_response;
    } else {
        document.getElementById('detail-provider-container').style.display = 'none';
    }
    
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}
</script>
