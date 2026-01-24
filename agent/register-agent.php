<?php
// Check if user is logged in
include_once '../connect.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "signup.php");
    exit;
}

$current_user = getCurrentUser();

// Check if already an agent
if ($current_user['role'] === 'agent') {
    $_SESSION['success'] = "You are already registered as an agent!";
    header("Location: " . SITE_URL . "dashboard.php");
    exit;
}

// Handle form submission BEFORE including header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Get form data
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $lga = sanitize($_POST['lga'] ?? '');
    $bank_name = sanitize($_POST['bank_name'] ?? '');
    $account_name = sanitize($_POST['account_name'] ?? '');
    $account_number = sanitize($_POST['account_number'] ?? '');
    $payment_preference = sanitize($_POST['payment_preference'] ?? '');
    
    // Validate required fields
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($state)) $errors[] = "State is required";
    if (empty($lga)) $errors[] = "LGA is required";
    if (empty($bank_name)) $errors[] = "Bank name is required";
    if (empty($account_name)) $errors[] = "Account name is required";
    if (empty($account_number)) $errors[] = "Account number is required";
    if (empty($payment_preference)) $errors[] = "Payment preference is required";
    
    if (empty($errors)) {
        // Update user role to agent with pending status
        $user_id = $current_user['id'];
        
        // Update users table with all agent information
        $stmt = $conn->prepare("UPDATE users SET 
            role = 'agent', 
            agent_status = 'pending',
            agent_applied_at = NOW(),
            phone = ?, 
            address = ?, 
            state = ?, 
            lga = ?,
            bank_name = ?,
            account_name = ?,
            account_number = ?,
            payment_preference = ?
            WHERE id = ?");
        $stmt->bind_param("ssssssssi", $phone, $address, $state, $lga, $bank_name, $account_name, $account_number, $payment_preference, $user_id);
        
        if ($stmt->execute()) {
            // Update session
            $_SESSION['role'] = 'agent';
            $_SESSION['agent_status'] = 'pending';
            
            // Send notification to agent
            createNotification(
                $user_id,
                'agent_application_submitted',
                'Agent Application Submitted!',
                'Your agent application has been submitted and is under review. You will be notified within 48 hours.',
                'dashboard.php'
            );
            
            // Send email confirmation
            sendTemplatedEmail(
                $current_user['email'],
                $current_user['first_name'] . ' ' . $current_user['last_name'],
                'Agent Application Received',
                "Thank you for applying to become an agent! Your application is under review and you'll receive a response within 48 hours.",
                'View Status',
                SITE_URL . 'dashboard.php'
            );
            
            // Notify all admins
            $admin_result = $conn->query("SELECT id, email, first_name, last_name FROM users WHERE role = 'admin'");
            while ($admin = $admin_result->fetch_assoc()) {
                createNotification(
                    $admin['id'],
                    'agent_application_pending',
                    'New Agent Application',
                    "{$current_user['first_name']} {$current_user['last_name']} has applied to become an agent.",
                    'admin/agents.php'
                );
                
                sendTemplatedEmail(
                    $admin['email'],
                    $admin['first_name'] . ' ' . $admin['last_name'],
                    'New Agent Application',
                    "A new agent application from {$current_user['first_name']} {$current_user['last_name']} requires your review.",
                    'Review Application',
                    SITE_URL . 'admin/agents.php'
                );
            }
            
            $_SESSION['success'] = "Congratulations! Your agent application has been submitted. You will be notified via email once approved (within 48 hours).";
            header("Location: " . SITE_URL . "dashboard.php");
            exit;
        } else {
            $errors[] = "An error occurred. Please try again.";
        }
    }
    
    $_SESSION['errors'] = $errors;
}

$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);

// NOW include header after all redirects are done
$page_title = "Become an Agent - Complete Your Profile";
include_once '../header.php';

// Nigerian states and LGAs
$nigerian_states = [
    'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno', 'Cross River',
    'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano',
    'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun',
    'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara'
];

$nigerian_banks = [
    'Access Bank', 'Citibank', 'Ecobank Nigeria', 'Fidelity Bank', 'First Bank of Nigeria',
    'First City Monument Bank (FCMB)', 'Guaranty Trust Bank (GTBank)', 'Heritage Bank',
    'Keystone Bank', 'Polaris Bank', 'Providus Bank', 'Stanbic IBTC Bank', 'Standard Chartered Bank',
    'Sterling Bank', 'Union Bank of Nigeria', 'United Bank for Africa (UBA)', 'Unity Bank',
    'Wema Bank', 'Zenith Bank', 'Kuda Bank', 'Moniepoint', 'OPay', 'PalmPay'
];
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-user-tie fa-4x text-primary"></i>
                        </div>
                        <h2>Complete Your Agent Profile</h2>
                        <p class="text-muted">You're one step away from earning ₦1,000 per poll!</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle"></i> <strong>Note:</strong> Your application will be reviewed within 48 hours. Once approved, you'll receive tasks via email.
                        </div>

                        <h5 class="mb-3"><i class="fas fa-user"></i> Personal Information</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['first_name']); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['last_name']); ?>" disabled>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($current_user['email']); ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="phone" placeholder="e.g., 08012345678" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="address" placeholder="Your residential address" required>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">State <span class="text-danger">*</span></label>
                                <select class="form-select" name="state" id="state" required>
                                    <option value="">Select State</option>
                                    <?php foreach ($nigerian_states as $state): ?>
                                        <option value="<?php echo $state; ?>"><?php echo $state; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">LGA <span class="text-danger">*</span></label>
                                <select class="form-select" name="lga" id="lga" required disabled>
                                    <option value="">Select State First</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3"><i class="fas fa-university"></i> Banking Details</h5>
                        <p class="text-muted small mb-3">This is where we'll send your earnings</p>

                        <div class="mb-3">
                            <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                            <select class="form-select" name="bank_name" required>
                                <option value="">Select Bank</option>
                                <?php foreach ($nigerian_banks as $bank): ?>
                                    <option value="<?php echo $bank; ?>"><?php echo $bank; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="account_name" placeholder="Account name as it appears on your bank statement" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Account Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="account_number" placeholder="10-digit account number" pattern="[0-9]{10}" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Preferred Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_preference" required>
                                <option value="">Select Payment Method</option>
                                <option value="cash">Bank Transfer (Cash)</option>
                                <option value="airtime">Airtime</option>
                                <option value="data">Data Bundle</option>
                            </select>
                            <small class="text-muted">Choose how you want to receive your ₦1,000 per poll earnings</small>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Ensure your banking details are correct. Incorrect details may delay your payments.
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check-circle"></i> Complete Agent Registration
                            </button>
                            <a href="<?php echo SITE_URL; ?>dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>

                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="mb-2"><i class="fas fa-gift text-success"></i> What You'll Earn:</h6>
                        <ul class="mb-0 small">
                            <li>₦1,000, airtime, or data per completed poll</li>
                            <li>Payment within 5 working days</li>
                            <li>Flexible work - work anytime, anywhere</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stateSelect = document.getElementById('state');
    const lgaSelect = document.getElementById('lga');
    let lgasData = {};

    // Load LGA data from JSON file
    fetch('<?php echo SITE_URL; ?>assets/data/nigeria-states-lgas.json')
        .then(response => response.json())
        .then(data => {
            lgasData = data;
            
            // Enable state change handler
            stateSelect.addEventListener('change', function() {
                const selectedState = this.value;
                
                // Clear previous LGA options
                lgaSelect.innerHTML = '<option value="">Select LGA</option>';
                
                if (selectedState && lgasData[selectedState]) {
                    // Enable LGA select
                    lgaSelect.disabled = false;
                    lgaSelect.required = true;
                    
                    // Populate LGA options
                    lgasData[selectedState].forEach(function(lga) {
                        const option = document.createElement('option');
                        option.value = lga;
                        option.textContent = lga;
                        lgaSelect.appendChild(option);
                    });
                } else {
                    // Disable LGA select if no state selected
                    lgaSelect.disabled = true;
                    lgaSelect.required = false;
                }
            });
        })
        .catch(error => {
            console.error('Error loading LGA data:', error);
            // Fallback: enable manual input if JSON fails to load
            const fallbackLga = document.createElement('input');
            fallbackLga.type = 'text';
            fallbackLga.className = 'form-control';
            fallbackLga.name = 'lga';
            fallbackLga.placeholder = 'Local Government Area';
            fallbackLga.required = true;
            lgaSelect.parentNode.replaceChild(fallbackLga, lgaSelect);
        });
});
</script>

<?php include_once '../footer.php'; ?>
