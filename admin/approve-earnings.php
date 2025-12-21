<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$page_title = "Approve Agent Earnings";

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $earning_id = intval($_POST['earning_id']);
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        // Get earning details
        $earning = $conn->query("SELECT * FROM agent_earnings WHERE id = $earning_id")->fetch_assoc();
        
        if ($earning) {
            // Update earning status to approved
            $conn->query("UPDATE agent_earnings SET status = 'approved' WHERE id = $earning_id");
            
            // Update user's earnings totals
            $agent_id = $earning['agent_id'];
            $amount = $earning['amount'];
            
            // Move from pending to approved (pending_earnings stays same, no change needed since we track by status in agent_earnings)
            // The totals will be calculated dynamically from agent_earnings table
            
            // Create notification
            createNotification(
                $agent_id,
                'earning_approved',
                'Earning Approved!',
                'Your earning of ₦' . number_format($amount, 2) . ' has been approved and is now available for withdrawal.',
                'agent/request-payout.php'
            );
            
            $_SESSION['success'] = "Earning approved successfully!";
        }
    } elseif ($action === 'reject') {
        $rejection_reason = trim($_POST['rejection_reason'] ?? 'Does not meet requirements');
        
        // Get earning details
        $earning = $conn->query("SELECT * FROM agent_earnings WHERE id = $earning_id")->fetch_assoc();
        
        if ($earning) {
            $agent_id = $earning['agent_id'];
            $amount = $earning['amount'];
            
            // Delete the earning record
            $conn->query("DELETE FROM agent_earnings WHERE id = $earning_id");
            
            // Update user's earnings totals - subtract from pending and total
            $conn->query("UPDATE users SET 
                         pending_earnings = GREATEST(pending_earnings - $amount, 0),
                         total_earnings = GREATEST(total_earnings - $amount, 0)
                         WHERE id = $agent_id");
            
            // Create notification
            createNotification(
                $agent_id,
                'earning_rejected',
                'Earning Not Approved',
                'Your earning of ₦' . number_format($amount, 2) . ' was not approved. Reason: ' . $rejection_reason,
                'agent/my-earnings.php'
            );
            
            $_SESSION['warning'] = "Earning rejected and removed.";
        }
    }
    
    header('Location: approve-earnings.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'pending';
$type_filter = isset($_GET['type']) ? sanitize($_GET['type']) : 'all';

// Build query
$where_clauses = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "ae.status = '$status_filter'";
}

if ($type_filter !== 'all') {
    $where_clauses[] = "ae.earning_type = '$type_filter'";
}

$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get total count
$count_result = $conn->query("SELECT COUNT(*) as total FROM agent_earnings ae $where_sql");
$total_count = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_count / $limit);

// Get earnings
$query = "SELECT ae.*, 
          u.first_name, u.last_name, u.email, u.phone,
          p.title as poll_title
          FROM agent_earnings ae
          INNER JOIN users u ON ae.agent_id = u.id
          LEFT JOIN polls p ON ae.poll_id = p.id
          $where_sql 
          ORDER BY ae.created_at DESC 
          LIMIT $limit OFFSET $offset";
$earnings = $conn->query($query);

// Get statistics
$stats = $conn->query("SELECT 
                      status,
                      earning_type,
                      COUNT(*) as count,
                      SUM(amount) as total
                      FROM agent_earnings 
                      GROUP BY status, earning_type")->fetch_all(MYSQLI_ASSOC);

$stats_by_status = [
    'pending' => ['count' => 0, 'total' => 0],
    'approved' => ['count' => 0, 'total' => 0],
    'paid' => ['count' => 0, 'total' => 0]
];

foreach ($stats as $row) {
    if (!isset($stats_by_status[$row['status']])) {
        $stats_by_status[$row['status']] = ['count' => 0, 'total' => 0];
    }
    $stats_by_status[$row['status']]['count'] += $row['count'];
    $stats_by_status[$row['status']]['total'] += $row['total'];
}

include_once '../header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-coins text-warning"></i> Approve Agent Earnings</h2>
            <p class="text-muted">Review and approve agent earnings from poll responses and referrals</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['warning'])): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <?php echo $_SESSION['warning']; unset($_SESSION['warning']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-clock text-warning"></i> Pending Approval</h6>
                    <h3 class="mb-0 text-warning">₦<?php echo number_format($stats_by_status['pending']['total'] ?? 0, 2); ?></h3>
                    <small class="text-muted"><?php echo $stats_by_status['pending']['count'] ?? 0; ?> earnings</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-check text-success"></i> Approved</h6>
                    <h3 class="mb-0 text-success">₦<?php echo number_format($stats_by_status['approved']['total'] ?? 0, 2); ?></h3>
                    <small class="text-muted"><?php echo $stats_by_status['approved']['count'] ?? 0; ?> earnings</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-check-circle text-info"></i> Paid Out</h6>
                    <h3 class="mb-0 text-info">₦<?php echo number_format($stats_by_status['paid']['total'] ?? 0, 2); ?></h3>
                    <small class="text-muted"><?php echo $stats_by_status['paid']['count'] ?? 0; ?> earnings</small>
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
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="poll_response" <?php echo $type_filter === 'poll_response' ? 'selected' : ''; ?>>Poll Response</option>
                        <option value="referral" <?php echo $type_filter === 'referral' ? 'selected' : ''; ?>>Referral</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Earnings Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Agent Earnings</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($earnings && $earnings->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Agent</th>
                                <th>Type</th>
                                <th>Poll</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($earning = $earnings->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $earning['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($earning['first_name'] . ' ' . $earning['last_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($earning['email']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($earning['earning_type'] === 'poll_response'): ?>
                                            <span class="badge bg-primary">Direct Response</span>
                                        <?php elseif ($earning['earning_type'] === 'referral'): ?>
                                            <span class="badge bg-info">Referral</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo ucfirst($earning['earning_type']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($earning['poll_title']): ?>
                                            <?php echo htmlspecialchars($earning['poll_title']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong>₦<?php echo number_format($earning['amount'], 2); ?></strong></td>
                                    <td>
                                        <?php if ($earning['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php elseif ($earning['status'] === 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php elseif ($earning['status'] === 'paid'): ?>
                                            <span class="badge bg-info">Paid</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo ucfirst($earning['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($earning['created_at'])); ?></td>
                                    <td>
                                        <?php if ($earning['status'] === 'pending'): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Approve this earning?');">
                                                <input type="hidden" name="earning_id" value="<?php echo $earning['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-danger" title="Reject" 
                                                    onclick="showRejectModal(<?php echo $earning['id']; ?>, '<?php echo addslashes($earning['first_name'] . ' ' . $earning['last_name']); ?>')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer bg-white">
                        <nav>
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No earnings found matching the selected filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Earning</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="earning_id" id="reject_earning_id">
                    <input type="hidden" name="action" value="reject">
                    <p>Are you sure you want to reject the earning for <strong id="reject_agent_name"></strong>?</p>
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason</label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required 
                                  placeholder="Enter reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Earning</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showRejectModal(earningId, agentName) {
    document.getElementById('reject_earning_id').value = earningId;
    document.getElementById('reject_agent_name').textContent = agentName;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>

<?php include_once '../footer.php'; ?>
