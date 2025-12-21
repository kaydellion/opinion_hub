<?php
/**
 * pay-for-ad.php - Payment page for advertisement
 */

require_once '../connect.php';
require_once '../functions.php';

// Require login
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$ad_id = isset($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;

if (!$ad_id) {
    $_SESSION['error'] = "Invalid advertisement ID.";
    header('Location: ' . SITE_URL . 'client/my-ads.php');
    exit;
}

// Get advertisement details
$stmt = $conn->prepare("SELECT * FROM advertisements WHERE id = ? AND advertiser_id = ?");
$stmt->bind_param('ii', $ad_id, $user['id']);
$stmt->execute();
$ad = $stmt->get_result()->fetch_assoc();

if (!$ad) {
    $_SESSION['error'] = "Advertisement not found or you don't have permission to access it.";
    header('Location: ' . SITE_URL . 'client/my-ads.php');
    exit;
}

// Check if already paid
if ($ad['amount_paid'] > 0 && $ad['status'] !== 'pending') {
    $_SESSION['error'] = "This advertisement has already been paid for.";
    header('Location: ' . SITE_URL . 'client/my-ads.php');
    exit;
}

$page_title = "Pay for Advertisement";
include_once '../header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-credit-card"></i> Pay for Advertisement</h4>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Complete payment to submit your advertisement for admin approval.
                    </div>

                    <h5 class="mb-3">Advertisement Details</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Title:</strong><br>
                            <?= htmlspecialchars($ad['title']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Placement:</strong><br>
                            <span class="badge bg-info"><?= htmlspecialchars($ad['placement']) ?></span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Ad Size:</strong><br>
                            <?= htmlspecialchars($ad['ad_size']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Duration:</strong><br>
                            <?= date('M d, Y', strtotime($ad['start_date'])) ?> - 
                            <?= date('M d, Y', strtotime($ad['end_date'])) ?>
                            (<?= ceil((strtotime($ad['end_date']) - strtotime($ad['start_date'])) / 86400) ?> days)
                        </div>
                    </div>

                    <?php if ($ad['image_url']): ?>
                        <div class="mb-3">
                            <strong>Advertisement Preview:</strong><br>
                            <img src="<?= SITE_URL . $ad['image_url'] ?>" class="img-fluid mt-2" 
                                 style="max-width: 400px; border: 1px solid #ddd; border-radius: 8px;">
                        </div>
                    <?php endif; ?>

                    <hr>

                    <div class="mb-4">
                        <h3 class="text-primary">Amount to Pay: ₦<?= number_format($ad['amount_paid'], 2) ?></h3>
                        <small class="text-muted">Payment will be processed securely via vPay Africa</small>
                    </div>

                    <button id="payButton" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-lock"></i> Pay ₦<?= number_format($ad['amount_paid'], 2) ?> Now
                    </button>

                    <div class="mt-3 text-center">
                        <a href="<?= SITE_URL ?>client/my-ads.php" class="btn btn-link">
                            <i class="fas fa-arrow-left"></i> Back to My Ads
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('payButton').addEventListener('click', function() {
    const amount = <?= $ad['amount_paid'] ?>;
    const email = '<?= $user['email'] ?>';
    const adId = <?= $ad['id'] ?>;
    const reference = 'AD_' + adId + '_' + Date.now() + '_' + Math.floor((Math.random() * 1000000) + 1);
    
    const options = {
        amount: amount,
        currency: 'NGN',
        domain: window.VPAY_CONFIG.domain,
        key: window.VPAY_CONFIG.key,
        email: email,
        transactionref: reference,
        customer_logo: window.VPAY_CONFIG.customerLogo,
        customer_service_channel: window.VPAY_CONFIG.customerService,
        txn_charge: 0,
        txn_charge_type: 'flat',
        onSuccess: function(response) {
            console.log('Payment successful:', response);
            window.location.href = '<?= SITE_URL ?>client/ad-payment-callback.php?reference=' + reference + '&ad_id=' + adId;
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
});
</script>

<?php include_once '../footer.php'; ?>
