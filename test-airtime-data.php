<?php
require_once 'connect.php';
require_once 'functions.php';

// Only allow admin access
if (!isLoggedIn() || !checkRole('admin')) {
    die("Admin access required");
}

$result_message = '';
$result_type = '';
$result_data = null;
$log_file = __DIR__ . '/uploads/vtpass_request_ids.log';

// Create log file directory if it doesn't exist
if (!is_dir(__DIR__ . '/uploads/')) {
    mkdir(__DIR__ . '/uploads/', 0755, true);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_type = $_POST['test_type'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    if ($test_type === 'airtime') {
        $network = $_POST['network'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        
        $result_data = vtpass_send_airtime($phone, $network, $amount);
        
        if ($result_data['success']) {
            $result_type = 'success';
            $result_message = "✓ Airtime sent successfully!";
            
            // Log the request ID
            $request_id = $result_data['request_id'] ?? $result_data['requestId'] ?? 'N/A';
            $log_entry = sprintf(
                "[%s] TYPE: AIRTIME | NETWORK: %s | AMOUNT: ₦%s | PHONE: %s | REQUEST_ID: %s | STATUS: SUCCESS\n",
                date('Y-m-d H:i:s'),
                $network,
                $amount,
                $phone,
                $request_id
            );
            file_put_contents($log_file, $log_entry, FILE_APPEND);
        } else {
            $result_type = 'error';
            $result_message = "✗ Airtime failed: " . ($result_data['message'] ?? 'Unknown error');
            
            // Log the failed request
            $log_entry = sprintf(
                "[%s] TYPE: AIRTIME | NETWORK: %s | AMOUNT: ₦%s | PHONE: %s | STATUS: FAILED | ERROR: %s\n",
                date('Y-m-d H:i:s'),
                $network,
                $amount,
                $phone,
                $result_data['message'] ?? 'Unknown error'
            );
            file_put_contents($log_file, $log_entry, FILE_APPEND);
        }
        
    } elseif ($test_type === 'data') {
        $variation_code = $_POST['variation_code'] ?? '';
        
        $result_data = vtpass_send_data($phone, $variation_code);
        
        if ($result_data['success']) {
            $result_type = 'success';
            $result_message = "✓ Data bundle sent successfully!";
            
            // Log the request ID
            $request_id = $result_data['request_id'] ?? $result_data['requestId'] ?? 'N/A';
            $log_entry = sprintf(
                "[%s] TYPE: DATA | VARIATION: %s | PHONE: %s | REQUEST_ID: %s | STATUS: SUCCESS\n",
                date('Y-m-d H:i:s'),
                $variation_code,
                $phone,
                $request_id
            );
            file_put_contents($log_file, $log_entry, FILE_APPEND);
        } else {
            $result_type = 'error';
            $result_message = "✗ Data failed: " . ($result_data['message'] ?? 'Unknown error');
            
            // Log the failed request
            $log_entry = sprintf(
                "[%s] TYPE: DATA | VARIATION: %s | PHONE: %s | STATUS: FAILED | ERROR: %s\n",
                date('Y-m-d H:i:s'),
                $variation_code,
                $phone,
                $result_data['message'] ?? 'Unknown error'
            );
            file_put_contents($log_file, $log_entry, FILE_APPEND);
        }
    }
}

// Get logs
$logs = [];
if (file_exists($log_file)) {
    $log_contents = file_get_contents($log_file);
    $logs = array_reverse(array_filter(explode("\n", $log_contents)));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>VTPass Airtime/Data Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .form-section { background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border: 1px solid #c3e6cb; border-radius: 4px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border: 1px solid #f5c6cb; border-radius: 4px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; margin: 10px 0; border: 1px solid #bee5eb; border-radius: 4px; }
        pre { background: #f8f9fa; padding: 10px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab { padding: 10px 20px; background: #e9ecef; border: none; cursor: pointer; border-radius: 4px; }
        .tab.active { background: #007bff; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>

<h1>🧪 VTPass Airtime/Data Testing</h1>
<p><a href="test-vtpass.php">← Back to Configuration Test</a> | <a href="admin/settings.php">← Admin Settings</a></p>
<hr>

<?php if (!VTPASS_ENABLED): ?>
    <div class="error">
        <strong>⚠ VTPass is disabled!</strong><br>
        Enable it in <a href="admin/settings.php">Admin Settings</a> first.
    </div>
<?php endif; ?>

<?php if ($result_message): ?>
    <div class="<?= $result_type ?>">
        <?= htmlspecialchars($result_message) ?>
    </div>
    
    <?php if ($result_data): ?>
        <details>
            <summary style="cursor: pointer; font-weight: bold;">View Full Response</summary>
            <pre><?php print_r($result_data); ?></pre>
        </details>
    <?php endif; ?>
<?php endif; ?>

<div class="info">
    <strong>ℹ️ Testing Tips:</strong><br>
    • Use your own phone number for testing<br>
    • Start with small amounts (₦50-100 for airtime)<br>
    • Sandbox mode may return mock responses<br>
    • Check your phone to confirm actual delivery<br>
    • Balance: ₦<?= number_format(1899837, 2) ?> (from earlier test)
</div>

<div class="tabs">
    <button class="tab active" onclick="showTab('airtime')">Send Airtime</button>
    <button class="tab" onclick="showTab('data')">Send Data Bundle</button>
</div>

<!-- Airtime Form -->
<div id="airtime" class="tab-content active">
    <div class="form-section">
        <h2>📱 Send Airtime</h2>
        <form method="POST">
            <input type="hidden" name="test_type" value="airtime">
            
            <div class="form-group">
                <label>Phone Number:</label>
                <input type="tel" name="phone" placeholder="2347012345678" required>
                <small>Format: 2347012345678 (without +)</small>
            </div>
            
            <div class="form-group">
                <label>Network:</label>
                <select name="network" required>
                    <option value="">-- Select Network --</option>
                    <option value="mtn">MTN Nigeria</option>
                    <option value="glo">Glo Nigeria</option>
                    <option value="airtel">Airtel Nigeria</option>
                    <option value="etisalat">9mobile (Etisalat)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Amount (₦):</label>
                <input type="number" name="amount" min="50" max="10000" value="100" required>
                <small>Minimum: ₦50, Maximum: ₦10,000 (for testing)</small>
            </div>
            
            <button type="submit">Send Airtime</button>
        </form>
    </div>
</div>

<!-- Data Form -->
<div id="data" class="tab-content">
    <div class="form-section">
        <h2>📶 Send Data Bundle</h2>
        <form method="POST">
            <input type="hidden" name="test_type" value="data">
            
            <div class="form-group">
                <label>Phone Number:</label>
                <input type="tel" name="phone" placeholder="2347012345678" required>
                <small>Format: 2347012345678 (without +)</small>
            </div>
            
            <div class="form-group">
                    <label>Data Bundle:</label>
                    <select name="variation_code" id="data-variation-select" required>
                        <option value="">-- Select Bundle --</option>
                    </select>
                    <small>Note: Data bundles are loaded live from VTPass API for accuracy.</small>
                    <script>
                    // Dynamically fetch VTPass data bundles
                    async function fetchVTPassDataBundles() {
                        const networks = [
                            {label: 'MTN Data', id: 'mtn-data'},
                            {label: 'Glo Data', id: 'glo-data'},
                            {label: 'Airtel Data', id: 'airtel-data'},
                            {label: '9mobile Data', id: 'etisalat-data'}
                        ];
                        const select = document.getElementById('data-variation-select');
                        select.innerHTML = '<option value="">-- Select Bundle --</option>';
                        for (const net of networks) {
                            // Use a backend endpoint to fetch variations securely
                            const resp = await fetch('vtpass-data-variations.php?serviceID=' + net.id);
                            if (!resp.ok) continue;
                            const data = await resp.json();
                            if (!data.variations || !Array.isArray(data.variations)) continue;
                            const optgroup = document.createElement('optgroup');
                            optgroup.label = net.label;
                            for (const v of data.variations) {
                                const option = document.createElement('option');
                                option.value = v.variation_code;
                                option.textContent = `${v.name} - ₦${v.variation_amount}`;
                                optgroup.appendChild(option);
                            }
                            select.appendChild(optgroup);
                        }
                    }
                    document.addEventListener('DOMContentLoaded', fetchVTPassDataBundles);
                    </script>
            </div>
            
            <button type="submit">Send Data Bundle</button>
        </form>
    </div>
</div>

<hr>

<h2>📋 VTPass Request ID Log</h2>
<div class="info">
    <strong>All successful transactions are logged with their Request IDs below:</strong>
</div>

<?php if (count($logs) > 0): ?>
    <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0;">
        <h3 style="margin-top: 0;">Recent Transactions (Latest First):</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #e9ecef; border-bottom: 2px solid #dee2e6;">
                    <th style="padding: 10px; text-align: left; border: 1px solid #dee2e6;">Timestamp</th>
                    <th style="padding: 10px; text-align: left; border: 1px solid #dee2e6;">Type</th>
                    <th style="padding: 10px; text-align: left; border: 1px solid #dee2e6;">Details</th>
                    <th style="padding: 10px; text-align: left; border: 1px solid #dee2e6;">Request ID</th>
                    <th style="padding: 10px; text-align: left; border: 1px solid #dee2e6;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <?php
                    // Parse log entry
                    preg_match('/\[(.*?)\]/', $log, $timestamp);
                    preg_match('/TYPE: (\w+)/', $log, $type);
                    preg_match('/REQUEST_ID: ([^\|]+)/', $log, $request_id);
                    preg_match('/STATUS: (\w+)/', $log, $status);
                    
                    $log_timestamp = $timestamp[1] ?? 'N/A';
                    $log_type = $type[1] ?? 'N/A';
                    $log_request_id = trim($request_id[1] ?? 'N/A');
                    $log_status = $status[1] ?? 'N/A';
                    
                    // Extract additional details
                    $details = '';
                    if ($log_type === 'AIRTIME') {
                        preg_match('/NETWORK: (\w+)/', $log, $network);
                        preg_match('/AMOUNT: ₦([^|]+)/', $log, $amount);
                        $details = ($network[1] ?? 'N/A') . ' - ₦' . ($amount[1] ?? 'N/A');
                    } elseif ($log_type === 'DATA') {
                        preg_match('/VARIATION: ([^\|]+)/', $log, $variation);
                        $details = trim($variation[1] ?? 'N/A');
                    }
                    
                    $status_color = ($log_status === 'SUCCESS') ? '#28a745' : '#dc3545';
                    ?>
                    <tr style="border-bottom: 1px solid #dee2e6;">
                        <td style="padding: 10px; border: 1px solid #dee2e6; font-size: 12px;"><?= htmlspecialchars($log_timestamp) ?></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;">
                            <strong><?= htmlspecialchars($log_type) ?></strong>
                        </td>
                        <td style="padding: 10px; border: 1px solid #dee2e6; font-size: 12px;"><?= htmlspecialchars($details) ?></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6; font-weight: bold; font-family: monospace; font-size: 11px;">
                            <code><?= htmlspecialchars($log_request_id) ?></code>
                        </td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;">
                            <span style="color: <?= $status_color ?>; font-weight: bold;">●</span> <?= htmlspecialchars($log_status) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <br>
        <p style="font-size: 12px; color: #666;">
            📌 <strong>Total Successful Transactions:</strong> <?= count(array_filter($logs, function($l) { return strpos($l, 'SUCCESS') !== false; })) ?><br>
            📌 <strong>Total Failed Transactions:</strong> <?= count(array_filter($logs, function($l) { return strpos($l, 'FAILED') !== false; })) ?>
        </p>
    </div>
<?php else: ?>
    <div style="background: #e7f3ff; padding: 15px; border-radius: 4px; margin: 10px 0; color: #0066cc;">
        <strong>📝 No transactions logged yet</strong><br>
        When you test airtime or data bundles above, all Request IDs will appear here automatically.
    </div>
<?php endif; ?>

<style>
    code {
        background: #f0f0f0;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
    }
</style>

<hr>
<div class="info">
    <strong>Where agents receive airtime/data:</strong><br><br>
    
    <strong>1. Agent Dashboard Earnings:</strong><br>
    When you pay agents for completed responses, they can choose:<br>
    • Bank Transfer (existing)<br>
    • Airtime (VTPass integration needed in agent payout system)<br>
    • Data Bundle (VTPass integration needed)<br><br>
    
    <strong>2. Files to integrate VTPass for agent payouts:</strong><br>
    • <code>agent/earnings.php</code> - Where agents request withdrawals<br>
    • <code>admin/agent-payouts.php</code> - Where admin processes payouts<br>
    • Add VTPass as payment method alongside bank transfers<br><br>
    
    <strong>3. Integration Steps:</strong><br>
    1. Add "Airtime" and "Data" options to withdrawal method<br>
    2. Store agent's phone number in user profile<br>
    3. When admin approves airtime/data payout, call:<br>
    <code>vtpass_send_airtime($phone, $network, $amount)</code><br>
    <code>vtpass_send_data($phone, $variation_code)</code><br>
    4. Log transaction in database<br>
    5. Update agent balance<br>
</div>

<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}
</script>

</body>
</html>
