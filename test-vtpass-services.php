<?php
// Quick VTPASS Services Test
require_once 'connect.php';
require_once 'functions.php';

echo "==============================================\n";
echo "VTPASS SERVICE REGISTRATION TEST\n";
echo "==============================================\n\n";

// Get VTPASS settings
$vtpass_enabled = getSetting('vtpass_enabled');
$vtpass_api_url = getSetting('vtpass_api_url');
$vtpass_api_key = getSetting('vtpass_api_key');
$vtpass_public_key = getSetting('vtpass_public_key');

echo "Status: " . ($vtpass_enabled ? "✓ ENABLED\n" : "✗ DISABLED\n");
echo "API URL: {$vtpass_api_url}\n";
echo "API Key: " . (substr($vtpass_api_key, 0, 10) . "...\n");
echo "\n";

if (!$vtpass_enabled || empty($vtpass_api_key)) {
    echo "VTPass not configured! Check settings.\n";
    exit;
}

// AIRTIME SERVICES
echo "==============================================\n";
echo "AIRTIME SERVICES\n";
echo "==============================================\n\n";

$airtime_services = [
    'MTN Airtime' => 'mtn-airtime',
    'MTN GIFTING' => 'mtn-gifting',
    'GLO Airtime' => 'glo-airtime',
    'GLO GIFTING' => 'glo-gifting',
    'Airtel Airtime' => 'airtel-airtime',
    'Airtel GIFTING' => 'airtel-gifting',
    '9mobile Airtime' => '9mobile-airtime',
    '9mobile GIFTING' => '9mobile-gifting',
];

foreach ($airtime_services as $name => $service_id) {
    echo "Testing: {$name} ({$service_id})\n";
    
    $test_data = [
        'request_id' => 'REQUEST_ID_' . strtoupper(str_replace('-', '_', $service_id)) . '_' . time(),
        'serviceID' => $service_id,
        'amount' => '100',
        'phone' => '08033782777',
        'billersCode' => ''
    ];
    
    $result = vtpass_curl_post('pay', $test_data);
    
    echo "   Request ID: " . $test_data['request_id'] . "\n";
    echo "   Response: ";
    
    if (isset($result['request_id'])) {
        echo "✓ " . $result['request_id'];
    } else if (isset($result['response_description'])) {
        echo $result['response_description'];
    } else {
        echo json_encode($result);
    }
    echo "\n\n";
}

// DATA SERVICES
echo "==============================================\n";
echo "DATA SERVICES\n";
echo "==============================================\n\n";

$data_services = [
    'MTN Data' => 'mtn-data',
    'GLO Data' => 'glo-data',
    'Airtel Data' => 'airtel-data',
    '9mobile Data' => '9mobile-data',
];

foreach ($data_services as $name => $service_id) {
    echo "Testing: {$name} ({$service_id})\n";
    
    // Get variations for each service
    $variations_endpoint = "service/{$service_id}";
    $variations = vtpass_curl_get($variations_endpoint);
    
    echo "   Request ID for Service: REQUEST_ID_" . strtoupper(str_replace('-', '_', $service_id)) . "_" . time() . "\n";
    
    if (isset($variations['contents']['variations']) && is_array($variations['contents']['variations'])) {
        echo "   Available Plans:\n";
        foreach ($variations['contents']['variations'] as $variation) {
            $variation_code = $variation['variation_code'] ?? $variation['id'] ?? 'N/A';
            $variation_name = $variation['name'] ?? 'N/A';
            $variation_amount = $variation['variation_amount'] ?? 'N/A';
            echo "      - {$variation_name} ({$variation_code}): ₦{$variation_amount}\n";
        }
    } else {
        echo "   Could not fetch variations\n";
    }
    echo "\n";
}

echo "==============================================\n";
echo "TEST COMPLETE\n";
echo "==============================================\n";
?>
