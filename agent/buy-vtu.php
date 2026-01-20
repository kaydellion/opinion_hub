<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('agent');

$user = getCurrentUser();
$page_title = "Buy Airtime & Data";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get agent's balance (SMS credits used as wallet)
$balance_result = $conn->query("SELECT sms_balance FROM messaging_credits WHERE user_id = {$user['id']}");
$balance = 0;
if ($balance_result && $balance_row = $balance_result->fetch_assoc()) {
    $balance = $balance_row['sms_balance'] ?? 0;
}

// Get transaction history
$history_query = "SELECT * FROM agent_earnings WHERE agent_id = {$user['id']} AND earning_type LIKE '%vtu%' 
                  ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$history = $conn->query($history_query);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM agent_earnings WHERE agent_id = {$user['id']} AND earning_type LIKE '%vtu%'";
$count_result = $conn->query($count_query);
$total_count = $count_result->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total_count / $limit);

include_once '../header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-phone text-primary"></i> Buy Airtime & Data</h1>
                <a href="<?php echo SITE_URL; ?>agent/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Wallet Balance -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title text-white-50">Wallet Balance</h6>
                            <h3 class="mb-0">₦<?php echo number_format($balance, 2); ?></h3>
                            <small>Available for VTU purchases</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title mb-2">💡 How It Works</h6>
                            <p class="mb-0 small"><strong>1.</strong> Buy airtime or data bundles directly from your wallet balance</p>
                            <p class="small"><strong>2.</strong> Instant delivery to any Nigerian phone number</p>
                            <p class="small"><strong>3.</strong> Track all purchases in your history below</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs for Airtime/Data -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#airtime">
                                <i class="fas fa-phone-alt"></i> Send Airtime
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#data">
                                <i class="fas fa-wifi"></i> Send Data Bundle
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content pt-4">
                        <!-- Airtime Tab -->
                        <div id="airtime" class="tab-pane fade show active">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" id="airtime-phone" class="form-control" placeholder="2347012345678" required>
                                    <small class="text-muted">Format: 2347012345678 (without +)</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Network</label>
                                    <select id="airtime-network" class="form-select" required>
                                        <option value="">-- Select Network --</option>
                                        <option value="mtn">MTN Nigeria</option>
                                        <option value="glo">Glo Nigeria</option>
                                        <option value="airtel">Airtel Nigeria</option>
                                        <option value="etisalat">9mobile (Etisalat)</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Amount (₦)</label>
                                    <input type="number" id="airtime-amount" class="form-control" min="50" max="50000" value="500" required>
                                    <small class="text-muted">Minimum: ₦50</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-primary w-100" id="send-airtime-btn" onclick="sendAirtime()">
                                        <i class="fas fa-send"></i> Send Airtime
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Data Tab -->
                        <div id="data" class="tab-pane fade">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" id="data-phone" class="form-control" placeholder="2347012345678" required>
                                    <small class="text-muted">Format: 2347012345678 (without +)</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Data Bundle</label>
                                    <select id="data-variation-select" class="form-select" required>
                                        <option value="">-- Loading bundles... --</option>
                                    </select>
                                    <small class="text-muted">Live pricing from provider</small>
                                </div>

                                <div class="col-md-12">
                                    <div id="bundle-price" class="alert alert-info d-none">
                                        <strong>Price: <span id="bundle-amount">₦0.00</span></strong>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <button type="button" class="btn btn-primary w-100" id="send-data-btn" onclick="sendData()">
                                        <i class="fas fa-send"></i> Send Data Bundle
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Transaction History (<?php echo number_format($total_count); ?> total)</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($history && $history->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Details</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($trans = $history->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($trans['created_at'])); ?><br>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($trans['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $type_badge = strpos($trans['earning_type'], 'airtime') !== false 
                                            ? '<span class="badge bg-primary">Airtime</span>'
                                            : '<span class="badge bg-info">Data</span>';
                                        echo $type_badge;
                                        ?>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($trans['description']); ?></small>
                                    </td>
                                    <td class="fw-bold text-danger">-₦<?php echo number_format($trans['amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $trans['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($trans['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="card-footer">
                        <nav>
                            <ul class="pagination justify-content-center mb-0">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No transactions yet</p>
                        <p>Start by sending airtime or data bundles above!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Send Airtime
async function sendAirtime() {
    const phone = document.getElementById('airtime-phone').value;
    const network = document.getElementById('airtime-network').value;
    const amount = document.getElementById('airtime-amount').value;
    
    if (!phone || !network || !amount) {
        alert('Please fill all fields');
        return;
    }
    
    const btn = document.getElementById('send-airtime-btn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    try {
        const resp = await fetch('<?php echo SITE_URL; ?>actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'agent_send_airtime',
                phone: phone,
                network: network,
                amount: amount
            })
        });
        
        const data = await resp.json();
        
        if (data.success) {
            alert('✓ Airtime sent successfully!');
            document.getElementById('airtime-phone').value = '';
            document.getElementById('airtime-amount').value = '500';
            location.reload();
        } else {
            alert('✗ Error: ' + (data.message || 'Failed to send airtime'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// Send Data
async function sendData() {
    const phone = document.getElementById('data-phone').value;
    const variationCode = document.getElementById('data-variation-select').value;
    
    if (!phone || !variationCode) {
        alert('Please fill all fields');
        return;
    }
    
    const btn = document.getElementById('send-data-btn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    try {
        const resp = await fetch('<?php echo SITE_URL; ?>actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'agent_send_data',
                phone: phone,
                variation_code: variationCode
            })
        });
        
        const data = await resp.json();
        
        if (data.success) {
            alert('✓ Data bundle sent successfully!');
            document.getElementById('data-phone').value = '';
            document.getElementById('data-variation-select').value = '';
            document.getElementById('bundle-price').classList.add('d-none');
            location.reload();
        } else {
            alert('✗ Error: ' + (data.message || 'Failed to send data'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// Dynamically fetch data bundles from all networks
async function loadDataBundles() {
    const networks = [
        {label: 'MTN Data', id: 'mtn-data'},
        {label: 'Glo Data', id: 'glo-data'},
        {label: 'Airtel Data', id: 'airtel-data'},
        {label: '9mobile Data', id: 'etisalat-data'}
    ];
    
    const select = document.getElementById('data-variation-select');
    select.innerHTML = '<option value="">-- Select Bundle --</option>';
    
    for (const net of networks) {
        try {
            const resp = await fetch('<?php echo SITE_URL; ?>vtpass-data-variations.php?serviceID=' + net.id);
            if (!resp.ok) continue;
            
            const data = await resp.json();
            if (!data.variations || !Array.isArray(data.variations)) continue;
            
            const optgroup = document.createElement('optgroup');
            optgroup.label = net.label;
            
            for (const v of data.variations) {
                const option = document.createElement('option');
                option.value = v.variation_code;
                option.textContent = `${v.name} - ₦${v.variation_amount}`;
                option.dataset.amount = v.variation_amount;
                optgroup.appendChild(option);
            }
            select.appendChild(optgroup);
        } catch (err) {
            console.error('Error loading bundles for ' + net.label + ':', err);
        }
    }
}

// Show price when bundle is selected
document.getElementById('data-variation-select').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const amount = selected.dataset.amount;
    
    if (amount) {
        document.getElementById('bundle-price').classList.remove('d-none');
        document.getElementById('bundle-amount').textContent = '₦' + parseFloat(amount).toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    } else {
        document.getElementById('bundle-price').classList.add('d-none');
    }
});

// Load bundles on page load
document.addEventListener('DOMContentLoaded', loadDataBundles);
</script>

<?php include_once '../footer.php'; ?>
