<?php
require_once '../../connect.php';
require_once '../../functions.php';

requireRole('client');

$user = getCurrentUser();
$credits = getMessagingCredits($user['id']);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipients = $_POST['recipients']; // Comma-separated emails
    $subject = sanitize($_POST['subject']);
    $message = $_POST['message']; // HTML content
    
    if (empty($recipients) || empty($subject) || empty($message)) {
        $error = 'Please fill all required fields';
    } else {
        // Parse recipients
        $emails = array_map('trim', explode(',', $recipients));
        $emails = array_filter($emails, 'validateEmail');
        
        $count = count($emails);
        
        // Check credits
        if ($credits && $credits['email_balance'] >= $count) {
            $sent = 0;
            $failed = 0;
            
            foreach ($emails as $email) {
                // Send Email
                $result = sendEmail_Brevo($email, $subject, $message);
                
                if (isset($result['messageId']) || (isset($result['success']) && $result['success'])) {
                    $sent++;
                    deductCredits($user['id'], 'email', 1);
                    logMessage($user['id'], 'email', $email, strip_tags($message), 'sent', 1, json_encode($result));
                } else {
                    $failed++;
                    logMessage($user['id'], 'email', $email, strip_tags($message), 'failed', 0, json_encode($result));
                }
            }
            
            $success = "Emails sent: $sent successful, $failed failed";
            $credits = getMessagingCredits($user['id']); // Refresh credits
        } else {
            $error = "Insufficient email credits. You need $count credits but only have " . ($credits['email_balance'] ?? 0);
        }
    }
}

$page_title = 'Send Email';
include '../../header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Send Email Messages</h5>
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
                            <label class="form-label">Recipients (Email Addresses) *</label>
                            <textarea name="recipients" class="form-control" rows="3" required 
                                      placeholder="Enter email addresses separated by commas&#10;Example: user@example.com, another@example.com"></textarea>
                            <small class="text-muted">
                                Separate multiple emails with commas
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Subject *</label>
                            <input type="text" name="subject" class="form-control" required
                                   placeholder="Email subject">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Message (HTML) *</label>
                            <textarea name="message" id="email_message" class="form-control" rows="12" required 
                                      placeholder="Type your message here... You can use HTML formatting."></textarea>
                            <small class="text-muted">
                                You can use HTML tags like &lt;p&gt;, &lt;b&gt;, &lt;i&gt;, &lt;a&gt;, &lt;br&gt;, etc.
                            </small>
                        </div>
                        
                        <div class="alert alert-info" id="cost_alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Cost Estimate:</strong> <span id="cost_estimate">0 credits</span>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Send Email
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
                    <h6 class="card-title">Email Credits Balance</h6>
                    <h2 class="text-primary mb-0">
                        <?= number_format($credits['email_balance'] ?? 0) ?>
                    </h2>
                    <small class="text-muted">Email units available</small>
                </div>
            </div>
            
            <div class="card shadow mb-3">
                <div class="card-body">
                    <h6 class="card-title">HTML Example</h6>
                    <pre class="small mb-0" style="font-size: 11px;">&lt;p&gt;Hello,&lt;/p&gt;
&lt;p&gt;This is a &lt;b&gt;bold&lt;/b&gt; message.&lt;/p&gt;
&lt;p&gt;Visit &lt;a href="https://example.com"&gt;our site&lt;/a&gt;&lt;/p&gt;</pre>
                </div>
            </div>
            
            <div class="card shadow">
                <div class="card-body">
                    <h6 class="card-title">Quick Tips</h6>
                    <ul class="small mb-0">
                        <li>Each email = 1 credit</li>
                        <li>Keep subject clear and concise</li>
                        <li>Test with your own email first</li>
                        <li>Include unsubscribe option for bulk sends</li>
                        <li>Use <a href="../contacts.php">Contact Lists</a> for organized sending</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const recipientsField = document.querySelector('[name="recipients"]');

function updateCost() {
    const recipients = recipientsField.value.split(',').filter(r => r.trim()).length;
    document.getElementById('cost_estimate').textContent = recipients + ' credits';
}

recipientsField.addEventListener('input', updateCost);
updateCost();
</script>

<?php include '../../footer.php'; ?>
