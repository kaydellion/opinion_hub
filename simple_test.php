<?php
require_once 'connect.php';
require_once 'functions.php';

echo "<h2>🔍 Simple Test - What's Happening?</h2>";

// Test 1: Check if settings table exists
echo "<h3>1. Settings Table Check:</h3>";
$table_check = $conn->query("SHOW TABLES LIKE 'settings'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<p style='color: green;'>✅ Settings table exists</p>";
} else {
    echo "<p style='color: red;'>❌ Settings table does NOT exist</p>";
}

// Test 2: Check getSetting function
echo "<h3>2. getSetting Function Test:</h3>";
$test_value = getSetting('subscription_price_basic_monthly', '99999');
echo "<p>getSetting('subscription_price_basic_monthly', '99999') = <strong>$test_value</strong></p>";

// Test 3: Direct database query
echo "<h3>3. Direct Database Query:</h3>";
$direct_query = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'subscription_price_basic_monthly' LIMIT 1");
if ($direct_query && $direct_query->num_rows > 0) {
    $db_value = $direct_query->fetch_assoc()['setting_value'];
    echo "<p>Database value: <strong>$db_value</strong></p>";
} else {
    echo "<p style='color: orange;'>⚠️ No record found in database</p>";
}

// Test 4: What admin settings would show
echo "<h3>4. Admin Settings Display:</h3>";
$admin_value = getSetting('subscription_price_basic_monthly', '35000');
echo "<p>Admin settings input value: <strong>₦" . number_format($admin_value, 0) . "</strong></p>";

// Test 5: What subscription page would show
echo "<h3>5. Subscription Page Display:</h3>";
$subscription_value = getSetting('subscription_price_basic_monthly', '35000');
echo "<p>Subscription page would show: <strong>₦" . number_format($subscription_value, 0) . "</strong></p>";

// Test 6: Add the setting if missing
echo "<h3>6. Fix Missing Setting:</h3>";
$exists = $conn->query("SELECT setting_key FROM settings WHERE setting_key = 'subscription_price_basic_monthly'");
if (!$exists || $exists->num_rows === 0) {
    $key = 'subscription_price_basic_monthly';
    $value = 35000;
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("ss", $key, $value);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Added missing setting: $key = ₦" . number_format($value, 0) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add setting</p>";
        }
    }
} else {
    echo "<p style='color: blue;'>ℹ️ Setting already exists</p>";
}

echo "<hr>";
echo "<h3>🔗 Quick Actions:</h3>";
echo "<ul>";
echo "<li><a href='admin/settings.php' target='_blank'>⚙️ Go to Admin Settings</a></li>";
echo "<li><a href='client/subscription.php' target='_blank'>💳 Go to Subscription Page</a></li>";
echo "<li><a href='simple_test.php' target='_blank'>🔄 Refresh This Test</a></li>";
echo "</ul>";

echo "<hr>";
echo "<h3>📋 Summary:</h3>";
echo "<p>This test checks if the subscription pricing system is working correctly.</p>";
echo "<p>If all tests show ✅, then the subscription page should reflect admin settings changes.</p>";
?>
