<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('client');
$user = getCurrentUser();
$page_title = "Pay for Poll";

$poll_id = (int)($_GET['id'] ?? 0);

// Get poll details
$poll = $conn->query("SELECT * FROM polls WHERE id = $poll_id AND created_by = {$user['id']}")->fetch_assoc();
if (!$poll) {
    die('Poll not found');
}

// Calculate costs
$price_per_response = floatval($poll['price_per_response'] ?? 100);
$target_responders = intval($poll['target_responders'] ?? 100);
$agent_total = $price_per_response * $target_responders;

// Get admin commission percentage
$admin_commission_percent = floatval(getSetting('admin_commission_percent', '10'));
$admin_commission = ($agent_total * $admin_commission_percent) / 100;
$total_cost = $agent_total + $admin_commission;

// Check if already paid
$already_paid = $conn->query("SELECT id FROM transactions 
                               WHERE user_id = {$user['id']} 
                               AND poll_id = $poll_id 
                               AND transaction_type = 'poll_payment' 
                               AND status = 'completed'")->num_rows > 0;

include_once '../header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-credit-card"></i> Pay for Poll Publication</h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($already_paid): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>Payment Completed!</strong><br>
                            This poll has already been paid for. You can publish it now.
                        </div>
                        <a href="<?php echo SITE_URL; ?>actions.php?action=publish_poll&poll_id=<?php echo $poll_id; ?>" class="btn btn-success">
                            <i class="fas fa-rocket"></i> Publish Poll Now
                        </a>
                    <?php else: ?>
                    
                    <h5><?php echo htmlspecialchars($poll['title']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars(substr($poll['description'], 0, 150)); ?>...</p>
                    
                    <hr>
                    
                    <h6 class="text-primary">Payment Breakdown</h6>
                    
                    <table class="table">
                        <tbody>
                            <tr>
                                <td><strong>Target Responders:</strong></td>
                                <td class="text-end"><?php echo number_format($target_responders); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Payment per Response:</strong></td>
                                <td class="text-end">₦<?php echo number_format($price_per_response, 2); ?></td>
                            </tr>
                            <tr class="table-light">
                                <td><strong>Agent Payments Total:</strong></td>
                                <td class="text-end"><strong>₦<?php echo number_format($agent_total, 2); ?></strong></td>
                            </tr>
                            <tr>
                                <td><strong>Platform Commission (<?php echo $admin_commission_percent; ?>%):</strong></td>
                                <td class="text-end">₦<?php echo number_format($admin_commission, 2); ?></td>
                            </tr>
                            <tr class="table-primary">
                                <td><h5 class="mb-0">Total Amount:</h5></td>
                                <td class="text-end"><h5 class="mb-0">₦<?php echo number_format($total_cost, 2); ?></h5></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Payment Information:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Agent payments will be distributed as responses are completed</li>
                            <li>Platform commission covers hosting, maintenance, and agent management</li>
                            <li>Once paid, your poll will be published immediately</li>
                            <li>Unused credits will be refunded if poll doesn't reach target</li>
                        </ul>
                    </div>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary btn-lg" id="payButton">
                            <i class="fas fa-lock"></i> Pay ₦<?php echo number_format($total_cost, 2); ?> with VPay
                        </button>
                        <a href="<?php echo SITE_URL; ?>client/review-poll.php?id=<?php echo $poll_id; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Review
                        </a>
                    </div>
                    
                    <?php endif; ?>
                    
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include_once '../footer.php'; ?>

<script>
document.getElementById('payButton')?.addEventListener('click', function() {
    const amount = <?php echo $total_cost; ?>;
    const pollId = <?php echo $poll_id; ?>;
    const reference = 'POLL_' + pollId + '_' + Date.now() + '_' + Math.floor((Math.random() * 1000000) + 1);
    
    if (!window.VPayDropin) {
        alert('Payment system not loaded. Please refresh the page and try again.');
        return;
    }
    
    const options = {
        amount: amount,
        currency: 'NGN',
        domain: window.VPAY_CONFIG.domain,
        key: window.VPAY_CONFIG.key,
        email: '<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>',
        transactionref: reference,
        customer_logo: window.VPAY_CONFIG.customerLogo,
        customer_service_channel: window.VPAY_CONFIG.customerService,
        txn_charge: 0,
        txn_charge_type: 'flat',
        onSuccess: function(response) {
            console.log('Payment successful:', response);
            window.location.href = '<?php echo SITE_URL; ?>vpay-callback.php?reference=' + reference + '&type=poll_payment&poll_id=' + pollId + '&amount=' + amount;
        },
        onExit: function(response) {
            console.log('Payment cancelled');
        }
    };
    
    const { open, exit } = VPayDropin.create(options);
    open();
});
</script>
