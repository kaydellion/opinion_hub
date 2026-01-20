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

// Check if client wants to pay agents (price_per_response > 0)
$wants_to_pay_agents = floatval($poll['price_per_response'] ?? 0) > 0;

// Calculate costs based on payment preference
if ($wants_to_pay_agents) {
    $platform_fee = 500; // Fixed platform fee per response
    $agent_commission = 1000; // Fixed agent commission per response
    $total_per_response = $platform_fee + $agent_commission; // ₦1,500 per response
    $target_responders = intval($poll['target_responders'] ?? 100);
    $total_cost = $total_per_response * $target_responders;
} else {
    // No payment required
    $platform_fee = 0;
    $agent_commission = 0;
    $total_per_response = 0;
    $target_responders = intval($poll['target_responders'] ?? 100);
    $total_cost = 0;
}

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
                    
                    <?php if ($already_paid || !$wants_to_pay_agents): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong><?php echo $wants_to_pay_agents ? 'Payment Completed!' : 'Ready to Publish!'; ?></strong><br>
                            <?php echo $wants_to_pay_agents ? 'This poll has already been paid for.' : 'No payment required for this poll.'; ?> You can publish it now.
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
                            <?php if ($wants_to_pay_agents): ?>
                            <tr>
                                <td><strong>Platform Fee per Response:</strong></td>
                                <td class="text-end">₦<?php echo number_format($platform_fee, 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Agent Commission per Response:</strong></td>
                                <td class="text-end">₦<?php echo number_format($agent_commission, 2); ?></td>
                            </tr>
                            <tr class="table-light">
                                <td><strong>Total per Response:</strong></td>
                                <td class="text-end"><strong>₦<?php echo number_format($total_per_response, 2); ?></strong></td>
                            </tr>
                            <?php else: ?>
                            <tr class="table-light">
                                <td><strong>Payment Required:</strong></td>
                                <td class="text-end"><strong>No payment required</strong></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="table-primary">
                                <td><h5 class="mb-0">Estimated Total Cost:</h5></td>
                                <td class="text-end"><h5 class="mb-0">₦<?php echo number_format($total_cost, 2); ?></h5></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Payment Information:</strong>
                        <ul class="mb-0 mt-2">
                            <?php if ($wants_to_pay_agents): ?>
                            <li>₦500 Platform Fee covers hosting, maintenance, and poll management</li>
                            <li>₦1,000 Agent Commission is paid to agents who help collect responses</li>
                            <li>Agent commissions will be distributed as responses are completed</li>
                            <li>Once paid, your poll will be published immediately</li>
                            <li>Unused funds will be refunded if poll doesn't reach target</li>
                            <?php else: ?>
                            <li>No payment is required for this poll</li>
                            <li>You will collect responses through your own means</li>
                            <li>Your poll will be published immediately</li>
                            <?php endif; ?>
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
