<?php
// Agent Dashboard
global $conn;
$user = getCurrentUser();

// Check if user is actually an agent
if ($user['role'] !== 'agent') {
    echo "<div class='container my-5'><div class='alert alert-warning'>You are not registered as an agent. <a href='" . SITE_URL . "agent/become-agent.php'>Become an agent now</a></div></div>";
    return;
}

// Get agent stats - count polls they've responded to
$total_responses_result = $conn->query("SELECT COUNT(*) as count FROM poll_responses WHERE respondent_id = " . $user['id']);
$total_responses = 0;
if ($total_responses_result) {
    $total_responses = $total_responses_result->fetch_assoc()['count'];
}

// Get actual earnings from database
$earnings_query = "SELECT 
                   SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_earnings,
                   SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_earnings,
                   SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_earnings
                   FROM agent_earnings 
                   WHERE agent_id = " . $user['id'];
$earnings_result = $conn->query($earnings_query);
$earnings = $earnings_result ? $earnings_result->fetch_assoc() : null;

$pending_earnings = floatval($earnings['pending_earnings'] ?? 0);
$approved_earnings = floatval($earnings['approved_earnings'] ?? 0);
$paid_earnings = floatval($earnings['paid_earnings'] ?? 0);
$total_earnings = $approved_earnings + $paid_earnings; // Available for payout (approved) + already paid

// Get recent poll responses
$recent_responses_query = "SELECT pr.*, p.title as poll_title, p.created_at as poll_created 
                          FROM poll_responses pr 
                          JOIN polls p ON pr.poll_id = p.id 
                          WHERE pr.user_id = " . $user['id'] . " 
                          ORDER BY pr.responded_at DESC 
                          LIMIT 10";
$recent_responses = $conn->query($recent_responses_query);

// Check if query failed
if (!$recent_responses) {
    $recent_responses = null;
    // Optionally log the error: error_log($conn->error);
}
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-3">
                <i class="fas fa-user-tie"></i> Agent Dashboard
            </h1>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
            
            <!-- Advertisement: Dashboard Top -->
            <?php displayAd('dashboard', 'mb-4'); ?>
            
            <?php if ($user['agent_status'] === 'pending'): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-clock"></i> <strong>Agent Application Pending</strong><br>
                    Your agent application is under review. You will be notified via email once approved (usually within 48 hours). 
                    You can browse and answer polls, but earnings will only be credited after approval.
                </div>
            <?php elseif ($user['agent_status'] === 'rejected'): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> <strong>Agent Application Rejected</strong><br>
                    Unfortunately, your agent application was not approved. Please contact support at <a href="mailto:hello@opinionhub.ng">hello@opinionhub.ng</a> for more information.
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <strong>You are an approved agent!</strong> Complete polls to earn ₦1,000 per response.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Earnings Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body">
                    <h6 class="mb-2"><i class="fas fa-money-bill-wave"></i> Total Earnings</h6>
                    <h2 class="mb-0">₦<?php echo number_format($total_earnings, 2); ?></h2>
                    <small>Approved + Paid</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning text-dark">
                <div class="card-body">
                    <h6 class="mb-2"><i class="fas fa-clock"></i> Pending Earnings</h6>
                    <h2 class="mb-0">₦<?php echo number_format($pending_earnings, 2); ?></h2>
                    <small>Awaiting approval</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body">
                    <h6 class="mb-2"><i class="fas fa-check-circle"></i> Approved Earnings</h6>
                    <h2 class="mb-0">₦<?php echo number_format($approved_earnings, 2); ?></h2>
                    <small>Ready for payout</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <h6 class="mb-2"><i class="fas fa-poll-h"></i> Polls Completed</h6>
                    <h2 class="mb-0"><?php echo $total_responses; ?></h2>
                    <small>Total responses submitted</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body">
                    <h6 class="mb-2"><i class="fas fa-chart-line"></i> Avg. per Response</h6>
                    <h2 class="mb-0">₦1,000</h2>
                    <small>Standard agent rate</small>
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
                            <a href="<?php echo SITE_URL; ?>polls.php" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-poll-h"></i> Browse Polls
                            </a>
                        </div>
                        <?php if ($user['agent_status'] === 'approved'): ?>
                        <div class="col-md-3 mb-3">
                            <a href="#available-polls" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-share-alt"></i> Share Polls
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo SITE_URL; ?>agent/buy-vtu.php" class="btn btn-danger btn-lg w-100">
                                <i class="fas fa-phone-alt"></i> Buy Airtime/Data
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo SITE_URL; ?>agent/payouts.php" class="btn btn-warning btn-lg w-100 text-dark">
                                <i class="fas fa-money-bill-wave"></i> Request Payout
                            </a>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo SITE_URL; ?>profile.php" class="btn btn-outline-primary btn-lg w-100">
                                <i class="fas fa-user-edit"></i> Update Profile
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo SITE_URL; ?>faq.php" class="btn btn-outline-secondary btn-lg w-100">
                                <i class="fas fa-question-circle"></i> Help & FAQ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($user['agent_status'] === 'approved'): ?>
    <!-- Available Polls to Share -->
    <div class="row mb-4" id="available-polls">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-share-nodes"></i> Active Polls to Share</h5>
                    <small class="text-muted">Share these polls and earn ₦1,000 for every completed response</small>
                </div>
                <div class="card-body">
                    <?php
                    // Get active polls
                    $active_polls_query = "SELECT p.*, c.name as category_name, 
                                          (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as response_count
                                          FROM polls p 
                                          LEFT JOIN categories c ON p.category_id = c.id 
                                          WHERE p.status = 'active' AND p.end_date >= CURDATE()
                                          ORDER BY p.created_at DESC 
                                          LIMIT 10";
                    $active_polls = $conn->query($active_polls_query);
                    ?>
                    
                    <?php if ($active_polls && $active_polls->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Poll Title</th>
                                        <th>Category</th>
                                        <th>Responses</th>
                                        <th>Ends On</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($poll = $active_polls->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($poll['title']); ?></strong>
                                                <?php if ($poll['description']): ?>
                                                    <br><small class="text-muted"><?php echo substr(htmlspecialchars($poll['description']), 0, 60); ?>...</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($poll['category_name']); ?></span>
                                            </td>
                                            <td>
                                                <i class="fas fa-users"></i> <?php echo $poll['response_count']; ?>
                                            </td>
                                            <td>
                                                <small><?php echo date('M d, Y', strtotime($poll['end_date'])); ?></small>
                                            </td>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>agent/share-poll.php?poll_id=<?php echo $poll['id']; ?>" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="fas fa-share-alt"></i> Share
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i> No active polls available at the moment. Check back soon!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Poll Responses</h5>
                </div>
                <div class="card-body">
                    <?php if (!$recent_responses || $recent_responses->num_rows === 0): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> You haven't completed any polls yet. <a href="<?php echo SITE_URL; ?>polls.php">Start earning now!</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Poll Title</th>
                                        <th>Responded On</th>
                                        <th>Earnings</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($response = $recent_responses->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($response['poll_title']); ?></strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i> 
                                                    <?php echo date('M d, Y h:i A', strtotime($response['responded_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">₦1,000</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">Completed</span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Banking Info -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-university"></i> Payment Information</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($user['bank_name']) && !empty($user['account_number'])): ?>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Bank Name:</strong><br>
                                <?php echo htmlspecialchars($user['bank_name']); ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Account Name:</strong><br>
                                <?php echo htmlspecialchars($user['account_name']); ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Account Number:</strong><br>
                                <?php echo htmlspecialchars($user['account_number']); ?>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="fas fa-info-circle"></i> Payments are processed within 5 working days after poll completion.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Please update your banking details to receive payments. 
                            <a href="<?php echo SITE_URL; ?>profile.php" class="alert-link">Update now</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
