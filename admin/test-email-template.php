<?php
/**
 * Email Template Demo
 * This file demonstrates how to use the email template system
 */

require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_email = $_POST['test_email'] ?? '';
    $test_name = $_POST['test_name'] ?? 'User';
    
    if (filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        // Example 1: Simple email with template
        $message = '
            <p>Hello <strong>' . htmlspecialchars($test_name) . '</strong>,</p>
            <p>This is a test email to demonstrate our beautiful email template system.</p>
            <p>Features include:</p>
            <ul>
                <li>Clean, minimal design</li>
                <li>Mobile-responsive layout</li>
                <li>Branded headers and footers</li>
                <li>Professional typography</li>
                <li>Call-to-action buttons</li>
            </ul>
            <p>Best regards,<br>The ' . SITE_NAME . ' Team</p>
        ';
        
        $result = sendTemplatedEmail(
            $test_email,
            $test_name,
            'Test Email - Beautiful Template Demo',
            $message,
            'Visit Dashboard',
            SITE_URL . 'dashboard.php'
        );
        
        if (isset($result['messageId']) || (isset($result['success']) && $result['success'])) {
            $_SESSION['success'] = "Test email sent successfully to {$test_email}!";
        } else {
            $_SESSION['errors'] = ["Failed to send test email. Check your Brevo API configuration."];
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['errors'] = ["Invalid email address."];
    }
}

$page_title = "Email Template Demo";
include_once '../header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-envelope"></i> Email Template Demo</h3>
                </div>
                <div class="card-body">
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            <?= $_SESSION['success'] ?>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['errors'])): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            <?php foreach ($_SESSION['errors'] as $error): ?>
                                <p class="mb-0"><?= $error ?></p>
                            <?php endforeach; ?>
                        </div>
                        <?php unset($_SESSION['errors']); ?>
                    <?php endif; ?>
                    
                    <p class="mb-4">
                        Send a test email to see our beautiful, professional email template in action.
                        All emails sent through the system automatically use this template.
                    </p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Recipient Email</label>
                            <input type="email" class="form-control" name="test_email" required 
                                   placeholder="your@email.com">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Recipient Name</label>
                            <input type="text" class="form-control" name="test_name" required 
                                   placeholder="John Doe">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Test Email
                        </button>
                        <a href="settings.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Settings
                        </a>
                    </form>
                    
                    <hr class="my-4">
                    
                    <h5>Template Features:</h5>
                    <ul>
                        <li><strong>Responsive Design:</strong> Looks great on all devices</li>
                        <li><strong>Branded Header:</strong> Site name and tagline</li>
                        <li><strong>Clean Layout:</strong> Professional gradient header</li>
                        <li><strong>Action Buttons:</strong> Call-to-action support</li>
                        <li><strong>Footer Links:</strong> Automatic links to website, account, support</li>
                        <li><strong>Copyright:</strong> Auto-updated year</li>
                    </ul>
                    
                    <h5 class="mt-4">How to Use:</h5>
                    <pre class="bg-light p-3 rounded"><code>// Simple templated email
sendTemplatedEmail(
    'user@example.com',
    'User Name',
    'Subject Line',
    '&lt;p&gt;Your HTML message here&lt;/p&gt;',
    'Button Text',    // Optional
    'https://link.url' // Optional
);

// Or use getEmailTemplate() for custom emails
$html = getEmailTemplate(
    'Email Title',
    '&lt;p&gt;Content here&lt;/p&gt;',
    'Button Text',
    'Button URL'
);
sendEmail_Brevo('user@example.com', 'Subject', $html, 'Name', false);</code></pre>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../footer.php'; ?>
