<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('client');

$user = getCurrentUser();
$credits = getMessagingCredits($user['id']);

// Success message from payment callback
if (isset($_GET['success'])) {
    $success = $_SESSION['success_message'] ?? 'Payment successful! Credits added to your account.';
    unset($_SESSION['success_message']);
}

$success = $success ?? '';
$error = '';

// Credit packages
$packages = [
    'sms' => [
        ['units' => 100, 'price' => 1200, 'per_unit' => 12],
        ['units' => 500, 'price' => 5000, 'per_unit' => 10],
        ['units' => 1000, 'price' => 9000, 'per_unit' => 9],
        ['units' => 5000, 'price' => 40000, 'per_unit' => 8],
    ],
    'email' => [
        ['units' => 100, 'price' => 800, 'per_unit' => 8],
        ['units' => 500, 'price' => 3000, 'per_unit' => 6],
        ['units' => 1000, 'price' => 5000, 'per_unit' => 5],
        ['units' => 5000, 'price' => 20000, 'per_unit' => 4],
    ],
    'whatsapp' => [
        ['units' => 100, 'price' => 800, 'per_unit' => 8],
        ['units' => 500, 'price' => 3000, 'per_unit' => 6],
        ['units' => 1000, 'price' => 5000, 'per_unit' => 5],
        ['units' => 5000, 'price' => 20000, 'per_unit' => 4],
    ]
];

$page_title = 'Buy Credits';
include '../header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-3">Buy Messaging Credits</h2>
            <p class="text-muted">Purchase SMS, Email, or WhatsApp credits to send messages to your audience.</p>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?= $success ?>
        </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-sms fa-3x text-primary mb-3"></i>
                    <h4>SMS Credits</h4>
                    <h2 class="text-primary"><?= number_format($credits['sms_balance'] ?? 0) ?></h2>
                    <small class="text-muted">Available</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-envelope fa-3x text-info mb-3"></i>
                    <h4>Email Credits</h4>
                    <h2 class="text-info"><?= number_format($credits['email_balance'] ?? 0) ?></h2>
                    <small class="text-muted">Available</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="fab fa-whatsapp fa-3x text-success mb-3"></i>
                    <h4>WhatsApp Credits</h4>
                    <h2 class="text-success"><?= number_format($credits['whatsapp_balance'] ?? 0) ?></h2>
                    <small class="text-muted">Available</small>
                </div>
            </div>
        </div>
    </div>
    
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sms-tab">
                <i class="fas fa-sms me-2"></i>SMS Credits
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#email-tab">
                <i class="fas fa-envelope me-2"></i>Email Credits
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#whatsapp-tab">
                <i class="fab fa-whatsapp me-2"></i>WhatsApp Credits
            </button>
        </li>
    </ul>
    
    <div class="tab-content">
        <!-- SMS Tab -->
        <div class="tab-pane fade show active" id="sms-tab">
            <div class="row">
                <?php foreach ($packages['sms'] as $pkg): ?>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body text-center">
                                <h3 class="text-primary"><?= number_format($pkg['units']) ?></h3>
                                <p class="text-muted mb-3">SMS Credits</p>
                                <h2 class="mb-3"><?= formatCurrency($pkg['price']) ?></h2>
                                <p class="small text-muted">₦<?= $pkg['per_unit'] ?> per SMS</p>
                                <button onclick="buyCredits('sms', <?= $pkg['units'] ?>, <?= $pkg['price'] ?>)" class="btn btn-primary w-100">
                                    <i class="fas fa-shopping-cart me-2"></i>Buy Now
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Email Tab -->
        <div class="tab-pane fade" id="email-tab">
            <div class="row">
                <?php foreach ($packages['email'] as $pkg): ?>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body text-center">
                                <h3 class="text-info"><?= number_format($pkg['units']) ?></h3>
                                <p class="text-muted mb-3">Email Credits</p>
                                <h2 class="mb-3"><?= formatCurrency($pkg['price']) ?></h2>
                                <p class="small text-muted">₦<?= $pkg['per_unit'] ?> per email</p>
                                <button onclick="buyCredits('email', <?= $pkg['units'] ?>, <?= $pkg['price'] ?>)" class="btn btn-info w-100">
                                    <i class="fas fa-shopping-cart me-2"></i>Buy Now
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- WhatsApp Tab -->
        <div class="tab-pane fade" id="whatsapp-tab">
            <div class="row">
                <?php foreach ($packages['whatsapp'] as $pkg): ?>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body text-center">
                                <h3 class="text-success"><?= number_format($pkg['units']) ?></h3>
                                <p class="text-muted mb-3">WhatsApp Credits</p>
                                <h2 class="mb-3"><?= formatCurrency($pkg['price']) ?></h2>
                                <p class="small text-muted">₦<?= $pkg['per_unit'] ?> per message</p>
                                <button onclick="buyCredits('whatsapp', <?= $pkg['units'] ?>, <?= $pkg['price'] ?>)" class="btn btn-success w-100">
                                    <i class="fas fa-shopping-cart me-2"></i>Buy Now
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info mt-4">
        <h5><i class="fas fa-info-circle me-2"></i>Payment Information</h5>
        <ul class="mb-0">
            <li>Payments are processed securely via vPay Africa</li>
            <li>Credits are added instantly after successful payment</li>
            <li>All prices are in Nigerian Naira (₦)</li>
            <li>Bulk packages offer better value per credit</li>
        </ul>
    </div>
</div>

<?php include '../footer.php'; ?>

<script>
function buyCredits(creditType, units, amount) {
    const creditLabel = creditType.toUpperCase();
    const reference = creditType.toUpperCase() + '_' + Date.now() + '_' + Math.floor((Math.random() * 1000000) + 1);
    
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
            window.location.href = '<?php echo SITE_URL; ?>vpay-callback.php?reference=' + reference + '&type=' + creditType + '_credits&units=' + units + '&amount=' + amount;
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
