<?php
// Secure endpoint to fetch VTPass data bundle variations for dropdown
header('Content-Type: application/json');
require_once 'connect.php';
require_once 'functions.php';

$serviceID = $_GET['serviceID'] ?? '';
$debug = isset($_GET['debug']);

if (!$serviceID) {
    echo json_encode(['error' => 'Missing serviceID']);
    exit;
}

// Keep service ID as-is - don't map etisalat to 9mobile
// VTPass may use 'etisalat-data' as the correct service ID

// Call VTPass API to get variations - use correct endpoint
$endpoint = 'service-variations?serviceID=' . $serviceID;
$response = vtpass_curl_get($endpoint);

// VTPass returns data in 'content' -> 'varations' (note: typo in their API)
$variations = $response['content']['varations'] ?? $response['content']['variations'] ?? $response['contents']['variations'] ?? [];

// Return only required fields
$out = [];
foreach ($variations as $v) {
    $out[] = [
        'variation_code' => $v['variation_code'] ?? ($v['id'] ?? ''),
        'name' => $v['name'] ?? '',
        'variation_amount' => $v['variation_amount'] ?? ($v['amount'] ?? '')
    ];
}

if ($debug) {
    echo json_encode(['variations' => $out, 'raw' => $response, 'serviceID' => $serviceID]);
} else {
    echo json_encode(['variations' => $out]);
}
