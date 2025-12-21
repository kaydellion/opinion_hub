<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('client');

$user = getCurrentUser();
$credits = getMessagingCredits($user['id']);

$success = '';
$error = '';

// Get client's polls
$polls = $conn->query("SELECT id, title, status FROM polls WHERE created_by = {$user['id']} AND status = 'active' ORDER BY created_at DESC");

// Get contact lists
$lists = $conn->query("SELECT * FROM contact_lists WHERE user_id = {$user['id']} ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $poll_id = (int)$_POST['poll_id'];
    $list_id = (int)$_POST['list_id'];
    $method = sanitize($_POST['method']);
    
    // Validate inputs
    if (!$poll_id || !$list_id || !in_array($method, ['sms', 'email', 'whatsapp'])) {
        $error = 'Please fill all fields correctly';
    } else {
        // Get poll details
        $poll = $conn->query("SELECT * FROM polls WHERE id = $poll_id AND created_by = {$user['id']}")->fetch_assoc();
        
        if (!$poll) {
            $error = 'Poll not found';
        } else {
            // Get contacts from list
            $contacts_query = $conn->query("SELECT * FROM contacts WHERE list_id = $list_id");
            $contacts_array = [];
            while ($c = $contacts_query->fetch_assoc()) {
                $contacts_array[] = $c;
            }
            
            $total = count($contacts_array);
            
            if ($total === 0) {
                $error = 'Selected list has no contacts';
            } else {
                // Check credits
                $available = 0;
                if ($method === 'sms') {
                    $available = $credits['sms_balance'];
                } elseif ($method === 'email') {
                    $available = $credits['email_balance'];
                } elseif ($method === 'whatsapp') {
                    $available = $credits['whatsapp_balance'];
                }
                
                if ($available < $total) {
                    $error = "Insufficient credits. You need $total but have $available. <a href='buy-credits.php'>Buy more credits</a>";
                } else {
                    $sent = 0;
                    $failed = 0;
                    
                    // Generate poll link using slug for pretty URLs
                    $poll_url = SITE_URL . "view-poll/" . $poll['slug'];
                    
                    // Prepare message
                    $message = "You're invited to participate in a poll: \"{$poll['title']}\"\n\n";
                    $message .= "Click here to respond: $poll_url\n\n";
                    $message .= "Thank you!";
                    
                    $subject = "Poll Invitation: " . $poll['title'];
                    
                    foreach ($contacts_array as $contact) {
                        $result = null;
                        $recipient = '';
                        
                        if ($method === 'sms' && !empty($contact['phone'])) {
                            $result = sendSMS_Termii($contact['phone'], $message);
                            $recipient = $contact['phone'];
                        } elseif ($method === 'email' && !empty($contact['email'])) {
                            $html = "<p>" . nl2br(htmlspecialchars($message)) . "</p>";
                            $result = sendEmail_Brevo($contact['email'], $subject, $html, $contact['name']);
                            $recipient = $contact['email'];
                        } elseif ($method === 'whatsapp' && !empty($contact['whatsapp'])) {
                            $result = sendWhatsAppAPI($contact['whatsapp'], $message);
                            $recipient = $contact['whatsapp'];
                        } else {
                            continue; // Skip if no valid contact info
                        }
                        
                        if ($result && ((isset($result['success']) && $result['success']) || isset($result['messageId']) || isset($result['message_id']))) {
                            $sent++;
                            deductCredits($user['id'], $method, 1);
                            logMessage($user['id'], $method, $recipient, $message, 'sent');
                            
                            // Log poll invitation
                            $stmt = $conn->prepare("INSERT INTO poll_invitations (poll_id, contact_id, method, status) VALUES (?, ?, ?, 'sent')");
                            $stmt->bind_param("iis", $poll_id, $contact['id'], $method);
                            $stmt->execute();
                        } else {
                            $failed++;
                            // Log failure with details
                            $error_msg = isset($result['error']) ? $result['error'] : (isset($result['response']) ? json_encode($result['response']) : 'Unknown error');
                            error_log("POLL INVITE FAILED - User: {$user['id']}, Poll: {$poll_id}, Method: {$method}, Recipient: {$recipient}, Error: {$error_msg}");
                            logMessage($user['id'], $method, $recipient, $message, 'failed');
                        }
                    }
                    
                    $success = "Invitations sent: $sent successful, $failed failed";
                    $credits = getMessagingCredits($user['id']); // Refresh
                }
            }
        }
    }
}

$page_title = 'Send Poll Invitations';
include '../header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-3"><i class="fas fa-paper-plane me-2"></i>Send Poll Invitations</h2>
            <p class="text-muted">Invite your contacts to participate in your polls via SMS, Email, or WhatsApp.</p>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?= $success ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="h5 mb-0"><i class="fas fa-envelope-open-text me-2"></i>Send Invitations</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Select Poll</label>
                            <select name="poll_id" class="form-select" required>
                                <option value="">-- Choose a poll --</option>
                                <?php while ($poll = $polls->fetch_assoc()): ?>
                                    <option value="<?= $poll['id'] ?>"><?= htmlspecialchars($poll['title']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Contact List</label>
                            <select name="list_id" class="form-select" required>
                                <option value="">-- Choose a contact list --</option>
                                <?php while ($list = $lists->fetch_assoc()): ?>
                                    <?php
                                    $count = $conn->query("SELECT COUNT(*) as total FROM contacts WHERE list_id = {$list['id']}")->fetch_assoc()['total'];
                                    ?>
                                    <option value="<?= $list['id'] ?>">
                                        <?= htmlspecialchars($list['name']) ?> (<?= $count ?> contacts)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="form-text">
                                <a href="contacts.php" target="_blank">Manage contact lists</a>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Invitation Method</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="method" id="method_sms" value="sms" required>
                                        <label class="form-check-label" for="method_sms">
                                            <i class="fas fa-sms text-primary me-1"></i> SMS
                                            <small class="d-block text-muted">Balance: <?= number_format($credits['sms_balance'] ?? 0) ?></small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="method" id="method_email" value="email" required>
                                        <label class="form-check-label" for="method_email">
                                            <i class="fas fa-envelope text-info me-1"></i> Email
                                            <small class="d-block text-muted">Balance: <?= number_format($credits['email_balance'] ?? 0) ?></small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="method" id="method_whatsapp" value="whatsapp" required>
                                        <label class="form-check-label" for="method_whatsapp">
                                            <i class="fab fa-whatsapp text-success me-1"></i> WhatsApp
                                            <small class="d-block text-muted">Balance: <?= number_format($credits['whatsapp_balance'] ?? 0) ?></small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-text mt-2">
                                Need more credits? <a href="buy-credits.php">Buy credits here</a>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> An automatic invitation message will be sent to all contacts in the selected list with a link to your poll.
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Send Invitations
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-secondary text-white">
                    <h3 class="h6 mb-0"><i class="fas fa-credit-card me-2"></i>Your Credits</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span><i class="fas fa-sms text-primary"></i> SMS</span>
                            <strong><?= number_format($credits['sms_balance'] ?? 0) ?></strong>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 50%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span><i class="fas fa-envelope text-info"></i> Email</span>
                            <strong><?= number_format($credits['email_balance'] ?? 0) ?></strong>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 50%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span><i class="fab fa-whatsapp text-success"></i> WhatsApp</span>
                            <strong><?= number_format($credits['whatsapp_balance'] ?? 0) ?></strong>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 50%"></div>
                        </div>
                    </div>
                    
                    <a href="buy-credits.php" class="btn btn-primary w-100">
                        <i class="fas fa-shopping-cart me-2"></i>Buy More Credits
                    </a>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h3 class="h6 mb-0"><i class="fas fa-lightbulb me-2"></i>Tips</h3>
                </div>
                <div class="card-body">
                    <ul class="small mb-0 ps-3">
                        <li>Email is most cost-effective for large lists</li>
                        <li>SMS has highest open rates</li>
                        <li>WhatsApp works best for engaged audiences</li>
                        <li>Make sure your contact lists are up to date</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
