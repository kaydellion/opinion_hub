<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$user = getCurrentUser();
$page_title = "Reported Polls Management";
include_once '../header.php';

// Handle status filter
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'pending';

// Build query
$where_clause = "";
if ($status_filter !== 'all') {
    $where_clause = "WHERE pr.status = '$status_filter'";
}

// Get reported polls with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$reports_query = $conn->query("SELECT pr.*, p.title as poll_title, p.status as poll_status, p.slug as poll_slug,
                              CONCAT(u.first_name, ' ', u.last_name) as reporter_name,
                              CONCAT(a.first_name, ' ', a.last_name) as reviewer_name
                              FROM poll_reports pr
                              JOIN polls p ON pr.poll_id = p.id
                              JOIN users u ON pr.reported_by = u.id
                              LEFT JOIN users a ON pr.reviewed_by = a.id
                              $where_clause
                              ORDER BY pr.created_at DESC
                              LIMIT $offset, $per_page");

$total_reports = $conn->query("SELECT COUNT(*) as count FROM poll_reports pr $where_clause")->fetch_assoc()['count'];
$total_pages = ceil($total_reports / $per_page);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-flag me-2 text-warning"></i>Reported Polls Management</h2>
                    <p class="text-muted">Review and manage reported polls</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x mb-3 text-warning"></i>
                    <h4><?php echo $conn->query("SELECT COUNT(*) as count FROM poll_reports WHERE status = 'pending'")->fetch_assoc()['count']; ?></h4>
                    <p class="text-muted mb-0">Pending Reports</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-3 text-success"></i>
                    <h4><?php echo $conn->query("SELECT COUNT(*) as count FROM poll_reports WHERE status = 'reviewed'")->fetch_assoc()['count']; ?></h4>
                    <p class="text-muted mb-0">Reviewed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-check-double fa-2x mb-3 text-info"></i>
                    <h4><?php echo $conn->query("SELECT COUNT(*) as count FROM poll_reports WHERE status = 'resolved'")->fetch_assoc()['count']; ?></h4>
                    <p class="text-muted mb-0">Resolved</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-poll fa-2x mb-3 text-primary"></i>
                    <h4><?php echo $conn->query("SELECT COUNT(DISTINCT poll_id) as count FROM poll_reports")->fetch_assoc()['count']; ?></h4>
                    <p class="text-muted mb-0">Polls Reported</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <a href="?status=pending" class="btn btn<?php echo $status_filter === 'pending' ? '' : '-outline'; ?>-warning btn-sm">
                            Pending (<?php echo $conn->query("SELECT COUNT(*) as count FROM poll_reports WHERE status = 'pending'")->fetch_assoc()['count']; ?>)
                        </a>
                        <a href="?status=reviewed" class="btn btn<?php echo $status_filter === 'reviewed' ? '' : '-outline'; ?>-success btn-sm">
                            Reviewed (<?php echo $conn->query("SELECT COUNT(*) as count FROM poll_reports WHERE status = 'reviewed'")->fetch_assoc()['count']; ?>)
                        </a>
                        <a href="?status=resolved" class="btn btn<?php echo $status_filter === 'resolved' ? '' : '-outline'; ?>-info btn-sm">
                            Resolved (<?php echo $conn->query("SELECT COUNT(*) as count FROM poll_reports WHERE status = 'resolved'")->fetch_assoc()['count']; ?>)
                        </a>
                        <a href="?status=all" class="btn btn<?php echo $status_filter === 'all' ? '' : '-outline'; ?>-secondary btn-sm">
                            All Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <?php
                        $status_title = $status_filter === 'all' ? 'All' : ucfirst($status_filter);
                        echo $status_title . " Reports";
                        ?>
                        <span class="badge bg-primary ms-2"><?php echo $total_reports; ?> total</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($reports_query && $reports_query->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Report ID</th>
                                        <th>Poll</th>
                                        <th>Status</th>
                                        <th>Reporter</th>
                                        <th>Reason</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($report = $reports_query->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $report['id']; ?></td>
                                            <td>
                                                <div>
                                                    <a href="<?php echo SITE_URL; ?>view-poll/<?php echo $report['poll_slug']; ?>" target="_blank" class="text-decoration-none">
                                                        <strong><?php echo htmlspecialchars(substr($report['poll_title'], 0, 40)); ?><?php echo strlen($report['poll_title']) > 40 ? '...' : ''; ?></strong>
                                                    </a>
                                                    <div class="mt-1">
                                                        <?php if ($report['poll_status'] === 'suspended'): ?>
                                                            <span class="badge bg-warning">Suspended</span>
                                                        <?php elseif ($report['poll_status'] === 'active'): ?>
                                                            <span class="badge bg-success">Active</span>
                                                        <?php elseif ($report['poll_status'] === 'deleted'): ?>
                                                            <span class="badge bg-danger">Deleted</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary"><?php echo ucfirst($report['poll_status']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $report['status'] === 'pending' ? 'warning' :
                                                         ($report['status'] === 'reviewed' ? 'success' : 'info');
                                                ?>">
                                                    <?php echo ucfirst($report['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($report['reporter_name']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($report['reason']); ?></span>
                                                <?php if (!empty($report['description'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($report['description'], 0, 50)); ?><?php echo strlen($report['description']) > 50 ? '...' : ''; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y H:i', strtotime($report['created_at'])); ?>
                                                <?php if ($report['status'] !== 'pending'): ?>
                                                    <br><small class="text-muted">Reviewed: <?php echo date('M d, Y H:i', strtotime($report['reviewed_at'])); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($report['poll_status'] !== 'suspended' && $report['poll_status'] !== 'deleted'): ?>
                                                        <button class="btn btn-outline-warning btn-sm suspend-btn"
                                                                data-poll-id="<?php echo $report['poll_id']; ?>"
                                                                title="Suspend Poll">
                                                            <i class="fas fa-pause"></i> Suspend
                                                        </button>
                                                    <?php elseif ($report['poll_status'] === 'suspended'): ?>
                                                        <button class="btn btn-outline-success btn-sm unsuspend-btn"
                                                                data-poll-id="<?php echo $report['poll_id']; ?>"
                                                                title="Unsuspend Poll">
                                                            <i class="fas fa-play"></i> Unsuspend
                                                        </button>
                                                    <?php endif; ?>

                                                    <?php if ($report['poll_status'] !== 'deleted'): ?>
                                                        <button class="btn btn-outline-danger btn-sm delete-btn"
                                                                data-poll-id="<?php echo $report['poll_id']; ?>"
                                                                title="Delete Poll">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="d-flex justify-content-center mt-4">
                                <nav>
                                    <ul class="pagination">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?status=<?php echo $status_filter; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?status=<?php echo $status_filter; ?>&page=<?php echo $page + 1; ?>">Next</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No Reports Found</h5>
                            <p class="text-muted">There are no <?php echo $status_filter === 'all' ? '' : $status_filter; ?> reports at this time.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle poll management actions
document.addEventListener('DOMContentLoaded', function() {
    // Suspend poll
    document.querySelectorAll('.suspend-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const pollId = this.dataset.pollId;
            if (confirm('Are you sure you want to suspend this poll? It will be hidden from regular users.')) {
                fetch('<?php echo SITE_URL; ?>actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=suspend_poll&poll_id=${pollId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Poll suspended successfully');
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to suspend poll');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Network error occurred');
                });
            }
        });
    });

    // Unsuspend poll
    document.querySelectorAll('.unsuspend-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const pollId = this.dataset.pollId;
            if (confirm('Are you sure you want to unsuspend this poll? It will be visible to regular users again.')) {
                fetch('<?php echo SITE_URL; ?>actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=unsuspend_poll&poll_id=${pollId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Poll unsuspended successfully');
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to unsuspend poll');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Network error occurred');
                });
            }
        });
    });

    // Delete poll
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const pollId = this.dataset.pollId;
            if (confirm('Are you sure you want to delete this poll? This action cannot be undone.')) {
                fetch('<?php echo SITE_URL; ?>actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=admin_delete_poll&poll_id=${pollId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Poll deleted successfully');
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to delete poll');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Network error occurred');
                });
            }
        });
    });
});
</script>

<?php include_once '../footer.php'; ?>
