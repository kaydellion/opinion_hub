<?php

// ============================================================
// functions.php - Core Functions
// ============================================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize Input
 */
function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

/**
 * Validate Email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Hash Password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify Password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if User is Logged In
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get Current User
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    global $conn;
    $user_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT * FROM users WHERE id = $user_id");
    return $result ? $result->fetch_assoc() : null;
}

/**
 * Check User Role
 */
function checkRole($allowed_roles = []) {
    if (!isLoggedIn()) return false;
    $user = getCurrentUser();
    // Handle both array and single string
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    return in_array($user['role'], $allowed_roles);
}

/**
 * Redirect if Not Authorized
 */
function requireRole($roles) {
    // Handle both array and single string
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    if (!checkRole($roles)) {
        header("Location: " . SITE_URL . "unauthorized.php");
        exit;
    }
}

/**
 * Generate Unique Reference
 */
function generateReference($prefix = '') {
    return $prefix . '-' . uniqid() . '-' . time();
}

/**
 * Generate Slug
 */
function generateSlug($string) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
}

/**
 * Generate unique slug for polls
 */
function generateUniquePollSlug($title) {
    global $conn;
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    $base_slug = $slug;
    $i = 1;
    
    // Check if slug exists and make it unique
    $check = $conn->query("SELECT id FROM polls WHERE slug = '$slug'");
    while ($check && $check->num_rows > 0) {
        $slug = $base_slug . '-' . $i;
        $i++;
        $check = $conn->query("SELECT id FROM polls WHERE slug = '$slug'");
    }
    
    return $slug;
}

/**
 * Format Currency (Nigerian Naira)
 */
function formatCurrency($amount) {
    return '₦' . number_format($amount, 2);
}

/**
 * Format Date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Get User Subscription
 */
function getUserSubscription($user_id) {
    global $conn;
    $query = "SELECT us.*, sp.* FROM user_subscriptions us 
              JOIN subscription_plans sp ON us.plan_id = sp.id 
              WHERE us.user_id = $user_id AND us.status = 'active' 
              ORDER BY us.created_at DESC LIMIT 1";
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc() : null;
}

/**
 * Get User's Active Subscription Plan
 */
function getUserPlan($user_id) {
    $subscription = getUserSubscription($user_id);
    return $subscription ? $subscription['type'] : 'free';
}

/**
 * Get Messaging Credits
 */
function getMessagingCredits($user_id) {
    global $conn;
    $result = $conn->query("SELECT * FROM messaging_credits WHERE user_id = $user_id");
    return $result ? $result->fetch_assoc() : null;
}

/**
 * Add Messaging Credits
 */
function addMessagingCredits($user_id, $sms = 0, $email = 0, $whatsapp = 0) {
    global $conn;
    $credits = getMessagingCredits($user_id);
    
    if ($credits) {
        $new_sms = $credits['sms_balance'] + $sms;
        $new_email = $credits['email_balance'] + $email;
        $new_whatsapp = $credits['whatsapp_balance'] + $whatsapp;
        
        return $conn->query("UPDATE messaging_credits 
                            SET sms_balance = $new_sms, 
                                email_balance = $new_email, 
                                whatsapp_balance = $new_whatsapp 
                            WHERE user_id = $user_id");
    } else {
        return $conn->query("INSERT INTO messaging_credits 
                            (user_id, sms_balance, email_balance, whatsapp_balance) 
                            VALUES ($user_id, $sms, $email, $whatsapp)");
    }
}

/**
 * Format Nigerian Phone Number to International Format (+234...)
 */
function formatNigerianPhone($phone) {
    // Remove all non-numeric characters except + at start
    $phone = preg_replace('/[^\d+]/', '', $phone);
    
    // Remove leading + or 00
    $phone = preg_replace('/^(\+|00)/', '', $phone);
    
    // If starts with 0, replace with 234
    if (substr($phone, 0, 1) === '0') {
        $phone = '234' . substr($phone, 1);
    }
    
    // If starts with 234, keep as is
    if (substr($phone, 0, 3) === '234') {
        return  $phone;
    }
    
    // If it's just 10 digits (Nigerian local format), add 234
    if (strlen($phone) === 10) {
        return '234' . $phone;
    }
    
    // Return with + prefix
    return $phone;
}

/**
 * Send SMS via Termii
 */
function sendSMS_Termii($to, $message, $from = TERMII_SENDER_ID) {
    $api_key = defined('TERMII_API_KEY') ? TERMII_API_KEY : '';
    
    if (empty($api_key)) {
        error_log("Termii API key not configured");
        return ['success' => false, 'error' => 'API key not configured'];
    }
    
    // Format phone number to international format (234...)
    $original_number = $to;
    $to = formatNigerianPhone($to);
    
    $url = 'https://api.ng.termii.com/api/sms/send';
    $payload = [
        'api_key' => $api_key,
        'to' => $to,
        'from' => $from,
        'sms' => $message,
        'type' => 'plain',
        'channel' => 'generic' // dnd Use DND for transactional/critical messages
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ]
    ]);

    $resp = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        error_log("Termii SMS Error: " . $err);
        return ['success' => false, 'error' => $err];
    }

    $data = json_decode($resp, true);
    
    if (isset($data['code']) && $data['code'] === 'ok') {
        return ['success' => true, 'data' => $data];
    }
    
    //error_log("Termii SMS Failed: " . $resp);
    return ['success' => false, 'response' => $resp, 'data' => $data];
}

/**
 * Generate Professional Email Template (Minimal & Clean Design)
 */
function getEmailTemplate($title, $content, $button_text = '', $button_url = '', $footer_text = '') {
    $site_name = SITE_NAME;
    $site_url = SITE_URL;
    $current_year = date('Y');
    
    // Button HTML
    $button_html = '';
    if ($button_text && $button_url) {
        $button_html = '
        <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
            <tr>
                <td align="center">
                    <a href="' . $button_url . '" style="display: inline-block; padding: 14px 40px; background: #6366f1; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
                        ' . htmlspecialchars($button_text) . '
                    </a>
                </td>
            </tr>
        </table>';
    }
    
    // Footer text
    if (!$footer_text) {
        $footer_text = 'You received this email because you are a member of ' . $site_name . '.';
    }
    
    $html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background: #f3f4f6; color: #1f2937;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #f3f4f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table width="600" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 30px; text-align: center; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: -0.5px;">
                                ' . $site_name . '
                            </h1>
                            <p style="margin: 8px 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                                ' . SITE_TAGLINE . '
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin: 0 0 20px; color: #1f2937; font-size: 24px; font-weight: 600;">
                                ' . htmlspecialchars($title) . '
                            </h2>
                            <div style="color: #4b5563; font-size: 16px; line-height: 1.6;">
                                ' . $content . '
                            </div>
                            ' . $button_html . '
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; background: #f9fafb; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 12px; color: #6b7280; font-size: 13px; text-align: center;">
                                ' . htmlspecialchars($footer_text) . '
                            </p>
                            <p style="margin: 0; color: #9ca3af; font-size: 12px; text-align: center;">
                                &copy; ' . $current_year . ' ' . $site_name . '. All rights reserved.
                            </p>
                            <p style="margin: 10px 0 0; text-align: center;">
                                <a href="' . $site_url . '" style="color: #6366f1; text-decoration: none; font-size: 12px; margin: 0 8px;">Visit Website</a>
                                <span style="color: #d1d5db;">|</span>
                                <a href="' . $site_url . 'profile.php" style="color: #6366f1; text-decoration: none; font-size: 12px; margin: 0 8px;">My Account</a>
                                <span style="color: #d1d5db;">|</span>
                                <a href="' . $site_url . 'contact.php" style="color: #6366f1; text-decoration: none; font-size: 12px; margin: 0 8px;">Support</a>
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    
    return $html;
}

/**
 * Send Email via Brevo (with template support)
 */
function sendEmail_Brevo($to_email, $subject, $htmlContent, $to_name = '', $use_template = true) {
    // If use_template is true and htmlContent doesn't contain <!DOCTYPE, wrap it
    if ($use_template && !str_contains($htmlContent, '<!DOCTYPE')) {
        $htmlContent = getEmailTemplate($subject, $htmlContent);
    }
    
    // Get Brevo settings from database
    $brevo_api_key = getSetting('brevo_api_key', '');
    $brevo_from_email = getSetting('brevo_from_email', 'noreply@opinionhub.ng');
    $brevo_from_name = getSetting('brevo_from_name', 'Opinion Hub NG');
    
    if (empty($brevo_api_key)) {
        return ['success' => false, 'error' => 'Brevo API key not configured'];
    }
    
    $url = 'https://api.brevo.com/v3/smtp/email';
    $payload = [
        'sender' => ['name' => $brevo_from_name, 'email' => $brevo_from_email],
        'to' => [[ 'email' => $to_email, 'name' => $to_name ]],
        'subject' => $subject,
        'htmlContent' => $htmlContent
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'api-key: ' . $brevo_api_key
        ]
    ]);

    $resp = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return ['success' => false, 'error' => $err];
    }

    $data = json_decode($resp, true);
    return $data ?: ['success' => false, 'response' => $resp];
}

/**
 * Quick send email with template (simplified wrapper)
 */
function sendTemplatedEmail($to_email, $to_name, $subject, $message, $button_text = '', $button_url = '') {
    $html = getEmailTemplate($subject, $message, $button_text, $button_url);
    return sendEmail_Brevo($to_email, $subject, $html, $to_name, false);
}

// ============================================================
// Advertisement Functions
// ============================================================

/**
 * Get active advertisements for a specific placement
 * @param string $placement - The placement identifier
 * @param int $limit - Maximum number of ads to return
 * @return array - Array of active ad records
 */
function getActiveAds($placement, $limit = 1) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT * FROM advertisements 
        WHERE placement = ? 
        AND status = 'active' 
        AND (start_date IS NULL OR start_date <= CURDATE())
        AND (end_date IS NULL OR end_date >= CURDATE())
        ORDER BY RAND()
        LIMIT ?
    ");
    
    $stmt->bind_param('si', $placement, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ads = [];
    while ($row = $result->fetch_assoc()) {
        $ads[] = $row;
    }
    
    return $ads;
}

/**
 * Display advertisement HTML for a specific placement
 * @param string $placement - The placement identifier
 * @param string $class - Additional CSS classes
 * @return void - Echoes HTML directly
 */
function displayAd($placement, $class = '') {
    $ads = getActiveAds($placement);
    
    if (empty($ads)) {
        return;
    }
    
    $ad = $ads[0]; // Get first ad
    
    // Track view
    trackAdView($ad['id']);
    
    // Generate ad HTML
    echo '<div class="advertisement ' . htmlspecialchars($class) . '" data-ad-id="' . $ad['id'] . '">';
    echo '  <a href="' . htmlspecialchars($ad['ad_url']) . '" target="_blank" onclick="trackAdClick(' . $ad['id'] . ')">';
    
    if ($ad['image_url']) {
        echo '    <img src="' . SITE_URL . htmlspecialchars($ad['image_url']) . '" ';
        echo '         alt="' . htmlspecialchars($ad['title'] ?? 'Advertisement') . '" ';
        echo '         class="img-fluid ad-image">';
    } else {
        echo '    <div class="ad-placeholder">' . htmlspecialchars($ad['title'] ?? 'Advertisement') . '</div>';
    }
    
    echo '  </a>';
    echo '</div>';
}

/**
 * Track advertisement view
 * @param int $ad_id - The advertisement ID
 * @return bool - Success status
 */
function trackAdView($ad_id) {
    global $conn;
    
    // Check if already tracked in this session
    if (!isset($_SESSION['ad_views'])) {
        $_SESSION['ad_views'] = [];
    }
    
    if (in_array($ad_id, $_SESSION['ad_views'])) {
        return false; // Already tracked in this session
    }
    
    $stmt = $conn->prepare("UPDATE advertisements SET total_views = total_views + 1 WHERE id = ?");
    $stmt->bind_param('i', $ad_id);
    $success = $stmt->execute();
    
    if ($success) {
        $_SESSION['ad_views'][] = $ad_id;
    }
    
    return $success;
}

/**
 * Track advertisement click (called via AJAX)
 * @param int $ad_id - The advertisement ID
 * @return bool - Success status
 */
function trackAdClick($ad_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE advertisements SET click_throughs = click_throughs + 1 WHERE id = ?");
    $stmt->bind_param('i', $ad_id);
    return $stmt->execute();
}

/**
 * Auto-pause expired advertisements
 * Should be called periodically (e.g., in header.php or via cron)
 * @return int - Number of ads paused
 */
function pauseExpiredAds() {
    global $conn;
    
    $result = $conn->query("
        UPDATE advertisements 
        SET status = 'paused' 
        WHERE status = 'active' 
        AND end_date IS NOT NULL 
        AND end_date < CURDATE()
    ");
    
    return $conn->affected_rows;
}

/**
 * Send expiry notification to advertiser
 * @param int $ad_id - The advertisement ID
 * @return bool - Success status
 */
function sendAdExpiryNotification($ad_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT a.*, u.email, u.first_name, u.last_name 
        FROM advertisements a
        LEFT JOIN users u ON a.advertiser_id = u.id
        WHERE a.id = ?
    ");
    
    $stmt->bind_param('i', $ad_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ad = $result->fetch_assoc();
    
    if (!$ad || !$ad['email']) {
        return false;
    }
    
    $advertiser_name = trim($ad['first_name'] . ' ' . $ad['last_name']);
    $subject = 'Your Advertisement Has Expired - ' . SITE_NAME;
    $message = "Your advertisement \"" . htmlspecialchars($ad['title']) . "\" has reached its end date and has been automatically paused.<br><br>";
    $message .= "Advertisement Details:<br>";
    $message .= "• Placement: " . htmlspecialchars($ad['placement']) . "<br>";
    $message .= "• Duration: " . date('M d', strtotime($ad['start_date'])) . " - " . date('M d, Y', strtotime($ad['end_date'])) . "<br>";
    $message .= "• Total Views: " . number_format($ad['total_views']) . "<br>";
    $message .= "• Click-throughs: " . number_format($ad['click_throughs']) . "<br><br>";
    $message .= "If you'd like to renew this advertisement, please contact our support team.";
    
    return sendTemplatedEmail(
        $ad['email'],
        $advertiser_name,
        $subject,
        $message,
        'Contact Support',
        SITE_URL . '/contact.php'
    );
}

/**
 * Send WhatsApp message via configured API endpoint
 */
function sendWhatsAppAPI($to, $message, $media = null) {
    // Termii WhatsApp uses the same endpoint as SMS with channel='whatsapp'
    if (empty(TERMII_API_KEY)) {
        error_log("Termii API key not configured for WhatsApp");
        return ['success' => false, 'error' => 'Termii API not configured'];
    }

    // Format phone number to international format (234...)
    $to = formatNigerianPhone($to);

    $payload = [
        'to' => $to,
        'from' => TERMII_SENDER_ID ?? 'OpinionHub',
        'sms' => $message,
        'type' => 'plain',
        'channel' => 'whatsapp', // Termii uses 'whatsapp' channel
        'api_key' => TERMII_API_KEY
    ];

    
    // Optional media for images/videos/audio
    if ($media !== null && is_array($media)) {
        $payload['media'] = $media;
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.ng.termii.com/api/sms/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ]
    ]);

    $resp = curl_exec($curl);
    $err = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err) {
        error_log("Termii WhatsApp cURL Error: " . $err);
        return ['success' => false, 'error' => $err];
    }

    // Log raw response for debugging
    error_log("Termii WhatsApp Response (HTTP $http_code): " . $resp);
    
    $data = json_decode($resp, true);
    
    if ($data === null) {
        error_log("Termii WhatsApp - Invalid JSON response");
        return ['success' => false, 'error' => 'Invalid API response', 'raw_response' => $resp];
    }
    
    // Check for successful response
    if (isset($data['code']) && $data['code'] === 'ok') {
        return ['success' => true, 'data' => $data];
    }

    error_log("Termii WhatsApp Failed: " . json_encode($data));
    return ['success' => false, 'error' => $data['message'] ?? 'Unknown error', 'response' => $data];
}

/**
 * Send Airtime/Data via VTU provider (generic)
 * NOTE: You must configure VTU_API_URL and VTU_API_KEY in connect.php
 */
function sendAirtime_VTU($phone, $product_code, $amount = null) {
    if (!defined('VTU_API_URL') || !defined('VTU_API_KEY')) {
        return ['success' => false, 'error' => 'VTU API not configured'];
    }

    $payload = [
        'phone' => $phone,
        'product_code' => $product_code
    ];
    if ($amount !== null) $payload['amount'] = $amount;

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'gggfgf',//VTU_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer '  //VTU_API_KEY
        ]
    ]);

    $resp = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) return ['success' => false, 'error' => $err];
    $data = json_decode($resp, true);
    return $data ?: ['success' => false, 'response' => $resp];
}

/**
 * VTPass API Integration - Send Airtime/Data to agents
 */
function vtpass_send_airtime($phone, $network_id, $amount) {
    if (!VTPASS_ENABLED) {
        return ['success' => false, 'message' => 'VTPass integration not enabled'];
    }

    // Generate proper request ID format: YYYYMMDDHHMI + random string
    date_default_timezone_set('Africa/Lagos');
    $request_id = date('YmdHi') . '_' . bin2hex(random_bytes(8));
    
    // Map network to proper serviceID for airtime
    $serviceID = $network_id;
    if (!str_contains($network_id, '-')) {
        $serviceID = $network_id; // mtn, glo, airtel, etisalat already correct for airtime
    }
    
    $payload = [
        'request_id' => $request_id,
        'serviceID' => $serviceID,
        'amount' => $amount,
        'phone' => $phone
    ];

    $response = vtpass_curl_post('pay', $payload);
    
    return [
        'success' => isset($response['code']) && $response['code'] === '000',
        'message' => $response['response_description'] ?? $response['message'] ?? 'Unknown error',
        'data' => $response,
        'request_id' => $response['requestId'] ?? $request_id
    ];
}

function vtpass_send_data($phone, $variation_code) {
    if (!VTPASS_ENABLED) {
        return ['success' => false, 'message' => 'VTPass integration not enabled'];
    }

    // Generate proper request ID format: YYYYMMDDHHMI + random string
    date_default_timezone_set('Africa/Lagos');
    $request_id = date('YmdHi') . '_' . bin2hex(random_bytes(8));
    
    // Map variation code prefix to correct serviceID
    // MTN: mtn-10mb-100 -> mtn-data
    // GLO: glo100, glo-daily-50 -> glo-data
    // Airtel: airt-50, airt-100 -> airtel-data
    // 9mobile: eti-100, eti-200 -> etisalat-data
    
    $serviceID = '';
    if (strpos($variation_code, 'mtn') === 0) {
        $serviceID = 'mtn-data';
    } elseif (strpos($variation_code, 'glo') === 0) {
        $serviceID = 'glo-data';
    } elseif (strpos($variation_code, 'airt') === 0) {
        $serviceID = 'airtel-data';
    } elseif (strpos($variation_code, 'eti') === 0) {
        $serviceID = 'etisalat-data';
    } else {
        // Fallback: try to extract from variation code
        $parts = explode('-', $variation_code);
        $serviceID = $parts[0] . '-data';
    }
    
    $payload = [
        'request_id' => $request_id,
        'serviceID' => $serviceID,
        'billersCode' => $phone,
        'variation_code' => $variation_code,
        'phone' => $phone
    ];

    $response = vtpass_curl_post('pay', $payload);
    
    return [
        'success' => isset($response['code']) && $response['code'] === '000',
        'message' => $response['response_description'] ?? $response['message'] ?? 'Unknown error',
        'data' => $response,
        'request_id' => $response['requestId'] ?? $request_id
    ];
}

function vtpass_curl_post($endpoint, $data) {
    $url = VTPASS_API_URL . $endpoint;
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'api-key: ' . VTPASS_API_KEY,
        'secret-key: ' . VTPASS_SECRET_KEY
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        return ['success' => false, 'message' => 'cURL Error: ' . $error];
    }
    
    return json_decode($response, true) ?: ['success' => false, 'message' => 'Invalid JSON response'];
}

function vtpass_curl_get($endpoint) {
    $url = VTPASS_API_URL . $endpoint;
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'api-key: ' . VTPASS_API_KEY,
        'public-key: ' . VTPASS_PUBLIC_KEY
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        return ['success' => false, 'message' => 'cURL Error: ' . $error];
    }
    
    return json_decode($response, true) ?: ['success' => false, 'message' => 'Invalid JSON response'];
}

/**
 * Get VTPass data bundles for a network
 */
function vtpass_get_data_variations($network) {
    // network can be: mtn, glo, airtel, etisalat
    $serviceID = $network . '-data';
    $response = vtpass_curl_get('service-variations?serviceID=' . $serviceID);
    
    if (isset($response['content']) && isset($response['content']['varations'])) {
        return $response['content']['varations'];
    }
    
    return [];
}

/**
 * Wrapper to deduct credits when sending messages
 */
function deductCredits($user_id, $type, $units = 1) {
    global $conn;
    $credits = getMessagingCredits($user_id);
    if (!$credits) {
        return false;
    }

    if ($type === 'sms') {
        $new = max(0, $credits['sms_balance'] - $units);
        return $conn->query("UPDATE messaging_credits SET sms_balance = $new WHERE user_id = $user_id");
    }
    if ($type === 'email') {
        $new = max(0, $credits['email_balance'] - $units);
        return $conn->query("UPDATE messaging_credits SET email_balance = $new WHERE user_id = $user_id");
    }
    if ($type === 'whatsapp') {
        $new = max(0, $credits['whatsapp_balance'] - $units);
        return $conn->query("UPDATE messaging_credits SET whatsapp_balance = $new WHERE user_id = $user_id");
    }
    return false;
}

/**
 * Award Referral Bonus
 * Awards bonus to referrer when referred user makes a purchase
 */
function awardReferralBonus($user_id, $purchase_type = 'sms_credits', $amount = 500) {
    global $conn;
    
    // Get the user who referred this user
    $user = $conn->query("SELECT referred_by FROM users WHERE id = $user_id")->fetch_assoc();
    
    if (!$user || !$user['referred_by']) {
        return false; // No referrer
    }
    
    $referrer_id = $user['referred_by'];
    
    // Define referral bonuses
    $bonus_amount = 0;
    switch ($purchase_type) {
        case 'sms_credits':
            $bonus_amount = 1000; // ₦1000 bonus for SMS purchase
            break;
        case 'subscription':
            $bonus_amount = 5000; // ₦5000 bonus for subscription
            break;
        case 'poll_payment':
            $bonus_amount = $amount * 0.10; // 10% of poll payment
            break;
        default:
            $bonus_amount = 500;
    }
    
    // Award to referrer's earnings
    $conn->query("UPDATE users SET total_earnings = total_earnings + $bonus_amount WHERE id = $referrer_id");
    
    // Create earnings record
    $description = "Referral bonus from {$purchase_type}";
    $conn->query("INSERT INTO agent_earnings (agent_id, earning_type, amount, description, status) 
                 VALUES ($referrer_id, 'referral_bonus', $bonus_amount, '$description', 'completed')");
    
    // Notify referrer
    createNotification(
        $referrer_id,
        'success',
        'Referral Bonus Earned!',
        "You earned ₦" . number_format($bonus_amount, 2) . " from a referral bonus ($purchase_type)"
    );
    
    return true;
}

/**
 * Create Payment Reference (Paystack)
 */
function initializePayment($email, $amount, $type = 'subscription', $metadata = null) {
    global $conn;
    
    $reference = generateReference('PAY');
    $user = getCurrentUser();
    $user_id = $user['id'];
    
    // Prepare metadata for storage
    $metadata_json = is_string($metadata) ? $metadata : json_encode($metadata);
    
    $stmt = $conn->prepare("INSERT INTO transactions 
                 (user_id, reference, amount, type, status, payment_method, metadata) 
                 VALUES (?, ?, ?, ?, 'pending', 'paystack', ?)");
    $stmt->bind_param("isdss", $user_id, $reference, $amount, $type, $metadata_json);
    $stmt->execute();
    
    $payload = [
        'reference' => $reference,
        'amount' => $amount * 100, // Convert to kobo
        'email' => $email,
        'callback_url' => SITE_URL . '/payment-callback.php'
    ];
    
    if ($metadata) {
        $payload['metadata'] = is_array($metadata) ? $metadata : json_decode($metadata, true);
    }
    
    $curl = curl_init();
    $paystack_key = defined('PAYSTACK_SECRET_KEY') ? constant('PAYSTACK_SECRET_KEY') : '';
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $paystack_key,
            "Content-Type: application/json"
        ]
    ]);
    
    $response = curl_exec($curl);
    curl_close($curl);
    
    return json_decode($response, true);
}

/**
 * Verify Payment (Paystack) - DISABLED
 * Note: Paystack is no longer used. Use verifyVPayPayment() instead.
 */
function verifyPayment($reference) {
    $secret_key = getSetting('paystack_secret_key');
    if (!$secret_key || $secret_key === 'sk_test_YOUR_PAYSTACK_SECRET_KEY') {
        $secret_key = defined('PAYSTACK_SECRET_KEY') ? constant('PAYSTACK_SECRET_KEY') : '';
    }
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.paystack.co/transaction/verify/$reference",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $secret_key,
        ]
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        return ['status' => false, 'message' => $error];
    }
    
    return json_decode($response, true);
}

/**
 * Verify Payment (vPay Africa)
 */
function verifyVPayPayment($reference) {
    $secret_key = defined('VPAY_SECRET_KEY') ? VPAY_SECRET_KEY : '';
    $merchant_id = defined('VPAY_MERCHANT_ID') ? VPAY_MERCHANT_ID : '';
    
    if (empty($secret_key) || empty($merchant_id)) {
        return [
            'status' => false, 
            'message' => 'vPay Africa API credentials not configured'
        ];
    }
    
    // vPay Africa verification endpoint
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.vpay.africa/v1/verify/$reference",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $secret_key,
            "X-Merchant-ID: " . $merchant_id,
            "Content-Type: application/json"
        ]
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($error) {
        return ['status' => false, 'message' => 'Connection error: ' . $error];
    }
    
    $result = json_decode($response, true);
    
    // Check for successful response
    if ($http_code === 200 && isset($result['status']) && $result['status'] === true) {
        return [
            'status' => true,
            'data' => [
                'status' => 'success',
                'amount' => $result['data']['amount'] ?? 0,
                'reference' => $result['data']['reference'] ?? $reference,
                'metadata' => $result['data']['metadata'] ?? []
            ]
        ];
    }
    
    return [
        'status' => false,
        'message' => $result['message'] ?? 'Payment verification failed'
    ];
}

/**
 * Get User by ID
 */
function getUserById($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    if (!$stmt) {
        error_log("getUserById prepare failed: " . $conn->error);
        return null;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get Poll Details
 */
function getPoll($poll_id) {
    global $conn;
    $query = "SELECT p.*, 
                     u.first_name, u.last_name, u.username,
                     c.name as category_name, 
                     sc.name as sub_category_name
              FROM polls p
              LEFT JOIN users u ON p.created_by = u.id
              LEFT JOIN categories c ON p.category_id = c.id
              LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
              WHERE p.id = $poll_id";
    $result = $conn->query($query);
    return $result ? $result->fetch_assoc() : null;
}

/**
 * Get Poll Questions with Options
 */
function getPollQuestions($poll_id) {
    global $conn;
    $questions = [];
    $result = $conn->query("SELECT * FROM poll_questions WHERE poll_id = $poll_id ORDER BY question_order");
    
    while ($question = $result->fetch_assoc()) {
        $options_result = $conn->query("SELECT * FROM poll_question_options WHERE question_id = " . $question['id'] . " ORDER BY option_order");
        $question['options'] = [];
        
        while ($option = $options_result->fetch_assoc()) {
            $question['options'][] = $option;
        }
        
        $questions[] = $question;
    }
    
    return $questions;
}

/**
 * Get Poll Response Count
 */
function getPollResponseCount($poll_id) {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) as count FROM poll_responses WHERE poll_id = $poll_id");
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Submit Poll Response
 */
function submitPollResponse($poll_id, $respondent_id, $responses) {
    global $conn;
    
    // Create response record
    $respondent_ip = $_SERVER['REMOTE_ADDR'];
    $session_id = session_id();
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // Get tracking code from session if present
    $tracking_code = isset($_SESSION['tracking_code']) ? sanitize($_SESSION['tracking_code']) : null;
    
    $conn->query("INSERT INTO poll_responses 
                 (poll_id, respondent_id, respondent_ip, session_id, user_agent, tracking_code) 
                 VALUES ($poll_id, " . ($respondent_id ?: 'NULL') . ", '$respondent_ip', '$session_id', '$user_agent', " . 
                 ($tracking_code ? "'$tracking_code'" : 'NULL') . ")");
    
    $response_id = $conn->insert_id;
    
    // Get poll commission rate
    $poll_query = $conn->query("SELECT price_per_response FROM polls WHERE id = $poll_id");
    $commission = 0;
    if ($poll_query && $poll_query->num_rows > 0) {
        $poll = $poll_query->fetch_assoc();
        $commission = floatval($poll['price_per_response'] ?? 0);
    }
    
    $agent_id_to_credit = null;
    $description = '';
    
    // Check if response was from agent tracking link (for referral commission)
    if ($tracking_code && preg_match('/USR(\d+)/', $tracking_code, $matches)) {
        $agent_id_to_credit = (int)$matches[1];
        $description = "Referral commission for poll response (Tracking: $tracking_code)";
    }
    // If no tracking code but respondent is an agent, credit them directly
    elseif ($respondent_id) {
        $user_check = $conn->query("SELECT role, status FROM users WHERE id = $respondent_id");
        if ($user_check && $user_check->num_rows > 0) {
            $user = $user_check->fetch_assoc();
            if ($user['role'] === 'agent' && $user['status'] !== 'suspended') {
                $agent_id_to_credit = $respondent_id;
                $description = "Direct completion of poll response";
            }
        }
    }
    
    // Credit the agent if applicable
    if ($agent_id_to_credit && $commission > 0) {
        // Verify agent exists and is active
        $agent_check = $conn->query("SELECT id, status FROM users 
                                    WHERE id = $agent_id_to_credit AND role = 'agent'");
        if ($agent_check && $agent_check->num_rows > 0) {
            $agent = $agent_check->fetch_assoc();
            
            // Only credit if agent is not suspended
            if ($agent['status'] !== 'suspended') {
                // Credit agent with commission
                $conn->query("INSERT INTO agent_earnings 
                            (agent_id, poll_id, earning_type, amount, description, status, created_at) 
                            VALUES ($agent_id_to_credit, $poll_id, 'poll_response', $commission, '$description', 'pending', NOW())");
                
                // Update user's total_earnings and pending_earnings
                $conn->query("UPDATE users 
                            SET total_earnings = total_earnings + $commission,
                                pending_earnings = pending_earnings + $commission
                            WHERE id = $agent_id_to_credit");
            }
        }
    }
    
    // Clear tracking code from session after use
    if ($tracking_code) {
        unset($_SESSION['tracking_code']);
    }
    
    // Insert question responses
    foreach ($responses as $question_id => $answer) {
        $question_id = (int)$question_id;
        
        // Handle different answer types
        if (is_array($answer)) {
            // Check if it's a nested array (Matrix or Date Range)
            $first_value = reset($answer);
            
            if (!empty($answer) && !isset($answer['start']) && !is_numeric(key($answer))) {
                // Matrix question - format: [row_id => column_index]
                // Store as JSON in text_response for proper grid display
                $matrix_data = [];
                foreach ($answer as $row_id => $col_idx) {
                    $matrix_data[(int)$row_id] = (int)$col_idx;
                }
                $matrix_json = json_encode($matrix_data);
                $conn->query("INSERT INTO question_responses 
                             (response_id, question_id, text_response) 
                             VALUES ($response_id, $question_id, '$matrix_json')");
            } elseif (isset($answer['start']) && isset($answer['end'])) {
                // Date Range question
                $start_date = sanitize($answer['start']);
                $end_date = sanitize($answer['end']);
                $text_response = json_encode(['start' => $start_date, 'end' => $end_date]);
                $conn->query("INSERT INTO question_responses 
                             (response_id, question_id, text_response) 
                             VALUES ($response_id, $question_id, '$text_response')");
            } else {
                // Multiple Answer question - array of option IDs
                foreach ($answer as $option_id) {
                    $option_id = (int)$option_id;
                    $conn->query("INSERT INTO question_responses 
                                 (response_id, question_id, option_id) 
                                 VALUES ($response_id, $question_id, $option_id)");
                }
            }
        } elseif (is_numeric($answer) && $answer > 0 && $answer < 1000) {
            // Likely an option ID (Multiple Choice, Quiz, Assessment, Dichotomous, etc.)
            $option_id = (int)$answer;
            $conn->query("INSERT INTO question_responses 
                         (response_id, question_id, option_id) 
                         VALUES ($response_id, $question_id, $option_id)");
        } else {
            // Text response (Open-ended, Word Cloud, Date, Yes/No, Ratings)
            $answer = sanitize($answer);
            $conn->query("INSERT INTO question_responses 
                         (response_id, question_id, text_response) 
                         VALUES ($response_id, $question_id, '$answer')");
        }
    }
    
    // Update poll response count
    $conn->query("UPDATE polls SET total_responses = total_responses + 1 WHERE id = $poll_id");
    
    return $response_id;
}

/**
 * Get Poll Statistics
 */
function getPollStats($poll_id) {
    global $conn;
    
    $stats = [
        'total_responses' => 0,
        'questions' => []
    ];
    
    $poll_result = $conn->query("SELECT total_responses FROM polls WHERE id = $poll_id");
    $poll = $poll_result->fetch_assoc();
    $stats['total_responses'] = $poll['total_responses'];
    
    $questions = getPollQuestions($poll_id);
    
    foreach ($questions as $question) {
        $question_stats = [
            'question_id' => $question['id'],
            'question_text' => $question['question_text'],
            'type' => $question['question_type'],
            'options' => []
        ];
        
        if (in_array($question['question_type'], ['multiple_choice', 'yes_no', 'rating'])) {
            foreach ($question['options'] as $option) {
                $count_result = $conn->query("SELECT COUNT(*) as count FROM question_responses 
                                              WHERE question_id = " . $question['id'] . " 
                                              AND option_id = " . $option['id']);
                $count_data = $count_result->fetch_assoc();
                $count = $count_data['count'];
                $percentage = $stats['total_responses'] > 0 ? ($count / $stats['total_responses']) * 100 : 0;
                
                $question_stats['options'][] = [
                    'option_id' => $option['id'],
                    'option_text' => $option['option_text'],
                    'count' => $count,
                    'percentage' => round($percentage, 2)
                ];
            }
        }
        
        $stats['questions'][] = $question_stats;
    }
    
    return $stats;
}

/**
 * Log Activity
 */
function logActivity($user_id, $action, $details = '') {
    // Can be extended to store activity logs in database
    // For now, just logging to system
}

/**
 * Process agent payout (airtime/data/cash)
 */
function processAgentPayout($agent_id, $task_id, $reward_type, $amount_or_bundle) {
    global $conn;
    
    // Get agent details
    $agent_query = $conn->query("SELECT a.*, u.phone FROM agents a 
                                 JOIN users u ON a.user_id = u.id 
                                 WHERE a.id = $agent_id");
    if (!$agent_query || $agent_query->num_rows === 0) {
        return ['success' => false, 'error' => 'Agent not found'];
    }
    $agent = $agent_query->fetch_assoc();
    
    if ($reward_type === 'airtime') {
        // Send airtime via VTU
        $product_code = 'AIRTIME_' . (int)$amount_or_bundle;
        $result = sendAirtime_VTU($agent['phone'], $product_code, $amount_or_bundle);
        
        // Log payout
        $status = $result['success'] ? 'completed' : 'failed';
        $response = json_encode($result);
        $stmt = $conn->prepare("INSERT INTO vtu_payouts (agent_id, task_id, phone, amount, product_code, payout_type, status, provider_response, completed_at) 
                                VALUES (?, ?, ?, ?, ?, 'airtime', ?, ?, NOW())");
        $stmt->bind_param("iisdsss", $agent_id, $task_id, $agent['phone'], $amount_or_bundle, $product_code, $status, $response);
        $stmt->execute();
        
        return $result;
        
    } elseif ($reward_type === 'data') {
        // Send data bundle via VTU
        $product_code = $amount_or_bundle;
        $result = sendAirtime_VTU($agent['phone'], $product_code);
        
        // Log payout
        $status = $result['success'] ? 'completed' : 'failed';
        $response = json_encode($result);
        $stmt = $conn->prepare("INSERT INTO vtu_payouts (agent_id, task_id, phone, product_code, payout_type, status, provider_response, completed_at) 
                                VALUES (?, ?, ?, ?, 'data', ?, ?, NOW())");
        $stmt->bind_param("iissss", $agent_id, $task_id, $agent['phone'], $product_code, $status, $response);
        $stmt->execute();
        
        return $result;
        
    } else {
        // Update pending earnings
        $conn->query("UPDATE agents SET pending_earnings = pending_earnings + $amount_or_bundle WHERE id = $agent_id");
        return ['success' => true, 'message' => 'Cash earnings added to pending balance'];
    }
}

/**
 * Import contacts from CSV array
 */
function importContacts($list_id, $contacts_array) {
    global $conn;
    
    $imported = 0;
    $errors = [];
    
    foreach ($contacts_array as $contact) {
        $name = isset($contact['name']) ? sanitize($contact['name']) : '';
        $phone = isset($contact['phone']) ? sanitize($contact['phone']) : '';
        $email = isset($contact['email']) ? sanitize($contact['email']) : '';
        $whatsapp = isset($contact['whatsapp']) ? sanitize($contact['whatsapp']) : $phone;
        
        if (empty($phone) && empty($email)) {
            $errors[] = "Skipped contact: must have phone or email";
            continue;
        }
        
        $stmt = $conn->prepare("INSERT INTO contacts (list_id, name, phone, email, whatsapp) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $list_id, $name, $phone, $email, $whatsapp);
        
        if ($stmt->execute()) {
            $imported++;
        } else {
            $errors[] = "Failed to import: " . ($name ?: $phone ?: $email);
        }
    }
    
    // Update contact count
    $conn->query("UPDATE contact_lists SET total_contacts = (SELECT COUNT(*) FROM contacts WHERE list_id = $list_id) WHERE id = $list_id");
    
    return ['imported' => $imported, 'errors' => $errors];
}

/**
 * Get agent by user_id
 */
function getAgentByUserId($user_id) {
    global $conn;
    $result = $conn->query("SELECT * FROM agents WHERE user_id = $user_id");
    return $result ? $result->fetch_assoc() : null;
}

/**
 * Log message send
 */
function logMessage($user_id, $type, $recipient, $message, $status, $credits_used = 1, $response = '') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO message_logs (user_id, message_type, recipient, message_content, status, credits_used, response_data) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        error_log("Failed to prepare logMessage statement: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("issssis", $user_id, $type, $recipient, $message, $status, $credits_used, $response);
    $result = $stmt->execute();
    
    if (!$result) {
        error_log("Failed to execute logMessage: " . $stmt->error);
    }
    
    return $result;
}

/**
 * Get filtered agents for targeting
 */
function getFilteredAgents($filters = []) {
    global $conn;
    
    $where = ["a.approval_status = 'approved'", "a.profile_completed = 1"];
    
    if (!empty($filters['age_min'])) {
        $where[] = "a.age >= " . (int)$filters['age_min'];
    }
    if (!empty($filters['age_max'])) {
        $where[] = "a.age <= " . (int)$filters['age_max'];
    }
    if (!empty($filters['gender'])) {
        $gender = sanitize($filters['gender']);
        $where[] = "a.gender = '$gender'";
    }
    if (!empty($filters['state'])) {
        $state = sanitize($filters['state']);
        $where[] = "a.state = '$state'";
    }
    if (!empty($filters['education_level'])) {
        $edu = sanitize($filters['education_level']);
        $where[] = "a.education_level = '$edu'";
    }
    
    $where_clause = implode(" AND ", $where);
    
    $query = "SELECT a.*, u.name, u.email, u.phone 
              FROM agents a 
              JOIN users u ON a.user_id = u.id 
              WHERE $where_clause";
    
    $result = $conn->query($query);
    $agents = [];
    while ($row = $result->fetch_assoc()) {
        $agents[] = $row;
    }
    return $agents;
}

/**
 * ============================================================
 * SITE SETTINGS FUNCTIONS
 * ============================================================
 * Get and update site-wide configurable settings
 */

/**
 * Get Site Setting Value
 * @param string $key The setting key
 * @param mixed $default Default value if setting not found
 * @return mixed The setting value, cast to appropriate type
 */
function getSetting($key, $default = null) {
    global $conn;
    $key = sanitize($key);
    
    $result = $conn->query("SELECT setting_value, setting_type FROM site_settings WHERE setting_key = '$key' LIMIT 1");
    
    if (!$result || $result->num_rows === 0) {
        return $default;
    }
    
    $setting = $result->fetch_assoc();
    $value = $setting['setting_value'];
    $type = $setting['setting_type'];
    
    // Cast to appropriate type
    switch ($type) {
        case 'number':
            return is_numeric($value) ? (strpos($value, '.') !== false ? floatval($value) : intval($value)) : $default;
        case 'boolean':
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        case 'json':
            return json_decode($value, true) ?? $default;
        default:
            return $value;
    }
}

/**
 * Update Site Setting
 * @param string $key The setting key
 * @param mixed $value The new value
 * @param int $user_id The admin user making the change
 * @return bool Success status
 */
function updateSetting($key, $value, $user_id = null) {
    global $conn;
    $key = sanitize($key);
    
    // Get current setting to determine type
    $result = $conn->query("SELECT setting_type FROM site_settings WHERE setting_key = '$key' LIMIT 1");
    
    if (!$result || $result->num_rows === 0) {
        return false;
    }
    
    $setting = $result->fetch_assoc();
    $type = $setting['setting_type'];
    
    // Convert value to string for storage
    if ($type === 'boolean') {
        $value = $value ? 'true' : 'false';
    } elseif ($type === 'json') {
        $value = json_encode($value);
    }
    
    $value = sanitize($value);
    $user_clause = $user_id ? ", updated_by = $user_id" : "";
    
    return $conn->query("UPDATE site_settings SET setting_value = '$value' $user_clause WHERE setting_key = '$key'");
}

/**
 * Get All Settings by Category
 * @param string $category The category name (optional)
 * @return array Array of settings
 */
function getSettingsByCategory($category = null) {
    global $conn;
    
    $where = $category ? "WHERE category = '" . sanitize($category) . "'" : "";
    $result = $conn->query("SELECT * FROM site_settings $where ORDER BY category, setting_key");
    
    $settings = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[] = $row;
        }
    }
    
    return $settings;
}

/**
 * Quick access functions for commonly used settings
 */
function getAgentCommission() {
    return getSetting('agent_commission_per_poll', 1000);
}

function getMinPayout() {
    return getSetting('agent_min_payout', 5000);
}

function getPaymentProcessingDays() {
    return getSetting('agent_payment_processing_days', 5);
}

function getCompanyInfo() {
    return [
        'name' => getSetting('company_name', 'Foraminifera Market Research Limited'),
        'address' => getSetting('company_address', '61-65 Egbe-Isolo Road, Iyana Ejigbo Shopping Arcade, Block A, Suite 19, Ejigbo, Lagos'),
        'phone' => getSetting('company_phone', '+234 (0) 803 3782 777'),
        'email' => getSetting('company_email', 'hello@opinionhub.ng')
    ];
}

/**
 * Create a notification for a user
 * @param int $user_id The user to notify
 * @param string $type Notification type (blog_approved, blog_rejected, new_comment, payout_processed, agent_status_changed)
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $link Optional link to related page
 * @return bool Success status
 */
function createNotification($user_id, $type, $title, $message, $link = '') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("issss", $user_id, $type, $title, $message, $link);
    return $stmt->execute();
}

/**
 * Get unread notification count for a user
 * @param int $user_id The user ID
 * @return int Number of unread notifications
 */
function getUnreadNotificationCount($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['count'] ?? 0;
}

/**
 * Mark notification as read
 * @param int $notification_id The notification ID
 * @return bool Success status
 */
function markNotificationRead($notification_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $notification_id);
    return $stmt->execute();
}

/**
 * Mark all notifications as read for a user
 * @param int $user_id The user ID
 * @return bool Success status
 */
function markAllNotificationsRead($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

/**
 * Send email via Brevo API
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (plain text)
 * @return bool Success status
 */
function sendEmailViaBrevo($to, $subject, $body) {
    $api_key = getSetting('brevo_api_key', '');
    
    if (empty($api_key) || $api_key === 'your-brevo-api-key') {
        error_log("Brevo API key not configured");
        return false;
    }
    
    $company_info = getCompanyInfo();
    
    $url = 'https://api.brevo.com/v3/smtp/email';
    $data = [
        'sender' => ['email' => 'noreply@opinionhub.ng', 'name' => $company_info['name']],
        'to' => [['email' => $to]],
        'subject' => $subject,
        'textContent' => $body
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code == 201;
}

/**
 * Send SMS via Termii API
 * @param string $to Recipient phone number (e.g., 2348012345678)
 * @param string $message SMS message
 * @return bool Success status
 */
function sendSMSViaTermii($to, $message) {
    $api_key = getSetting('termii_api_key', '');
    
    if (empty($api_key) || $api_key === 'your-termii-api-key') {
        error_log("Termii API key not configured");
        return false;
    }
    
    // Format phone number (remove leading 0, add 234)
    $to = preg_replace('/^0/', '234', $to);
    $to = preg_replace('/^(\+|00)?234/', '234', $to);
    
    $url = 'https://api.ng.termii.com/api/sms/send';
    $data = [
        'to' => $to,
        'from' => 'OpinionHub',
        'sms' => $message,
        'type' => 'plain',
        'channel' => 'generic',
        'api_key' => $api_key
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return isset($result['message_id']);
}

/**
 * Get agent's SMS credits balance
 * @param int $agent_id The agent user ID
 * @return int Credits balance
 */
function getAgentSMSCredits($agent_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT SUM(CASE WHEN transaction_type = 'purchase' THEN amount 
                                        WHEN transaction_type = 'used' THEN -amount 
                                        WHEN transaction_type = 'refund' THEN amount 
                                        ELSE 0 END) as balance 
                           FROM agent_sms_credits 
                           WHERE agent_id = ?");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return max(0, intval($result['balance'] ?? 0));
}

/**
 * Add SMS credits to agent account
 * @param int $agent_id The agent user ID
 * @param int $credits Number of credits to add
 * @param float $amount_paid Amount paid for credits
 * @param string $description Transaction description
 * @return bool Success status
 */
function addAgentSMSCredits($agent_id, $credits, $amount_paid = 0, $description = 'Credits purchase') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO agent_sms_credits (agent_id, credits, transaction_type, amount, amount_paid, description) 
                           VALUES (?, ?, 'purchase', ?, ?, ?)");
    $stmt->bind_param("iiids", $agent_id, $credits, $credits, $amount_paid, $description);
    return $stmt->execute();
}

/**
 * Deduct SMS credit from agent account
 * @param int $agent_id The agent user ID
 * @param string $description Transaction description
 * @return bool Success status
 */
function deductAgentSMSCredit($agent_id, $description = 'SMS sent') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO agent_sms_credits (agent_id, credits, transaction_type, amount, description) 
                           VALUES (?, 1, 'used', 1, ?)");
    $stmt->bind_param("is", $agent_id, $description);
    return $stmt->execute();
}

/**
 * Get User's Current Active Subscription
 * @param int $user_id User ID
 * @return array|null Subscription details or null if no active subscription
 */
function getCurrentSubscription($user_id) {
    global $conn;
    
    $result = $conn->query("SELECT us.*, sp.* 
                           FROM user_subscriptions us 
                           JOIN subscription_plans sp ON us.plan_id = sp.id 
                           WHERE us.user_id = $user_id 
                           AND us.status = 'active' 
                           AND us.end_date > NOW() 
                           ORDER BY us.end_date DESC 
                           LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // Return free plan as default
    return $conn->query("SELECT * FROM subscription_plans WHERE type = 'free' LIMIT 1")->fetch_assoc();
}

/**
 * Check if User Can Create Poll (within subscription limits)
 * @param int $user_id User ID
 * @return array ['allowed' => bool, 'message' => string, 'current' => int, 'limit' => int]
 */
function checkPollCreationLimit($user_id) {
    global $conn;
    
    $subscription = getCurrentSubscription($user_id);
    
    // Count polls created this month
    $count = $conn->query("SELECT COUNT(*) as count FROM polls 
                          WHERE created_by = $user_id 
                          AND MONTH(created_at) = MONTH(NOW()) 
                          AND YEAR(created_at) = YEAR(NOW())")->fetch_assoc()['count'];
    
    $limit = $subscription['max_polls_per_month'];
    $allowed = ($limit == 999) || ($count < $limit);
    
    $message = $allowed ? 
               "You can create " . ($limit == 999 ? 'unlimited' : ($limit - $count)) . " more polls this month" :
               "You've reached your monthly poll limit ($limit). <a href='client/subscription.php'>Upgrade your plan</a>";
    
    return [
        'allowed' => $allowed,
        'message' => $message,
        'current' => $count,
        'limit' => $limit
    ];
}

/**
 * Check if Poll Can Accept More Responses (within subscription limits)
 * @param int $poll_id Poll ID
 * @return array ['allowed' => bool, 'message' => string, 'current' => int, 'limit' => int]
 */
function checkPollResponseLimit($poll_id) {
    global $conn;
    
    // Get poll owner
    $poll = $conn->query("SELECT created_by FROM polls WHERE id = $poll_id")->fetch_assoc();
    if (!$poll) {
        return ['allowed' => false, 'message' => 'Poll not found', 'current' => 0, 'limit' => 0];
    }
    
    $subscription = getCurrentSubscription($poll['created_by']);
    
    // Count responses for this poll
    $count = $conn->query("SELECT COUNT(*) as count FROM poll_responses WHERE poll_id = $poll_id")->fetch_assoc()['count'];
    
    $limit = $subscription['responses_per_poll'];
    $allowed = ($limit == 999999) || ($count < $limit);
    
    $message = $allowed ? 
               "This poll can accept " . ($limit == 999999 ? 'unlimited' : ($limit - $count)) . " more responses" :
               "This poll has reached its response limit ($limit)";
    
    return [
        'allowed' => $allowed,
        'message' => $message,
        'current' => $count,
        'limit' => $limit
    ];
}

/**
 * Check and Expire Old Subscriptions (Run via cron or on page load)
 */
function expireOldSubscriptions() {
    global $conn;
    
    $conn->query("UPDATE user_subscriptions 
                 SET status = 'expired' 
                 WHERE status = 'active' 
                 AND end_date < NOW()");
    
    return $conn->affected_rows;
}

/**
 * ============================================================
 * VPAY PAYMENT HELPERS
 * ============================================================
 */

/**
 * Get VPay environment (sandbox or live) based on merchant ID
 * @return string 'sandbox' or 'live'
 */
function getVPayEnvironment() {
    $merchant_id = defined('VPAY_MERCHANT_ID') ? VPAY_MERCHANT_ID : '';
    
    // If merchant ID contains 'test' or 'sandbox', use sandbox
    if (stripos($merchant_id, 'test') !== false || 
        stripos($merchant_id, 'sandbox') !== false || 
        stripos($merchant_id, 'YOUR_MERCHANT') !== false) {
        return 'sandbox';
    }
    
    return 'live';
}

/**
 * Get VPay Dropin script URL based on environment
 * @return string Script URL
 */
function getVPayScriptUrl() {
    $env = getVPayEnvironment();
    
    if ($env === 'sandbox') {
        return 'https://dropin-sandbox.vpay.africa/dropin/v1/initialise.js';
    }
     
    return 'https://dropin.vpay.africa/dropin/v1/initialise.js';
}

/**
 * Get VPay configuration array for JavaScript
 * @return array VPay config
 */
function getVPayConfig() {
    return [
        'key' => defined('VPAY_PUBLIC_KEY') ? VPAY_PUBLIC_KEY : '',
        'domain' => getVPayEnvironment(),
        'script_url' => getVPayScriptUrl()
    ];
}

/**
 * Get Question Count for a Poll
 * @param int $poll_id
 * @return int
 */
function getPollQuestionCount($poll_id) {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) as count FROM poll_questions WHERE poll_id = $poll_id");
    return $result ? $result->fetch_assoc()['count'] : 0;
}

/**
 * Get Poll Progress Percentage
 * @param array $poll
 * @return float
 */
function getPollProgressPercentage($poll) {
    $target = intval($poll['target_responders'] ?? 100);
    $responses = intval($poll['total_responses'] ?? 0);

    if ($target <= 0) return 0;

    return min(100, round(($responses / $target) * 100, 1));
}