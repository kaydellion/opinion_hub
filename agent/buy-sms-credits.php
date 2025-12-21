<?php
require_once '../connect.php';
require_once '../functions.php';

// Check if user is logged in and is an agent
if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . 'signin.php');
    exit;
}

$current_user = getCurrentUser();
if ($current_user['role'] !== 'agent') {
    header('Location: ' . SITE_URL . 'dashboards/agent-dashboard.php');
    exit;
}

$errors = [];
$success = '';

// Define packages
$packages = [
    1 => ['credits' => 10, 'price' => 100],
    2 => ['credits' => 50, 'price' => 450],
    3 => ['credits' => 100, 'price' => 800],
    4 => ['credits' => 200, 'price' => 1500],
    5 => ['credits' => 500, 'price' => 3500],
];

// Handle success from payment callback
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = $_SESSION['success_message'] ?? 'Payment successful! Credits added to your account.';
    unset($_SESSION['success_message']);
}

// Get current credits
$current_credits = getAgentSMSCredits($_SESSION['user_id']);

// Get transaction history
$stmt = $conn->prepare("
    SELECT * FROM agent_sms_credits 
    WHERE agent_id = ? 
    ORDER BY created_at DESC 
    LIMIT 20
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Buy SMS Credits";
include '../header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= SITE_URL ?>dashboards/agent-dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Buy SMS Credits</li>
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
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center p-4">
                    <h3 class="mb-2">Current SMS Credits</h3>
                    <h1 class="display-3 text-primary mb-0">
                        <i class="fas fa-wallet"></i> <?= $current_credits ?>
                    </h1>
                    <p class="text-muted">Available Credits</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Credit Packages -->
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="mb-3"><i class="fas fa-shopping-cart me-2"></i>Choose a Package</h3>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h4 class="mb-3">Starter</h4>
                    <div class="display-4 text-primary mb-2">10</div>
                    <p class="text-muted mb-3">SMS Credits</p>
                    <h3 class="mb-3">₦100</h3>
                    <p class="text-muted small mb-3">₦10 per SMS</p>
                    <button onclick="payWithPaystack(1, 10, 100)" class="btn btn-primary">
                        <i class="fas fa-shopping-cart me-2"></i>Buy Now
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-primary">
                <div class="card-header bg-primary text-white text-center">
                    <strong>POPULAR</strong>
                </div>
                <div class="card-body text-center">
                    <h4 class="mb-3">Basic</h4>
                    <div class="display-4 text-primary mb-2">50</div>
                    <p class="text-muted mb-3">SMS Credits</p>
                    <h3 class="mb-3">₦450</h3>
                    <p class="text-muted small mb-3">₦9 per SMS <span class="badge bg-success">Save 10%</span></p>
                    <button onclick="payWithPaystack(2, 50, 450)" class="btn btn-primary">
                        <i class="fas fa-shopping-cart me-2"></i>Buy Now
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h4 class="mb-3">Standard</h4>
                    <div class="display-4 text-primary mb-2">100</div>
                    <p class="text-muted mb-3">SMS Credits</p>
                    <h3 class="mb-3">₦800</h3>
                    <p class="text-muted small mb-3">₦8 per SMS <span class="badge bg-success">Save 20%</span></p>
                    <button onclick="payWithPaystack(3, 100, 800)" class="btn btn-primary">
                        <i class="fas fa-shopping-cart me-2"></i>Buy Now
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white text-center">
                    <strong>BEST VALUE</strong>
                </div>
                <div class="card-body text-center">
                    <h4 class="mb-3">Professional</h4>
                    <div class="display-4 text-success mb-2">200</div>
                    <p class="text-muted mb-3">SMS Credits</p>
                    <h3 class="mb-3">₦1,500</h3>
                    <p class="text-muted small mb-3">₦7.50 per SMS <span class="badge bg-success">Save 25%</span></p>
                    <button onclick="payWithPaystack(4, 200, 1500)" class="btn btn-success">
                        <i class="fas fa-shopping-cart me-2"></i>Buy Now
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-warning">
                <div class="card-header bg-warning text-dark text-center">
                    <strong>BULK DISCOUNT</strong>
                </div>
                <div class="card-body text-center">
                    <h4 class="mb-3">Enterprise</h4>
                    <div class="display-4 text-warning mb-2">500</div>
                    <p class="text-muted mb-3">SMS Credits</p>
                    <h3 class="mb-3">₦3,500</h3>
                    <p class="text-muted small mb-3">₦7 per SMS <span class="badge bg-success">Save 30%</span></p>
                    <button onclick="payWithPaystack(5, 500, 3500)" class="btn btn-warning">
                        <i class="fas fa-shopping-cart me-2"></i>Buy Now
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Transaction History -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h3 class="h5 mb-0"><i class="fas fa-history me-2"></i>Transaction History</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($transactions)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No transactions yet. Purchase your first SMS credits package above!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th class="text-end">Credits</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?= date('M d, Y H:i', strtotime($transaction['created_at'])) ?></td>
                                            <td>
                                                <?php if ($transaction['transaction_type'] == 'purchase'): ?>
                                                    <span class="badge bg-success">Purchase</span>
                                                <?php elseif ($transaction['transaction_type'] == 'used'): ?>
                                                    <span class="badge bg-danger">Used</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Refund</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($transaction['description']) ?></td>
                                            <td class="text-end">
                                                <?php if ($transaction['transaction_type'] == 'purchase' || $transaction['transaction_type'] == 'refund'): ?>
                                                    <span class="text-success">+<?= $transaction['credits'] ?></span>
                                                <?php else: ?>
                                                    <span class="text-danger">-<?= abs($transaction['credits']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($transaction['amount_paid'] > 0): ?>
                                                    ₦<?= number_format($transaction['amount_paid'], 2) ?>
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
    
    <!-- Payment Integration Notice -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Secure Payment:</strong> All payments are processed securely through vPay Africa. 
                You can pay via card, bank transfer, or USSD. Credits are added automatically after successful payment.
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>

<script>
function payWithPaystack(packageId, credits, amount) {
    const reference = 'SMS_' + Date.now() + '_' + Math.floor((Math.random() * 1000000) + 1);
    
    const options = {
        amount: amount,
        currency: 'NGN',
        domain: window.VPAY_CONFIG.domain,
        key: window.VPAY_CONFIG.key,
        email: '<?php echo htmlspecialchars($current_user['email'], ENT_QUOTES); ?>',
        transactionref: reference,
        customer_logo: window.VPAY_CONFIG.customerLogo,
        customer_service_channel: window.VPAY_CONFIG.customerService,
        txn_charge: 0,
        txn_charge_type: 'flat',
        onSuccess: function(response) {
            console.log('Payment successful:', response);
            window.location.href = '<?php echo SITE_URL; ?>vpay-callback.php?reference=' + reference + '&type=sms_credits&units=' + credits + '&amount=' + amount;
        },
        onExit: function(response) {
            console.log('Payment cancelled');
        }
    };
    
    if (window.VPayDropin) {
        const { open, exit } = VPayDropin.create(options);
        open();
    } else {
        alert('Payment system not loaded. Please refresh the page and try again.');
    }
}
</script>
