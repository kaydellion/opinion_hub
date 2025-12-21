<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$user = getCurrentUser();
$page_title = "Manage Payouts";

// Handle payout actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $payout_id = intval($_POST['payout_id']);
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $update = $conn->prepare("UPDATE agent_payouts SET status = 'completed', processed_at = NOW(), processed_by = ? WHERE id = ?");
        if (!$update) {
            error_log('Payout approve update prepare failed: ' . $conn->error);
            $_SESSION['error'] = 'Database error while processing payout.';
        } else {
            $update->bind_param("ii", $_SESSION['user_id'], $payout_id);

            if ($update->execute()) {
                // Get payout details for notification
                $payout_stmt = $conn->prepare("SELECT agent_id, amount FROM agent_payouts WHERE id = ?");
                if (!$payout_stmt) {
                    error_log('Payout select prepare failed: ' . $conn->error);
                } else {
                    $payout_stmt->bind_param("i", $payout_id);
                    $payout_stmt->execute();
                    $payout_data = $payout_stmt->get_result()->fetch_assoc();

                    // Create notification
                    createNotification(
                        $payout_data['agent_id'],
                        'payout_processed',
                        'Payout Completed!',
                        'Your payout request of ₦' . number_format($payout_data['amount'], 2) . ' has been processed and sent.',
                        'agent/payouts.php'
                    );

                    $_SESSION['success'] = "Payout approved and processed!";
                }
            }
        }
    } elseif ($action === 'reject') {
        $rejection_reason = trim($_POST['rejection_reason'] ?? '');
        
        $update = $conn->prepare("UPDATE agent_payouts SET status = 'rejected', rejection_reason = ?, processed_at = NOW(), processed_by = ? WHERE id = ?");
        if (!$update) {
            error_log('Payout reject update prepare failed: ' . $conn->error);
            $_SESSION['error'] = 'Database error while rejecting payout.';
        } else {
            $update->bind_param("sii", $rejection_reason, $_SESSION['user_id'], $payout_id);

            if ($update->execute()) {
                // Get payout details for notification
                $payout_stmt = $conn->prepare("SELECT agent_id, amount FROM agent_payouts WHERE id = ?");
                if (!$payout_stmt) {
                    error_log('Payout select prepare failed: ' . $conn->error);
                } else {
                    $payout_stmt->bind_param("i", $payout_id);
                    $payout_stmt->execute();
                    $payout_data = $payout_stmt->get_result()->fetch_assoc();

                    // Create notification
                    createNotification(
                        $payout_data['agent_id'],
                        'payout_rejected',
                        'Payout Request Declined',
                        'Your payout request of ₦' . number_format($payout_data['amount'], 2) . ' was declined. Reason: ' . $rejection_reason,
                        'agent/payouts.php'
                    );

                    $_SESSION['success'] = "Payout rejected.";
                }
            }
        }
    }
    
    header("Location: payouts.php");
    exit();
}

// Get filter
$filter = isset($_GET['status']) ? $_GET['status'] : 'pending';

// Pagination
$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Search functionality
$search_term = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = $conn->real_escape_string(trim($_GET['search']));
}

// Count total payouts for pagination
$count_query = "SELECT COUNT(*) as total
                FROM agent_payouts ap
                JOIN users u ON ap.agent_id = u.id
                WHERE ap.status = ?";
if (!empty($search_term)) {
    $count_query .= " AND (CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search_term%' 
                           OR u.email LIKE '%$search_term%' 
                           OR ap.amount LIKE '%$search_term%'
                           OR ap.bank_name LIKE '%$search_term%'
                           OR ap.account_number LIKE '%$search_term%')";
}

$count_stmt = $conn->prepare($count_query);
if (!$count_stmt) {
    die("Error preparing count statement: " . $conn->error);
}
$count_stmt->bind_param("s", $filter);
$count_stmt->execute();
$total_payouts = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_payouts / $per_page);

// Get payouts
$query = "SELECT ap.*, CONCAT(u.first_name, ' ', u.last_name) as agent_name, u.email as agent_email
          FROM agent_payouts ap
          JOIN users u ON ap.agent_id = u.id
          WHERE ap.status = ?";
if (!empty($search_term)) {
    $query .= " AND (CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search_term%' 
                     OR u.email LIKE '%$search_term%' 
                     OR ap.amount LIKE '%$search_term%'
                     OR ap.bank_name LIKE '%$search_term%'
                     OR ap.account_number LIKE '%$search_term%')";
}
$query .= " ORDER BY ap.requested_at DESC LIMIT $per_page OFFSET $offset";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("s", $filter);
$stmt->execute();
$payouts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include_once '../header.php';
?>

<style>
/* Fix active nav-link text color */
.nav-pills .nav-link.active {
    color: #fff !important;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-money-check-alt me-2"></i>Agent Payout Management</h2>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            <!-- Status Filter Tabs -->
            <ul class="nav nav-pills mb-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'pending' ? 'active' : ''; ?>" 
                       href="?status=pending">
                        <i class="fas fa-clock me-1"></i>Pending
                        <?php
                        $pending_count = $conn->query("SELECT COUNT(*) as count FROM agent_payouts WHERE status = 'pending'")->fetch_assoc();
                        echo " ({$pending_count['count']})";
                        ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'completed' ? 'active' : ''; ?>" 
                       href="?status=completed">
                        <i class="fas fa-check-circle me-1"></i>Completed
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'rejected' ? 'active' : ''; ?>" 
                       href="?status=rejected">
                        <i class="fas fa-times-circle me-1"></i>Rejected
                    </a>
                </li>
            </ul>

            <!-- Search Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="payouts.php" class="row g-3">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter); ?>">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by agent name, email, amount, bank name, account number..." 
                                   value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                        <?php if (!empty($search_term)): ?>
                        <div class="col-12">
                            <a href="?status=<?php echo htmlspecialchars($filter); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear Search
                            </a>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if (empty($payouts)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No <?php echo $filter; ?> payouts found.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Type</th>
                                <th>Requested</th>
                                <?php if ($filter !== 'pending'): ?>
                                    <th>Processed</th>
                                <?php endif; ?>
                                <?php if ($filter === 'rejected'): ?>
                                    <th>Reason</th>
                                <?php endif; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payouts as $payout): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($payout['agent_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($payout['agent_email']); ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-success">₦<?php echo number_format($payout['amount'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        $method_icons = [
                                            'cash' => 'fa-money-bill-wave text-success',
                                            'airtime' => 'fa-mobile-alt text-primary',
                                            'data' => 'fa-wifi text-info'
                                        ];
                                        $icon = $method_icons[$payout['payment_method']] ?? 'fa-question';
                                        ?>
                                        <i class="fas <?php echo $icon; ?> me-2"></i>
                                        <?php echo ucfirst($payout['payment_method']); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo ucfirst($payout['payment_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y g:i A', strtotime($payout['requested_at'])); ?>
                                    </td>
                                    <?php if ($filter !== 'pending'): ?>
                                        <td>
                                            <?php echo $payout['processed_at'] ? date('M d, Y g:i A', strtotime($payout['processed_at'])) : 'N/A'; ?>
                                        </td>
                                    <?php endif; ?>
                                    <?php if ($filter === 'rejected'): ?>
                                        <td>
                                            <small><?php echo htmlspecialchars($payout['rejection_reason'] ?: 'No reason provided'); ?></small>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <?php if ($filter === 'pending'): ?>
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="payout_id" value="<?php echo $payout['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success text-white" style="color: #fff !important;" 
                                                            onclick="return confirm('Process this payout?')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                        <button class="btn btn-danger text-white" style="color: #fff !important;" 
                                                        onclick="showRejectModal(<?php echo $payout['id']; ?>, '<?php echo htmlspecialchars($payout['agent_name']); ?>', <?php echo $payout['amount']; ?>)">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-<?php echo $filter === 'completed' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($filter); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div>
                        <p class="text-muted mb-0">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_payouts); ?> of <?php echo $total_payouts; ?> payouts
                        </p>
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?status=<?php echo htmlspecialchars($filter); ?>&page=<?php echo $page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?status=<?php echo htmlspecialchars($filter); ?>&page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?status=<?php echo htmlspecialchars($filter); ?>&page=<?php echo $page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
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
                    <h5 class="modal-title">Reject Payout Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="payout_id" id="reject-payout-id">
                    <input type="hidden" name="action" value="reject">
                    
                    <p>You are about to reject payout request for:</p>
                    <ul>
                        <li><strong>Agent:</strong> <span id="reject-agent-name"></span></li>
                        <li><strong>Amount:</strong> ₦<span id="reject-amount"></span></li>
                    </ul>
                    
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason *</label>
                        <textarea class="form-control" 
                                  name="rejection_reason" 
                                  id="rejection_reason" 
                                  rows="3"
                                  required
                                  placeholder="Provide a clear reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger text-white" style="color:#fff !important;">
                        <i class="fas fa-times me-2"></i>Reject Payout
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showRejectModal(payoutId, agentName, amount) {
    document.getElementById('reject-payout-id').value = payoutId;
    document.getElementById('reject-agent-name').textContent = agentName;
    document.getElementById('reject-amount').textContent = amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('rejection_reason').value = '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>

<?php include_once '../footer.php'; ?>
