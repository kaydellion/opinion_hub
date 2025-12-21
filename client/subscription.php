<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('client');

$user = getCurrentUser();

// Get current subscription
$current_sub = $conn->query("SELECT us.*, sp.* 
                             FROM user_subscriptions us 
                             JOIN subscription_plans sp ON us.plan_id = sp.id 
                             WHERE us.user_id = {$user['id']} 
                             AND us.status = 'active' 
                             AND us.end_date > NOW() 
                             ORDER BY us.end_date DESC 
                             LIMIT 1")->fetch_assoc();

// Determine billing cycle from amount paid or subscription duration
if ($current_sub) {
    // Calculate billing cycle based on end_date duration or amount_paid
    if (isset($current_sub['amount_paid'])) {
        // If amount matches monthly price, it's monthly; otherwise annual
        if ($current_sub['amount_paid'] == $current_sub['monthly_price']) {
            $current_sub['billing_cycle'] = 'monthly';
        } else {
            $current_sub['billing_cycle'] = 'annual';
        }
    } else {
        // Fallback: calculate from subscription duration
        $start = strtotime($current_sub['start_date']);
        $end = strtotime($current_sub['end_date']);
        $days_diff = ($end - $start) / (60 * 60 * 24);
        $current_sub['billing_cycle'] = $days_diff > 45 ? 'annual' : 'monthly';
    }
}

// Get all subscription plans
$plans = $conn->query("SELECT * FROM subscription_plans ORDER BY monthly_price ASC");

// Calculate usage stats
$usage = [
    'polls_this_month' => $conn->query("SELECT COUNT(*) as count FROM polls 
                                       WHERE created_by = {$user['id']} 
                                       AND MONTH(created_at) = MONTH(NOW()) 
                                       AND YEAR(created_at) = YEAR(NOW())")->fetch_assoc()['count'],
    'total_responses' => $conn->query("SELECT COUNT(DISTINCT pr.id) as count 
                                      FROM poll_responses pr 
                                      JOIN polls p ON pr.poll_id = p.id 
                                      WHERE p.created_by = {$user['id']}")->fetch_assoc()['count']
];

// Success message from payment callback
if (isset($_GET['success'])) {
    $success = $_SESSION['success_message'] ?? 'Subscription activated successfully!';
    unset($_SESSION['success_message']);
}

$success = $success ?? '';

$page_title = 'Subscription Plans';
include '../header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h2 class="mb-3"><i class="fas fa-crown me-2"></i>Choose Your Plan</h2>
            <p class="text-muted">Select the perfect plan for your polling needs</p>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?= $success ?>
        </div>
    <?php endif; ?>
    
    <?php if ($current_sub): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-primary text-white">
                        <h3 class="h5 mb-0"><i class="fas fa-star me-2"></i>Your Current Plan</h3>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <h4 class="text-primary mb-0"><?= htmlspecialchars($current_sub['name']) ?></h4>
                                <small class="text-muted">
                                    <?= isset($current_sub['billing_cycle']) && $current_sub['billing_cycle'] === 'monthly' ? 'Monthly' : 'Annual' ?>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Expires</small>
                                <strong><?= date('M d, Y', strtotime($current_sub['end_date'])) ?></strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Polls This Month</small>
                                <strong><?= $usage['polls_this_month'] ?> / <?= $current_sub['max_polls_per_month'] == 999 ? 'Unlimited' : $current_sub['max_polls_per_month'] ?></strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Total Responses</small>
                                <strong><?= number_format($usage['total_responses']) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Billing Toggle -->
    <div class="row mb-4">
        <div class="col-12 text-center">
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="billing_cycle" id="monthly" value="monthly" checked>
                <label class="btn btn-outline-primary" for="monthly">Monthly</label>
                
                <input type="radio" class="btn-check" name="billing_cycle" id="annual" value="annual">
                <label class="btn btn-outline-primary" for="annual">Annual <span class="badge bg-success ms-1">Save 15%</span></label>
            </div>
        </div>
    </div>
    
    <!-- Subscription Plans -->
    <div class="row" id="plans-container">
        <?php while ($plan = $plans->fetch_assoc()): ?>
            <?php
            $is_current = $current_sub && $current_sub['plan_id'] == $plan['id'];
            $is_free = $plan['type'] === 'free';
            $is_popular = $plan['type'] === 'basic';
            $is_best = $plan['type'] === 'enterprise';
            ?>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card shadow-sm h-100 <?= $is_popular ? 'border-primary' : '' ?> <?= $is_current ? 'border-success' : '' ?>">
                    <?php if ($is_popular): ?>
                        <div class="card-header bg-primary text-white text-center">
                            <strong><i class="fas fa-star me-1"></i>POPULAR</strong>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($is_best): ?>
                        <div class="card-header bg-warning text-dark text-center">
                            <strong><i class="fas fa-crown me-1"></i>BEST VALUE</strong>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($is_current): ?>
                        <div class="card-header bg-success text-white text-center">
                            <strong><i class="fas fa-check-circle me-1"></i>CURRENT PLAN</strong>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body text-center">
                        <h4 class="mb-3"><?= htmlspecialchars($plan['name']) ?></h4>
                        
                        <div class="price-monthly" style="display: block;">
                            <h2 class="text-primary mb-0">
                                <?= $is_free ? 'Free' : formatCurrency($plan['monthly_price']) ?>
                            </h2>
                            <small class="text-muted"><?= $is_free ? 'Forever' : 'per month' ?></small>
                        </div>
                        
                        <div class="price-annual" style="display: none;">
                            <h2 class="text-primary mb-0">
                                <?= $is_free ? 'Free' : formatCurrency($plan['annual_price']) ?>
                            </h2>
                            <small class="text-muted"><?= $is_free ? 'Forever' : 'per year' ?></small>
                        </div>
                        
                        <hr>
                        
                        <ul class="list-unstyled text-start small">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong><?= $plan['max_polls_per_month'] == 999 ? 'Unlimited' : $plan['max_polls_per_month'] ?></strong> polls/month
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong><?= number_format($plan['responses_per_poll']) ?></strong> responses/poll
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-<?= $plan['export_data'] ? 'check text-success' : 'times text-muted' ?> me-2"></i>
                                Export data
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong><?= number_format($plan['sms_invite_units']) ?></strong> free SMS
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong><?= number_format($plan['email_invite_units']) ?></strong> free emails
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-<?= $plan['custom_branding'] ? 'check text-success' : 'times text-muted' ?> me-2"></i>
                                Custom branding
                            </li>
                        </ul>
                        
                        <?php if ($is_free): ?>
                            <button class="btn btn-outline-secondary w-100" disabled>
                                Default Plan
                            </button>
                        <?php elseif ($is_current): ?>
                            <button class="btn btn-success w-100" disabled>
                                <i class="fas fa-check me-2"></i>Active
                            </button>
                        <?php else: ?>
                            <button onclick="subscribe(<?= $plan['id'] ?>, '<?= htmlspecialchars($plan['name']) ?>', 'monthly')" 
                                    class="btn btn-primary w-100 subscribe-btn-monthly" 
                                    data-plan-id="<?= $plan['id'] ?>"
                                    data-monthly-price="<?= $plan['monthly_price'] ?>"
                                    data-annual-price="<?= $plan['annual_price'] ?>">
                                <i class="fas fa-shopping-cart me-2"></i>Subscribe
                            </button>
                            <button onclick="subscribe(<?= $plan['id'] ?>, '<?= htmlspecialchars($plan['name']) ?>', 'annual')" 
                                    class="btn btn-primary w-100 subscribe-btn-annual" 
                                    data-plan-id="<?= $plan['id'] ?>"
                                    data-monthly-price="<?= $plan['monthly_price'] ?>"
                                    data-annual-price="<?= $plan['annual_price'] ?>"
                                    style="display: none;">
                                <i class="fas fa-shopping-cart me-2"></i>Subscribe
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    
    <!-- Features Comparison -->
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="text-center mb-4">Compare Plans</h3>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Feature</th>
                            <th>Free</th>
                            <th>Basic</th>
                            <th>Classic</th>
                            <th>Enterprise</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $plans->data_seek(0);
                        $plans_array = [];
                        while ($p = $plans->fetch_assoc()) {
                            $plans_array[$p['type']] = $p;
                        }
                        ?>
                        <tr>
                            <td><strong>Polls per Month</strong></td>
                            <td><?= $plans_array['free']['max_polls_per_month'] ?></td>
                            <td><?= $plans_array['basic']['max_polls_per_month'] ?></td>
                            <td><?= $plans_array['classic']['max_polls_per_month'] ?></td>
                            <td>Unlimited</td>
                        </tr>
                        <tr>
                            <td><strong>Responses per Poll</strong></td>
                            <td><?= number_format($plans_array['free']['responses_per_poll']) ?></td>
                            <td><?= number_format($plans_array['basic']['responses_per_poll']) ?></td>
                            <td><?= number_format($plans_array['classic']['responses_per_poll']) ?></td>
                            <td>Unlimited</td>
                        </tr>
                        <tr>
                            <td><strong>Free SMS Invites</strong></td>
                            <td>-</td>
                            <td><?= number_format($plans_array['basic']['sms_invite_units']) ?></td>
                            <td><?= number_format($plans_array['classic']['sms_invite_units']) ?></td>
                            <td><?= number_format($plans_array['enterprise']['sms_invite_units']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Free Email Invites</strong></td>
                            <td>-</td>
                            <td><?= number_format($plans_array['basic']['email_invite_units']) ?></td>
                            <td><?= number_format($plans_array['classic']['email_invite_units']) ?></td>
                            <td><?= number_format($plans_array['enterprise']['email_invite_units']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Export Data</strong></td>
                            <td><i class="fas fa-times text-danger"></i></td>
                            <td><i class="fas fa-times text-danger"></i></td>
                            <td><i class="fas fa-times text-danger"></i></td>
                            <td><i class="fas fa-times text-danger"></i></td>
                        </tr>
                        <tr>
                            <td><strong>Custom Branding</strong></td>
                            <td><i class="fas fa-times text-danger"></i></td>
                            <td><i class="fas fa-times text-danger"></i></td>
                            <td><i class="fas fa-times text-danger"></i></td>
                            <td><i class="fas fa-times text-danger"></i></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>

<script>
// Toggle billing cycle
document.querySelectorAll('input[name="billing_cycle"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const isAnnual = this.value === 'annual';
        
        // Toggle price display
        document.querySelectorAll('.price-monthly').forEach(el => el.style.display = isAnnual ? 'none' : 'block');
        document.querySelectorAll('.price-annual').forEach(el => el.style.display = isAnnual ? 'block' : 'none');
        
        // Toggle buttons
        document.querySelectorAll('.subscribe-btn-monthly').forEach(el => el.style.display = isAnnual ? 'none' : 'block');
        document.querySelectorAll('.subscribe-btn-annual').forEach(el => el.style.display = isAnnual ? 'block' : 'none');
    });
});

function subscribe(planId, planName, billingCycle) {
    const button = document.querySelector(`[data-plan-id="${planId}"]`);
    const amount = billingCycle === 'monthly' ? 
                   parseFloat(button.dataset.monthlyPrice) : 
                   parseFloat(button.dataset.annualPrice);
    
    if (amount === 0) {
        alert('This is the free plan');
        return;
    }
    
    const reference = 'SUB_' + Date.now() + '_' + Math.floor((Math.random() * 1000000) + 1);
    
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
            window.location.href = '<?php echo SITE_URL; ?>vpay-callback.php?reference=' + reference + '&type=subscription&plan_id=' + planId + '&billing_cycle=' + billingCycle + '&amount=' + amount;
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
