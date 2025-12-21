<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('client');

$user = getCurrentUser();
$credits = getMessagingCredits($user['id']);

$success = '';
$error = '';

// Get contact lists with contact counts
$lists = $conn->query("SELECT cl.*, 
                       (SELECT COUNT(*) FROM contacts c WHERE c.list_id = cl.id) as total_contacts 
                       FROM contact_lists cl 
                       WHERE cl.user_id = {$user['id']} 
                       ORDER BY cl.name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $list_id = (int)$_POST['list_id'];
    $message_type = sanitize($_POST['message_type']);
    $message = sanitize($_POST['message']);
    $subject = isset($_POST['subject']) ? sanitize($_POST['subject']) : '';
    
    // Get contacts from list
    $contacts_query = $conn->query("SELECT * FROM contacts WHERE list_id = $list_id");
    $contacts_array = [];
    while ($c = $contacts_query->fetch_assoc()) {
        $contacts_array[] = $c;
    }
    
    $total = count($contacts_array);
    
    if ($total === 0) {
        $error = 'Selected list has no contacts';
    } elseif (empty($message)) {
        $error = 'Message is required';
    } else {
        // Check credits
        $available = 0;
        if ($message_type === 'sms') {
            $available = $credits['sms_balance'];
        } elseif ($message_type === 'email') {
            $available = $credits['email_balance'];
        } elseif ($message_type === 'whatsapp') {
            $available = $credits['whatsapp_balance'];
        }
        
        if ($available < $total) {
            $error = "Insufficient credits. You need $total but have $available";
        } else {
            $sent = 0;
            $failed = 0;
            
            foreach ($contacts_array as $contact) {
                $result = null;
                
                if ($message_type === 'sms' && !empty($contact['phone'])) {
                    $result = sendSMS_Termii($contact['phone'], $message);
                    $recipient = $contact['phone'];
                } elseif ($message_type === 'email' && !empty($contact['email'])) {
                    $html = "<p>" . nl2br(htmlspecialchars($message)) . "</p>";
                    $result = sendEmail_Brevo($contact['email'], $subject, $html, $contact['name']);
                    $recipient = $contact['email'];
                } elseif ($message_type === 'whatsapp' && !empty($contact['whatsapp'])) {
                    $result = sendWhatsAppAPI($contact['whatsapp'], $message);
                    $recipient = $contact['whatsapp'];
                } else {
                    continue; // Skip if no valid contact info
                }
                
                if ($result && (isset($result['success']) && $result['success']) || isset($result['messageId']) || isset($result['message_id'])) {
                    $sent++;
                    deductCredits($user['id'], $message_type, 1);
                    logMessage($user['id'], $message_type, $recipient, $message, 'sent');
                } else {
                    $failed++;
                    // Log failure with details
                    $error_msg = isset($result['error']) ? $result['error'] : (isset($result['response']) ? json_encode($result['response']) : 'Unknown error');
                    error_log("BULK SEND FAILED - User: {$user['id']}, Type: {$message_type}, Recipient: {$recipient}, Error: {$error_msg}");
                    logMessage($user['id'], $message_type, $recipient, $message, 'failed');
                }
            }
            
            $success = "Bulk send completed: $sent sent, $failed failed";
            $credits = getMessagingCredits($user['id']); // Refresh
        }
    }
}

$page_title = 'Bulk Messaging';
include '../header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Bulk Messaging</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Select Contact List *</label>
                            <select name="list_id" id="list_select" class="form-select" required>
                                <option value="">Choose a list...</option>
                                <?php while ($list = $lists->fetch_assoc()): ?>
                                    <option value="<?= $list['id'] ?>" data-count="<?= $list['total_contacts'] ?>">
                                        <?= htmlspecialchars($list['name']) ?> (<?= number_format($list['total_contacts']) ?> contacts)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Message Type *</label>
                            <select name="message_type" id="message_type" class="form-select" required>
                                <option value="sms">SMS (<?= number_format($credits['sms_balance']) ?> available)</option>
                                <option value="email">Email (<?= number_format($credits['email_balance']) ?> available)</option>
                                <option value="whatsapp">WhatsApp (<?= number_format($credits['whatsapp_balance']) ?> available)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="subject_field" style="display:none;">
                            <label class="form-label">Email Subject</label>
                            <input type="text" name="subject" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Message *</label>
                            <textarea name="message" class="form-control" rows="8" required></textarea>
                        </div>
                        
                        <div class="alert alert-info" id="cost_alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Recipients:</strong> <span id="recipient_count">0</span> | 
                            <strong>Credits needed:</strong> <span id="credits_needed">0</span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Send Bulk Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow mb-3">
                <div class="card-body">
                    <h6 class="card-title">Current Credits</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-sms text-primary me-2"></i>
                            SMS: <strong><?= number_format($credits['sms_balance']) ?></strong>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-envelope text-info me-2"></i>
                            Email: <strong><?= number_format($credits['email_balance']) ?></strong>
                        </li>
                        <li>
                            <i class="fab fa-whatsapp text-success me-2"></i>
                            WhatsApp: <strong><?= number_format($credits['whatsapp_balance']) ?></strong>
                        </li>
                    </ul>
                    <a href="buy-credits.php" class="btn btn-sm btn-outline-success mt-3 w-100">
                        <i class="fas fa-plus me-2"></i>Buy Credits
                    </a>
                </div>
            </div>
            
            <div class="card shadow">
                <div class="card-body">
                    <h6 class="card-title">Bulk Messaging Tips</h6>
                    <ul class="small mb-0">
                        <li>Test with a small list first</li>
                        <li>Personalize your message when possible</li>
                        <li>Check credits before sending</li>
                        <li>Messages are sent one by one</li>
                        <li>Failed sends don't deduct credits</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const listSelect = document.getElementById('list_select');
const messageType = document.getElementById('message_type');
const subjectField = document.getElementById('subject_field');

listSelect.addEventListener('change', updateCost);
messageType.addEventListener('change', function() {
    subjectField.style.display = this.value === 'email' ? 'block' : 'none';
    updateCost();
});

function updateCost() {
    const selected = listSelect.options[listSelect.selectedIndex];
    const count = selected ? parseInt(selected.dataset.count || 0) : 0;
    document.getElementById('recipient_count').textContent = count;
    document.getElementById('credits_needed').textContent = count;
}

updateCost();
</script>

<?php include '../footer.php'; ?>
