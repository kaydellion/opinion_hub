<?php
$page_title = "Subscription Plans";
include_once 'header.php';

global $conn;

// Get subscription plans
$plans = $conn->query("SELECT * FROM subscription_plans ORDER BY monthly_price ASC");
?>

<div class="container my-5">
    <div class="row mb-5">
        <div class="col-md-12 text-center">
            <h1 class="display-4 mb-3">Subscription Plans</h1>
            <p class="lead text-muted">Choose the perfect plan for your polling needs</p>
        </div>
    </div>

    <div class="row">
        <?php while ($plan = $plans->fetch_assoc()): 
            $badge_colors = [
                'free' => 'secondary',
                'basic' => 'primary',
                'classic' => 'success',
                'enterprise' => 'warning'
            ];
            $badge_color = $badge_colors[$plan['type']] ?? 'info';
        ?>
        <div class="col-md-3 mb-4">
            <div class="card h-100 border-0 shadow-lg <?php echo $plan['type'] === 'classic' ? 'border-success' : ''; ?>" 
                 style="<?php echo $plan['type'] === 'classic' ? 'border: 3px solid #28a745 !important;' : ''; ?>">
                <?php if ($plan['type'] === 'classic'): ?>
                    <div class="card-header bg-success text-white text-center">
                        <i class="fas fa-star"></i> MOST POPULAR
                    </div>
                <?php endif; ?>
                
                <div class="card-body text-center">
                    <h3 class="mb-3"><?php echo htmlspecialchars($plan['name']); ?></h3>
                    
                    <div class="mb-4">
                        <h2 class="display-4"><?php echo $plan['monthly_price'] > 0 ? formatCurrency($plan['monthly_price']) : 'FREE'; ?></h2>
                        <p class="text-muted"><?php echo $plan['monthly_price'] > 0 ? 'per month' : 'forever'; ?></p>
                        <?php if ($plan['annual_price'] > 0): ?>
                            <p class="text-success">
                                <small>Annual: <?php echo formatCurrency($plan['annual_price']); ?></small>
                            </p>
                        <?php endif; ?>
                    </div>

                    <ul class="list-unstyled text-start mb-4">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <?php echo $plan['max_polls_per_month'] == 999 ? 'Unlimited' : $plan['max_polls_per_month']; ?> polls/month
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <?php echo $plan['responses_per_poll'] == 999999 ? 'Unlimited' : number_format($plan['responses_per_poll']); ?> responses/poll
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <?php echo number_format($plan['sms_invite_units']); ?> SMS invites
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <?php echo number_format($plan['email_invite_units']); ?> Email invites
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <?php echo number_format($plan['whatsapp_invite_units']); ?> WhatsApp invites
                        </li>
                        <?php if ($plan['export_data']): ?>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Export data & screenshots
                            </li>
                        <?php endif; ?>
                        <?php if ($plan['custom_branding']): ?>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Custom branding
                            </li>
                        <?php endif; ?>
                    </ul>

                    <?php if (isLoggedIn()): ?>
                        <button class="btn btn-<?php echo $badge_color; ?> w-100" 
                                onclick="subscribePlan(<?php echo $plan['id']; ?>, <?php echo $plan['monthly_price']; ?>)">
                            <?php echo $plan['monthly_price'] > 0 ? 'Subscribe Now' : 'Get Started'; ?>
                        </button>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>register.php" class="btn btn-<?php echo $badge_color; ?> w-100">
                            Sign Up to Subscribe
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Features Comparison -->
    <div class="row mt-5">
        <div class="col-md-12">
            <h3 class="text-center mb-4">Full Features Comparison</h3>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Feature</th>
                            <th class="text-center">Free</th>
                            <th class="text-center">Basic</th>
                            <th class="text-center bg-light">Classic</th>
                            <th class="text-center">Enterprise</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Monthly Polls</td>
                            <td class="text-center">5</td>
                            <td class="text-center">50</td>
                            <td class="text-center bg-light">200</td>
                            <td class="text-center">Unlimited</td>
                        </tr>
                        <tr>
                            <td>Responses per Poll</td>
                            <td class="text-center">500</td>
                            <td class="text-center">5,000</td>
                            <td class="text-center bg-light">20,000</td>
                            <td class="text-center">Unlimited</td>
                        </tr>
                        <tr>
                            <td>Export Data</td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center bg-light"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td>Custom Branding</td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center bg-light"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td>Priority Listing</td>
                            <td class="text-center">1x</td>
                            <td class="text-center">3x</td>
                            <td class="text-center bg-light">5x</td>
                            <td class="text-center">7x</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function subscribePlan(planId, amount) {
    if (amount === 0) {
        window.location.href = '<?php echo SITE_URL; ?>actions.php?action=activate_free_plan&plan_id=' + planId;
        return;
    }
    
    // Initialize Paystack payment
    fetch('<?php echo SITE_URL; ?>actions.php?action=initialize_payment', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'amount=' + amount + '&type=subscription&plan_id=' + planId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.authorization_url;
        } else {
            alert('Payment initialization failed. Please try again.');
        }
    });
}
</script>

<?php include_once 'footer.php'; ?>
