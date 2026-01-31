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
        
        $log_file = __DIR__ . '/logs/credit_debug.log';
        if (!is_dir(__DIR__ . '/logs')) {
            mkdir(__DIR__ . '/logs', 0755, true);
        }
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Processing $transaction_type. user_id=$user_id, units=$units, credit_type=$credit_type\n", FILE_APPEND);
        error_log("vPay callback - Crediting user: user_id=$user_id, type=$credit_type, units=$units");
        
        // Validate credit type to prevent SQL injection
        $valid_credit_types = ['sms', 'email', 'whatsapp', 'poll'];
        if (!in_array($credit_type, $valid_credit_types)) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - ERROR: Invalid credit type: $credit_type\n", FILE_APPEND);
            error_log("vPay callback - ERROR: Invalid credit type: $credit_type");
            $_SESSION['error_message'] = "Invalid credit type.";
            header('Location: dashboard.php');
            exit;
        }
        
        // Update messaging credits table
        if (in_array($credit_type, ['sms', 'email', 'whatsapp'])) {
            $column_name = $credit_type . '_balance';
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Column name: $column_name\n", FILE_APPEND);
            
            // Check if user has messaging credits record
            $check_stmt = $conn->prepare("SELECT id FROM messaging_credits WHERE user_id = ?");
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $exists = $check_result->fetch_assoc();
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Record exists: " . ($exists ? "YES (id={$exists['id']})" : "NO") . "\n", FILE_APPEND);
            
            if ($exists) {
                // Update existing record - use direct query since column name can't be parameterized
                $update_query = "UPDATE messaging_credits SET " . $column_name . " = " . $column_name . " + " . intval($units) . " WHERE user_id = " . intval($user_id);
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Executing UPDATE: " . $update_query . "\n", FILE_APPEND);
                
                $result = $conn->query($update_query);
                $affected_rows = $conn->affected_rows;
                
                if ($result === false) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - SQL Error on UPDATE: " . $conn->error . "\n", FILE_APPEND);
                    error_log("vPay callback - SQL Error on UPDATE: " . $conn->error);
                    $_SESSION['error_message'] = "Failed to update credits: " . $conn->error;
                    header('Location: dashboard.php');
                    exit;
                }
                
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - UPDATE affected rows: " . $affected_rows . "\n", FILE_APPEND);
                
                // Verify the update
                $verify_query = "SELECT $column_name FROM messaging_credits WHERE user_id = " . intval($user_id);
                $verify_result = $conn->query($verify_query);
                if ($verify_result) {
                    $verify_row = $verify_result->fetch_assoc();
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - VERIFIED: $column_name = {$verify_row[$column_name]} for user_id=$user_id\n", FILE_APPEND);
                    error_log("vPay callback - CREDIT_UPDATE verified: $column_name = {$verify_row[$column_name]} for user_id=$user_id");
                }
            } else {
                // Create new record with defaults
                $insert_query = "INSERT INTO messaging_credits (user_id, sms_balance, email_balance, whatsapp_balance) VALUES (?, 0, 0, 0)";
                $insert_stmt = $conn->prepare($insert_query);
                if (!$insert_stmt) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - SQL Error preparing INSERT init: " . $conn->error . "\n", FILE_APPEND);
                    error_log("vPay callback - SQL Error on INSERT init: " . $conn->error);
                    $_SESSION['error_message'] = "Failed to create credits record.";
                    header('Location: dashboard.php');
                    exit;
                }
                $insert_stmt->bind_param("i", $user_id);
                $result = $insert_stmt->execute();
                if (!$result) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - SQL Error executing INSERT init: " . $conn->error . "\n", FILE_APPEND);
                    error_log("vPay callback - SQL Error on INSERT init execute: " . $conn->error);
                    $_SESSION['error_message'] = "Failed to create credits record.";
                    header('Location: dashboard.php');
                    exit;
                }
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Created new messaging_credits record for user_id=$user_id\n", FILE_APPEND);
                
                // Now update with the credits
                $update_query = "UPDATE messaging_credits SET " . $column_name . " = " . intval($units) . " WHERE user_id = " . intval($user_id);
                $result = $conn->query($update_query);
                $affected_rows = $conn->affected_rows;
                
                if ($result === false) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - SQL Error on UPDATE after INSERT: " . $conn->error . "\n", FILE_APPEND);
                    error_log("vPay callback - SQL Error on UPDATE after INSERT: " . $conn->error);
                    $_SESSION['error_message'] = "Failed to update credits.";
                    header('Location: dashboard.php');
                    exit;
                }
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - UPDATE after INSERT affected rows: " . $affected_rows . "\n", FILE_APPEND);
                
                // Verify the update
                $verify_query = "SELECT $column_name FROM messaging_credits WHERE user_id = " . intval($user_id);
                $verify_result = $conn->query($verify_query);
                if ($verify_result) {
                    $verify_row = $verify_result->fetch_assoc();
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - VERIFIED after INSERT: $column_name = {$verify_row[$column_name]} for user_id=$user_id\n", FILE_APPEND);
                    error_log("vPay callback - CREDIT_INSERT verified: $column_name = {$verify_row[$column_name]} for user_id=$user_id");
                }
            }
            
            // For SMS credits, also add to agent_sms_credits table if user is an agent
            if ($credit_type === 'sms') {
                $user = getUserById($user_id);
                if ($user && $user['role'] === 'agent') {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - User is agent, adding to agent_sms_credits table\n", FILE_APPEND);
                    $amount_paid = floatval($_GET['amount'] ?? 0);
                    $description = "SMS credits purchase via vPay";
                    
                    // Add using the addAgentSMSCredits function
                    if (function_exists('addAgentSMSCredits')) {
                        $add_result = addAgentSMSCredits($user_id, $units, $amount_paid, $description);
                        if ($add_result) {
                            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Successfully added $units SMS credits to agent_sms_credits for user_id=$user_id\n", FILE_APPEND);
                            error_log("vPay callback - Added $units SMS credits to agent_sms_credits for user_id=$user_id");
                        } else {
                            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Failed to add SMS credits to agent_sms_credits for user_id=$user_id\n", FILE_APPEND);
                            error_log("vPay callback - Failed to add SMS credits to agent_sms_credits for user_id=$user_id");
                        }
                    }
                }
            }
        }
        
        // Create notification
        createNotification(
            $user_id,
            'success',
            "Payment Successful",
            "Your payment of ₦" . number_format($transaction['amount'] / 100, 2) . " was successful. $units $credit_type credits have been added to your account."
        );
        
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
            // Get poll details and compute agent payout from settings
            $poll = $conn->query("SELECT price_per_response, target_responders, created_by, title FROM polls WHERE id = $poll_id")->fetch_assoc();
            $agent_payout = floatval(getAgentCommission()); // amount per poll as defined in settings
            $admin_commission = $amount - $agent_payout;
            
            // Record transaction with poll_id and admin_commission
            // First check if transaction already exists
            $existing = $conn->query("SELECT id FROM transactions WHERE reference = '$reference'")->fetch_assoc();

            if ($existing) {
                // Update existing transaction
                $stmt = $conn->prepare("UPDATE transactions SET poll_id = ?, admin_commission = ?, transaction_type = 'poll_payment', status = 'completed' WHERE reference = ?");
                $stmt->bind_param("ids", $poll_id, $admin_commission, $reference);
                $stmt->execute();
            } else {
                // Insert new transaction record
                $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, reference, transaction_type, poll_id, admin_commission, status, created_at)
                                       VALUES (?, ?, ?, 'poll_payment', ?, ?, 'completed', NOW())");
                $stmt->bind_param("idsid", $user_id, $amount, $reference, $poll_id, $admin_commission);
                $stmt->execute();
            }
            // Credit the poll creator (agent) with configured commission per poll
            if (!empty($poll['created_by'])) {
                $creator_id = intval($poll['created_by']);
                // Record agent earning
                $earning_stmt = $conn->prepare("INSERT INTO agent_earnings (agent_id, poll_id, earning_type, amount, description, status, created_at) VALUES (?, ?, 'poll_sale', ?, ?, 'pending', NOW())");
                if ($earning_stmt) {
                    $desc = "Sale of poll: " . ($poll['title'] ?? $poll_id);
                    $earning_stmt->bind_param("iids", $creator_id, $poll_id, $agent_payout, $desc);
                    $earning_stmt->execute();
                }

                // Update user's earnings counters
                $conn->query("UPDATE users SET total_earnings = total_earnings + $agent_payout, pending_earnings = pending_earnings + $agent_payout WHERE id = $creator_id");
            }
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
        $plan_id = intval($_GET['plan_id'] ?? 0);
        $billing_cycle = $_GET['billing_cycle'] ?? 'monthly';
        
        if ($plan_id) {
            // Calculate subscription end date
            if ($billing_cycle === 'annual') {
                $end_date = date('Y-m-d H:i:s', strtotime('+365 days'));
            } else {
                $end_date = date('Y-m-d H:i:s', strtotime('+30 days'));
            }
            
            // Get plan details
            $plan = $conn->query("SELECT name FROM subscription_plans WHERE id = $plan_id")->fetch_assoc();
            $plan_name = $plan['name'] ?? 'Subscription';
            
            // Insert into user_subscriptions table
            $stmt = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_id, status, start_date, end_date, payment_reference, amount_paid) 
                                   VALUES (?, ?, 'active', NOW(), ?, ?, ?)");
            if (!$stmt) {
                error_log("vPay callback - SQL Error preparing subscription insert: " . $conn->error);
                $_SESSION['error_message'] = "Failed to activate subscription: " . $conn->error;
                header('Location: client/subscription.php');
                exit;
            }
            
            $amount_paid = floatval($_GET['amount'] ?? 0) / 100; // Convert from kobo to naira
            $stmt->bind_param("iissd", $user_id, $plan_id, $end_date, $reference, $amount_paid);
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("vPay callback - SQL Error inserting subscription: " . $conn->error);
                $_SESSION['error_message'] = "Failed to activate subscription: " . $conn->error;
                header('Location: client/subscription.php');
                exit;
            }
            
            // Allocate subscription benefits (SMS/Email/WhatsApp units) to user's messaging credits
            $plan_units = $conn->query("SELECT sms_invite_units, email_invite_units, whatsapp_invite_units FROM subscription_plans WHERE id = $plan_id")->fetch_assoc();
            $sms_units = intval($plan_units['sms_invite_units'] ?? 0);
            $email_units = intval($plan_units['email_invite_units'] ?? 0);
            $whatsapp_units = intval($plan_units['whatsapp_invite_units'] ?? 0);

            // Ensure messaging_credits record exists
            $mc_check = $conn->query("SELECT id FROM messaging_credits WHERE user_id = " . intval($user_id))->fetch_assoc();
            if (!$mc_check) {
                $ins = $conn->prepare("INSERT INTO messaging_credits (user_id, sms_balance, email_balance, whatsapp_balance) VALUES (?, 0, 0, 0)");
                if ($ins) {
                    $ins->bind_param("i", $user_id);
                    $ins->execute();
                }
            }

            // Build update for messaging_credits
            $update_parts = [];
            if ($sms_units > 0) $update_parts[] = "sms_balance = sms_balance + " . intval($sms_units);
            if ($email_units > 0) $update_parts[] = "email_balance = email_balance + " . intval($email_units);
            if ($whatsapp_units > 0) $update_parts[] = "whatsapp_balance = whatsapp_balance + " . intval($whatsapp_units);

            if (!empty($update_parts)) {
                $update_query = "UPDATE messaging_credits SET " . implode(', ', $update_parts) . " WHERE user_id = " . intval($user_id);
                $conn->query($update_query);
                // Record transactions for each credit type (so user sees incoming credits)
                if ($sms_units > 0) {
                    $ref_bonus = 'SUB_BONUS_SMS_' . $reference;
                    $stmt_tx = $conn->prepare("INSERT INTO transactions (user_id, type, amount, reference, status, payment_method, metadata, created_at) VALUES (?, 'sms_credits', 0.00, ?, 'completed', 'system', 'subscription bonus', NOW())");
                    if ($stmt_tx) {
                        $stmt_tx->bind_param("is", $user_id, $ref_bonus);
                        $stmt_tx->execute();
                    }
                }
                if ($email_units > 0) {
                    $ref_bonus = 'SUB_BONUS_EMAIL_' . $reference;
                    $stmt_tx = $conn->prepare("INSERT INTO transactions (user_id, type, amount, reference, status, payment_method, metadata, created_at) VALUES (?, 'email_credits', 0.00, ?, 'completed', 'system', 'subscription bonus', NOW())");
                    if ($stmt_tx) {
                        $stmt_tx->bind_param("is", $user_id, $ref_bonus);
                        $stmt_tx->execute();
                    }
                }
                if ($whatsapp_units > 0) {
                    $ref_bonus = 'SUB_BONUS_WA_' . $reference;
                    $stmt_tx = $conn->prepare("INSERT INTO transactions (user_id, type, amount, reference, status, payment_method, metadata, created_at) VALUES (?, 'whatsapp_credits', 0.00, ?, 'completed', 'system', 'subscription bonus', NOW())");
                    if ($stmt_tx) {
                        $stmt_tx->bind_param("is", $user_id, $ref_bonus);
                        $stmt_tx->execute();
                    }
                }
            }

            // If agent, also add to agent_sms_credits ledger for SMS units
            $user_role = getUserById($user_id)['role'] ?? '';
            if ($user_role === 'agent' && $sms_units > 0 && function_exists('addAgentSMSCredits')) {
                addAgentSMSCredits($user_id, $sms_units, 0.00, 'Subscription bonus');
            }

            error_log("vPay callback - Subscription activated: user_id=$user_id, plan_id=$plan_id, end_date=$end_date");
            
            createNotification(
                $user_id,
                'success',
                "Subscription Activated",
                "Your $plan_name subscription is now active until " . date('F j, Y', strtotime($end_date))
            );
            
            $_SESSION['success_message'] = "Subscription activated successfully!";
            header('Location: client/subscription.php?success=1');
            exit;
        }
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
            // Remove format parameter - grants access to both combined and single formats

            error_log("vPay callback - Databank purchase: user_id=$user_id, poll_id=$poll_id (access to both formats)");
            
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

                    // Record the dataset format download
                    $download_stmt = $conn->prepare("INSERT INTO dataset_downloads (user_id, poll_id, dataset_format, time_period) VALUES (?, ?, ?, 'monthly')");
                    $download_stmt->bind_param("iis", $user_id, $poll_id, $dataset_format);
                    $download_stmt->execute();

                    error_log("vPay callback - Access granted: user_id=$user_id, poll_id=$poll_id, format=$dataset_format");

                    // Get poll details for notification
                    $poll_query = $conn->query("SELECT title, created_by FROM polls WHERE id = $poll_id");
                    $poll = $poll_query->fetch_assoc();

                    // Notify buyer
                    createNotification(
                        $user_id,
                        'success',
                        "Poll Results Purchased",
                        "You now have lifetime access to poll results: " . $poll['title'] . " (" . strtoupper($dataset_format) . " format)"
                    );

                    // Send email notification to buyer
                    $buyer_info = $conn->query("SELECT first_name, last_name, email FROM users WHERE id = $user_id")->fetch_assoc();
                    if ($buyer_info) {
                        $buyer_email_subject = "Dataset Purchase Confirmation - " . $poll['title'];
                        $buyer_email_message = "Dear {$buyer_info['first_name']} {$buyer_info['last_name']},

Thank you for purchasing dataset access!

Purchase Details:
- Dataset: {$poll['title']}
- Format: " . strtoupper($dataset_format) . "
- Amount Paid: ₦" . number_format($amount_paid, 2) . "
- Purchase Date: " . date('F j, Y \a\t g:i A') . "
- Access: Lifetime

You can now access your purchased dataset at:
" . SITE_URL . "view-purchased-result.php?id=$poll_id&format=$dataset_format

Dataset Formats Available:
- COMBINED: Aggregated responses with trend analysis
- SINGLE: Individual responses from each participant

Access your purchased datasets anytime from your account dashboard.

If you have any questions about your dataset, please contact our support team.

Best regards,
Opinion Hub NG Team
hello@opinionhub.ng
+234 (0) 803 3782 777";

                        sendTemplatedEmail(
                            $buyer_info['email'],
                            "{$buyer_info['first_name']} {$buyer_info['last_name']}",
                            $buyer_email_subject,
                            nl2br($buyer_email_message),
                            "View Dataset",
                            SITE_URL . "view-purchased-result.php?id=$poll_id&format=$dataset_format"
                        );
                    }

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

                        // Send email notification to poll creator
                        $creator_info = $conn->query("SELECT first_name, last_name, email FROM users WHERE id = $creator_id")->fetch_assoc();
                        if ($creator_info) {
                            $creator_email_subject = "Poll Dataset Sold - " . $poll['title'];
                            $creator_email_message = "Dear {$creator_info['first_name']} {$creator_info['last_name']},

Congratulations! Your poll dataset has been purchased!

Sale Details:
- Poll Title: {$poll['title']}
- Dataset Format: " . strtoupper($dataset_format) . "
- Amount Earned: ₦" . number_format($creator_earnings, 2) . "
- Sale Date: " . date('F j, Y \a\t g:i A') . "

The earnings have been added to your account as messaging credits.
You can use these credits to send SMS, email, or WhatsApp invitations for your polls.

View your earnings: " . SITE_URL . "client/manage-polls.php
Purchase more credits: " . SITE_URL . "client/buy-credits.php

Thank you for using Opinion Hub NG!

Best regards,
Opinion Hub NG Team
hello@opinionhub.ng
+234 (0) 803 3782 777";

                            sendTemplatedEmail(
                                $creator_info['email'],
                                "{$creator_info['first_name']} {$creator_info['last_name']}",
                                $creator_email_subject,
                                nl2br($creator_email_message),
                                "View Earnings",
                                SITE_URL . "client/manage-polls.php"
                            );
                        }
                    }
                    
                    $_SESSION['success_message'] = "Purchase successful! You now have lifetime access to these poll results in " . strtoupper($dataset_format) . " format.";
                    header('Location: view-purchased-result.php?id=' . $poll_id . '&format=' . $dataset_format);
                    exit;
                } else {
                    // User already has access - just record the format preference
                    $download_stmt = $conn->prepare("INSERT INTO dataset_downloads (user_id, poll_id, dataset_format, time_period)
                                                   VALUES (?, ?, ?, 'monthly')
                                                   ON DUPLICATE KEY UPDATE
                                                   dataset_format = VALUES(dataset_format),
                                                   download_date = NOW(),
                                                   download_count = download_count + 1");
                    $download_stmt->bind_param("iis", $user_id, $poll_id, $dataset_format);
                    $download_stmt->execute();

                    error_log("vPay callback - User already has access, updated format preference: user_id=$user_id, poll_id=$poll_id, format=$dataset_format");
                    $_SESSION['success_message'] = "You already have access to these results. Viewing in " . strtoupper($dataset_format) . " format.";
                    header('Location: view-purchased-result.php?id=' . $poll_id . '&format=' . $dataset_format);
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
