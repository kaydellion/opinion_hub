<?php
/**
 * track-ad.php - AJAX endpoint for tracking advertisement clicks
 */

require_once 'connect.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get ad ID from request
$ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;

if ($ad_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid ad ID']);
    exit;
}

// Track the click
$success = trackAdClick($ad_id);

if ($success) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to track click']);
}
