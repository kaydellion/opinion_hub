<?php
require_once 'connect.php';
require_once 'functions.php';

echo "<h2>🔍 Debug SMS Pricing Issue</h2>";

// Check what SMS-related settings exist
echo "<h3>📋 Current SMS Settings in Database:</h3>";
$sms_settings = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE '%sms%' ORDER BY setting_key");

if ($sms_settings && $sms_settings->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Setting Key</th><th>Setting Value</th><th>getSetting() Result</th></tr>";
    
    while ($row = $sms_settings->fetch_assoc()) {
        $key = $row['setting_key'];
        $direct_value = $row['setting_value'];
        $getsetting_value = getSetting($key, 'DEFAULT');
        
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
        echo "<td>" . htmlspecialchars($direct_value) . "</td>";
        echo "<td>" . htmlspecialchars($getsetting_value) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No SMS settings found in database</p>";
}

// Test the specific key we're using
echo "<h3>🧪 Testing Specific Keys:</h3>";

$test_keys = [
    'sms_price_agent',
    'sms_price_free', 
    'sms_price_basic',
    'sms_price',
    'agent_sms_price',
    'price_per_sms'
];

foreach ($test_keys as $key) {
    $value = getSetting($key, 'NOT_FOUND');
    $status = ($value !== 'NOT_FOUND') ? '✅ FOUND' : '❌ NOT FOUND';
    $color = ($value !== 'NOT_FOUND') ? 'green' : 'red';
    
    echo "<p style='color: $color;'>";
    echo "<strong>$key:</strong> " . htmlspecialchars($value) . " [$status]";
    echo "</p>";
}

// Check what the buy-sms-credits page is actually getting
echo "<h3>🔍 What buy-sms-credits.php is Getting:</h3>";

// Simulate the exact code from buy-sms-credits.php
$sms_price_per_credit = getSetting('sms_price_agent', 20);
echo "<p><strong>sms_price_per_credit:</strong> " . htmlspecialchars($sms_price_per_credit) . "</p>";

// Calculate packages like the page does
$packages = [
    1 => ['credits' => 10, 'price' => 10 * $sms_price_per_credit],
    2 => ['credits' => 50, 'price' => 50 * $sms_price_per_credit],
    3 => ['credits' => 100, 'price' => 100 * $sms_price_per_credit],
    4 => ['credits' => 200, 'price' => 200 * $sms_price_per_credit],
    5 => ['credits' => 500, 'price' => 500 * $sms_price_per_credit],
];

echo "<h4>Calculated Package Prices:</h4>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Package</th><th>Credits</th><th>Price</th><th>Per SMS</th></tr>";

foreach ($packages as $id => $package) {
    $per_sms = $package['price'] / $package['credits'];
    echo "<tr>";
    echo "<td>Package $id</td>";
    echo "<td>" . $package['credits'] . "</td>";
    echo "<td>₦" . number_format($package['price'], 0) . "</td>";
    echo "<td>₦" . number_format($per_sms, 2) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Add a test setting if needed
echo "<h3>🔧 Add Test Setting:</h3>";
echo "<p>If sms_price_agent doesn't exist, let's add it:</p>";

$test_key = 'sms_price_agent';
$test_value = 25; // Set to 25 for testing

// Check if it exists first
$exists = $conn->query("SELECT setting_key FROM settings WHERE setting_key = '$test_key'");
if (!$exists || $exists->num_rows === 0) {
    // Add the setting
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("ss", $test_key, $test_value);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Added setting: $test_key = $test_value</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add setting</p>";
        }
    }
} else {
    echo "<p style='color: blue;'>ℹ️ Setting $test_key already exists</p>";
}

echo "<hr>";
echo "<h3>🔗 Quick Links:</h3>";
echo "<ul>";
echo "<li><a href='admin/settings.php' target='_blank'>⚙️ Admin Settings</a></li>";
echo "<li><a href='agent/buy-sms-credits.php' target='_blank'>💳 SMS Credits Page</a></li>";
echo "<li><a href='test_getsetting.php' target='_blank'>🧪 getSetting Test</a></li>";
echo "</ul>";

echo "<hr>";
echo "<h3>💡 Next Steps:</h3>";
echo "<ol>";
echo "<li>Check if 'sms_price_agent' setting exists above</li>";
echo "<li>If not, add it in admin settings or use the auto-add above</li>";
echo "<li>Update the setting in admin settings to test changes</li>";
echo "<li>Refresh the SMS credits page to see updates</li>";
echo "</ol>";
?>
