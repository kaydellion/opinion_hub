<?php
require_once '../connect.php';
require_once '../functions.php';

// Check if user is logged in and is an approved agent
if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "signin.php");
    exit;
}

// Get fresh user data from database
$user = getCurrentUser();

if (!$user) {
    $_SESSION['errors'] = ["Unable to load user data. Please try logging in again."];
    header("Location: " . SITE_URL . "signin.php");
    exit;
}

if ($user['role'] !== 'agent') {
    $_SESSION['errors'] = ["Access Denied: You must be registered as an agent to access payouts. <a href='" . SITE_URL . "agent/register-agent.php'>Apply to become an agent here</a>."];
    header("Location: " . SITE_URL . "dashboard.php");
    exit;
}

if (isset($user['agent_status']) && $user['agent_status'] !== 'approved') {
    $_SESSION['errors'] = ["Access Denied: Your agent application is currently <strong>" . htmlspecialchars($user['agent_status']) . "</strong>. You'll be notified via email once approved (usually within 48 hours)."];
    header("Location: " . SITE_URL . "dashboard.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user details (already loaded in $user from checks above, but reload for safety)
// Calculate available earnings
$responses_sql = "SELECT COUNT(*) as count FROM poll_responses WHERE respondent_id = ?";
$stmt = $conn->prepare($responses_sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_responses = $stmt->get_result()->fetch_assoc()['count'];

// Get total paid already
$paid_sql = "SELECT COALESCE(SUM(amount), 0) as total_paid 
             FROM agent_payouts 
             WHERE agent_id = ? AND status = 'completed'";
$stmt = $conn->prepare($paid_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_paid = $stmt->get_result()->fetch_assoc()['total_paid'];

// Calculate available balance
$total_earned = $total_responses * 1000;
$available_balance = $total_earned - $total_paid;

// Get pending payouts
$pending_sql = "SELECT COALESCE(SUM(amount), 0) as total_pending 
                FROM agent_payouts 
                WHERE agent_id = ? AND status IN ('pending', 'processing')";
$stmt = $conn->prepare($pending_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_pending = $stmt->get_result()->fetch_assoc()['total_pending'];

$withdrawable_amount = $available_balance - $total_pending;

// Handle payment preference update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_preference'])) {
    $new_preference = sanitize($_POST['payment_preference'] ?? '');
    
    if (in_array($new_preference, ['cash', 'airtime', 'data'])) {
        $update_sql = "UPDATE users SET payment_preference = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $new_preference, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Payment preference updated successfully!";
            $user['payment_preference'] = $new_preference; // Update local copy
        } else {
            $_SESSION['errors'] = ["Failed to update preference."];
        }
    } else {
        $_SESSION['errors'] = ["Invalid payment preference."];
    }
    
    header("Location: " . SITE_URL . "agent/payouts.php");
    exit;
}

// Handle payout request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_type = sanitize($_POST['payment_type'] ?? '');
    $payment_method = sanitize($_POST['payment_method'] ?? '');
    
    $errors = [];
    
    if ($amount <= 0) {
        $errors[] = "Invalid payout amount.";
    }
    
    if ($amount > $withdrawable_amount) {
        $errors[] = "Insufficient balance. You can only withdraw ₦" . number_format($withdrawable_amount, 2);
    }
    
    if ($amount < 5000) {
        $errors[] = "Minimum payout amount is ₦5,000.";
    }
    
    if (!in_array($payment_type, ['cash', 'airtime', 'data'])) {
        $errors[] = "Invalid payment type.";
    }
    
    if (empty($errors)) {
        // Insert payout request
        $insert_sql = "INSERT INTO agent_payouts (agent_id, amount, payment_type, payment_method, status, requested_at) 
                      VALUES (?, ?, ?, ?, 'pending', NOW())";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("idss", $user_id, $amount, $payment_type, $payment_method);
        
        if ($stmt->execute()) {
            $payout_id = $conn->insert_id;
            
            // Send notification to agent
            createNotification(
                $user_id,
                'payout_requested',
                'Payout Request Submitted',
                "Your payout request of ₦" . number_format($amount, 2) . " has been submitted and is pending review.",
                'agent/payouts.php'
            );
            
            // Get agent details
            $agent_details = $conn->query("SELECT first_name, last_name, email FROM users WHERE id = $user_id")->fetch_assoc();
            
            // Send email confirmation
            sendTemplatedEmail(
                $agent_details['email'],
                $agent_details['first_name'] . ' ' . $agent_details['last_name'],
                'Payout Request Received',
                "Your payout request of ₦" . number_format($amount, 2) . " has been received and is being processed. You will be notified within 5 working days.",
                'View Status',
                SITE_URL . 'agent/payouts.php'
            );
            
            // Notify admin (get all admins)
            $admin_result = $conn->query("SELECT id, email, first_name, last_name FROM users WHERE role = 'admin'");
            while ($admin = $admin_result->fetch_assoc()) {
                createNotification(
                    $admin['id'],
                    'payout_pending',
                    'New Payout Request',
                    "Agent {$agent_details['first_name']} {$agent_details['last_name']} requested a payout of ₦" . number_format($amount, 2),
                    'admin/payouts.php'
                );
                
                sendTemplatedEmail(
                    $admin['email'],
                    $admin['first_name'] . ' ' . $admin['last_name'],
                    'New Agent Payout Request',
                    "A new payout request of ₦" . number_format($amount, 2) . " from agent {$agent_details['first_name']} {$agent_details['last_name']} requires your review.",
                    'Review Request',
                    SITE_URL . 'admin/payouts.php'
                );
            }
            
            $_SESSION['success'] = "Payout request submitted successfully! You will be notified within 5 working days.";
            header("Location: " . SITE_URL . "agent/payouts.php");
            exit;
        } else {
            $errors[] = "Failed to submit payout request. Please try again.";
        }
    }
    
    $_SESSION['errors'] = $errors;
}

$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

// Get payout history
$history_sql = "SELECT * FROM agent_payouts 
                WHERE agent_id = ? 
                ORDER BY requested_at DESC 
                LIMIT 20";
$stmt = $conn->prepare($history_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payout_history = $stmt->get_result();

$page_title = "Payout Requests";
include_once '../header.php';
?>

<div class="container py-5">
    <div class="row">
        
        <!-- Balance Summary -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-wallet"></i> Earnings Summary</h5>
                </div>
                <div class="card-body">
                    
                    <div class="mb-3">
                        <small class="text-muted">Total Earned</small>
                        <h3 class="mb-0">₦<?= number_format($total_earned, 2) ?></h3>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Total Paid</small>
                        <h4 class="mb-0 text-success">₦<?= number_format($total_paid, 2) ?></h4>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Pending Payouts</small>
                        <h4 class="mb-0 text-warning">₦<?= number_format($total_pending, 2) ?></h4>
                    </div>
                    
                    <hr>
                    
                    <div>
                        <small class="text-muted">Available to Withdraw</small>
                        <h3 class="mb-0 text-primary">₦<?= number_format($withdrawable_amount, 2) ?></h3>
                    </div>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Based on <?= $total_responses ?> completed responses @ ₦1,000 each
                        </small>
                    </div>
                    
                </div>
            </div>
            
            <!-- Payment Info -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-university"></i> Payment Details</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($user['bank_name']) && !empty($user['account_number'])): ?>
                        <p class="mb-1"><strong>Bank:</strong> <?= htmlspecialchars($user['bank_name']) ?></p>
                        <p class="mb-1"><strong>Account:</strong> <?= htmlspecialchars($user['account_name']) ?></p>
                        <p class="mb-1"><strong>Number:</strong> <?= htmlspecialchars($user['account_number']) ?></p>
                        
                        <hr class="my-3">
                        
                        <form method="POST" class="mt-3">
                            <label class="form-label fw-bold small">Payment Preference</label>
                            <select class="form-select form-select-sm" name="payment_preference" required>
                                <option value="cash" <?= ($user['payment_preference'] ?? 'cash') === 'cash' ? 'selected' : '' ?>>Bank Transfer (Cash)</option>
                                <option value="airtime" <?= ($user['payment_preference'] ?? '') === 'airtime' ? 'selected' : '' ?>>Airtime</option>
                                <option value="data" <?= ($user['payment_preference'] ?? '') === 'data' ? 'selected' : '' ?>>Data Bundle</option>
                            </select>
                            <button type="submit" name="update_preference" class="btn btn-sm btn-primary w-100 mt-2">
                                <i class="fas fa-save"></i> Update Preference
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning alert-sm mb-0">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Please <a href="<?= SITE_URL ?>profile.php">update your banking details</a> before requesting payouts.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Request Form & History -->
        <div class="col-lg-8">
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <?= htmlspecialchars($success) ?>
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
            
            <!-- Request Form -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> Request Payout</h5>
                </div>
                <div class="card-body">
                    
                    <?php if ($withdrawable_amount < 5000): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Minimum payout: ₦5,000</strong><br>
                            Continue completing polls to reach the minimum withdrawal amount. You currently have ₦<?= number_format($withdrawable_amount, 2) ?> available.
                        </div>
                    <?php elseif (empty($user['bank_name']) || empty($user['account_number'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Please <a href="<?= SITE_URL ?>profile.php" class="alert-link">complete your payment details</a> before requesting payouts.
                        </div>
                    <?php else: ?>
                        
                        <form method="POST">
                            
                            <div class="mb-3">
                                <label for="amount" class="form-label fw-bold">Payout Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₦</span>
                                    <input type="number" class="form-control" id="amount" name="amount" 
                                           min="5000" max="<?= $withdrawable_amount ?>" step="100" required>
                                </div>
                                <div class="form-text">
                                    Minimum: ₦5,000 | Maximum: ₦<?= number_format($withdrawable_amount, 2) ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="payment_type" class="form-label fw-bold">Payment Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_type" name="payment_type" required>
                                    <option value="cash" <?= ($user['payment_preference'] === 'cash') ? 'selected' : '' ?>>Bank Transfer (Cash)</option>
                                    <option value="airtime" <?= ($user['payment_preference'] === 'airtime') ? 'selected' : '' ?>>Airtime</option>
                                    <option value="data" <?= ($user['payment_preference'] === 'data') ? 'selected' : '' ?>>Data Bundle</option>
                                </select>
                                <div class="form-text">Your preferred payment type: <?= ucfirst($user['payment_preference'] ?? 'cash') ?></div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="payment_method" class="form-label fw-bold">Additional Notes (Optional)</label>
                                <textarea class="form-control" id="payment_method" name="payment_method" rows="2" 
                                          placeholder="e.g., Network preference for airtime/data, alternative contact, etc."></textarea>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-clock"></i> Payouts are processed within <strong>5 working days</strong> after approval.
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-paper-plane"></i> Submit Request
                                </button>
                                <a href="<?= SITE_URL ?>dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                            
                        </form>
                        
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <!-- Payout History -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Payout History</h5>
                </div>
                <div class="card-body">
                    
                    <?php if ($payout_history->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($payout = $payout_history->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($payout['requested_at'])) ?></td>
                                            <td><strong>₦<?= number_format($payout['amount'], 2) ?></strong></td>
                                            <td>
                                                <span class="badge bg-secondary"><?= ucfirst($payout['payment_type']) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($payout['status'] === 'completed'): ?>
                                                    <span class="badge bg-success">Completed</span>
                                                <?php elseif ($payout['status'] === 'processing'): ?>
                                                    <span class="badge bg-info">Processing</span>
                                                <?php elseif ($payout['status'] === 'failed'): ?>
                                                    <span class="badge bg-danger">Failed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($payout['reference_number']): ?>
                                                    <code><?= htmlspecialchars($payout['reference_number']) ?></code>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No payout requests yet.</p>
                    <?php endif; ?>
                    
                </div>
            </div>
            
        </div>
        
    </div>
</div>

<?php include_once '../footer.php'; ?>
