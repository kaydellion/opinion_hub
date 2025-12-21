<?php
// Client Dashboard
global $conn;
$user = getCurrentUser();

// Check if user is actually a client
if ($user['role'] !== 'client') {
    echo "<div class='container my-5'><div class='alert alert-warning'>You do not have access to this page.</div></div>";
    return;
}

// Get stats
$total_polls = $conn->query("SELECT COUNT(*) as count FROM polls WHERE created_by = " . $user['id'])->fetch_assoc()['count'];
$active_polls = $conn->query("SELECT COUNT(*) as count FROM polls WHERE created_by = " . $user['id'] . " AND status = 'active'")->fetch_assoc()['count'];
$total_responses = $conn->query("SELECT COALESCE(SUM(total_responses), 0) as count FROM polls WHERE created_by = " . $user['id'])->fetch_assoc()['count'];

// Get messaging credits
$credits = $conn->query("SELECT * FROM messaging_credits WHERE user_id = " . $user['id'])->fetch_assoc();
if (!$credits) {
    $credits = ['sms_balance' => 0, 'email_balance' => 0, 'whatsapp_balance' => 0];
}

// Get subscription
$subscription = $conn->query("SELECT sp.*, us.* FROM user_subscriptions us 
                             JOIN subscription_plans sp ON us.plan_id = sp.id 
                             WHERE us.user_id = " . $user['id'] . " AND us.status = 'active' 
                             ORDER BY us.created_at DESC LIMIT 1")->fetch_assoc();

$plan_name = $subscription['name'] ?? 'Free Plan';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-3">
                <i class="fas fa-tachometer-alt"></i> Client Dashboard
            </h1>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
        </div>
    </div>

    <!-- Advertisement: Dashboard Top -->
    <?php displayAd('dashboard', 'mb-4'); ?>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Polls</h6>
                            <h2 class="mb-0"><?php echo $total_polls; ?></h2>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-poll fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Active Polls</h6>
                            <h2 class="mb-0"><?php echo $active_polls; ?></h2>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-check-circle fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Responses</h6>
                            <h2 class="mb-0"><?php echo $total_responses; ?></h2>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-users fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Current Plan</h6>
                            <h5 class="mb-0"><?php echo $plan_name; ?></h5>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-star fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Messaging Credits -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-envelope"></i> Messaging Credits</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <h6 class="text-muted">SMS Balance</h6>
                            <h3 class="text-primary"><?php echo number_format($credits['sms_balance']); ?></h3>
                            <a href="<?php echo SITE_URL; ?>client/buy-credits.php?type=sms" class="btn btn-sm btn-outline-primary">Buy SMS</a>
                        </div>
                        <div class="col-md-4 text-center">
                            <h6 class="text-muted">Email Balance</h6>
                            <h3 class="text-success"><?php echo number_format($credits['email_balance']); ?></h3>
                            <a href="<?php echo SITE_URL; ?>client/buy-credits.php?type=email" class="btn btn-sm btn-outline-success">Buy Email</a>
                        </div>
                        <div class="col-md-4 text-center">
                            <h6 class="text-muted">WhatsApp Balance</h6>
                            <h3 class="text-info"><?php echo number_format($credits['whatsapp_balance']); ?></h3>
                            <a href="<?php echo SITE_URL; ?>client/buy-credits.php?type=whatsapp" class="btn btn-sm btn-outline-info">Buy WhatsApp</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo SITE_URL; ?>client/create-poll.php" class="btn btn-primary w-100">
                                <i class="fas fa-plus-circle"></i> Create New Poll
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo SITE_URL; ?>client/manage-polls.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-list"></i> View My Polls
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo SITE_URL; ?>client/send-invites.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-paper-plane"></i> Send Invites
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo SITE_URL; ?>client/subscription.php" class="btn btn-outline-warning w-100">
                                <i class="fas fa-crown"></i> Upgrade Plan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Polls -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Recent Polls</h5>
                    <a href="<?php echo SITE_URL; ?>client/manage-polls.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Responses</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent_polls = $conn->query("SELECT * FROM polls WHERE created_by = " . $user['id'] . " ORDER BY created_at DESC LIMIT 5");
                                while ($poll = $recent_polls->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($poll['title']); ?></td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'draft' => 'secondary',
                                            'active' => 'success',
                                            'paused' => 'warning',
                                            'closed' => 'danger'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $badges[$poll['status']]; ?>">
                                            <?php echo ucfirst($poll['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $poll['total_responses']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($poll['created_at'])); ?></td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>client/view-poll-results.php?id=<?php echo $poll['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-chart-pie"></i> Results
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>client/add-questions.php?id=<?php echo $poll['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
