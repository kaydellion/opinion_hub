<?php
require_once '../../connect.php';
require_once '../../functions.php';

requireRole('client');

$user = getCurrentUser();
$credits = getMessagingCredits($user['id']);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipients = $_POST['recipients']; // Comma-separated WhatsApp numbers
    $message = sanitize($_POST['message']);
    
    if (empty($recipients) || empty($message)) {
        $error = 'Please fill all required fields';
    } else {
        // Parse recipients
        $phone_numbers = array_map('trim', explode(',', $recipients));
        $phone_numbers = array_filter($phone_numbers);
        
        $count = count($phone_numbers);
        
        // Check credits
        if ($credits && $credits['whatsapp_balance'] >= $count) {
            $sent = 0;
            $failed = 0;
            
            foreach ($phone_numbers as $phone) {
                // Clean phone number
                $phone = preg_replace('/[^0-9+]/', '', $phone);
                
                // Send WhatsApp
                $result = sendWhatsAppAPI($phone, $message);
                
                if (isset($result['message_id']) || (isset($result['success']) && $result['success'])) {
                    $sent++;
                    deductCredits($user['id'], 'whatsapp', 1);
                    logMessage($user['id'], 'whatsapp', $phone, $message, 'sent', 1, json_encode($result));
                } else {
                    $failed++;
                    // Log failure with details
                    $error_msg = isset($result['error']) ? $result['error'] : (isset($result['response']) ? json_encode($result['response']) : 'Unknown error');
                    error_log("WHATSAPP SEND FAILED - User: {$user['id']}, Recipient: {$phone}, Error: {$error_msg}");
                    logMessage($user['id'], 'whatsapp', $phone, $message, 'failed', 0, json_encode($result));
                }
            }
            
            $success = "WhatsApp messages sent: $sent successful, $failed failed";
            $credits = getMessagingCredits($user['id']); // Refresh credits
        } else {
            $error = "Insufficient WhatsApp credits. You need $count credits but only have " . ($credits['whatsapp_balance'] ?? 0);
        }
    }
}

$page_title = 'Send WhatsApp';
include '../../header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fab fa-whatsapp me-2"></i>Send WhatsApp Messages</h5>
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
                            <label class="form-label">Recipients (WhatsApp Numbers) *</label>
                            <textarea name="recipients" class="form-control" rows="3" required 
                                      placeholder="Enter WhatsApp numbers separated by commas&#10;Example: +2348012345678, 08023456789, 07034567890"></textarea>
                            <small class="text-muted">
                                Separate multiple numbers with commas. Format: +234XXXXXXXXXX or 0XXXXXXXXXX
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Message *</label>
                            <textarea name="message" id="whatsapp_message" class="form-control" rows="8" 
                                      maxlength="1000" required placeholder="Type your WhatsApp message here..."></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">
                                    <span id="char_count">0</span>/1000 characters
                                </small>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Cost Estimate:</strong> <span id="cost_estimate">0 credits</span>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fab fa-whatsapp me-2"></i>Send WhatsApp
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
                    <h6 class="card-title">WhatsApp Credits Balance</h6>
                    <h2 class="text-success mb-0">
                        <?= number_format($credits['whatsapp_balance'] ?? 0) ?>
                    </h2>
                    <small class="text-muted">WhatsApp units available</small>
                </div>
            </div>
            
            <div class="card shadow">
                <div class="card-body">
                    <h6 class="card-title">Quick Tips</h6>
                    <ul class="small mb-0">
                        <li>Each WhatsApp message = 1 credit</li>
                        <li>Recipients must have WhatsApp installed</li>
                        <li>Messages are delivered instantly</li>
                        <li>Keep messages concise and clear</li>
                        <li>Test with your number first</li>
                        <li>Use <a href="../contacts.php">Contact Lists</a> for bulk sending</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const messageField = document.getElementById('whatsapp_message');
const recipientsField = document.querySelector('[name="recipients"]');

function updateCounts() {
    const charCount = messageField.value.length;
    document.getElementById('char_count').textContent = charCount;
    
    // Calculate cost
    const recipients = recipientsField.value.split(',').filter(r => r.trim()).length;
    document.getElementById('cost_estimate').textContent = recipients + ' credits';
}

messageField.addEventListener('input', updateCounts);
recipientsField.addEventListener('input', updateCounts);
updateCounts();
</script>

<?php include '../../footer.php'; ?>
