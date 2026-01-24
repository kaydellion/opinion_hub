<?php
require_once '../connect.php';
require_once '../functions.php';

// Check if user is logged in and is an approved agent
if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "signin.php");
    exit;
}

// Get fresh user data from database
$user = getCurrentUser();

if (!$user) {
    $_SESSION['errors'] = ["Unable to load user data. Please try logging in again."];
    header("Location: " . SITE_URL . "signin.php");
    exit;
}

if ($user['role'] !== 'agent') {
    $_SESSION['errors'] = ["Access Denied: You must be registered as an agent to share polls. <a href='" . SITE_URL . "agent/register-agent.php'>Apply to become an agent here</a>."];
    header("Location: " . SITE_URL . "dashboard.php");
    exit;
}

if (isset($user['agent_status']) && $user['agent_status'] !== 'approved') {
    $_SESSION['errors'] = ["Access Denied: Your agent application is currently <strong>" . htmlspecialchars($user['agent_status']) . "</strong>. You'll be notified via email once approved (usually within 48 hours)."];
    header("Location: " . SITE_URL . "dashboard.php");
    exit;
}

$poll_id = isset($_GET['poll_id']) ? intval($_GET['poll_id']) : 0;

// Fetch poll details
$poll_sql = "SELECT p.*, c.name as category_name,
             CONCAT(u.first_name, ' ', u.last_name) as creator_name
             FROM polls p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN users u ON p.created_by = u.id
             WHERE p.id = ? AND p.status = 'active'";
$stmt = $conn->prepare($poll_sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $poll_id);
$stmt->execute();
$result = $stmt->get_result();
$poll = $result->fetch_assoc();

if (!$poll) {
    $_SESSION['errors'] = ["Poll not found or inactive."];
    header("Location: " . SITE_URL . "dashboard.php");
    exit;
}

// Check if this poll uses agents for data collection
$uses_agents = intval($poll['price_per_response'] ?? 0) > 0; // If price_per_response > 0, agents are used

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $share_method = sanitize($_POST['share_method'] ?? '');
    $recipients = sanitize($_POST['recipients'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    $errors = [];
    
    if (empty($share_method) || !in_array($share_method, ['email', 'sms', 'whatsapp'])) {
        $errors[] = "Please select a valid sharing method.";
    }
    
    if (empty($recipients)) {
        $errors[] = "Please enter at least one recipient.";
    }
    
    // Check SMS credits if SMS method selected
    if ($share_method === 'sms') {
        $sms_credits = getAgentSMSCredits($_SESSION['user_id']);
        $recipient_list = array_map('trim', explode(',', $recipients));
        $recipient_count = count(array_filter($recipient_list));
        
        if ($sms_credits < $recipient_count) {
            $errors[] = "Insufficient SMS credits. You have $sms_credits credits but need $recipient_count. <a href='" . SITE_URL . "agent/buy-sms-credits.php' class='alert-link'>Buy more credits</a>";
        }
    }
    
    if (empty($errors)) {
        // Generate unique tracking code for this share batch
        $tracking_code = bin2hex(random_bytes(8));
        $poll_url = SITE_URL . "view-poll/" . $poll['slug'] . "?ref=" . $tracking_code;
        
        // Split recipients
        $recipient_list = array_map('trim', explode(',', $recipients));
        
        $success_count = 0;
        $fail_count = 0;
        $whatsapp_links = [];
        
        foreach ($recipient_list as $recipient) {
            if (empty($recipient)) continue;
            
            $sent = false;
            $error_message = '';
            
            // Send based on method
            if ($share_method === 'email') {
                $subject = "You're invited to share your opinion - " . $poll['title'];
                $body = ($message ? $message . "\n\n" : "") . 
                        "Click here to participate: " . $poll_url . "\n\n" .
                        "This poll will take less than 2 minutes to complete.\n\n" .
                        "Opinion Hub NG - Your voice matters!";
                
                $sent = sendEmailViaBrevo($recipient, $subject, $body);
                if (!$sent) {
                    $error_message = "Failed to send email";
                }
                
            } elseif ($share_method === 'sms') {
                $sms_body = ($message ? $message . " " : "") . 
                            "Share your opinion: " . $poll['title'] . ". " .
                            "Click: " . $poll_url;
                
                // Truncate to 160 characters
                if (strlen($sms_body) > 160) {
                    $sms_body = substr($sms_body, 0, 157) . '...';
                }
                
                $sent = sendSMSViaTermii($recipient, $sms_body);
                if ($sent) {
                    // Deduct credit
                    deductAgentSMSCredit($_SESSION['user_id'], 'Poll share SMS to ' . $recipient);
                } else {
                    $error_message = "Failed to send SMS";
                }
                
            } elseif ($share_method === 'whatsapp') {
                // WhatsApp sharing uses URL scheme
                $whatsapp_text = ($message ? $message . "\n\n" : "") . 
                                "📊 *" . $poll['title'] . "*\n\n" .
                                "Share your opinion on this important topic!\n\n" .
                                "🔗 " . $poll_url . "\n\n" .
                                "_Brought to you by Opinion Hub NG_";
                
                // Format phone number for WhatsApp
                $whatsapp_phone = preg_replace('/[^0-9]/', '', $recipient);
                $whatsapp_phone = preg_replace('/^0/', '234', $whatsapp_phone);
                
                $whatsapp_url = "https://wa.me/" . $whatsapp_phone . "?text=" . urlencode($whatsapp_text);
                $whatsapp_links[] = [
                    'phone' => $recipient,
                    'url' => $whatsapp_url
                ];
                
                $sent = true; // Mark as ready to share
            }
            
            // Insert share record
            $share_sql = "INSERT INTO poll_shares (poll_id, agent_id, share_method, recipient, tracking_code, shared_at) 
                         VALUES (?, ?, ?, ?, ?, NOW())";
            $share_stmt = $conn->prepare($share_sql);
            if (!$share_stmt) {
                die("Database error: " . $conn->error);
            }
            $share_stmt->bind_param("iisss", $poll_id, $_SESSION['user_id'], $share_method, $recipient, $tracking_code);
            
            if ($share_stmt->execute() && $sent) {
                $success_count++;
            } else {
                $fail_count++;
            }
        }
        
        // Store WhatsApp links in session for display
        if (!empty($whatsapp_links)) {
            $_SESSION['whatsapp_links'] = $whatsapp_links;
        }
        
        // Success messages
        if ($success_count > 0) {
            if ($share_method === 'email') {
                $_SESSION['success'] = "✅ Poll shared via email with $success_count recipient(s)! Emails have been sent. You'll earn ₦100 for each response received.";
            } elseif ($share_method === 'sms') {
                $_SESSION['success'] = "✅ Poll shared via SMS with $success_count recipient(s)! SMS messages have been sent. You'll earn ₦100 for each response received.";
            } elseif ($share_method === 'whatsapp') {
                $_SESSION['success'] = "✅ WhatsApp links generated for $success_count recipient(s)! Click the links below to send via WhatsApp. You'll earn ₦100 for each response received.";
                // Don't redirect immediately for WhatsApp
                header("Location: " . SITE_URL . "agent/share-poll.php?poll_id=" . $poll_id . "&show_whatsapp=1");
                exit;
            }
        }
        if ($fail_count > 0) {
            $_SESSION['errors'] = ["Failed to share with $fail_count recipient(s)."];
        }
        
        header("Location: " . SITE_URL . "dashboards/agent-dashboard.php");
        exit;
    }
    
    $_SESSION['errors'] = $errors;
}

$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
$whatsapp_links = $_SESSION['whatsapp_links'] ?? [];
$show_whatsapp = isset($_GET['show_whatsapp']) && $_GET['show_whatsapp'] == '1';
unset($_SESSION['errors'], $_SESSION['success'], $_SESSION['whatsapp_links']);

// Get SMS credits balance
$sms_credits = getAgentSMSCredits($_SESSION['user_id']);

$page_title = "Share Poll - " . $poll['title'];
include_once '../header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
        
            <?php if ($show_whatsapp && !empty($whatsapp_links)): ?>
                <!-- WhatsApp Links Display -->
                <div class="alert alert-success">
                    <h5><i class="fab fa-whatsapp me-2"></i>WhatsApp Sharing Links Ready!</h5>
                    <p>Click each link below to open WhatsApp and send the poll to your contacts:</p>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <i class="fab fa-whatsapp me-2"></i>Click to Send via WhatsApp
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($whatsapp_links as $link): ?>
                            <a href="<?php echo htmlspecialchars($link['url']); ?>" 
                               target="_blank" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span>
                                    <i class="fab fa-whatsapp text-success me-2"></i>
                                    <strong><?php echo htmlspecialchars($link['phone']); ?></strong>
                                </span>
                                <span class="badge bg-success">
                                    <i class="fas fa-external-link-alt me-1"></i>Open WhatsApp
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Each link will open WhatsApp with a pre-filled message. Just click Send!
                        </small>
                    </div>
                </div>
                
                <div class="d-flex gap-2 mb-4">
                    <a href="<?php echo SITE_URL; ?>dashboards/agent-dashboard.php" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Back to Dashboard
                    </a>
                    <a href="<?php echo SITE_URL; ?>agent/share-poll.php?poll_id=<?php echo $poll_id; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-share me-2"></i>Share More
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Poll Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><?= htmlspecialchars($poll['title']) ?></h2>
                    <p class="text-muted mb-2">
                        <i class="fas fa-folder me-2"></i><?= htmlspecialchars($poll['category_name']) ?>
                        <span class="ms-3"><i class="fas fa-clock me-2"></i>Ends: <?= date('M d, Y', strtotime($poll['end_date'])) ?></span>
                    </p>
                    <?php if ($poll['description']): ?>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($poll['description'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($uses_agents): ?>
            <!-- Quick Referral Link -->
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i>Your Referral Link</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Generate agent's tracking code
                    $agent_tracking_code = "POLL{$poll_id}-USR{$user['id']}-" . time();
                    $referral_link = SITE_URL . "view-poll/" . $poll['slug'] . "?ref=" . $agent_tracking_code;
                    ?>
                    <div class="alert alert-success mb-3">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        <strong>Earn ₦100 per completed response!</strong>
                        <p class="mb-0 mt-2 small">Share your referral link below and earn for every person who completes the poll through your link.</p>
                    </div>
                    
                    <label class="form-label fw-bold">Copy & Share This Link:</label>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="referralLink" value="<?= $referral_link ?>" readonly>
                        <button class="btn btn-primary" type="button" onclick="copyReferralLink()">
                            <i class="fas fa-copy me-1"></i>Copy Link
                        </button>
                    </div>
                    
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="https://wa.me/?text=<?= urlencode("Check out this poll: {$poll['title']}\n\n{$referral_link}") ?>" 
                           target="_blank" class="btn btn-success btn-sm">
                            <i class="fab fa-whatsapp me-1"></i>Share on WhatsApp
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($referral_link) ?>" 
                           target="_blank" class="btn btn-primary btn-sm">
                            <i class="fab fa-facebook me-1"></i>Share on Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($referral_link) ?>&text=<?= urlencode($poll['title']) ?>" 
                           target="_blank" class="btn btn-info btn-sm text-white">
                            <i class="fab fa-twitter me-1"></i>Share on Twitter
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Share Form -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="h5 mb-0"><i class="fas fa-share-alt me-2"></i>Share This Poll</h3>
                </div>
                <div class="card-body">
                    
                    <?php if ($uses_agents): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Earn ₦100 per response!</strong> Share this poll and earn for every completed response from your referrals.
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Referral earnings not available for this poll.</strong> This poll doesn't use agent collection, so referral earnings are not enabled.
                    </div>
                    <?php endif; ?>
                    
                    <!-- SMS Credits Balance -->
                    <div class="alert <?= $sms_credits > 10 ? 'alert-success' : ($sms_credits > 0 ? 'alert-warning' : 'alert-danger') ?> d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-wallet me-2"></i>
                            <strong>SMS Credits:</strong> <?= $sms_credits ?> credits available
                            <?php if ($sms_credits <= 10): ?>
                                <span class="ms-2">(<?= $sms_credits == 0 ? 'Buy credits to send SMS' : 'Low balance' ?>)</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?= SITE_URL ?>agent/buy-sms-credits.php" class="btn btn-sm <?= $sms_credits > 10 ? 'btn-outline-success' : 'btn-success' ?>">
                            <i class="fas fa-plus me-1"></i>Buy Credits
                        </a>
                    </div>
                    
                    <form method="POST">
                        
                        <!-- Share Method -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Sharing Method <span class="text-danger">*</span></label>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-check card h-100">
                                        <div class="card-body text-center">
                                            <input class="form-check-input" type="radio" name="share_method" value="email" id="method_email" required>
                                            <label class="form-check-label w-100" for="method_email">
                                                <i class="fas fa-envelope fa-2x text-primary d-block mb-2"></i>
                                                <strong>Email</strong>
                                                <small class="d-block text-muted mt-1">Free</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-check card h-100">
                                        <div class="card-body text-center">
                                            <input class="form-check-input" type="radio" name="share_method" value="sms" id="method_sms" required <?= $sms_credits == 0 ? 'disabled' : '' ?>>
                                            <label class="form-check-label w-100" for="method_sms">
                                                <i class="fas fa-sms fa-2x text-success d-block mb-2"></i>
                                                <strong>SMS</strong>
                                                <small class="d-block text-muted mt-1">
                                                    <?= $sms_credits == 0 ? 'No credits' : '1 credit/SMS' ?>
                                                </small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-check card h-100">
                                        <div class="card-body text-center">
                                            <input class="form-check-input" type="radio" name="share_method" value="whatsapp" id="method_whatsapp" required>
                                            <label class="form-check-label w-100" for="method_whatsapp">
                                                <i class="fab fa-whatsapp fa-2x text-success d-block mb-2"></i>
                                                <strong>WhatsApp</strong>
                                                <small class="d-block text-muted mt-1">Free (Manual)</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recipients -->
                        <div class="mb-3">
                            <label for="recipients" class="form-label fw-bold">Recipients <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="recipients" name="recipients" rows="3" required 
                                      placeholder="Enter email addresses, phone numbers, or WhatsApp numbers (comma-separated)"></textarea>
                            <div class="form-text">
                                For email: email1@example.com, email2@example.com<br>
                                For SMS/WhatsApp: 08012345678, 08098765432
                            </div>
                        </div>
                        
                        <!-- Custom Message -->
                        <div class="mb-4">
                            <label for="message" class="form-label fw-bold">Custom Message (Optional)</label>
                            <textarea class="form-control" id="message" name="message" rows="3" 
                                      placeholder="Add a personal message to encourage participation"></textarea>
                            <div class="form-text">This message will be included before the poll link.</div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Share Poll
                            </button>
                            <a href="<?= SITE_URL ?>dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                        
                    </form>
                    
                </div>
            </div>
            
            <!-- Share History -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="h6 mb-0">Your Sharing History for This Poll</h4>
                </div>
                <div class="card-body">
                    <?php
                    $history_sql = "SELECT share_method, recipient, clicks, responses, tracking_code, shared_at 
                                   FROM poll_shares 
                                   WHERE poll_id = ? AND agent_id = ? 
                                   ORDER BY shared_at DESC 
                                   LIMIT 10";
                    $history_stmt = $conn->prepare($history_sql);
                    
                    if (!$history_stmt) {
                        echo "<div class='alert alert-warning'>Unable to load sharing history: " . htmlspecialchars($conn->error) . "</div>";
                    } else {
                        $history_stmt->bind_param("ii", $poll_id, $_SESSION['user_id']);
                        $history_stmt->execute();
                        $history_result = $history_stmt->get_result();
                    ?>
                    
                    <?php if ($history_result && $history_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Recipient</th>
                                        <th>Clicks</th>
                                        <th>Responses</th>
                                        <th>Earnings</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($share = $history_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <?php if ($share['share_method'] === 'email'): ?>
                                                    <i class="fas fa-envelope text-primary"></i> Email
                                                <?php elseif ($share['share_method'] === 'sms'): ?>
                                                    <i class="fas fa-sms text-success"></i> SMS
                                                <?php else: ?>
                                                    <i class="fab fa-whatsapp text-success"></i> WhatsApp
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($share['recipient']) ?></td>
                                            <td><?= $share['clicks'] ?></td>
                                            <td><?= $share['responses'] ?></td>
                                            <td>₦<?= number_format($share['responses'] * 100) ?></td>
                                            <td><?= date('M d, Y', strtotime($share['shared_at'])) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">You haven't shared this poll yet.</p>
                    <?php endif; ?>
                    <?php } // End of history_stmt check ?>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
function copyReferralLink() {
    const linkInput = document.getElementById('referralLink');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
        }, 2000);
    } catch (err) {
        alert('Failed to copy link. Please copy manually.');
    }
}
</script>

<?php include_once '../footer.php'; ?>
