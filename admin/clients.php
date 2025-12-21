<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$user = getCurrentUser();
$page_title = "Manage Clients";

// Handle client actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $target_user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    if ($action === 'activate') {
        $update = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        if (!$update) {
            error_log('Client activate update prepare failed: ' . $conn->error);
            $_SESSION['error'] = 'Database error while activating client.';
        } else {
            $update->bind_param("i", $target_user_id);
            if ($update->execute()) {
                createNotification(
                    $target_user_id,
                    'account_activated',
                    'Account Activated!',
                    'Your account has been activated. You can now access all features.',
                    'dashboards/client-dashboard.php'
                );
                $_SESSION['success'] = "Client activated successfully!";
            }
        }
    } elseif ($action === 'suspend') {
        $reason = trim($_POST['reason'] ?? '');
        $update = $conn->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
        if (!$update) {
            error_log('Client suspend update prepare failed: ' . $conn->error);
            $_SESSION['error'] = 'Database error while suspending client.';
        } else {
            $update->bind_param("i", $target_user_id);
            if ($update->execute()) {
                createNotification(
                    $target_user_id,
                    'account_suspended',
                    'Account Suspended',
                    'Your account has been suspended. Reason: ' . $reason . ' Please contact support for assistance.',
                    'contact.php'
                );
                $_SESSION['success'] = "Client suspended successfully!";
            }
        }
    }
    
    header("Location: clients.php" . (isset($_GET['status']) ? '?status=' . $_GET['status'] : ''));
    exit();
}

// Pagination settings
$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Get filter and search
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'active';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where_conditions = ["u.role = 'client'"];
if ($status_filter !== 'all') {
    $where_conditions[] = "u.status = '" . $conn->real_escape_string($status_filter) . "'";
}
if (!empty($search)) {
    $search_term = $conn->real_escape_string($search);
    $where_conditions[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search_term%' 
                            OR u.email LIKE '%$search_term%' 
                            OR u.username LIKE '%$search_term%' 
                            OR u.phone LIKE '%$search_term%')";
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users u WHERE $where_clause";
$total_clients = $conn->query($count_query)->fetch_assoc()['total'];
$total_pages = ceil($total_clients / $per_page);

// Get clients with pagination
$query = "SELECT u.*,
          CONCAT(u.first_name, ' ', u.last_name) as full_name,
          (SELECT COUNT(*) FROM polls WHERE created_by = u.id) as total_polls,
          (SELECT COUNT(*) FROM poll_responses pr JOIN polls p ON pr.poll_id = p.id WHERE p.created_by = u.id) as total_poll_responses,
          (SELECT COUNT(*) FROM advertisements WHERE advertiser_id = u.id) as total_ads,
          (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = u.id AND status = 'completed') as total_spent,
          (SELECT plan_id FROM user_subscriptions WHERE user_id = u.id AND status = 'active' LIMIT 1) as active_subscription
          FROM users u
          WHERE $where_clause
          ORDER BY u.created_at DESC
          LIMIT $per_page OFFSET $offset";

$stmt = $conn->query($query);
if (!$stmt) {
    die("Query failed: " . $conn->error);
}
$clients = $stmt->fetch_all(MYSQLI_ASSOC);

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
                <h2><i class="fas fa-briefcase me-2"></i>Client Management</h2>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by name, email, username, or phone..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select name="status" class="form-select">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stats -->
            <div class="row mb-4">
                <?php
                $stats = [
                    'total' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'client'")->fetch_assoc()['count'],
                    'active' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'client' AND status = 'active'")->fetch_assoc()['count'],
                    'subscribed' => $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM user_subscriptions WHERE status = 'active'")->fetch_assoc()['count'],
                ];
                ?>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-primary"><?php echo number_format($stats['total']); ?></h3>
                            <p class="text-muted mb-0">Total Clients</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-success"><?php echo number_format($stats['active']); ?></h3>
                            <p class="text-muted mb-0">Active Clients</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-info"><?php echo number_format($stats['subscribed']); ?></h3>
                            <p class="text-muted mb-0">Subscribed Clients</p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($clients)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No clients found matching your criteria.
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Activity</th>
                                        <th>Revenue</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clients as $c): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($c['full_name']); ?></strong><br>
                                                <small class="text-muted">@<?php echo htmlspecialchars($c['username']); ?></small>
                                                <?php if ($c['active_subscription']): ?>
                                                    <br><span class="badge bg-success">Subscribed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-envelope me-1"></i>
                                                <small><?php echo htmlspecialchars($c['email']); ?></small><br>
                                                <?php if ($c['phone']): ?>
                                                    <i class="fas fa-phone me-1"></i>
                                                    <small><?php echo htmlspecialchars($c['phone']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status_badges = [
                                                    'active' => 'success',
                                                    'suspended' => 'warning'
                                                ];
                                                $badge_color = $status_badges[$c['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $badge_color; ?>">
                                                    <?php echo ucfirst($c['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <i class="fas fa-poll text-primary me-1"></i>
                                                    <?php echo $c['total_polls']; ?> polls<br>
                                                    <i class="fas fa-chart-bar text-success me-1"></i>
                                                    <?php echo $c['total_poll_responses']; ?> responses<br>
                                                    <i class="fas fa-ad text-warning me-1"></i>
                                                    <?php echo $c['total_ads']; ?> ads
                                                </small>
                                            </td>
                                            <td>
                                                <strong class="text-success">
                                                    ₦<?php echo number_format($c['total_spent'] / 100, 2); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <small><?php echo date('M d, Y', strtotime($c['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($c['status'] === 'active'): ?>
                                                        <button class="btn btn-warning text-white" 
                                                                onclick="showSuspendModal(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['full_name']); ?>')">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="user_id" value="<?php echo $c['id']; ?>">
                                                            <input type="hidden" name="action" value="activate">
                                                            <button type="submit" class="btn btn-success text-white" 
                                                                    onclick="return confirm('Activate this client?')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Client pagination" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page - 1); ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start = max(1, $page - 2);
                                    $end = min($total_pages, $page + 2);
                                    
                                    for ($i = $start; $i <= $end; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page + 1); ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                                <p class="text-center text-muted">
                                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_clients); ?> 
                                    of <?php echo number_format($total_clients); ?> clients
                                </p>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Suspend Modal -->
<div class="modal fade" id="suspendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Suspend Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="suspend-user-id">
                    <input type="hidden" name="action" value="suspend">
                    
                    <p>You are about to suspend: <strong id="suspend-user-name"></strong></p>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Suspension Reason *</label>
                        <textarea class="form-control" name="reason" id="reason" rows="3" required
                                  placeholder="Provide a clear reason for suspension..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-white">
                        <i class="fas fa-ban me-2"></i>Suspend Client
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showSuspendModal(userId, userName) {
    document.getElementById('suspend-user-id').value = userId;
    document.getElementById('suspend-user-name').textContent = userName;
    new bootstrap.Modal(document.getElementById('suspendModal')).show();
}
</script>

<?php include_once '../footer.php'; ?>
