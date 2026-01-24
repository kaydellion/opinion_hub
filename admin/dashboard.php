<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$user = getCurrentUser();

// Get statistics
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'total_clients' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'client'")->fetch_assoc()['count'],
    'total_agents' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'agent' AND agent_status = 'approved'")->fetch_assoc()['count'],
    'pending_agents' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'agent' AND agent_status = 'pending'")->fetch_assoc()['count'],
    'total_polls' => $conn->query("SELECT COUNT(*) as count FROM polls")->fetch_assoc()['count'],
    'active_polls' => $conn->query("SELECT COUNT(*) as count FROM polls WHERE status = 'active'")->fetch_assoc()['count'],
    'total_responses' => $conn->query("SELECT COUNT(*) as count FROM poll_responses")->fetch_assoc()['count'],
    'pending_payouts' => $conn->query("SELECT COUNT(*) as count FROM agent_earnings WHERE status = 'pending'")->fetch_assoc()['count'],
    'pending_payout_amount' => $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM agent_earnings WHERE status = 'pending'")->fetch_assoc()['total'],
    'total_subscriptions' => $conn->query("SELECT COUNT(*) as count FROM user_subscriptions WHERE status = 'active'")->fetch_assoc()['count'],
    'total_blog_posts' => $conn->query("SELECT COUNT(*) as count FROM blog_posts")->fetch_assoc()['count'],
];

// Get revenue statistics (amounts in transactions table are in kobo, convert to naira)
$transaction_revenue = $conn->query("SELECT
    SUM(CASE WHEN type = 'subscription' THEN amount / 100 ELSE 0 END) as subscription_revenue,
    SUM(CASE WHEN type IN ('sms_credits', 'email_credits', 'whatsapp_credits') THEN amount / 100 ELSE 0 END) as credits_revenue,
    SUM(amount / 100) as total_revenue
    FROM transactions
    WHERE status = 'completed'")->fetch_assoc();

// Get advertisement revenue from advertisements table
$ad_revenue = $conn->query("SELECT COALESCE(SUM(amount_paid), 0) as ad_revenue FROM advertisements WHERE status IN ('active', 'approved')")->fetch_assoc()['ad_revenue'];

// Combine revenues
$revenue_stats = [
    'subscription_revenue' => $transaction_revenue['subscription_revenue'] ?? 0,
    'credits_revenue' => $transaction_revenue['credits_revenue'] ?? 0,
    'ad_revenue' => $ad_revenue,
    'total_revenue' => ($transaction_revenue['total_revenue'] ?? 0) + $ad_revenue
];

// Get recent activities
$recent_polls = $conn->query("SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) as creator_name 
                              FROM polls p 
                              JOIN users u ON p.created_by = u.id 
                              ORDER BY p.created_at DESC 
                              LIMIT 5");

$recent_users = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, email, role, created_at 
                              FROM users 
                              ORDER BY created_at DESC 
                              LIMIT 5");

$page_title = "Admin Dashboard";
include_once '../header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h2>
            <p class="text-muted">Welcome back, <?= htmlspecialchars($user['first_name']) ?>!</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <!-- Users -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Users</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_users']) ?></h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded p-3">
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </div>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-user-tie"></i> <?= $stats['total_clients'] ?> Clients | 
                        <i class="fas fa-user-secret"></i> <?= $stats['total_agents'] ?> Agents
                    </small>
                </div>
            </div>
        </div>

        <!-- Polls -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Polls</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_polls']) ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded p-3">
                            <i class="fas fa-poll fa-2x text-success"></i>
                        </div>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-check-circle text-success"></i> <?= $stats['active_polls'] ?> Active
                    </small>
                </div>
            </div>
        </div>

        <!-- Responses -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Poll Responses</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_responses']) ?></h3>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded p-3">
                            <i class="fas fa-chart-bar fa-2x text-info"></i>
                        </div>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-trophy"></i> Engagement Rate: High
                    </small>
                </div>
            </div>
        </div>

        <!-- Revenue -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Revenue</h6>
                            <h3 class="mb-0">₦<?= number_format($revenue_stats['total_revenue'] ?? 0, 2) ?></h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded p-3">
                            <i class="fas fa-money-bill-wave fa-2x text-warning"></i>
                        </div>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-credit-card"></i> Subscriptions: ₦<?= number_format($revenue_stats['subscription_revenue'] ?? 0, 0) ?><br>
                        <i class="fas fa-ad"></i> Ads: ₦<?= number_format($revenue_stats['ad_revenue'] ?? 0, 0) ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Actions -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-warning border-4">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-user-clock text-warning me-2"></i>Pending Agent Approvals
                    </h6>
                    <h2 class="mb-3"><?= $stats['pending_agents'] ?></h2>
                    <a href="agents.php?status=pending" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-eye"></i> Review Now
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-warning border-4">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-coins text-warning me-2"></i>Pending Earnings
                    </h6>
                    <h2 class="mb-3"><?= $stats['pending_payouts'] ?></h2>
                    <p class="text-muted mb-3">
                        <strong>Total Amount:</strong> ₦<?= number_format($stats['pending_payout_amount'] ?? 0, 2) ?>
                    </p>
                    <a href="approve-earnings.php" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-check-circle"></i> Review Earnings
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-success border-4">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-crown text-success me-2"></i>Active Subscriptions
                    </h6>
                    <h2 class="mb-3"><?= $stats['total_subscriptions'] ?></h2>
                    <a href="settings.php#subscriptions" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-cog"></i> Manage Plans
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row g-4 mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-rocket me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="agents.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-users fa-2x d-block mb-2"></i>
                                Manage Agents
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="payouts.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-money-bill-wave fa-2x d-block mb-2"></i>
                                Process Payouts
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="settings.php" class="btn btn-outline-warning w-100">
                                <i class="fas fa-cog fa-2x d-block mb-2"></i>
                                Platform Settings
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="polls.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-poll fa-2x d-block mb-2"></i>
                                Manage Polls
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="blog.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-blog fa-2x d-block mb-2"></i>
                                Manage Blog
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row g-4">
        <!-- Recent Polls -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-poll me-2"></i>Recent Polls</h5>
                </div>
                <div class="card-body">
                    <?php if ($recent_polls->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($poll = $recent_polls->fetch_assoc()): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($poll['title']) ?></h6>
                                            <small class="text-muted">
                                                By <?= htmlspecialchars($poll['creator_name']) ?> • 
                                                <?= date('M d, Y', strtotime($poll['created_at'])) ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?= $poll['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($poll['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">No polls yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Recent Users</h5>
                </div>
                <div class="card-body">
                    <?php if ($recent_users->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($new_user = $recent_users->fetch_assoc()): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($new_user['name']) ?></h6>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($new_user['email']) ?> • 
                                                <?= date('M d, Y', strtotime($new_user['created_at'])) ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-primary">
                                            <?= ucfirst($new_user['role']) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">No users yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Reported Polls Management -->
    <div class="row g-4 mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-flag me-2 text-warning"></i>Polls Management</h5>
                </div>
                <div class="card-body border-bottom">
                    <!-- Status Tabs -->
                    <ul class="nav nav-tabs" id="pollTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="reported-tab" data-bs-toggle="tab" data-bs-target="#reported" type="button" role="tab">
                                <i class="fas fa-flag"></i> Reported
                                <?php
                                $reported_count = $conn->query("SELECT COUNT(*) as count FROM poll_reports WHERE status = 'pending'")->fetch_assoc()['count'];
                                if ($reported_count > 0) echo "<span class='badge bg-danger ms-1'>$reported_count</span>";
                                ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="suspended-tab" data-bs-toggle="tab" data-bs-target="#suspended" type="button" role="tab">
                                <i class="fas fa-pause"></i> Suspended
                                <?php
                                $suspended_count = $conn->query("SELECT COUNT(*) as count FROM polls WHERE status = 'paused'")->fetch_assoc()['count'];
                                if ($suspended_count > 0) echo "<span class='badge bg-warning ms-1'>$suspended_count</span>";
                                ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="reported-polls.php" class="nav-link">
                                <i class="fas fa-list"></i> All Reports
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <!-- Tab Content -->
                    <div class="tab-content" id="pollTabsContent">
                        <!-- Reported Polls Tab -->
                        <div class="tab-pane fade show active" id="reported" role="tabpanel">
                    <?php
                    // Get pending reports
                    $reports_query = $conn->query("SELECT pr.*, p.title as poll_title, p.status as poll_status,
                                                  CONCAT(u.first_name, ' ', u.last_name) as reporter_name,
                                                  p.slug as poll_slug
                                                  FROM poll_reports pr
                                                  JOIN polls p ON pr.poll_id = p.id
                                                  JOIN users u ON pr.reported_by = u.id
                                                  WHERE pr.status = 'pending'
                                                  ORDER BY pr.created_at DESC
                                                  LIMIT 5");

                    if ($reports_query && $reports_query->num_rows > 0):
                    ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Poll</th>
                                        <th>Reporter</th>
                                        <th>Reason</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($report = $reports_query->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>view-poll/<?php echo $report['poll_slug']; ?>" target="_blank">
                                                    <?php echo htmlspecialchars(substr($report['poll_title'], 0, 30)); ?><?php echo strlen($report['poll_title']) > 30 ? '...' : ''; ?>
                                                </a>
                                                <?php if ($report['poll_status'] === 'paused'): ?>
                                                    <span class="badge bg-warning ms-1">Suspended</span>
                                                <?php elseif ($report['poll_status'] === 'active'): ?>
                                                    <span class="badge bg-success ms-1">Active</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($report['reporter_name']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($report['reason']); ?></span>
                                            </td>
                                            <td><?php echo date('M d, H:i', strtotime($report['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($report['poll_status'] === 'active'): ?>
                                                        <button class="btn btn-outline-warning btn-sm suspend-btn"
                                                                data-poll-id="<?php echo $report['poll_id']; ?>"
                                                                title="Suspend Poll">
                                                            <i class="fas fa-pause"></i> Suspend
                                                        </button>
                                                    <?php elseif ($report['poll_status'] === 'paused'): ?>
                                                        <button class="btn btn-outline-success btn-sm unsuspend-btn"
                                                                data-poll-id="<?php echo $report['poll_id']; ?>"
                                                                title="Unsuspend Poll">
                                                            <i class="fas fa-play"></i> Unsuspend
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted small">N/A</span>
                                                    <?php endif; ?>
                                                    <button class="btn btn-outline-danger btn-sm delete-btn"
                                                            data-poll-id="<?php echo $report['poll_id']; ?>"
                                                            title="Delete Poll">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5>No Pending Reports</h5>
                            <p class="text-muted">All reports have been reviewed</p>
                        </div>
                    <?php endif; ?>
                        </div>

                        <!-- Suspended Polls Tab -->
                        <div class="tab-pane fade" id="suspended" role="tabpanel">
                            <?php
                            // Get suspended polls
                            // Get suspended polls count for debugging
                            $total_suspended = $conn->query("SELECT COUNT(*) as count FROM polls WHERE status = 'paused'")->fetch_assoc()['count'];

                            $suspended_query = $conn->query("SELECT p.*,
                                                          CONCAT(u.first_name, ' ', u.last_name) as creator_name,
                                                          p.slug as poll_slug,
                                                          COUNT(DISTINCT pr.id) as report_count
                                                          FROM polls p
                                                          LEFT JOIN users u ON p.created_by = u.id
                                                          LEFT JOIN poll_reports pr ON p.id = pr.poll_id AND pr.status IN ('pending', 'reviewed')
                                                          WHERE p.status = 'paused'
                                                          GROUP BY p.id
                                                          ORDER BY p.updated_at DESC");


                            if ($suspended_query && $suspended_query->num_rows > 0):
                            ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Poll</th>
                                                <th>Creator</th>
                                                <th>Reports</th>
                                                <th>Suspended Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($poll = $suspended_query->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <a href="<?php echo SITE_URL; ?>view-poll/<?php echo $poll['poll_slug']; ?>" target="_blank" class="text-decoration-none">
                                                                <strong><?php echo htmlspecialchars(substr($poll['title'], 0, 40)); ?><?php echo strlen($poll['title']) > 40 ? '...' : ''; ?></strong>
                                                            </a>
                                                            <span class="badge bg-warning ms-1">Suspended</span>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($poll['creator_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-danger"><?php echo $poll['report_count']; ?> reports</span>
                                                    </td>
                                                    <td><?php echo date('M d, Y H:i', strtotime($poll['updated_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <button class="btn btn-outline-success btn-sm unsuspend-btn"
                                                                    data-poll-id="<?php echo $poll['id']; ?>"
                                                                    title="Unsuspend Poll">
                                                                <i class="fas fa-play"></i> Unsuspend
                                                            </button>
                                                            <button class="btn btn-outline-danger btn-sm delete-btn"
                                                                    data-poll-id="<?php echo $poll['id']; ?>"
                                                                    title="Delete Poll">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-play-circle fa-3x text-success mb-3"></i>
                                    <h5>No Suspended Polls</h5>
                                    <p class="text-muted">All polls are currently active</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize Bootstrap tabs
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tabs
    var triggerTabList = [].slice.call(document.querySelectorAll('#pollTabs button'))
    triggerTabList.forEach(function (triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl)
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault()
            tabTrigger.show()
        })
    });
});

// Handle poll management actions
document.addEventListener('DOMContentLoaded', function() {
    // Suspend poll
    document.querySelectorAll('.suspend-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const pollId = this.dataset.pollId;
            if (confirm('Are you sure you want to suspend this poll? It will be hidden from regular users.')) {
                console.log('Suspending poll ID:', pollId);
                fetch('<?php echo SITE_URL; ?>actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=suspend_poll&poll_id=${pollId}`
                })
                .then(response => {
                    console.log('Suspend response status:', response.status);
                    return response.text();
                })
                .then(text => {
                    console.log('Suspend raw response:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Suspend parsed data:', data);
                        if (data.success) {
                            alert('Poll suspended successfully. It has been moved to the Suspended tab.');
                            // Switch to suspended tab after reload
                            location.href = location.href + '#suspended';
                            location.reload();
                        } else {
                            alert(data.message || 'Failed to suspend poll');
                        }
                    } catch (e) {
                        console.error('Suspend JSON parse error:', e);
                        alert('Server error: ' + text);
                    }
                })
                .catch(error => {
                    console.error('Suspend network error:', error);
                    alert('Network error: ' + error.message);
                });
            }
        });
    });

    // Unsuspend poll
    document.querySelectorAll('.unsuspend-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const pollId = this.dataset.pollId;
            if (confirm('Are you sure you want to unsuspend this poll? It will be visible to regular users again.')) {
                console.log('Unsuspending poll ID:', pollId);
                fetch('<?php echo SITE_URL; ?>actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=unsuspend_poll&poll_id=${pollId}`
                })
                .then(response => {
                    console.log('Unsuspend response status:', response.status);
                    return response.text();
                })
                .then(text => {
                    console.log('Unsuspend raw response:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Unsuspend parsed data:', data);
                        if (data.success) {
                            alert('Poll unsuspended successfully');
                            location.reload();
                        } else {
                            alert(data.message || 'Failed to unsuspend poll');
                        }
                    } catch (e) {
                        console.error('Unsuspend JSON parse error:', e);
                        alert('Server error: ' + text);
                    }
                })
                .catch(error => {
                    console.error('Unsuspend network error:', error);
                    alert('Network error: ' + error.message);
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

// Handle tab navigation via hash
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash;
    if (hash === '#suspended') {
        const suspendedTab = document.querySelector('a[href="#suspended"]');
        if (suspendedTab) {
            const tab = new bootstrap.Tab(suspendedTab);
            tab.show();
        }
    }
});
</script>

<?php include_once '../footer.php'; ?>
