<?php
// ============================================================
// connect.php - Database Connection & Configuration
// ============================================================
error_reporting(E_ALL); ini_set('display_errors', 1); ini_set('log_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*local Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'opinionhub_ng');
*/


//live config
define('DB_HOST', 'localhost');
define('DB_USER', 'opinionh_opinionh');
define('DB_PASS', 'opinionh_opinionh');
define('DB_NAME', 'opinionh_opinionhub_ng');


// Site Configuration
define('SITE_URL', 'http://localhost/opinion/');
define('SITE_NAME', 'Opinion Hub NG');
define('SITE_TAGLINE', 'What Gets Measured, Gets Done!');
define('SITE_EMAIL', 'hello@opinionhub.ng');
define('SITE_PHONE', '+234 (0) 803 3782 777');

// Payment Gateway Configuration
// Note: Paystack is now DISABLED - Using vPay Africa instead
define('PAYMENT_GATEWAY', 'vpay'); // 'paystack' or 'vpay'

// Paystack Configuration (DISABLED - Do not use)
// define('PAYSTACK_PUBLIC_KEY', 'pk_test_YOUR_PAYSTACK_PUBLIC_KEY');
// define('PAYSTACK_SECRET_KEY', 'sk_test_YOUR_PAYSTACK_SECRET_KEY');
// define('PAYSTACK_CALLBACK_URL', SITE_URL . 'payment-callback.php');

// Upload Configuration (NOT from database - filesystem settings)
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Pagination
define('ITEMS_PER_PAGE', 20);

// Database Connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection Failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Log error (in production, log to file instead of displaying)
    error_log("Database Error: " . $e->getMessage());
    die("Unable to connect to database. Please try again later.");
}

// Include core functions BEFORE loading settings
require_once __DIR__ . '/functions.php';

// ============================================================
// LOAD CONFIGURATION FROM DATABASE (requires getSetting function)
// ============================================================

// Termii SMS Configuration (loaded from database)
$termii_api_key = getSetting('termii_api_key', 'YOUR_TERMII_API_KEY');
$termii_sender_id = getSetting('termii_sender_id', 'OpinionHub');
define('TERMII_API_KEY', $termii_api_key);
define('TERMII_SENDER_ID', $termii_sender_id);

// Log the API key (first 10 chars only for security)
//error_log("Termii API Key loaded: " . substr($termii_api_key, 0, 10) . "... (length: " . strlen($termii_api_key) . ")");

// Brevo Email Configuration (loaded from database)
$brevo_api_key = getSetting('brevo_api_key', 'YOUR_BREVO_API_KEY');
$brevo_from_email = getSetting('brevo_from_email', 'noreply@opinionhub.ng');
$brevo_from_name = getSetting('brevo_from_name', 'Opinion Hub NG');
define('BREVO_API_KEY', $brevo_api_key);
define('BREVO_FROM_EMAIL', $brevo_from_email);
define('BREVO_FROM_NAME', $brevo_from_name);

// WhatsApp Configuration (loaded from database)
$whatsapp_api_url = getSetting('whatsapp_api_url', '');
$whatsapp_api_key = getSetting('whatsapp_api_key', '');
define('WHATSAPP_API_URL', $whatsapp_api_url);
define('WHATSAPP_API_KEY', $whatsapp_api_key);

// VTPass Configuration (loaded from database)
$vtpass_api_url = getSetting('vtpass_api_url', 'https://sandbox.vtpass.com/api/');
$vtpass_api_key = getSetting('vtpass_api_key', '');
$vtpass_public_key = getSetting('vtpass_public_key', '');
$vtpass_secret_key = getSetting('vtpass_secret_key', '');
$vtpass_enabled_value = getSetting('vtpass_enabled', '0');
$vtpass_enabled = in_array($vtpass_enabled_value, ['1', 'true', true, 1], true);
define('VTPASS_API_URL', $vtpass_api_url);
define('VTPASS_API_KEY', $vtpass_api_key);
define('VTPASS_PUBLIC_KEY', $vtpass_public_key);
define('VTPASS_SECRET_KEY', $vtpass_secret_key);
define('VTPASS_ENABLED', $vtpass_enabled);

// Agent Commission (loaded from database)
$default_commission = getSetting('default_commission_per_response', '1000');
define('DEFAULT_COMMISSION_PER_RESPONSE', intval($default_commission));

// Hydrate vPay configuration from site settings when available
$vpay_public_key = getSetting('vpay_public_key', 'vpay_pub_YOUR_PUBLIC_KEY_HERE');
$vpay_secret_key = getSetting('vpay_secret_key', 'vpay_sec_YOUR_SECRET_KEY_HERE');
$vpay_merchant_id = getSetting('vpay_merchant_id', 'YOUR_MERCHANT_ID_HERE');
$vpay_callback_url = getSetting('vpay_callback_url', SITE_URL . 'vpay-callback.php');

if (!defined('VPAY_PUBLIC_KEY')) {
    define('VPAY_PUBLIC_KEY', $vpay_public_key);
}
if (!defined('VPAY_SECRET_KEY')) {
    define('VPAY_SECRET_KEY', $vpay_secret_key);
}
if (!defined('VPAY_MERCHANT_ID')) {
    define('VPAY_MERCHANT_ID', $vpay_merchant_id);
}
if (!defined('VPAY_CALLBACK_URL')) {
    define('VPAY_CALLBACK_URL', $vpay_callback_url);
}

// OPTIONAL: Load configurable settings from database (currently using hardcoded values above)
// Uncomment the lines below if you want to use database-driven settings via /admin/settings.php
// Note: The hardcoded values above will be used as fallbacks if database settings are not found

/*
// Site Configuration (admin-editable)
if (!defined('SITE_NAME_DB')) {
    define('SITE_NAME_DB', getSetting('site_name', SITE_NAME));
}
if (!defined('SITE_FAVICON')) {
    define('SITE_FAVICON', getSetting('site_favicon', 'favicon.jpg'));
}
if (!defined('SITE_LOGO')) {
    define('SITE_LOGO', getSetting('site_logo', 'logo.png'));
}
// ... other database settings
*/

// For now, using hardcoded SITE_FAVICON and SITE_LOGO
if (!defined('SITE_FAVICON')) {
    define('SITE_FAVICON', 'favicon.jpg');
}
if (!defined('SITE_LOGO')) {
    define('SITE_LOGO', 'logo.png');
}

// Timezone
date_default_timezone_set('Africa/Lagos');

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}

// Create subdirectories only if main upload dir exists and is writable
if (file_exists(UPLOAD_DIR) && is_writable(UPLOAD_DIR)) {
    $subdirs = ['polls', 'profiles', 'ads', 'blog'];
    foreach ($subdirs as $dir) {
        $subdir_path = UPLOAD_DIR . '/' . $dir;
        if (!file_exists($subdir_path)) {
            @mkdir($subdir_path, 0755, true);
        }
    }
}
?>