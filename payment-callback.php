<?php
/**
 * =============================================================================
 * IMPORTANT: THIS FILE IS NOW DEPRECATED
 * =============================================================================
 * 
 * Paystack integration has been DISABLED.
 * This file is kept for historical reference only.
 * 
 * NEW PAYMENT GATEWAY: vPay Africa
 * NEW CALLBACK FILE: vpay-callback.php
 * 
 * Please update all payment links to use vpay-callback.php instead.
 * =============================================================================
 */

require_once 'connect.php';
require_once 'functions.php';

// Define Paystack secret key (replace with your actual secret key or load from config)
if (!defined('PAYSTACK_SECRET_KEY')) {
    define('PAYSTACK_SECRET_KEY', getenv('PAYSTACK_SECRET_KEY') ?: 'your_paystack_secret_key_here');
}

// Redirect to new callback handler
if (isset($_GET['reference'])) {
    // Redirect Paystack callbacks to vPay handler (if any legacy links exist)
    header('Location: vpay-callback.php?' . http_build_query($_GET));
    exit;
}

// Below is legacy Paystack code - DO NOT USE
// ===========================================================================

// Simple file logging for debugging
$log_file = __DIR__ . '/payment_debug.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - LEGACY Paystack Callback (DEPRECATED)\n", FILE_APPEND);
file_put_contents($log_file, "GET: " . print_r($_GET, true) . "\n", FILE_APPEND);
file_put_contents($log_file, "SESSION: " . print_r($_SESSION, true) . "\n\n", FILE_APPEND);

/**
 * Paystack Payment Callback Handler - DEPRECATED
 * Use vpay-callback.php instead
 */

// Handle redirect callback (user redirected after payment)
if (isset($_GET['reference'])) {
    $reference = sanitize($_GET['reference']);
    
    // Debug logging
    error_log("Payment callback - Reference: $reference");
    error_log("Payment callback - GET params: " . print_r($_GET, true));
    error_log("Payment callback - Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
    
    // Verify payment with Paystack
    $result = verifyPayment($reference);
    
    error_log("Payment callback - Verify result: " . print_r($result, true));
    
    if ($result['status']) {
        $data = $result['data'];
        $user_id = $_SESSION['user_id'] ?? null;
        
        if (!$user_id) {
            error_log("Payment callback - ERROR: No user_id in session");
            $_SESSION['error_message'] = "Session expired. Please login again.";
            header('Location: signin.php');
            exit;
        }
        
        // Check if payment was successful
        if ($data['status'] !== 'success') {
            error_log("Payment callback - ERROR: Payment status not success: " . $data['status']);
            $_SESSION['error_message'] = "Payment was not successful.";
            header('Location: dashboard.php');
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
            $amount = $data['amount']; // Already in kobo from Paystack
            $metadata = json_encode($data['metadata'] ?? []);
            
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, reference, status, metadata, created_at) VALUES (?, ?, ?, ?, 'completed', ?, NOW())");
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
            $conn->query("UPDATE transactions SET status = 'completed' WHERE reference = '$reference'");
        }
        
        // Process based on transaction type
        switch ($transaction['type']) {
                case 'sms_credits':
                case 'email_credits':
                case 'whatsapp_credits':
                    // Credit purchase - get from GET params or metadata
                    $units = $_GET['units'] ?? 0;
                    if (!$units) {
                        $metadata = json_decode($transaction['metadata'] ?? '{}', true);
                        $units = $metadata['units'] ?? 0;
                    }
                    
                    // Determine credit type from transaction type
                    $credit_type_map = [
                        'sms_credits' => 'sms',
                        'email_credits' => 'email',
                        'whatsapp_credits' => 'whatsapp'
                    ];
                    $credit_type = $credit_type_map[$transaction['type']] ?? '';
                    
                    if ($credit_type && $units > 0) {
                        // Add credits
                        if ($credit_type === 'sms') {
                            addMessagingCredits($user_id, $units, 0, 0);
                        } elseif ($credit_type === 'email') {
                            addMessagingCredits($user_id, 0, $units, 0);
                        } elseif ($credit_type === 'whatsapp') {
                            addMessagingCredits($user_id, 0, 0, $units);
                        }
                        
                        // Get user details for notification
                        $user_details = $conn->query("SELECT first_name, last_name, email FROM users WHERE id = $user_id")->fetch_assoc();
                        
                        // Send notification
                        createNotification(
                            $user_id,
                            'credits_purchased',
                            'Credits Added!',
                            "$units " . strtoupper($credit_type) . " credits have been added to your account. Reference: $reference",
                            'client/buy-credits.php'
                        );
                        
                        // Send email confirmation
                        sendTemplatedEmail(
                            $user_details['email'],
                            $user_details['first_name'] . ' ' . $user_details['last_name'],
                            'Credits Purchase Successful',
                            "Your purchase of $units " . strtoupper($credit_type) . " credits was successful! You can now use them to send invitations.",
                            'View Credits',
                            SITE_URL . 'client/buy-credits.php'
                        );
                        
                        $_SESSION['success_message'] = "Payment successful! $units " . strtoupper($credit_type) . " credits added to your account.";
                    }
                    header('Location: client/buy-credits.php?success=1');
                    exit;
                    
                case 'subscription':
                    // Subscription payment - activate subscription
                    $plan_id = $_GET['plan_id'] ?? 0;
                    $billing_cycle = $_GET['billing_cycle'] ?? 'monthly';
                    
                    if ($plan_id > 0) {
                        // Get plan details
                        $plan = $conn->query("SELECT * FROM subscription_plans WHERE id = $plan_id")->fetch_assoc();
                        
                        if ($plan) {
                            // Deactivate current subscriptions
                            $conn->query("UPDATE user_subscriptions SET status = 'expired' WHERE user_id = $user_id AND status = 'active'");
                            
                            // Calculate dates
                            $start_date = date('Y-m-d H:i:s');
                            $end_date = $billing_cycle === 'monthly' ? 
                                       date('Y-m-d H:i:s', strtotime('+1 month')) : 
                                       date('Y-m-d H:i:s', strtotime('+1 year'));
                            
                            // Create new subscription
                            $stmt = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, payment_reference, amount_paid, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                            if ($stmt) {
                                $amount_paid = $billing_cycle === 'monthly' ? $plan['monthly_price'] : $plan['annual_price'];
                                $stmt->bind_param("iisssd", $user_id, $plan_id, $start_date, $end_date, $reference, $amount_paid);
                                $stmt->execute();
                                $stmt->close();
                            }
                            
                            // Add free credits from plan
                            if ($plan['sms_invite_units'] > 0 || $plan['email_invite_units'] > 0 || $plan['whatsapp_invite_units'] > 0) {
                                addMessagingCredits($user_id, $plan['sms_invite_units'], $plan['email_invite_units'], $plan['whatsapp_invite_units']);
                            }
                            
                            // Get user details for notification
                            $user_details = $conn->query("SELECT first_name, last_name, email FROM users WHERE id = $user_id")->fetch_assoc();
                            
                            // Send notification
                            createNotification(
                                $user_id,
                                'subscription_activated',
                                'Subscription Activated!',
                                "Your {$plan['name']} subscription is now active! Enjoy all premium features.",
                                'client/subscription.php'
                            );
                            
                            // Send email confirmation
                            sendTemplatedEmail(
                                $user_details['email'],
                                $user_details['first_name'] . ' ' . $user_details['last_name'],
                                'Welcome to ' . $plan['name'] . '!',
                                "Your subscription to {$plan['name']} is now active! You now have access to all premium features. Valid until " . date('M d, Y', strtotime($end_date)),
                                'View Dashboard',
                                SITE_URL . 'dashboards/client-dashboard.php'
                            );
                            
                            $_SESSION['success_message'] = "Subscription activated successfully! Welcome to {$plan['name']}.";
                        }
                    }
                    header('Location: client/subscription.php?success=1');
                    exit;
                    
                case 'agent_sms_credits':
                    // Agent SMS credits purchase
                    $package_id = $_GET['package_id'] ?? 0;
                    $credits = $_GET['credits'] ?? 0;
                    
                    if ($credits > 0) {
                        // Add credits to agent account
                        addAgentSMSCredits($user_id, $credits, $transaction['amount'] / 100);
                        
                        // Get user details for notification
                        $user_details = $conn->query("SELECT first_name, last_name, email FROM users WHERE id = $user_id")->fetch_assoc();
                        
                        // Send notification
                        createNotification(
                            $user_id,
                            'sms_credits_purchased',
                            'SMS Credits Added!',
                            "$credits SMS credits have been added to your agent account. Start sharing polls now!",
                            'agent/share-poll.php'
                        );
                        
                        // Send email confirmation
                        sendTemplatedEmail(
                            $user_details['email'],
                            $user_details['first_name'] . ' ' . $user_details['last_name'],
                            'SMS Credits Purchase Successful',
                            "Your purchase of $credits SMS credits was successful! You can now use them to share polls and earn commissions.",
                            'Share Polls',
                            SITE_URL . 'agent/share-poll.php'
                        );
                        
                        $_SESSION['success_message'] = "Payment successful! $credits SMS credits added to your account.";
                    }
                    header('Location: agent/buy-sms-credits.php?success=1');
                    exit;
                    
                case 'agent_payout':
                    // This shouldn't happen via redirect, but handle it
                    $_SESSION['success_message'] = "Payment processed successfully!";
                    header('Location: dashboard.php');
                    exit;
                    
                default:
                    $_SESSION['success_message'] = "Payment successful!";
                    header('Location: dashboard.php');
                    exit;
            }
    } else {
        // Payment verification failed
        $_SESSION['error_message'] = "Payment verification failed. Please contact support if you were charged.";
        header('Location: dashboard.php');
        exit;
    }
}

// Handle webhook (Paystack server notification)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify webhook signature
    $input = @file_get_contents("php://input");
    $event = json_decode($input);
    
    // Validate signature (optional but recommended)
    if (defined('PAYSTACK_SECRET_KEY')) {
        $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
        $computed = hash_hmac('sha512', $input, PAYSTACK_SECRET_KEY);
        
        if ($signature !== $computed) {
            http_response_code(400);
            exit('Invalid signature');
        }
    }
    
    // Process webhook event
    if ($event && $event->event === 'charge.success') {
        $reference = $event->data->reference;
        $amount = $event->data->amount / 100; // Paystack sends amount in kobo
        
        // Get transaction
        $stmt = $conn->prepare("SELECT * FROM transactions WHERE reference = ?");
        $stmt->bind_param("s", $reference);
        $stmt->execute();
        $transaction = $stmt->get_result()->fetch_assoc();
        
        if ($transaction && $transaction['status'] !== 'completed') {
            $user_id = $transaction['user_id'];
            
            // Update transaction
            $conn->query("UPDATE transactions SET status = 'completed' WHERE reference = '$reference'");
            
            // Process based on type
            switch ($transaction['type']) {
                case 'sms_credits':
                case 'email_credits':
                case 'whatsapp_credits':
                    $metadata = json_decode($transaction['metadata'] ?? '{}', true);
                    $credit_type = $metadata['credit_type'] ?? '';
                    $units = $metadata['units'] ?? 0;
                    
                    if ($credit_type && $units > 0) {
                        if ($credit_type === 'sms') {
                            addMessagingCredits($user_id, $units, 0, 0);
                        } elseif ($credit_type === 'email') {
                            addMessagingCredits($user_id, 0, $units, 0);
                        } elseif ($credit_type === 'whatsapp') {
                            addMessagingCredits($user_id, 0, 0, $units);
                        }
                    }
                    break;
                    
                case 'subscription':
                    // Handle subscription activation
                    // (Implementation depends on your subscription logic)
                    break;
            }
            
            http_response_code(200);
            echo 'Webhook processed';
        } else {
            http_response_code(200);
            echo 'Already processed';
        }
    } else {
        http_response_code(200);
        echo 'Event received';
    }
    exit;
}

// If neither GET nor POST, redirect to homepage
header('Location: index.php');
exit;
