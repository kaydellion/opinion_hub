<?php
require_once 'connect.php';
require_once 'functions.php';

// Only allow admin access
if (!isLoggedIn() || !checkRole('admin')) {
    die("Admin access required");
}

echo "<h2>VTPass Configuration Test</h2>";
echo "<hr>";

// 1. Check if VTPass is enabled
echo "<h3>1. VTPass Status</h3>";
echo "VTPASS_ENABLED: " . (VTPASS_ENABLED ? '<span style="color: green;">✓ ENABLED</span>' : '<span style="color: red;">✗ DISABLED</span>') . "<br>";
echo "<br>";

// 2. Show configuration
echo "<h3>2. Configuration (from database)</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

$vtpass_settings = [
    'vtpass_enabled' => VTPASS_ENABLED ? '1' : '0',
    'vtpass_api_url' => VTPASS_API_URL,
    'vtpass_api_key' => VTPASS_API_KEY,
    'vtpass_public_key' => VTPASS_PUBLIC_KEY,
    'vtpass_secret_key' => VTPASS_SECRET_KEY
];

foreach ($vtpass_settings as $key => $value) {
    $status = '';
    $display_value = $value;
    
    if ($key === 'vtpass_enabled') {
        $status = $value === '1' ? '<span style="color: green;">✓ Enabled</span>' : '<span style="color: orange;">⚠ Disabled</span>';
    } elseif (empty($value)) {
        $status = '<span style="color: red;">✗ Not Set</span>';
        $display_value = '<em>(empty)</em>';
    } else {
        // Mask API keys for security (show first 10 chars only)
        if (in_array($key, ['vtpass_api_key', 'vtpass_public_key', 'vtpass_secret_key'])) {
            $display_value = substr($value, 0, 15) . '... (length: ' . strlen($value) . ')';
        }
        $status = '<span style="color: green;">✓ Set</span>';
    }
    
    echo "<tr>";
    echo "<td><strong>{$key}</strong></td>";
    echo "<td>{$display_value}</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}

echo "</table>";
echo "<br>";

// 3. Test API connectivity (if enabled and configured)
echo "<h3>3. API Connectivity Test</h3>";

if (!VTPASS_ENABLED) {
    echo '<p style="color: orange;">⚠ VTPass is disabled. Enable it in settings to test.</p>';
} elseif (empty(VTPASS_API_KEY) || empty(VTPASS_PUBLIC_KEY) || empty(VTPASS_SECRET_KEY)) {
    echo '<p style="color: red;">✗ API credentials not configured. Add them in admin settings.</p>';
} else {
    echo '<p>Testing VTPass API connection...</p>';
    
    // Test with balance check endpoint
    $test_endpoint = 'balance';
    $test_result = vtpass_curl_get($test_endpoint);
    
    echo "<pre>";
    echo "<strong>Endpoint:</strong> " . VTPASS_API_URL . $test_endpoint . "\n";
    echo "<strong>Response:</strong>\n";
    print_r($test_result);
    echo "</pre>";
    
    // VTPass uses 'contents' not 'content' in balance response
    if (isset($test_result['contents']['balance']) || isset($test_result['content']['balance'])) {
        $balance = $test_result['contents']['balance'] ?? $test_result['content']['balance'];
        echo '<p style="color: green;">✓ API connection successful! Balance: ₦' . number_format($balance, 2) . '</p>';
    } elseif (isset($test_result['response_description'])) {
        echo '<p style="color: red;">✗ API Error: ' . htmlspecialchars($test_result['response_description']) . '</p>';
    } else {
        echo '<p style="color: orange;">⚠ Unexpected response format</p>';
    }
}

echo "<br>";

// 4. Database settings check
echo "<h3>4. Database Settings</h3>";
$db_settings = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'vtpass_%' ORDER BY setting_key");

if ($db_settings && $db_settings->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Setting Key</th><th>Value (from DB)</th></tr>";
    while ($row = $db_settings->fetch_assoc()) {
        $display = $row['setting_value'];
        if (strpos($row['setting_key'], 'key') !== false) {
            $display = substr($display, 0, 15) . '... (length: ' . strlen($display) . ')';
        }
        echo "<tr><td>{$row['setting_key']}</td><td>{$display}</td></tr>";
    }
    echo "</table>";
} else {
    echo '<p style="color: orange;">⚠ No VTPass settings found in database. Add them via <a href="admin/settings.php">Admin Settings</a></p>';
}

echo "<br>";
echo "<hr>";
echo '<p><a href="admin/settings.php">← Go to Settings</a> | <a href="index.php">← Go to Dashboard</a></p>';
?>
