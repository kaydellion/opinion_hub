<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('agent');

$user = getCurrentUser();
$page_title = "Request Payout";

// Get user earnings stats
$user_stats = $conn->query("SELECT total_earnings, pending_earnings, paid_earnings
                            FROM users WHERE id = {$user['id']}")->fetch_assoc();

// Get approved earnings from agent_earnings table
$approved_earnings_result = $conn->query("SELECT SUM(amount) as approved_total FROM agent_earnings
                                         WHERE agent_id = {$user['id']}
                                         AND earning_type != 'payout_request'
                                         AND status = 'approved'");
$approved_earnings = 0;
if ($approved_earnings_result && $approved_earnings_row = $approved_earnings_result->fetch_assoc()) {
    $approved_earnings = $approved_earnings_row['approved_total'] ?? 0;
}

// Calculate available balance (approved earnings - paid earnings - pending payout requests)
$pending_payout_total = 0;
$pending_payouts_query = $conn->query("SELECT SUM(amount) as total FROM agent_earnings
                                       WHERE agent_id = {$user['id']}
                                       AND earning_type = 'payout_request'
                                       AND status = 'pending'");
if ($pending_payouts_query && $pending_payouts_result = $pending_payouts_query->fetch_assoc()) {
    $pending_payout_total = $pending_payouts_result['total'] ?? 0;
}

$available_balance = $approved_earnings - ($user_stats['paid_earnings'] ?? 0) - $pending_payout_total;

// Minimum payout amount
define('MIN_PAYOUT', 5000);

// Get pending payout requests
$pending_payouts = $conn->query("SELECT * FROM agent_earnings
                                WHERE agent_id = {$user['id']}
                                AND earning_type = 'payout_request'
                                AND status IN ('pending', 'approved')
                                ORDER BY created_at DESC");

include_once '../header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>agent/my-earnings.php">My Earnings</a></li>
                    <li class="breadcrumb-item active">Request Payout</li>
                </ol>
            </nav>
            <h2><i class="fas fa-hand-holding-usd text-success"></i> Request Payout</h2>
            <p class="text-muted">Withdraw your approved earnings</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Payout Request Form -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);">
                    <h5 class="mb-0"><i class="fas fa-money-check-alt"></i> Payout Request Form</h5>
                </div>
                <div class="card-body">
                    <?php if ($available_balance < MIN_PAYOUT): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Minimum Payout Not Met</strong><br>
                        You need at least ₦<?php echo number_format(MIN_PAYOUT, 2); ?> to request a payout.
                        Your current available balance is ₦<?php echo number_format($available_balance, 2); ?>.
                    </div>
                    <?php else: ?>
                    <form id="payoutRequestForm">
                        <div class="mb-3">
                            <label class="form-label">Available Balance</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">₦</span>
                                <input type="text" class="form-control" value="<?php echo number_format($available_balance, 2); ?>" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payout Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" class="form-control" id="payoutAmount" name="amount" 
                                       min="<?php echo MIN_PAYOUT; ?>" 
                                       max="<?php echo $available_balance; ?>" 
                                       step="1000" 
                                       required>
                            </div>
                            <small class="text-muted">Minimum: ₦<?php echo number_format(MIN_PAYOUT, 2); ?> | Maximum: ₦<?php echo number_format($available_balance, 2); ?></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payout Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="payoutMethod" name="payout_method" required>
                                <option value="">Select payout method...</option>
                                <option value="bank_transfer" <?php echo ($user['payment_preference'] ?? '') === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                <option value="mobile_money" <?php echo ($user['payment_preference'] ?? '') === 'mobile_money' ? 'selected' : ''; ?>>Mobile Money</option>
                                <option value="airtime" <?php echo ($user['payment_preference'] ?? '') === 'airtime' ? 'selected' : ''; ?>>Airtime</option>
                                <option value="data" <?php echo ($user['payment_preference'] ?? '') === 'data' ? 'selected' : ''; ?>>Data Bundle</option>
                            </select>
                        </div>

                        <div id="bankDetails" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                                <select class="form-select" name="bank_name">
                                    <option value="">Select bank...</option>
                                    <option value="Access Bank" <?php echo ($user['bank_name'] ?? '') === 'Access Bank' ? 'selected' : ''; ?>>Access Bank</option>
                                    <option value="Zenith Bank" <?php echo ($user['bank_name'] ?? '') === 'Zenith Bank' ? 'selected' : ''; ?>>Zenith Bank</option>
                                    <option value="GTBank" <?php echo ($user['bank_name'] ?? '') === 'GTBank' ? 'selected' : ''; ?>>GTBank</option>
                                    <option value="First Bank" <?php echo ($user['bank_name'] ?? '') === 'First Bank' ? 'selected' : ''; ?>>First Bank</option>
                                    <option value="UBA" <?php echo ($user['bank_name'] ?? '') === 'UBA' ? 'selected' : ''; ?>>UBA</option>
                                    <option value="Fidelity Bank" <?php echo ($user['bank_name'] ?? '') === 'Fidelity Bank' ? 'selected' : ''; ?>>Fidelity Bank</option>
                                    <option value="Union Bank" <?php echo ($user['bank_name'] ?? '') === 'Union Bank' ? 'selected' : ''; ?>>Union Bank</option>
                                    <option value="Stanbic IBTC" <?php echo ($user['bank_name'] ?? '') === 'Stanbic IBTC' ? 'selected' : ''; ?>>Stanbic IBTC</option>
                                    <option value="Sterling Bank" <?php echo ($user['bank_name'] ?? '') === 'Sterling Bank' ? 'selected' : ''; ?>>Sterling Bank</option>
                                    <option value="Polaris Bank" <?php echo ($user['bank_name'] ?? '') === 'Polaris Bank' ? 'selected' : ''; ?>>Polaris Bank</option>
                                    <option value="Ecobank" <?php echo ($user['bank_name'] ?? '') === 'Ecobank' ? 'selected' : ''; ?>>Ecobank</option>
                                    <option value="Wema Bank" <?php echo ($user['bank_name'] ?? '') === 'Wema Bank' ? 'selected' : ''; ?>>Wema Bank</option>
                                    <option value="FCMB" <?php echo ($user['bank_name'] ?? '') === 'FCMB' ? 'selected' : ''; ?>>FCMB</option>
                                    <option value="Kuda Bank" <?php echo ($user['bank_name'] ?? '') === 'Kuda Bank' ? 'selected' : ''; ?>>Kuda Bank</option>
                                    <option value="Opay" <?php echo ($user['bank_name'] ?? '') === 'Opay' ? 'selected' : ''; ?>>Opay</option>
                                    <option value="Palmpay" <?php echo ($user['bank_name'] ?? '') === 'Palmpay' ? 'selected' : ''; ?>>Palmpay</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="account_number" value="<?php echo htmlspecialchars($user['account_number'] ?? ''); ?>" maxlength="10" pattern="[0-9]{10}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="account_name" value="<?php echo htmlspecialchars($user['account_name'] ?? ''); ?>">
                            </div>
                        </div>

                        <div id="mobileMoneyDetails" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Mobile Money Provider <span class="text-danger">*</span></label>
                                <select class="form-select" name="mobile_provider">
                                    <option value="">Select provider...</option>
                                    <option value="Opay" <?php echo (strtolower($user['mobile_money_provider'] ?? '') === 'opay') ? 'selected' : ''; ?>>Opay</option>
                                    <option value="Palmpay" <?php echo (strtolower($user['mobile_money_provider'] ?? '') === 'palmpay') ? 'selected' : ''; ?>>Palmpay</option>
                                    <option value="Paga" <?php echo (strtolower($user['mobile_money_provider'] ?? '') === 'paga') ? 'selected' : ''; ?>>Paga</option>
                                    <option value="MTN MoMo" <?php echo (strtolower($user['mobile_money_provider'] ?? '') === 'mtn') ? 'selected' : ''; ?>>MTN MoMo</option>
                                    <option value="Airtel Money" <?php echo (strtolower($user['mobile_money_provider'] ?? '') === 'airtel') ? 'selected' : ''; ?>>Airtel Money</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="mobile_number" value="<?php echo htmlspecialchars($user['mobile_money_number'] ?? ''); ?>" maxlength="11" pattern="[0-9]{11}">
                            </div>
                        </div>

                        <div id="airtimeDetails" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Network Provider <span class="text-danger">*</span></label>
                                <select class="form-select" name="airtime_network">
                                    <option value="">Select network...</option>
                                    <option value="mtn">MTN</option>
                                    <option value="airtel">Airtel</option>
                                    <option value="glo">Glo</option>
                                    <option value="etisalat">9mobile</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="airtime_number" maxlength="11" pattern="[0-9]{11}">
                            </div>
                        </div>

                        <div id="dataDetails" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Network Provider <span class="text-danger">*</span></label>
                                <select class="form-select" name="data_network" id="dataNetworkSelect" onchange="loadDataBundles()">
                                    <option value="">Select network...</option>
                                    <option value="mtn-data">MTN</option>
                                    <option value="airtel-data">Airtel</option>
                                    <option value="glo-data">Glo</option>
                                    <option value="etisalat-data">9mobile</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Data Bundle <span class="text-danger">*</span></label>
                                <select class="form-select" name="data_variation" id="dataVariationSelect">
                                    <option value="">Select network first...</option>
                                </select>
                                <small class="text-muted">Bundles load dynamically from VTPass API</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="data_number" maxlength="11" pattern="[0-9]{11}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Additional Notes (Optional)</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Any additional information..."></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <small>
                                <strong>Note:</strong> Only <strong>approved earnings</strong> can be withdrawn. Payout requests are typically processed within 24-48 hours.
                                You will receive a notification once your payout is processed.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="fas fa-paper-plane"></i> Submit Payout Request
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Balance Summary -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Balance Summary</h6>
                    <div class="mb-3">
                        <small class="text-muted">Total Earnings</small>
                        <h4>₦<?php echo number_format($user_stats['total_earnings'] ?? 0, 2); ?></h4>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Already Paid</small>
                        <h5 class="text-muted">₦<?php echo number_format($user_stats['paid_earnings'] ?? 0, 2); ?></h5>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Pending Approval</small>
                        <h5 class="text-warning">₦<?php echo number_format($user_stats['pending_earnings'] ?? 0, 2); ?></h5>
                    </div>
                    <hr>
                    <div>
                        <small class="text-muted">Available to Withdraw</small>
                        <h3 class="text-success">₦<?php echo number_format($available_balance, 2); ?></h3>
                    </div>
                </div>
            </div>

            <!-- Quick Info -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-question-circle text-primary"></i> Payout Information</h6>
                    <ul class="small mb-0">
                        <li class="mb-2">Minimum payout: ₦<?php echo number_format(MIN_PAYOUT, 2); ?></li>
                        <li class="mb-2">Processing time: 24-48 hours</li>
                        <li class="mb-2">Available methods: Bank, Mobile Money, Airtime, Data</li>
                        <li class="mb-2">No processing fees</li>
                        <li class="mb-2">Ensure your details are correct to avoid delays</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('payoutMethod')?.addEventListener('change', function() {
    // Hide all detail sections
    document.getElementById('bankDetails').style.display = 'none';
    document.getElementById('mobileMoneyDetails').style.display = 'none';
    document.getElementById('airtimeDetails').style.display = 'none';
    document.getElementById('dataDetails').style.display = 'none';
    
    // Show relevant section
    switch(this.value) {
        case 'bank_transfer':
            document.getElementById('bankDetails').style.display = 'block';
            break;
        case 'mobile_money':
            document.getElementById('mobileMoneyDetails').style.display = 'block';
            break;
        case 'airtime':
            document.getElementById('airtimeDetails').style.display = 'block';
            break;
        case 'data':
            document.getElementById('dataDetails').style.display = 'block';
            break;
    }
});

// Load data bundles dynamically from VTPass API
async function loadDataBundles() {
    const networkSelect = document.getElementById('dataNetworkSelect');
    const variationSelect = document.getElementById('dataVariationSelect');
    const serviceID = networkSelect.value;
    
    if (!serviceID) {
        variationSelect.innerHTML = '<option value="">Select network first...</option>';
        return;
    }
    
    variationSelect.innerHTML = '<option value="">Loading bundles...</option>';
    
    try {
        const response = await fetch('<?php echo SITE_URL; ?>vtpass-data-variations.php?serviceID=' + serviceID);
        const data = await response.json();
        
        if (data.variations && data.variations.length > 0) {
            variationSelect.innerHTML = '<option value="">Select data bundle...</option>';
            data.variations.forEach(function(v) {
                const option = document.createElement('option');
                option.value = v.variation_code;
                option.textContent = v.name + ' - ₦' + parseFloat(v.variation_amount).toLocaleString();
                variationSelect.appendChild(option);
            });
        } else {
            variationSelect.innerHTML = '<option value="">No bundles available</option>';
        }
    } catch (error) {
        console.error('Error loading data bundles:', error);
        variationSelect.innerHTML = '<option value="">Error loading bundles</option>';
    }
}

document.getElementById('payoutRequestForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'requestPayout');
    
    fetch('<?php echo SITE_URL; ?>actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Payout request submitted successfully!');
            window.location.href = '<?php echo SITE_URL; ?>agent/my-earnings.php';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});

// Initialize payment method display on page load (for pre-selected preference)
document.addEventListener('DOMContentLoaded', function() {
    const payoutMethodSelect = document.getElementById('payoutMethod');
    if (payoutMethodSelect && payoutMethodSelect.value) {
        // Trigger change event to show the appropriate section
        togglePaymentFields();
    }
});
</script>

<?php include_once '../footer.php'; ?>
