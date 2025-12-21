<?php
require_once '../connect.php';
require_once '../functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . 'signin.php');
    exit;
}

$current_user = getCurrentUser();
if ($current_user['role'] !== 'admin') {
    header('Location: ' . SITE_URL . 'dashboards/admin-dashboard.php');
    exit;
}

$errors = [];
$success = '';

// Handle manual credit adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_credits'])) {
    $agent_id = (int)$_POST['agent_id'];
    $adjustment = (int)$_POST['adjustment'];
    $description = trim($_POST['description']);
    
    if (empty($description)) {
        $errors[] = 'Please provide a description for this adjustment.';
    } elseif ($adjustment == 0) {
        $errors[] = 'Adjustment amount cannot be zero.';
    } else {
        if ($adjustment > 0) {
            // Adding credits
            if (addAgentSMSCredits($agent_id, $adjustment, 0, $description)) {
                $success = "Successfully added $adjustment credits.";
            } else {
                $errors[] = 'Failed to add credits.';
            }
        } else {
            // Deducting credits
            $current = getAgentSMSCredits($agent_id);
            $deduct = abs($adjustment);
            
            if ($deduct > $current) {
                $errors[] = "Cannot deduct $deduct credits. Agent only has $current credits.";
            } else {
                // Deduct one by one
                $success_count = 0;
                for ($i = 0; $i < $deduct; $i++) {
                    if (deductAgentSMSCredit($agent_id, $description)) {
                        $success_count++;
                    }
                }
                
                if ($success_count == $deduct) {
                    $success = "Successfully deducted $deduct credits.";
                } else {
                    $errors[] = "Only deducted $success_count out of $deduct credits.";
                }
            }
        }
    }
}

// Get all agents with their credit balances
$stmt = $conn->prepare("
    SELECT 
        u.id,
        CONCAT(u.first_name, ' ', u.last_name) as full_name,
        u.email,
        COALESCE(SUM(CASE 
            WHEN ac.transaction_type IN ('purchase', 'refund') THEN ac.credits
            WHEN ac.transaction_type = 'used' THEN -ac.credits
            ELSE 0
        END), 0) as total_credits,
        COUNT(DISTINCT CASE WHEN ac.transaction_type = 'purchase' THEN ac.id END) as total_purchases,
        COUNT(DISTINCT CASE WHEN ac.transaction_type = 'used' THEN ac.id END) as total_used,
        SUM(CASE WHEN ac.transaction_type = 'purchase' THEN ac.amount_paid ELSE 0 END) as total_spent
    FROM users u
    LEFT JOIN agent_sms_credits ac ON u.id = ac.agent_id
    WHERE u.role = 'agent'
    GROUP BY u.id
    ORDER BY total_credits DESC, CONCAT(u.first_name, ' ', u.last_name) ASC
");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->execute();
$agents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent transactions across all agents
$stmt = $conn->prepare("
    SELECT 
        ac.*,
        CONCAT(u.first_name, ' ', u.last_name) as full_name,
        u.email
    FROM agent_sms_credits ac
    JOIN users u ON ac.agent_id = u.id
    ORDER BY ac.created_at DESC
    LIMIT 50
");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->execute();
$recent_transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_stats = [
    'total_credits_in_system' => 0,
    'total_revenue' => 0,
    'total_agents' => count($agents),
    'active_agents' => 0
];

foreach ($agents as $agent) {
    $total_stats['total_credits_in_system'] += $agent['total_credits'];
    $total_stats['total_revenue'] += $agent['total_spent'];
    if ($agent['total_credits'] > 0) {
        $total_stats['active_agents']++;
    }
}

$page_title = "SMS Credits Management";
include '../header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= SITE_URL ?>dashboards/admin-dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">SMS Credits Management</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Credits in System</h6>
                    <h2 class="mb-0 text-primary"><?= number_format($total_stats['total_credits_in_system']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Revenue</h6>
                    <h2 class="mb-0 text-success">₦<?= number_format($total_stats['total_revenue'], 2) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Agents</h6>
                    <h2 class="mb-0"><?= $total_stats['total_agents'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Active Agents</h6>
                    <h2 class="mb-0 text-info"><?= $total_stats['active_agents'] ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Agents List -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="h5 mb-0"><i class="fas fa-users me-2"></i>Agent SMS Credits</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($agents)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <p>No agents found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Agent</th>
                                        <th>Email</th>
                                        <th class="text-center">Current Credits</th>
                                        <th class="text-center">Total Purchases</th>
                                        <th class="text-center">Total Used</th>
                                        <th class="text-end">Total Spent</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($agents as $agent): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($agent['full_name']) ?></strong></td>
                                            <td><?= htmlspecialchars($agent['email']) ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= $agent['total_credits'] > 10 ? 'success' : ($agent['total_credits'] > 0 ? 'warning' : 'danger') ?> fs-6">
                                                    <?= $agent['total_credits'] ?>
                                                </span>
                                            </td>
                                            <td class="text-center"><?= $agent['total_purchases'] ?></td>
                                            <td class="text-center"><?= $agent['total_used'] ?></td>
                                            <td class="text-end">₦<?= number_format($agent['total_spent'], 2) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#adjustModal<?= $agent['id'] ?>">
                                                    <i class="fas fa-edit"></i> Adjust
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        <!-- Adjust Credits Modal -->
                                        <div class="modal fade" id="adjustModal<?= $agent['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Adjust Credits - <?= htmlspecialchars($agent['full_name']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="agent_id" value="<?= $agent['id'] ?>">
                                                            
                                                            <div class="alert alert-info">
                                                                Current Balance: <strong><?= $agent['total_credits'] ?> credits</strong>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Adjustment Amount</label>
                                                                <input type="number" class="form-control" name="adjustment" required
                                                                       placeholder="Enter positive to add, negative to deduct">
                                                                <div class="form-text">
                                                                    Example: 10 to add 10 credits, -5 to deduct 5 credits
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                                                <textarea class="form-control" name="description" rows="3" required
                                                                          placeholder="Reason for adjustment (e.g., Manual refund, Bonus credits, Correction)"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="adjust_credits" class="btn btn-primary">
                                                                <i class="fas fa-save me-2"></i>Save Adjustment
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h3 class="h5 mb-0"><i class="fas fa-history me-2"></i>Recent Transactions (Last 50)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_transactions)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No transactions yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Agent</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th class="text-end">Credits</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_transactions as $trans): ?>
                                        <tr>
                                            <td><?= date('M d, Y H:i', strtotime($trans['created_at'])) ?></td>
                                            <td>
                                                <small><?= htmlspecialchars($trans['full_name']) ?></small>
                                            </td>
                                            <td>
                                                <?php if ($trans['transaction_type'] == 'purchase'): ?>
                                                    <span class="badge bg-success">Purchase</span>
                                                <?php elseif ($trans['transaction_type'] == 'used'): ?>
                                                    <span class="badge bg-danger">Used</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Refund</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?= htmlspecialchars($trans['description']) ?></small></td>
                                            <td class="text-end">
                                                <?php if ($trans['transaction_type'] == 'used'): ?>
                                                    <span class="text-danger">-<?= $trans['credits'] ?></span>
                                                <?php else: ?>
                                                    <span class="text-success">+<?= $trans['credits'] ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($trans['amount_paid'] > 0): ?>
                                                    ₦<?= number_format($trans['amount_paid'], 2) ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
