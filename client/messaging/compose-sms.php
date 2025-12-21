<?php
require_once '../../connect.php';
require_once '../../functions.php';

requireRole('client');

$user = getCurrentUser();
$credits = getMessagingCredits($user['id']);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipients = $_POST['recipients']; // Comma-separated phone numbers
    $message = sanitize($_POST['message']);
    $sender_id = isset($_POST['sender_id']) ? sanitize($_POST['sender_id']) : TERMII_SENDER_ID;
    
    if (empty($recipients) || empty($message)) {
        $error = 'Please fill all required fields';
    } else {
        // Parse recipients
        $phone_numbers = array_map('trim', explode(',', $recipients));
        $phone_numbers = array_filter($phone_numbers);
        
        $count = count($phone_numbers);
        
        // Check credits
        if ($credits && $credits['sms_balance'] >= $count) {
            $sent = 0;
            $failed = 0;
            
            foreach ($phone_numbers as $phone) {
                // Clean phone number
                $phone = preg_replace('/[^0-9+]/', '', $phone);
                
                // Send SMS
                $result = sendSMS_Termii($phone, $message, $sender_id);
                
                if (isset($result['message_id']) || (isset($result['success']) && $result['success'])) {
                    $sent++;
                    deductCredits($user['id'], 'sms', 1);
                    logMessage($user['id'], 'sms', $phone, $message, 'sent', 1, json_encode($result));
                } else {
                    $failed++;
                    // Log failure with details
                    $error_msg = isset($result['error']) ? $result['error'] : (isset($result['response']) ? json_encode($result['response']) : 'Unknown error');
                    error_log("SMS SEND FAILED - User: {$user['id']}, Recipient: {$phone}, Error: {$error_msg}");
                    logMessage($user['id'], 'sms', $phone, $message, 'failed', 0, json_encode($result));
                }
            }
            
            $success = "SMS sent: $sent successful, $failed failed";
            $credits = getMessagingCredits($user['id']); // Refresh credits
        } else {
            $error = "Insufficient SMS credits. You need $count credits but only have " . ($credits['sms_balance'] ?? 0);
        }
    }
}

$page_title = 'Send SMS';
include '../../header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-sms me-2"></i>Send SMS Messages</h5>
                </div>
                <div class="card-body">
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
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Recipients (Phone Numbers) *</label>
                            <textarea name="recipients" class="form-control" rows="3" required 
                                      placeholder="Enter phone numbers separated by commas&#10;Example: +2348012345678, 08023456789, 07034567890"></textarea>
                            <small class="text-muted">
                                Separate multiple numbers with commas. Format: +234XXXXXXXXXX or 0XXXXXXXXXX
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sender ID</label>
                            <input type="text" name="sender_id" class="form-control" 
                                   value="<?= TERMII_SENDER_ID ?>" maxlength="11"
                                   placeholder="Max 11 characters">
                            <small class="text-muted">
                                The name that will appear as the sender. Leave default for best delivery.
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Message *</label>
                            <textarea name="message" id="sms_message" class="form-control" rows="5" 
                                      maxlength="480" required placeholder="Type your message here..."></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">
                                    <span id="char_count">0</span>/480 characters | 
                                    <span id="sms_count">0</span> SMS page(s)
                                </small>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Cost Estimate:</strong> <span id="cost_estimate">0 credits</span>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Send SMS
                            </button>
                            <a href="../buy-credits.php" class="btn btn-outline-success">
                                <i class="fas fa-plus me-2"></i>Buy Credits
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow mb-3">
                <div class="card-body">
                    <h6 class="card-title">SMS Credits Balance</h6>
                    <h2 class="text-primary mb-0">
                        <?= number_format($credits['sms_balance'] ?? 0) ?>
                    </h2>
                    <small class="text-muted">SMS units available</small>
                </div>
            </div>
            
            <div class="card shadow">
                <div class="card-body">
                    <h6 class="card-title">Quick Tips</h6>
                    <ul class="small mb-0">
                        <li>Each SMS page is 160 characters</li>
                        <li>Messages over 160 chars use multiple credits</li>
                        <li>Avoid special characters to save credits</li>
                        <li>Test with one number first</li>
                        <li>Use <a href="../contacts.php">Contact Lists</a> for bulk sending</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const messageField = document.getElementById('sms_message');
const recipientsField = document.querySelector('[name="recipients"]');

function updateCounts() {
    const message = messageField.value;
    const charCount = message.length;
    const smsCount = Math.ceil(charCount / 160) || 1;
    
    document.getElementById('char_count').textContent = charCount;
    document.getElementById('sms_count').textContent = smsCount;
    
    // Calculate cost
    const recipients = recipientsField.value.split(',').filter(r => r.trim()).length;
    const totalCredits = recipients * smsCount;
    document.getElementById('cost_estimate').textContent = totalCredits + ' credits';
}

messageField.addEventListener('input', updateCounts);
recipientsField.addEventListener('input', updateCounts);
updateCounts();
</script>

<?php include '../../footer.php'; ?>
