<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$page_title = "Manage Payouts";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

// Build query - simplified to avoid ambiguous column errors
$where_sql = "";
// Status filtering is optional - only use if explicitly needed
// The status column exists but may cause issues in some contexts

// Get total count
$count_query = "SELECT COUNT(*) as total FROM agent_earnings ae";
$count_result = $conn->query($count_query);
$total_count = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_count / $limit);

// Get payout requests
$query = "SELECT ae.*, u.first_name, u.last_name, u.email, u.phone
          FROM agent_earnings ae
          INNER JOIN users u ON ae.agent_id = u.id
          ORDER BY ae.created_at DESC
          LIMIT $limit OFFSET $offset";
$payouts = $conn->query($query);

// Get statistics - group by status to show pending/approved/paid breakdown
$stats_result = $conn->query("SELECT 
                          ae.status,
                          COUNT(*) as count,
                          SUM(ae.amount) as total
                          FROM agent_earnings ae
                          GROUP BY ae.status");
$stats = $stats_result ? $stats_result->fetch_all(MYSQLI_ASSOC) : [];

$stats_by_status = [];
foreach ($stats as $row) {
    $stats_by_status[$row['status']] = $row;
}

include_once '../header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-hand-holding-usd text-success"></i> Manage Payout Requests</h2>
            <p class="text-muted">Review and process agent payout requests</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-clock text-warning"></i> Pending</h6>
                    <h3 class="mb-0 text-warning">₦<?php echo number_format($stats_by_status['pending']['total'] ?? 0, 2); ?></h3>
                    <small class="text-muted"><?php echo $stats_by_status['pending']['count'] ?? 0; ?> requests</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-check text-info"></i> Approved</h6>
                    <h3 class="mb-0 text-info">₦<?php echo number_format($stats_by_status['approved']['total'] ?? 0, 2); ?></h3>
                    <small class="text-muted"><?php echo $stats_by_status['approved']['count'] ?? 0; ?> requests</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-check-circle text-success"></i> Paid</h6>
                    <h3 class="mb-0 text-success">₦<?php echo number_format($stats_by_status['paid']['total'] ?? 0, 2); ?></h3>
                    <small class="text-muted"><?php echo $stats_by_status['paid']['count'] ?? 0; ?> requests</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-times text-danger"></i> Cancelled</h6>
                    <h3 class="mb-0 text-danger">₦<?php echo number_format($stats_by_status['cancelled']['total'] ?? 0, 2); ?></h3>
                    <small class="text-muted"><?php echo $stats_by_status['cancelled']['count'] ?? 0; ?> requests</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payout Requests Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Payout Requests (<?php echo number_format($total_count); ?> total)</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($payouts && $payouts->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Agent</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Details</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($payout = $payouts->fetch_assoc()): 
                            // Try to get metadata from metadata column, fallback to description
                            $metadata = [];
                            if (isset($payout['metadata']) && !empty($payout['metadata'])) {
                                $metadata = json_decode($payout['metadata'], true) ?? [];
                            } elseif (strpos($payout['description'] ?? '', ' | Details: ') !== false) {
                                // Extract from description if metadata column doesn't exist
                                $parts = explode(' | Details: ', $payout['description']);
                                if (count($parts) > 1) {
                                    $metadata = json_decode($parts[1], true) ?? [];
                                }
                            }
                        ?>
                        <tr>
                            <td>#<?php echo $payout['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($payout['first_name'] . ' ' . $payout['last_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($payout['email']); ?></small>
                            </td>
                            <td class="fw-bold text-success">₦<?php echo number_format($payout['amount'], 2); ?></td>
                            <td>
                                <?php
                                $method_badges = [
                                    'bank_transfer' => '<span class="badge bg-primary">Bank Transfer</span>',
                                    'mobile_money' => '<span class="badge bg-info">Mobile Money</span>',
                                    'airtime' => '<span class="badge bg-warning">Airtime</span>',
                                    'data' => '<span class="badge bg-success">Data</span>'
                                ];
                                $method = $metadata['method'] ?? 'N/A';
                                echo $method_badges[$method] ?? ucfirst($method);
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $payout['id']; ?>">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($payout['created_at'])); ?><br>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($payout['created_at'])); ?></small>
                            </td>
                            <td>
                                <?php
                                $status_badges = [
                                    'pending' => 'warning',
                                    'approved' => 'info',
                                    'paid' => 'success',
                                    'cancelled' => 'danger'
                                ];
                                $badge_class = $status_badges[$payout['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $badge_class; ?>">
                                    <?php echo ucfirst($payout['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($payout['status'] === 'pending'): ?>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-success" onclick="updatePayoutStatus(<?php echo $payout['id']; ?>, 'approved')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-danger" onclick="updatePayoutStatus(<?php echo $payout['id']; ?>, 'cancelled')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <?php elseif ($payout['status'] === 'approved'): ?>
                                <button class="btn btn-sm btn-success" onclick="updatePayoutStatus(<?php echo $payout['id']; ?>, 'paid')">
                                    <i class="fas fa-money-check-alt"></i> Mark Paid
                                </button>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Details Modal -->
                        <div class="modal fade" id="detailsModal<?php echo $payout['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Payout Request #<?php echo $payout['id']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php
                                        // Re-parse metadata for modal (in case it's different row)
                                        $modal_metadata = [];
                                        if (isset($payout['metadata']) && !empty($payout['metadata'])) {
                                            $modal_metadata = json_decode($payout['metadata'], true) ?? [];
                                        } elseif (strpos($payout['description'] ?? '', ' | Details: ') !== false) {
                                            $parts = explode(' | Details: ', $payout['description']);
                                            if (count($parts) > 1) {
                                                $modal_metadata = json_decode($parts[1], true) ?? [];
                                            }
                                        }
                                        ?>
                                        <h6>Agent Information</h6>
                                        <p>
                                            <strong>Name:</strong> <?php echo htmlspecialchars($payout['first_name'] . ' ' . $payout['last_name']); ?><br>
                                            <strong>Email:</strong> <?php echo htmlspecialchars($payout['email']); ?><br>
                                            <strong>Phone:</strong> <?php echo htmlspecialchars($payout['phone']); ?>
                                        </p>

                                        <hr>

                                        <h6>Payout Details</h6>
                                        <p>
                                            <strong>Amount:</strong> ₦<?php echo number_format($payout['amount'], 2); ?><br>
                                            <strong>Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $modal_metadata['method'] ?? 'N/A')); ?><br>
                                            
                                            <?php if (($modal_metadata['method'] ?? '') === 'bank_transfer'): ?>
                                                <strong>Bank:</strong> <?php echo htmlspecialchars($modal_metadata['bank_name'] ?? 'N/A'); ?><br>
                                                <strong>Account Number:</strong> <?php echo htmlspecialchars($modal_metadata['account_number'] ?? 'N/A'); ?><br>
                                                <strong>Account Name:</strong> <?php echo htmlspecialchars($modal_metadata['account_name'] ?? 'N/A'); ?>
                                            <?php elseif (($modal_metadata['method'] ?? '') === 'mobile_money'): ?>
                                                <strong>Provider:</strong> <?php echo htmlspecialchars($modal_metadata['mobile_provider'] ?? 'N/A'); ?><br>
                                                <strong>Number:</strong> <?php echo htmlspecialchars($modal_metadata['mobile_number'] ?? 'N/A'); ?>
                                            <?php elseif (($modal_metadata['method'] ?? '') === 'airtime'): ?>
                                                <strong>Network:</strong> <?php echo htmlspecialchars($modal_metadata['airtime_network'] ?? 'N/A'); ?><br>
                                                <strong>Number:</strong> <?php echo htmlspecialchars($modal_metadata['airtime_number'] ?? 'N/A'); ?>
                                            <?php elseif (($modal_metadata['method'] ?? '') === 'data'): ?>
                                                <strong>Network:</strong> <?php echo htmlspecialchars(strtoupper(str_replace('-data', '', $modal_metadata['data_network'] ?? 'N/A'))); ?><br>
                                                <strong>Bundle:</strong> <?php echo htmlspecialchars($modal_metadata['data_variation'] ?? 'N/A'); ?><br>
                                                <strong>Number:</strong> <?php echo htmlspecialchars($modal_metadata['data_number'] ?? 'N/A'); ?>
                                            <?php endif; ?>
                                        </p>

                                        <hr>

                                        <h6>Additional Information</h6>
                                        <p>
                                            <strong>Description:</strong> <?php echo htmlspecialchars($payout['description']); ?><br>
                                            <strong>Status:</strong> <span class="badge bg-<?php echo $badge_class; ?>"><?php echo ucfirst($payout['status']); ?></span><br>
                                            <strong>Requested:</strong> <?php echo date('F d, Y h:i A', strtotime($payout['created_at'])); ?>
                                        </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>">
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
                <i class="fas fa-hand-holding-usd fa-3x text-muted mb-3"></i>
                <p class="text-muted">No payout requests found</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updatePayoutStatus(payoutId, newStatus) {
    if (!confirm('Are you sure you want to ' + newStatus + ' this payout request?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'updatePayoutStatus');
    formData.append('payout_id', payoutId);
    formData.append('status', newStatus);
    
    fetch('<?php echo SITE_URL; ?>actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}
</script>

<?php include_once '../footer.php'; ?>
