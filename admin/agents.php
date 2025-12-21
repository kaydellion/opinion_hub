<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$user = getCurrentUser();
$page_title = "Manage Agents";

// Handle agent status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $update = $conn->prepare("UPDATE users SET agent_status = 'approved' WHERE id = ?");
        if (!$update) {
            error_log('Agent approve update prepare failed: ' . $conn->error);
            $_SESSION['error'] = 'Database error while approving agent.';
        } else {
            $update->bind_param("i", $user_id);

            if ($update->execute()) {
                // Get user details for notification
                $user_stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE id = ?");
                if (!$user_stmt) {
                    error_log('Agent user select prepare failed: ' . $conn->error);
                } else {
                    $user_stmt->bind_param("i", $user_id);
                    $user_stmt->execute();
                    $user_data = $user_stmt->get_result()->fetch_assoc();

                    // Create notification
                    createNotification(
                        $user_id,
                        'agent_approved',
                        'Agent Application Approved!',
                        'Congratulations! Your agent application has been approved. You can now start sharing polls and earning commissions.',
                        'dashboards/agent-dashboard.php'
                    );

                    $_SESSION['success'] = "Agent approved successfully!";
                }
            }
        }
    } elseif ($action === 'reject') {
        $rejection_reason = trim($_POST['rejection_reason'] ?? '');
        
        $update = $conn->prepare("UPDATE users SET agent_status = 'rejected' WHERE id = ?");
        if (!$update) {
            error_log('Agent reject update prepare failed: ' . $conn->error);
            $_SESSION['error'] = 'Database error while rejecting agent.';
        } else {
            $update->bind_param("i", $user_id);

            if ($update->execute()) {
                // Create notification
                createNotification(
                    $user_id,
                    'agent_rejected',
                    'Agent Application Status',
                    'Your agent application was not approved at this time. Reason: ' . $rejection_reason . ' You can reapply after addressing the feedback.',
                    'agent/register-agent.php'
                );

                $_SESSION['success'] = "Agent application rejected.";
            }
        }
    } elseif ($action === 'suspend') {
        $suspension_reason = trim($_POST['suspension_reason'] ?? '');
        
        $update = $conn->prepare("UPDATE users SET status = 'suspended', agent_status = 'suspended' WHERE id = ?");
        if (!$update) {
            error_log('Agent suspend update prepare failed: ' . $conn->error);
            $_SESSION['error'] = 'Database error while suspending agent.';
        } else {
            $update->bind_param("i", $user_id);

            if ($update->execute()) {
                // Create notification
                createNotification(
                    $user_id,
                    'agent_suspended',
                    'Account Suspended',
                    'Your agent account has been suspended. Reason: ' . $suspension_reason . ' Please contact support for more information.',
                    'contact.php'
                );

                $_SESSION['success'] = "Agent suspended successfully.";
            }
        }
    } elseif ($action === 'unsuspend') {
        $update = $conn->prepare("UPDATE users SET status = 'active', agent_status = 'approved' WHERE id = ?");
        if (!$update) {
            error_log('Agent unsuspend update prepare failed: ' . $conn->error);
            $_SESSION['error'] = 'Database error while unsuspending agent.';
        } else {
            $update->bind_param("i", $user_id);

            if ($update->execute()) {
                // Create notification
                createNotification(
                    $user_id,
                    'agent_unsuspended',
                    'Account Reactivated',
                    'Your agent account has been reactivated. You can now resume your activities.',
                    'dashboard.php'
                );

                $_SESSION['success'] = "Agent account reactivated successfully.";
            }
        }
    }
    
    header("Location: agents.php" . (isset($_GET['status']) ? '?status=' . $_GET['status'] : ''));
    exit();
}

// Pagination settings
$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Get filter and search
$filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build where conditions
$where_conditions = ["u.role = 'agent'", "u.agent_status = '" . $conn->real_escape_string($filter) . "'"];
if (!empty($search)) {
    $search_term = $conn->real_escape_string($search);
    $where_conditions[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search_term%' 
                            OR u.email LIKE '%$search_term%' 
                            OR u.phone LIKE '%$search_term%')";
}
$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_query = "SELECT COUNT(DISTINCT u.id) as total FROM users u WHERE $where_clause";
$total_agents = $conn->query($count_query)->fetch_assoc()['total'];
$total_pages = ceil($total_agents / $per_page);

// Get agents with pagination
$query = "SELECT u.*, 
          CONCAT(u.first_name, ' ', u.last_name) as full_name,
          COUNT(DISTINCT ps.id) as total_shares,
          COUNT(DISTINCT pr.id) as total_responses,
          COALESCE(SUM(CASE WHEN ap.status = 'completed' THEN ap.amount ELSE 0 END), 0) as total_paid
          FROM users u
          LEFT JOIN poll_shares ps ON u.id = ps.agent_id
          LEFT JOIN poll_responses pr ON ps.tracking_code = pr.tracking_code
          LEFT JOIN agent_payouts ap ON u.id = ap.agent_id
          WHERE $where_clause
          GROUP BY u.id
          ORDER BY u.created_at DESC
          LIMIT $per_page OFFSET $offset";

$stmt = $conn->query($query);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$agents = $stmt->fetch_all(MYSQLI_ASSOC);

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
                <h2><i class="fas fa-users me-2"></i>Agent Management</h2>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            <!-- Search Box -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter); ?>">
                        <div class="col-md-10">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by name, email, or phone..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Status Filter Tabs -->
            <ul class="nav nav-pills mb-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'pending' ? 'active' : ''; ?>" 
                       href="?status=pending">
                        <i class="fas fa-clock me-1"></i>Pending
                        <?php
                        $pending_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'agent' AND agent_status = 'pending'")->fetch_assoc();
                        echo " ({$pending_count['count']})";
                        ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'approved' ? 'active' : ''; ?>" 
                       href="?status=approved">
                        <i class="fas fa-check-circle me-1"></i>Approved
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'rejected' ? 'active' : ''; ?>" 
                       href="?status=rejected">
                        <i class="fas fa-times-circle me-1"></i>Rejected
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'suspended' ? 'active' : ''; ?>" 
                       href="?status=suspended">
                        <i class="fas fa-ban me-1"></i>Suspended
                    </a>
                </li>
            </ul>

            <?php if (empty($agents)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No <?php echo $filter; ?> agents found.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Contact</th>
                                <th>Payment Preference</th>
                                <th>Performance</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agents as $agent): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($agent['full_name']); ?></strong><br>
                                        <small class="text-muted">ID: <?php echo $agent['id']; ?></small>
                                    </td>
                                    <td>
                                        <i class="fas fa-envelope me-1"></i>
                                        <?php echo htmlspecialchars($agent['email']); ?><br>
                                        <i class="fas fa-phone me-1"></i>
                                        <?php echo htmlspecialchars($agent['phone'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $pref_icons = [
                                            'cash' => 'fa-money-bill-wave text-success',
                                            'airtime' => 'fa-mobile-alt text-primary',
                                            'data' => 'fa-wifi text-info'
                                        ];
                                        $icon = $pref_icons[$agent['payment_preference']] ?? 'fa-question';
                                        ?>
                                        <i class="fas <?php echo $icon; ?> me-2"></i>
                                        <?php echo ucfirst($agent['payment_preference'] ?? 'Not Set'); ?>
                                    </td>
                                    <td>
                                        <small>
                                            <i class="fas fa-share text-primary me-1"></i>
                                            <?php echo $agent['total_shares']; ?> shares<br>
                                            <i class="fas fa-poll text-success me-1"></i>
                                            <?php echo $agent['total_responses']; ?> responses<br>
                                            <i class="fas fa-money-bill text-warning me-1"></i>
                                            ₦<?php echo number_format($agent['total_paid'], 2); ?> paid
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($agent['created_at'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($filter === 'pending'): ?>
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $agent['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-success text-white" style="color: #fff !important;" 
                                                            onclick="return confirm('Approve this agent?')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <button class="btn btn-danger text-white" style="color: #fff !important;" 
                                                        onclick="showRejectModal(<?php echo $agent['id']; ?>, '<?php echo htmlspecialchars($agent['full_name']); ?>')">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </div>
                                        <?php elseif ($filter === 'approved'): ?>
                                            <div class="btn-group btn-group-sm">
                                                <span class="badge bg-success me-2">Approved</span>
                                                <button class="btn btn-warning text-white btn-sm" style="color: #fff !important;" 
                                                        onclick="showSuspendModal(<?php echo $agent['id']; ?>, '<?php echo htmlspecialchars($agent['full_name']); ?>')">
                                                    <i class="fas fa-ban"></i> Suspend
                                                </button>
                                            </div>
                                        <?php elseif ($filter === 'suspended'): ?>
                                            <div class="btn-group btn-group-sm">
                                                <span class="badge bg-warning me-2">Suspended</span>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $agent['id']; ?>">
                                                    <input type="hidden" name="action" value="unsuspend">
                                                    <button type="submit" class="btn btn-success text-white btn-sm" style="color: #fff !important;" 
                                                            onclick="return confirm('Reactivate this agent account?')">
                                                        <i class="fas fa-check-circle"></i> Reactivate
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-<?php echo $filter === 'rejected' ? 'danger' : 'secondary'; ?>">
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
                    <nav aria-label="Agent pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page - 1); ?>&status=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            
                            for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page + 1); ?>&status=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                        <p class="text-center text-muted">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_agents); ?> 
                            of <?php echo number_format($total_agents); ?> agents
                        </p>
                    </nav>
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
                    <h5 class="modal-title">Reject Agent Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="reject-user-id">
                    <input type="hidden" name="action" value="reject">
                    
                    <p>You are about to reject agent application for: <strong id="reject-agent-name"></strong></p>
                    
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason *</label>
                        <textarea class="form-control" 
                                  name="rejection_reason" 
                                  id="rejection_reason" 
                                  rows="3"
                                  required
                                  placeholder="Provide feedback to the applicant..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger text-white" style="color: #fff !important;">
                        <i class="fas fa-times me-2"></i>Reject Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Suspend Modal -->
<div class="modal fade" id="suspendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Suspend Agent Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="suspend-user-id">
                    <input type="hidden" name="action" value="suspend">
                    
                    <p>You are about to suspend agent account for: <strong id="suspend-agent-name"></strong></p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> The agent will not be able to access their account or earn commissions while suspended.</p>
                    
                    <div class="mb-3">
                        <label for="suspension_reason" class="form-label">Suspension Reason *</label>
                        <textarea class="form-control" 
                                  name="suspension_reason" 
                                  id="suspension_reason" 
                                  rows="3"
                                  required
                                  placeholder="Provide reason for suspension..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-white" style="color: #fff !important;">
                        <i class="fas fa-ban me-2"></i>Suspend Agent
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showRejectModal(userId, agentName) {
    document.getElementById('reject-user-id').value = userId;
    document.getElementById('reject-agent-name').textContent = agentName;
    document.getElementById('rejection_reason').value = '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function showSuspendModal(userId, agentName) {
    document.getElementById('suspend-user-id').value = userId;
    document.getElementById('suspend-agent-name').textContent = agentName;
    document.getElementById('suspension_reason').value = '';
    new bootstrap.Modal(document.getElementById('suspendModal')).show();
}
</script>

<?php include_once '../footer.php'; ?>
