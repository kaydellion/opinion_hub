<?php
require_once '../connect.php';
require_once '../functions.php';

// Require agent role
requireRole('agent');

$user = getCurrentUser();
$agent = getAgentByUserId($user['id']);

if (!$agent) {
    header('Location: ../dashboard.php');
    exit;
}

// If profile not completed, redirect to complete profile
if (!$agent['profile_completed']) {
    header('Location: complete-profile.php');
    exit;
}

// If contract already accepted, redirect to dashboard
if ($agent['contract_accepted']) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_contract'])) {
    // Accept contract
    $stmt = $conn->prepare("UPDATE agents SET contract_accepted = 1, contract_accepted_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $agent['id']);
    
    if ($stmt->execute()) {
        header('Location: index.php?welcome=1');
        exit;
    } else {
        $error = 'Failed to accept contract. Please try again.';
    }
}

$page_title = 'Agent Contract';
include '../header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Agent Agreement & Contract</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <div class="border rounded p-4 mb-4" style="max-height: 500px; overflow-y: auto; background: #f8f9fa;">
                        <h4>Opinion Hub NG - Agent Terms & Conditions</h4>
                        <p><small class="text-muted">Last Updated: November 22, 2025</small></p>
                        
                        <h5 class="mt-4">1. Introduction</h5>
                        <p>
                            Welcome to Opinion Hub NG. By accepting this agreement, you agree to work as an independent 
                            agent to collect survey responses on behalf of our clients. Please read these terms carefully.
                        </p>
                        
                        <h5 class="mt-4">2. Agent Responsibilities</h5>
                        <ul>
                            <li>Collect genuine, honest responses from real people</li>
                            <li>Do not fabricate or duplicate responses</li>
                            <li>Maintain professional conduct when interacting with respondents</li>
                            <li>Meet assigned response targets within specified deadlines</li>
                            <li>Protect the privacy and confidentiality of survey participants</li>
                            <li>Follow all quality guidelines provided by Opinion Hub NG</li>
                        </ul>
                        
                        <h5 class="mt-4">3. Compensation</h5>
                        <p>
                            Agents will be compensated based on verified responses collected. Payment options include:
                        </p>
                        <ul>
                            <li><strong>Cash Payment:</strong> ₦1,000 per verified response (paid to bank account)</li>
                            <li><strong>Airtime:</strong> ₦1,000 airtime credit per verified response (sent instantly)</li>
                            <li><strong>Data Bundle:</strong> Equivalent data bundle per verified response</li>
                        </ul>
                        <p>
                            Your preferred payment method: <strong><?= ucfirst($agent['reward_preference']) ?></strong>
                        </p>
                        
                        <h5 class="mt-4">4. Quality Standards</h5>
                        <p>
                            All responses must meet our quality standards. We reserve the right to:
                        </p>
                        <ul>
                            <li>Review and verify all submitted responses</li>
                            <li>Reject responses that appear fraudulent or duplicate</li>
                            <li>Withhold payment for responses that fail quality checks</li>
                            <li>Suspend or terminate agents who repeatedly submit poor quality work</li>
                        </ul>
                        
                        <h5 class="mt-4">5. Payment Schedule</h5>
                        <ul>
                            <li><strong>Airtime/Data:</strong> Processed automatically upon response verification</li>
                            <li><strong>Cash Payments:</strong> Processed weekly for verified responses</li>
                            <li>Minimum payout threshold: ₦5,000 for cash payments</li>
                        </ul>
                        
                        <h5 class="mt-4">6. Independent Contractor Status</h5>
                        <p>
                            You are an independent contractor, not an employee of Opinion Hub NG. You are responsible 
                            for your own taxes and expenses. No employment relationship is created by this agreement.
                        </p>
                        
                        <h5 class="mt-4">7. Confidentiality</h5>
                        <p>
                            You agree to maintain the confidentiality of:
                        </p>
                        <ul>
                            <li>Client information and survey content</li>
                            <li>Respondent personal information</li>
                            <li>Business practices and proprietary information</li>
                        </ul>
                        
                        <h5 class="mt-4">8. Prohibited Activities</h5>
                        <p>The following activities are strictly prohibited:</p>
                        <ul>
                            <li>Submitting fake or fabricated responses</li>
                            <li>Using automated tools or bots</li>
                            <li>Sharing survey links publicly without authorization</li>
                            <li>Collecting responses from the same person multiple times</li>
                            <li>Misrepresenting yourself or Opinion Hub NG</li>
                        </ul>
                        
                        <h5 class="mt-4">9. Termination</h5>
                        <p>
                            Either party may terminate this agreement at any time. Opinion Hub NG reserves the right to 
                            immediately suspend or terminate your account for violations of these terms.
                        </p>
                        
                        <h5 class="mt-4">10. Data Protection</h5>
                        <p>
                            You agree to comply with all applicable data protection laws and handle respondent information 
                            in accordance with our Privacy Policy.
                        </p>
                        
                        <h5 class="mt-4">11. Limitation of Liability</h5>
                        <p>
                            Opinion Hub NG is not liable for any indirect, incidental, or consequential damages arising 
                            from your work as an agent.
                        </p>
                        
                        <h5 class="mt-4">12. Governing Law</h5>
                        <p>
                            This agreement is governed by the laws of the Federal Republic of Nigeria.
                        </p>
                        
                        <h5 class="mt-4">13. Changes to Terms</h5>
                        <p>
                            Opinion Hub NG reserves the right to modify these terms at any time. Continued participation 
                            as an agent constitutes acceptance of any changes.
                        </p>
                        
                        <h5 class="mt-4">14. Contact Information</h5>
                        <p>
                            For questions about this agreement, contact us at:<br>
                            Email: agents@opinionhubng.com<br>
                            Phone: +234 XXX XXX XXXX
                        </p>
                    </div>
                    
                    <form method="POST">
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="accept_terms" required>
                            <label class="form-check-label" for="accept_terms">
                                <strong>I have read and agree to the Agent Terms & Conditions above</strong>
                            </label>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <a href="index.php" class="btn btn-outline-secondary btn-lg w-100">
                                    Decline
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <button type="submit" name="accept_contract" class="btn btn-success btn-lg w-100">
                                    Accept & Continue
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    if (!document.getElementById('accept_terms').checked) {
        e.preventDefault();
        alert('Please check the box to accept the terms and conditions.');
    }
});
</script>

<?php include '../footer.php'; ?>
