<?php
// Admin Dashboard
global $conn;
$user = getCurrentUser();

// Get overall stats
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_clients = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'client'")->fetch_assoc()['count'];
$total_agents = $conn->query("SELECT COUNT(*) as count FROM agents")->fetch_assoc()['count'];
$pending_agents = $conn->query("SELECT COUNT(*) as count FROM agents WHERE approval_status = 'pending'")->fetch_assoc()['count'];
$total_polls = $conn->query("SELECT COUNT(*) as count FROM polls")->fetch_assoc()['count'];
$active_polls = $conn->query("SELECT COUNT(*) as count FROM polls WHERE status = 'active'")->fetch_assoc()['count'];
$total_responses = $conn->query("SELECT COUNT(*) as count FROM poll_responses")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE status = 'completed'")->fetch_assoc()['total'];
?>

<div class="container-fluid my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-3">
                <i class="fas fa-cog"></i> Admin Dashboard
            </h1>
            <p class="text-muted">System Overview and Management</p>
        </div>
    </div>

    <!-- Advertisement: Dashboard Top -->
    <?php displayAd('dashboard', 'mb-4'); ?>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Total Users</h6>
                            <h2><?php echo number_format($total_users); ?></h2>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Total Polls</h6>
                            <h2><?php echo number_format($total_polls); ?></h2>
                            <small><?php echo $active_polls; ?> active</small>
                        </div>
                        <i class="fas fa-poll fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Total Responses</h6>
                            <h2><?php echo number_format($total_responses); ?></h2>
                        </div>
                        <i class="fas fa-chart-bar fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Total Revenue</h6>
                            <h2><?php echo formatCurrency($total_revenue); ?></h2>
                        </div>
                        <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
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
                        <div class="col-md-2 mb-3">
                            <a href="<?php echo SITE_URL; ?>admin/manage-users.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-users-cog"></i> Manage Users
                            </a>
                        </div>
                        <div class="col-md-2 mb-3">
                            <a href="<?php echo SITE_URL; ?>admin/manage-polls.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-poll-h"></i> Manage Polls
                            </a>
                        </div>
                        <div class="col-md-2 mb-3">
                            <a href="<?php echo SITE_URL; ?>admin/approve-agents.php" class="btn btn-outline-warning w-100">
                                <i class="fas fa-user-check"></i> Approve Agents 
                                <?php if ($pending_agents > 0): ?>
                                <span class="badge bg-danger"><?php echo $pending_agents; ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="col-md-2 mb-3">
                            <a href="<?php echo SITE_URL; ?>admin/manage-categories.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-tags"></i> Categories
                            </a>
                        </div>
                        <div class="col-md-2 mb-3">
                            <a href="<?php echo SITE_URL; ?>admin/manage-subscriptions.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-crown"></i> Subscriptions
                            </a>
                        </div>
                        <div class="col-md-2 mb-3">
                            <a href="<?php echo SITE_URL; ?>admin/system-settings.php" class="btn btn-outline-dark w-100">
                                <i class="fas fa-cogs"></i> Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> Recent Users</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
                                while ($u = $recent_users->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $u['role']; ?></span></td>
                                    <td><?php echo date('M d', strtotime($u['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-poll"></i> Recent Polls</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Creator</th>
                                    <th>Status</th>
                                    <th>Responses</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent_polls = $conn->query("SELECT p.*, u.first_name, u.last_name 
                                                              FROM polls p 
                                                              JOIN users u ON p.created_by = u.id 
                                                              ORDER BY p.created_at DESC LIMIT 10");
                                while ($p = $recent_polls->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo substr(htmlspecialchars($p['title']), 0, 30); ?></td>
                                    <td><?php echo htmlspecialchars($p['first_name']); ?></td>
                                    <td><span class="badge bg-<?php echo $p['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo $p['status']; ?></span></td>
                                    <td><?php echo $p['total_responses']; ?></td>
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
