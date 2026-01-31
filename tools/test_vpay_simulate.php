<?php
// Simple test harness to simulate vPay redirect callback for local testing
// Usage: run `php tools/test_vpay_simulate.php` from project root

require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../functions.php';

// Create a session for user_id 2 (adjust as needed)
session_start();
$_SESSION['user_id'] = 2;

// Build GET params to simulate an SMS credits purchase
$params = [
    'reference' => 'TEST_SMS_' . time(),
    'type' => 'sms_credits',
    'units' => 10,
    'amount' => 18000 // amount in smallest currency unit if required by callback
];

// Inject into $_GET then include the callback handler
$_GET = $params;

echo "Simulating vPay callback with params:\n" . print_r($params, true) . "\n";

// Include the callback handler (it will use $_SESSION and $_GET)
include __DIR__ . '/../vpay-callback.php';

echo "Simulation complete. Check logs and DB tables for changes.\n";

?>