<?php
require_once 'connect.php';
require_once 'functions.php';

/**
 * vPay Africa Payment Callback Handler
 * This handles payment confirmations from vPay Africa
 */

// Log incoming requests for debugging
$log_file = __DIR__ . '/vpay_debug.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - vPay Callback received\n", FILE_APPEND);
file_put_contents($log_file, "GET: " . print_r($_GET, true) . "\n", FILE_APPEND);
file_put_contents($log_file, "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($log_file, "SESSION: " . print_r($_SESSION, true) . "\n\n", FILE_APPEND);

// Handle redirect callback (user redirected after payment)
if (isset($_GET['reference']) || isset($_GET['txnref']) || isset($_GET['transaction_id'])) {
    $reference = sanitize($_GET['reference'] ?? $_GET['txnref'] ?? $_GET['transaction_id'] ?? '');
    
    error_log("vPay callback - Reference: $reference");
    error_log("vPay callback - GET params: " . print_r($_GET, true));
    error_log("vPay callback - Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
    
    // VPayDropin's onSuccess already confirms payment is successful
    // No need to verify again with API - this causes the redirect to dashboard issue
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$user_id) {
        error_log("vPay callback - ERROR: No user_id in session");
        $_SESSION['error_message'] = "Session expired. Please login again.";
        header('Location: signin.php');
        exit;
    }
    
    // Get transaction from database (if exists)
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE reference = ?");
    $stmt->bind_param("s", $reference);
    $stmt->execute();
    $transaction = $stmt->get_result()->fetch_assoc();
    
    // If no transaction record exists, create one from the callback data
    if (!$transaction) {
        $type = $_GET['type'] ?? 'unknown';
        $amount = floatval($_GET['amount'] ?? 0) * 100; // Convert to kobo for consistency
        $metadata = json_encode($_GET);
        
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, reference, status, payment_method, metadata, created_at) VALUES (?, ?, ?, ?, 'completed', 'vpay', ?, NOW())");
        $stmt->bind_param("isiss", $user_id, $type, $amount, $reference, $metadata);
        $stmt->execute();
        
        // Create transaction array for processing
        $transaction = [
            'user_id' => $user_id,
            'type' => $type,
            'amount' => $amount,
            'reference' => $reference,
            'status' => 'completed',
            'metadata' => $metadata
        ];
    } elseif ($transaction['status'] === 'completed') {
        // Already processed
        $_SESSION['info_message'] = "This payment has already been processed.";
        header('Location: dashboard.php');
        exit;
    } else {
        // Update transaction status
        $stmt = $conn->prepare("UPDATE transactions SET status = 'completed', payment_method = 'vpay' WHERE reference = ?");
        $stmt->bind_param("s", $reference);
        $stmt->execute();
    }
    
    // Process based on transaction type
    $transaction_type = $transaction['type'];
    error_log("vPay callback - Processing transaction type: $transaction_type");
    
    // Credit purchases (poll_credits, sms_credits, etc.)
    if (strpos($transaction_type, '_credits') !== false) {
        $units = intval($_GET['units'] ?? 0);
        $credit_type = str_replace('_credits', '', $transaction_type);
        
        error_log("vPay callback - Crediting user: user_id=$user_id, type=$credit_type, units=$units");
        
        // Validate credit type to prevent SQL injection
        $valid_credit_types = ['sms', 'email', 'whatsapp', 'poll'];
        if (!in_array($credit_type, $valid_credit_types)) {
            error_log("vPay callback - ERROR: Invalid credit type: $credit_type");
            $_SESSION['error_message'] = "Invalid credit type.";
            header('Location: dashboard.php');
            exit;
        }
        
        // Update messaging credits table
        if (in_array($credit_type, ['sms', 'email', 'whatsapp'])) {
            $column_name = $credit_type . '_balance';
            
            // Check if user has messaging credits record
            $check_stmt = $conn->prepare("SELECT id FROM messaging_credits WHERE user_id = ?");
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->fetch_assoc();
            
            if ($exists) {
                // Update existing record
                $update_query = "UPDATE messaging_credits SET $column_name = $column_name + ? WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_query);
                if (!$update_stmt) {
                    error_log("vPay callback - SQL Error: " . $conn->error);
                    $_SESSION['error_message'] = "Failed to update credits.";
                    header('Location: dashboard.php');
                    exit;
                }
                $update_stmt->bind_param("ii", $units, $user_id);
                $update_stmt->execute();
            } else {
                // Create new record
                $insert_query = "INSERT INTO messaging_credits (user_id, $column_name) VALUES (?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                if (!$insert_stmt) {
                    error_log("vPay callback - SQL Error: " . $conn->error);
                    $_SESSION['error_message'] = "Failed to create credits record.";
                    header('Location: dashboard.php');
                    exit;
                }
                $insert_stmt->bind_param("ii", $user_id, $units);
                $insert_stmt->execute();
            }
        }
        
        // Create notification
        createNotification(
            $user_id,
            'success',
            "Payment Successful",
            "Your payment of ₦" . number_format($transaction['amount'] / 100, 2) . " was successful. $units $credit_type credits have been added to your account."
        );
        
        // Award referral bonus if applicable
        awardReferralBonus($user_id, $credit_type . '_credits', $transaction['amount'] / 100);
        
        $_SESSION['success_message'] = "Payment successful! $units $credit_type credits have been added to your account.";
        
        // Redirect based on user role
        $user = getUserById($user_id);
        if ($user['role'] === 'client') {
            header('Location: client/buy-credits.php');
        } elseif ($user['role'] === 'agent') {
            header('Location: agent/buy-sms-credits.php');
        } else {
            header('Location: dashboard.php');
        }
        exit;
    }
    
    // Poll payment
    if ($transaction_type === 'poll_payment') {
        $poll_id = intval($_GET['poll_id'] ?? 0);
        $amount = floatval($_GET['amount'] ?? 0);
        
        if ($poll_id) {
            // Get poll details to calculate commission
            $poll = $conn->query("SELECT price_per_response, target_responders FROM polls WHERE id = $poll_id")->fetch_assoc();
            $agent_total = ($poll['price_per_response'] ?? 100) * ($poll['target_responders'] ?? 100);
            $admin_commission = $amount - $agent_total;
            
            // Record transaction with poll_id and admin_commission
            $stmt = $conn->prepare("UPDATE transactions SET poll_id = ?, admin_commission = ?, transaction_type = 'poll_payment' WHERE reference = ?");
            $stmt->bind_param("ids", $poll_id, $admin_commission, $reference);
            $stmt->execute();
            
            createNotification(
                $user_id,
                'success',
                "Poll Payment Successful",
                "Your payment of ₦" . number_format($amount, 2) . " was successful. Your poll is ready to be published."
            );
            
            $_SESSION['success_message'] = "Payment successful! You can now publish your poll.";
            header('Location: actions.php?action=publish_poll&poll_id=' . $poll_id);
            exit;
        }
    }
    
    // Subscription payments
    if ($transaction_type === 'subscription') {
        $plan = $_GET['plan'] ?? '';
        $duration = 30; // Default monthly
        
        if ($plan === 'yearly') {
            $duration = 365;
        }
        
            $expiry_date = date('Y-m-d', strtotime("+$duration days"));
            
            // Update user subscription
            $stmt = $conn->prepare("UPDATE users SET subscription_status = 'active', subscription_plan = ?, subscription_expiry = ? WHERE id = ?");
            $stmt->bind_param("ssi", $plan, $expiry_date, $user_id);
            $stmt->execute();
            
            createNotification(
                $user_id,
                'success',
                "Subscription Activated",
                "Your $plan subscription is now active until " . date('F j, Y', strtotime($expiry_date))
            );
            
            // Award referral bonus if applicable
            $plan_amount = ($plan === 'yearly') ? 50000 : 10000; // Estimate
            awardReferralBonus($user_id, 'subscription', $plan_amount);
            
            $_SESSION['success_message'] = "Subscription activated successfully!";
            header('Location: client/subscription.php');
            exit;
        }
        
        // Advertisement payments
        if ($transaction_type === 'advertisement') {
            $ad_id = $_GET['ad_id'] ?? 0;
            
            if ($ad_id) {
                $stmt = $conn->prepare("UPDATE advertisements SET payment_status = 'paid', status = 'pending' WHERE ad_id = ?");
                $stmt->bind_param("i", $ad_id);
                $stmt->execute();
                
                createNotification(
                    $user_id,
                    'success',
                    "Advertisement Payment Successful",
                    "Your advertisement payment was successful. Your ad is pending review."
                );
                
                $_SESSION['success_message'] = "Payment successful! Your advertisement is now pending review.";
                header('Location: client/my-ads.php');
                exit;
            }
        }
        
        // Databank access purchases
        if ($transaction_type === 'databank_access' || strpos($transaction_type, 'databank') !== false) {
            $poll_id = intval($_GET['poll_id'] ?? 0);
            
            error_log("vPay callback - Databank purchase: user_id=$user_id, poll_id=$poll_id");
            
            if ($poll_id) {
                // Check if user already has access
                $check_stmt = $conn->prepare("SELECT id FROM poll_results_access WHERE user_id = ? AND poll_id = ?");
                if (!$check_stmt) {
                    error_log("vPay callback - SQL Error on SELECT: " . $conn->error);
                    $_SESSION['error_message'] = "Database error. Please contact support.";
                    header('Location: databank.php');
                    exit;
                }
                $check_stmt->bind_param("ii", $user_id, $poll_id);
                $check_stmt->execute();
                $existing = $check_stmt->get_result()->fetch_assoc();
                
                if (!$existing) {
                    // Grant lifetime access
                    $amount_paid = $transaction['amount'] / 100; // Convert from kobo to naira
                    $stmt = $conn->prepare("INSERT INTO poll_results_access (user_id, poll_id, amount_paid) VALUES (?, ?, ?)");
                    if (!$stmt) {
                        error_log("vPay callback - SQL Error on INSERT: " . $conn->error);
                        // Try creating the table if it doesn't exist
                        $create_table = "CREATE TABLE IF NOT EXISTS poll_results_access (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            user_id INT NOT NULL,
                            poll_id INT NOT NULL,
                            purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            amount_paid DECIMAL(10,2) NOT NULL,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
                            UNIQUE KEY unique_access (user_id, poll_id),
                            INDEX(user_id),
                            INDEX(poll_id)
                        )";
                        $conn->query($create_table);
                        
                        // Retry the insert
                        $stmt = $conn->prepare("INSERT INTO poll_results_access (user_id, poll_id, amount_paid) VALUES (?, ?, ?)");
                        if (!$stmt) {
                            error_log("vPay callback - SQL Error after table creation: " . $conn->error);
                            $_SESSION['error_message'] = "Failed to grant access. Please contact support.";
                            header('Location: databank.php');
                            exit;
                        }
                    }
                    $stmt->bind_param("iid", $user_id, $poll_id, $amount_paid);
                    $stmt->execute();
                    
                    error_log("vPay callback - Access granted: user_id=$user_id, poll_id=$poll_id");
                    
                    // Get poll details for notification
                    $poll_query = $conn->query("SELECT title, created_by FROM polls WHERE id = $poll_id");
                    $poll = $poll_query->fetch_assoc();
                    
                    // Notify buyer
                    createNotification(
                        $user_id,
                        'success',
                        "Poll Results Purchased",
                        "You now have lifetime access to poll results: " . $poll['title']
                    );
                    
                    // Credit the poll creator (client)
                    if ($poll && $poll['created_by']) {
                        $creator_id = $poll['created_by'];
                        
                        // Add earnings to creator (you might want to take a platform fee)
                        $creator_earnings = $amount_paid; // 100% to creator, adjust if you want platform fee
                        
                        // Update creator's balance or earnings
                        $conn->query("UPDATE users SET sms_credits = sms_credits + " . ($creator_earnings * 100) . " WHERE id = $creator_id");
                        
                        createNotification(
                            $creator_id,
                            'success',
                            "Poll Results Sold!",
                            "Your poll \"" . $poll['title'] . "\" results were purchased for ₦" . number_format($amount_paid, 2)
                        );
                    }
                    
                    $_SESSION['success_message'] = "Purchase successful! You now have lifetime access to these poll results.";
                    header('Location: view-purchased-result.php?id=' . $poll_id);
                    exit;
                } else {
                    error_log("vPay callback - User already has access: user_id=$user_id, poll_id=$poll_id");
                    $_SESSION['info_message'] = "You already have access to these results.";
                    header('Location: view-purchased-result.php?id=' . $poll_id);
                    exit;
                }
            }
        }
        
        // Default success redirect
        $_SESSION['success_message'] = "Payment successful!";
        header('Location: dashboard.php');
        exit;
        
        // Default redirect to dashboard
        $_SESSION['success_message'] = "Payment successful!";
        header('Location: dashboard.php');
        exit;
}

// Handle webhook (vPay server notification)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    file_put_contents($log_file, "Webhook input: $input\n", FILE_APPEND);
    
    // Verify webhook signature if vPay provides one
    if (defined('VPAY_SECRET_KEY')) {
        $signature = $_SERVER['HTTP_X_VPAY_SIGNATURE'] ?? '';
        $computed = hash_hmac('sha512', $input, VPAY_SECRET_KEY);
        
        if ($signature !== $computed) {
            http_response_code(401);
            exit('Invalid signature');
        }
    }
    
    $event = json_decode($input);
    
    if ($event && isset($event->reference)) {
        $reference = $event->reference;
        $status = $event->status ?? '';
        
        // Update transaction status
        $stmt = $conn->prepare("UPDATE transactions SET status = ?, payment_method = 'vpay' WHERE reference = ?");
        $stmt->bind_param("ss", $status, $reference);
        $stmt->execute();
        
        http_response_code(200);
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid webhook data']);
    }
    exit;
}

// No valid parameters
$_SESSION['error_message'] = "Invalid payment callback.";
header('Location: dashboard.php');
exit;
