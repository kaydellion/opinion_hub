<?php
require_once 'connect.php';

echo "<h2>Update Paystack API Keys</h2>";
echo "<p>Get your keys from: <a href='https://dashboard.paystack.com/#/settings/developers' target='_blank'>Paystack Dashboard</a></p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $public_key = $_POST['public_key'] ?? '';
    $secret_key = $_POST['secret_key'] ?? '';
    
    if ($public_key && $secret_key) {
        $conn->query("UPDATE site_settings SET setting_value = '$public_key' WHERE setting_key = 'paystack_public_key'");
        $conn->query("UPDATE site_settings SET setting_value = '$secret_key' WHERE setting_key = 'paystack_secret_key'");
        
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 20px 0; border-radius: 5px;'>
                <strong>Success!</strong> Paystack keys have been updated. You can now delete this file for security.
              </div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 5px;'>
                <strong>Error!</strong> Please provide both keys.
              </div>";
    }
}

$current_public = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'paystack_public_key'")->fetch_assoc()['setting_value'];
$current_secret = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'paystack_secret_key'")->fetch_assoc()['setting_value'];
?>

<form method="POST" style="max-width: 600px; margin: 20px;">
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Public Key:</label>
        <input type="text" name="public_key" value="<?= htmlspecialchars($current_public) ?>" 
               style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" 
               placeholder="pk_test_...">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Secret Key:</label>
        <input type="text" name="secret_key" value="<?= htmlspecialchars($current_secret) ?>" 
               style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" 
               placeholder="sk_test_...">
    </div>
    
    <button type="submit" style="background: #6366f1; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
        Update Keys
    </button>
</form>

<div style="background: #fff3cd; color: #856404; padding: 15px; margin: 20px 0; border-radius: 5px; max-width: 600px;">
    <strong>Security Note:</strong> Delete this file after updating your keys!
</div>
