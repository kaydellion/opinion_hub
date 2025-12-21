<?php
session_start();
require_once '../connect.php';
require_once '../functions.php';

// Check if user is an agent
if (!isLoggedIn() || $_SESSION['user_role'] !== 'agent') {
    header("Location: " . SITE_URL);
    exit();
}

$agent_id = $_SESSION['user_id'];
$page_title = "My Referrals";

// Check if referral system columns exist
$check_columns = $conn->query("SHOW COLUMNS FROM users LIKE 'referral_code'");
if (!$check_columns || $check_columns->num_rows === 0) {
    // Columns don't exist - show migration message
    include_once '../header.php';
    ?>
    <div class="container py-5">
        <div class="alert alert-warning">
            <h4><i class="fas fa-exclamation-triangle me-2"></i>Database Migration Required</h4>
            <p>The referral system requires database updates. Please run the migration:</p>
            <ol>
                <li>Visit: <a href="<?php echo SITE_URL; ?>migrations/run_referral_migration.php" class="alert-link fw-bold" target="_blank">Run Referral System Migration</a></li>
                <li>Wait for "Migration completed successfully" message</li>
                <li>Return to this page and refresh</li>
            </ol>
            <hr>
            <p class="mb-0"><small><i class="fas fa-info-circle me-1"></i>This is a one-time setup process.</small></p>
        </div>
        <div class="text-center">
            <a href="<?php echo SITE_URL; ?>agent/dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
    <?php
    include_once '../footer.php';
    exit;
}

// Get agent's referral code
$agent_stmt = $conn->prepare("SELECT referral_code, total_earnings FROM users WHERE id = ?");
if (!$agent_stmt) {
    die("Database error: " . $conn->error);
}
$agent_stmt->bind_param("i", $agent_id);
$agent_stmt->execute();
$agent_data = $agent_stmt->get_result()->fetch_assoc();
$referral_code = $agent_data['referral_code'] ?? '';

// Generate referral code if doesn't exist
if (empty($referral_code)) {
    $referral_code = strtoupper(substr(md5($agent_id . time()), 0, 8));
    $update_stmt = $conn->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
    if (!$update_stmt) {
        die("Database error: " . $conn->error);
    }
    $update_stmt->bind_param("si", $referral_code, $agent_id);
    $update_stmt->execute();
}

// Get referral stats
$stats_query = "SELECT 
                COUNT(DISTINCT u.id) as total_referrals,
                COUNT(DISTINCT CASE WHEN u.status = 'active' THEN u.id END) as active_referrals,
                SUM(CASE WHEN pr.id IS NOT NULL THEN 1 ELSE 0 END) as total_responses,
                COUNT(DISTINCT s.id) as subscribed_referrals
                FROM users u
                LEFT JOIN poll_responses pr ON u.id = pr.respondent_id
                LEFT JOIN user_subscriptions s ON u.id = s.user_id AND s.status = 'active'
                WHERE u.referred_by = ?";
$stats_stmt = $conn->prepare($stats_query);
if (!$stats_stmt) {
    die("Database error in stats query: " . $conn->error);
}
$stats_stmt->bind_param("i", $agent_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Calculate estimated earnings from referrals
$earnings_query = "SELECT 
                   SUM(amount) as total_earned
                   FROM agent_earnings 
                   WHERE agent_id = ? AND earning_type = 'referral'";
$earnings_stmt = $conn->prepare($earnings_query);
if (!$earnings_stmt) {
    // Table might not exist yet, set to 0
    $referral_earnings = 0;
} else {
    $earnings_stmt->bind_param("i", $agent_id);
    $earnings_stmt->execute();
    $earnings_data = $earnings_stmt->get_result()->fetch_assoc();
    $referral_earnings = $earnings_data['total_earned'] ?? 0;
}

// Get list of referred users with pagination
$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Count total referred users
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE referred_by = ?");
if (!$count_stmt) {
    die("Database error in count query: " . $conn->error);
}
$count_stmt->bind_param("i", $agent_id);
$count_stmt->execute();
$total_referrals = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_referrals / $per_page);

// Get referred users
$referrals_query = "SELECT u.id, u.first_name, u.last_name, u.email, u.created_at, u.status,
                    (SELECT COUNT(*) FROM poll_responses WHERE respondent_id = u.id) as response_count,
                    (SELECT COUNT(*) FROM user_subscriptions WHERE user_id = u.id AND status = 'active') as is_subscribed
                    FROM users u
                    WHERE u.referred_by = ?
                    ORDER BY u.created_at DESC
                    LIMIT ? OFFSET ?";
$referrals_stmt = $conn->prepare($referrals_query);
if (!$referrals_stmt) {
    die("Database error in referrals query: " . $conn->error);
}
$referrals_stmt->bind_param("iii", $agent_id, $per_page, $offset);
$referrals_stmt->execute();
$referrals = $referrals_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include_once '../header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-users me-2"></i>My Referrals</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            <!-- Referral Code Card -->
            <div class="card mb-4 border-primary">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-link me-2"></i>Your Referral Code</h5>
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control form-control-lg" id="referralCode" 
                                       value="<?php echo htmlspecialchars($referral_code); ?>" readonly>
                                <button class="btn btn-primary" type="button" onclick="copyReferralCode()">
                                    <i class="fas fa-copy me-2"></i>Copy Code
                                </button>
                            </div>
                            <small class="text-muted">Share this code with new users during registration</small>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" id="referralLink" 
                                       value="<?php echo SITE_URL; ?>signup.php?ref=<?php echo htmlspecialchars($referral_code); ?>" readonly>
                                <button class="btn btn-outline-primary" type="button" onclick="copyReferralLink()">
                                    <i class="fas fa-copy me-2"></i>Copy Link
                                </button>
                            </div>
                            <small class="text-muted">Or share this direct signup link</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h3 class="mb-0"><?php echo number_format($stats['total_referrals']); ?></h3>
                            <small>Total Referrals</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h3 class="mb-0"><?php echo number_format($stats['active_referrals']); ?></h3>
                            <small>Active Users</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h3 class="mb-0"><?php echo number_format($stats['subscribed_referrals']); ?></h3>
                            <small>Subscribed Users</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h3 class="mb-0">₦<?php echo number_format($referral_earnings / 100, 2); ?></h3>
                            <small>Referral Earnings</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Referrals Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Referred Users</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($referrals)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            You haven't referred any users yet. Share your referral code to start earning!
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Joined Date</th>
                                        <th>Status</th>
                                        <th>Responses</th>
                                        <th>Subscription</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($referrals as $referral): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($referral['first_name'] . ' ' . $referral['last_name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($referral['email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($referral['created_at'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $referral['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($referral['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $referral['response_count']; ?> responses</span>
                                            </td>
                                            <td>
                                                <?php if ($referral['is_subscribed'] > 0): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-crown me-1"></i>Subscribed
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Free</span>
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
                                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_referrals); ?> of <?php echo $total_referrals; ?> referrals
                                </p>
                            </div>
                            <nav>
                                <ul class="pagination mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- How It Works Section -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-question-circle me-2"></i>How Referrals Work</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center p-3">
                                <i class="fas fa-share-alt fa-3x text-primary mb-3"></i>
                                <h6>1. Share Your Code</h6>
                                <p class="text-muted">Share your referral code or link with potential users</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3">
                                <i class="fas fa-user-plus fa-3x text-success mb-3"></i>
                                <h6>2. Users Sign Up</h6>
                                <p class="text-muted">New users register using your referral code</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3">
                                <i class="fas fa-money-bill-wave fa-3x text-warning mb-3"></i>
                                <h6>3. Earn Commissions</h6>
                                <p class="text-muted">Earn when referred users participate in polls or subscribe</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyReferralCode() {
    const codeInput = document.getElementById('referralCode');
    codeInput.select();
    codeInput.setSelectionRange(0, 99999); // For mobile devices
    
    navigator.clipboard.writeText(codeInput.value).then(() => {
        // Show success message
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Copied!';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
        }, 2000);
    }).catch(err => {
        alert('Failed to copy code. Please copy manually.');
    });
}

function copyReferralLink() {
    const linkInput = document.getElementById('referralLink');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // For mobile devices
    
    navigator.clipboard.writeText(linkInput.value).then(() => {
        // Show success message
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Copied!';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-primary');
        }, 2000);
    }).catch(err => {
        alert('Failed to copy link. Please copy manually.');
    });
}
</script>

<?php include_once '../footer.php'; ?>
