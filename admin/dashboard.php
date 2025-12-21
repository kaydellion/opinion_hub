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
$revenue_stats = $conn->query("SELECT 
    SUM(CASE WHEN type = 'subscription' THEN amount / 100 ELSE 0 END) as subscription_revenue,
    SUM(CASE WHEN type IN ('sms_credits', 'email_credits', 'whatsapp_credits') THEN amount / 100 ELSE 0 END) as credits_revenue,
    SUM(amount / 100) as total_revenue
    FROM transactions 
    WHERE status = 'completed'")->fetch_assoc();

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
                        <i class="fas fa-credit-card"></i> Subscriptions: ₦<?= number_format($revenue_stats['subscription_revenue'] ?? 0, 0) ?>
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
</div>

<?php include_once '../footer.php'; ?>
