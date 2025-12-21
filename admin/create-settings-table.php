<?php
/**
 * Create settings table and populate with default values
 * This allows dynamic configuration via admin/settings.php
 */

require_once '../connect.php';

// Create settings table
$create_table = "CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text,
  `setting_type` enum('text','number','boolean','email','url') DEFAULT 'text',
  `category` varchar(50) DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `updated_by` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($create_table)) {
    echo "✅ Settings table created successfully<br>";
} else {
    die("❌ Error creating settings table: " . $conn->error);
}

// Define default settings
$default_settings = [
    // vPay Payment API
    ['vpay_public_key', '', 'text', 'payment_api_vpay', 'vPay Africa Public Key'],
    ['vpay_secret_key', '', 'text', 'payment_api_vpay', 'vPay Africa Secret Key'],
    ['vpay_merchant_id', '', 'text', 'payment_api_vpay', 'vPay Africa Merchant ID'],
    ['vpay_mode', 'sandbox', 'text', 'payment_api_vpay', 'Mode: sandbox or live'],
    
    // Termii SMS API
    ['termii_api_key', '', 'text', 'sms_api', 'Termii API Key'],
    ['termii_sender_id', 'OpinionHub', 'text', 'sms_api', 'Termii Sender ID (11 characters max)'],
    ['termii_channel', 'generic', 'text', 'sms_api', 'Termii Channel: generic, dnd, or whatsapp'],
    
    // Brevo Email API
    ['brevo_api_key', '', 'text', 'email_api', 'Brevo (Sendinblue) API Key'],
    ['brevo_from_email', 'noreply@opinionhubng.com', 'email', 'email_api', 'From Email Address'],
    ['brevo_from_name', 'Opinion Hub NG', 'text', 'email_api', 'From Name'],
    
    // Paystack (legacy/additional payment option)
    ['paystack_public_key', '', 'text', 'payment_api_paystack', 'Paystack Public Key'],
    ['paystack_secret_key', '', 'text', 'payment_api_paystack', 'Paystack Secret Key'],
    
    // Site Configuration
    ['site_name', 'Opinion Hub NG', 'text', 'site_config', 'Website Name'],
    ['site_url', SITE_URL, 'url', 'site_config', 'Site Base URL'],
    ['site_email', 'info@opinionhubng.com', 'email', 'site_config', 'Contact Email'],
    ['site_phone', '+234 XXX XXX XXXX', 'text', 'site_config', 'Contact Phone'],
    
    // Agent Settings
    ['agent_earnings_percentage', '10', 'number', 'agent_earnings', 'Agent commission percentage (0-100)'],
    ['minimum_payout_amount', '5000', 'number', 'agent_earnings', 'Minimum payout amount in Naira'],
    ['payout_processing_days', '7', 'number', 'agent_earnings', 'Days to process payout requests'],
    ['require_admin_approval', 'true', 'boolean', 'agent_approval', 'Require admin approval for new agents'],
    
    // Credit Pricing
    ['sms_credit_price', '10', 'number', 'pricing', 'Price per SMS credit (Naira)'],
    ['email_credit_price', '5', 'number', 'pricing', 'Price per Email credit (Naira)'],
    ['whatsapp_credit_price', '15', 'number', 'pricing', 'Price per WhatsApp credit (Naira)'],
    
    // Advertisement Rates
    ['ad_rate_homepage_top', '50000', 'number', 'advertising', 'Homepage top banner rate (per month, Naira)'],
    ['ad_rate_poll_page_top', '30000', 'number', 'advertising', 'Poll page top ad rate (per month, Naira)'],
    ['ad_rate_poll_page_sidebar', '20000', 'number', 'advertising', 'Poll page sidebar ad rate (per month, Naira)'],
    ['ad_rate_dashboard', '25000', 'number', 'advertising', 'Dashboard ad rate (per month, Naira)'],
    
    // Poll Settings
    ['max_poll_options', '10', 'number', 'polls', 'Maximum number of poll options'],
    ['allow_anonymous_voting', 'false', 'boolean', 'polls', 'Allow users to vote without logging in'],
    ['require_poll_approval', 'false', 'boolean', 'polls', 'Require admin approval for new polls'],
    
    // Company Information
    ['company_name', 'Opinion Hub Nigeria', 'text', 'company', 'Registered Company Name'],
    ['company_address', 'Lagos, Nigeria', 'text', 'company', 'Company Address'],
    ['company_rc_number', '', 'text', 'company', 'RC/Business Registration Number'],
    
    // System
    ['maintenance_mode', 'false', 'boolean', 'system', 'Enable maintenance mode'],
    ['debug_mode', 'false', 'boolean', 'system', 'Enable debug logging'],
];

// Insert default settings
$inserted = 0;
$skipped = 0;

foreach ($default_settings as $setting) {
    list($key, $value, $type, $category, $description) = $setting;
    
    $stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, setting_type, category, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $key, $value, $type, $category, $description);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $inserted++;
            echo "✅ Inserted: $key<br>";
        } else {
            $skipped++;
            echo "⏭️  Skipped (exists): $key<br>";
        }
    } else {
        echo "❌ Error inserting $key: " . $stmt->error . "<br>";
    }
    $stmt->close();
}

echo "<br><strong>Summary:</strong><br>";
echo "✅ Inserted: $inserted settings<br>";
echo "⏭️  Skipped: $skipped existing settings<br>";
echo "<br><a href='settings.php' class='btn btn-primary'>Go to Settings Page</a>";

$conn->close();
?>
