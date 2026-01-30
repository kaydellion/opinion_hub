<?php
require_once 'connect.php';
require_once 'functions.php';

echo "<h2>⚡ Quick Subscription Pricing Check</h2>";

// Test each subscription pricing setting
$settings = [
    'subscription_price_basic_monthly' => 35000,
    'subscription_price_basic_annual' => 392000,
    'subscription_price_classic_monthly' => 65000,
    'subscription_price_classic_annual' => 735000,
    'subscription_price_enterprise_monthly' => 100000,
    'subscription_price_enterprise_annual' => 1050000
];

echo "<h3>📊 Current Subscription Pricing:</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Setting</th><th>Database Value</th><th>getSetting() Result</th><th>Status</th>";
echo "</tr>";

foreach ($settings as $key => $default) {
    // Check database directly
    $db_check = $conn->query("SELECT setting_value FROM settings WHERE setting_key = '$key' LIMIT 1");
    $db_value = ($db_check && $db_check->num_rows > 0) ? $db_check->fetch_assoc()['setting_value'] : 'NOT FOUND';
    
    // Check via getSetting()
    $getsetting_value = getSetting($key, $default);
    
    // Status
    if ($db_value === 'NOT FOUND') {
        $status = "❌ Missing";
        $color = "red";
    } elseif ($db_value != $getsetting_value) {
        $status = "⚠️ Mismatch";
        $color = "orange";
    } else {
        $status = "✅ OK";
        $color = "green";
    }
    
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
    echo "<td>" . htmlspecialchars($db_value) . "</td>";
    echo "<td>₦" . number_format($getsetting_value, 0) . "</td>";
    echo "<td style='color: $color;'>$status</td>";
    echo "</tr>";
}
echo "</table>";

// Add missing settings
echo "<h3>🔧 Fix Missing Settings:</h3>";
$fixed = 0;
foreach ($settings as $key => $value) {
    $exists = $conn->query("SELECT setting_key FROM settings WHERE setting_key = '$key'");
    if (!$exists || $exists->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $key, $value);
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✅ Added: $key = ₦" . number_format($value, 0) . "</p>";
                $fixed++;
            }
        }
    }
}

if ($fixed === 0) {
    echo "<p style='color: blue;'>ℹ️ All settings already exist</p>";
}

// Test what subscription page would show
echo "<h3>💳 What Subscription Page Shows:</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Plan</th><th>Monthly</th><th>Annual</th></tr>";

$plans = [
    'basic' => 'Basic Plan',
    'classic' => 'Classic Plan', 
    'enterprise' => 'Enterprise Plan'
];

foreach ($plans as $type => $name) {
    $monthly = getSetting('subscription_price_' . $type . '_monthly', 0);
    $annual = getSetting('subscription_price_' . $type . '_annual', 0);
    
    echo "<tr>";
    echo "<td><strong>$name</strong></td>";
    echo "<td>₦" . number_format($monthly, 0) . "</td>";
    echo "<td>₦" . number_format($annual, 0) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>🔗 Test Links:</h3>";
echo "<ul>";
echo "<li><a href='admin/settings.php' target='_blank'>⚙️ Update Admin Settings</a></li>";
echo "<li><a href='client/subscription.php' target='_blank'>💳 Check Subscription Page</a></li>";
echo "<li><a href='quick_subscription_test.php' target='_blank'>🔄 Refresh This Test</a></li>";
echo "</ul>";

echo "<hr>";
echo "<h3>💡 Instructions:</h3>";
echo "<ol>";
echo "<li>Check the table above - all should show '✅ OK'</li>";
echo "<li>If any show '❌ Missing', they should be auto-fixed above</li>";
echo "<li>Go to admin settings and change a subscription price</li>";
echo "<li>Come back to this test to see the updated values</li>";
echo "<li>Check the subscription page to see if changes appear</li>";
echo "</ol>";
?>
