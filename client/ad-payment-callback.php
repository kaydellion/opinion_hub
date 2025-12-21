<?php
/**
 * ad-payment-callback.php - Handle advertisement payment callback from vPay Africa
 */

require_once '../connect.php';
require_once '../functions.php';

// Require login
if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$reference = isset($_GET['reference']) ? sanitize($_GET['reference']) : '';
$ad_id = isset($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;

if (!$reference || !$ad_id) {
    $_SESSION['error'] = "Invalid payment reference.";
    header('Location: ' . SITE_URL . 'client/my-ads.php');
    exit;
}

// Verify payment with vPay Africa
$result = verifyVPayPayment($reference);

if (!$result['status']) {
    $_SESSION['error'] = "Error verifying payment: " . $result['message'];
    header('Location: ' . SITE_URL . 'client/my-ads.php');
    exit;
}

$response = json_encode($result);

$result = json_decode($response, true);

if ($result['status'] && $result['data']['status'] === 'success') {
    $amount = $result['data']['amount'] / 100; // Convert from kobo to naira
    
    // Get advertisement
    $stmt = $conn->prepare("SELECT * FROM advertisements WHERE id = ? AND advertiser_id = ?");
    $stmt->bind_param('ii', $ad_id, $user['id']);
    $stmt->execute();
    $ad = $stmt->get_result()->fetch_assoc();
    
    if (!$ad) {
        $_SESSION['error'] = "Advertisement not found.";
        header('Location: ' . SITE_URL . 'client/my-ads.php');
        exit;
    }
    
    // Update advertisement with payment confirmation
    $stmt = $conn->prepare("
        UPDATE advertisements 
        SET amount_paid = ?, status = 'pending' 
        WHERE id = ?
    ");
    $stmt->bind_param('di', $amount, $ad_id);
    $stmt->execute();
    
    // Record transaction
    $transaction_type = 'advertisement_payment';
    $description = "Payment for advertisement: " . $ad['title'];
    
    $stmt = $conn->prepare("
        INSERT INTO transactions 
        (user_id, type, amount, description, payment_method, payment_reference, status) 
        VALUES (?, ?, ?, ?, 'paystack', ?, 'completed')
    ");
    $stmt->bind_param('isdss', $user['id'], $transaction_type, $amount, $description, $reference);
    $stmt->execute();
    
    // Send in-app notification to user
    createNotification(
        $user['id'],
        'ad_payment_successful',
        'Ad Payment Successful!',
        "Your payment of ₦" . number_format($amount, 2) . " for '{$ad['title']}' was successful. Your ad is now pending admin approval.",
        'client/my-ads.php'
    );
    
    // Send confirmation email to user
    $message = "Thank you for your payment!<br><br>";
    $message .= "Your advertisement \"<strong>" . htmlspecialchars($ad['title']) . "</strong>\" has been paid for successfully.<br><br>";
    $message .= "<strong>Payment Details:</strong><br>";
    $message .= "• Amount Paid: ₦" . number_format($amount, 2) . "<br>";
    $message .= "• Payment Reference: " . htmlspecialchars($reference) . "<br>";
    $message .= "• Placement: " . htmlspecialchars($ad['placement']) . "<br>";
    $message .= "• Duration: " . date('M d', strtotime($ad['start_date'])) . " - " . date('M d, Y', strtotime($ad['end_date'])) . "<br><br>";
    $message .= "Your advertisement is now pending admin approval. You'll receive an email once it's reviewed.";
    
    sendTemplatedEmail(
        $user['email'],
        $user['first_name'] . ' ' . $user['last_name'],
        'Advertisement Payment Successful',
        $message,
        'View My Ads',
        SITE_URL . 'client/my-ads.php'
    );
    
    // Notify admin
    $admin_query = $conn->query("SELECT id, email, first_name, last_name FROM users WHERE role = 'admin' LIMIT 1");
    if ($admin = $admin_query->fetch_assoc()) {
        // Send in-app notification to admin
        createNotification(
            $admin['id'],
            'ad_payment_received',
            'New Paid Advertisement',
            "A new advertisement from {$user['first_name']} {$user['last_name']} has been paid (₦" . number_format($amount, 2) . ") and awaits approval.",
            'admin/ads.php'
        );
        
        $admin_message = "A new advertisement has been paid for and is awaiting your approval.<br><br>";
        $admin_message .= "<strong>Advertisement Details:</strong><br>";
        $admin_message .= "• Advertiser: " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "<br>";
        $admin_message .= "• Email: " . htmlspecialchars($user['email']) . "<br>";
        $admin_message .= "• Title: " . htmlspecialchars($ad['title']) . "<br>";
        $admin_message .= "• Amount Paid: ₦" . number_format($amount, 2) . "<br>";
        $admin_message .= "• Placement: " . htmlspecialchars($ad['placement']) . "<br>";
        $admin_message .= "• Duration: " . date('M d', strtotime($ad['start_date'])) . " - " . date('M d, Y', strtotime($ad['end_date'])) . "<br>";
        
        sendTemplatedEmail(
            $admin['email'],
            $admin['first_name'] . ' ' . $admin['last_name'],
            'New Paid Advertisement - Approval Required',
            $admin_message,
            'Review Advertisement',
            SITE_URL . 'admin/ads.php'
        );
    }
    
    $_SESSION['success'] = "Payment successful! Your advertisement is now pending admin approval.";
    header('Location: ' . SITE_URL . 'client/my-ads.php');
    exit;
    
} else {
    $_SESSION['error'] = "Payment verification failed. Please contact support if you were charged.";
    header('Location: ' . SITE_URL . 'client/my-ads.php');
    exit;
}
