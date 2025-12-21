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

// If already completed, redirect
if ($agent['profile_completed']) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $age = (int)$_POST['age'];
    $gender = sanitize($_POST['gender']);
    $state = sanitize($_POST['state']);
    $lga = sanitize($_POST['lga']);
    $education_level = sanitize($_POST['education_level']);
    $occupation = sanitize($_POST['occupation']);
    $interests = isset($_POST['interests']) ? json_encode($_POST['interests']) : '[]';
    $reward_preference = sanitize($_POST['reward_preference']);
    
    // Validate
    if ($age < 18 || $age > 100) {
        $error = 'Please enter a valid age (18-100)';
    } elseif (empty($gender) || empty($state) || empty($education_level)) {
        $error = 'Please fill all required fields';
    } else {
        // Update profile
        $stmt = $conn->prepare("UPDATE agents SET age = ?, gender = ?, state = ?, lga = ?, education_level = ?, 
                                occupation = ?, interests = ?, reward_preference = ?, profile_completed = 1 
                                WHERE id = ?");
        $stmt->bind_param("isssssssi", $age, $gender, $state, $lga, $education_level, $occupation, $interests, $reward_preference, $agent['id']);
        
        if ($stmt->execute()) {
            $success = 'Profile completed successfully!';
            header('Refresh: 2; url=contract.php');
        } else {
            $error = 'Failed to update profile. Please try again.';
        }
    }
}

$page_title = 'Complete Your Profile';
include '../header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Complete Your Agent Profile</h2>
                    <p class="text-muted text-center mb-4">
                        Help us match you with relevant surveys by completing your demographic profile.
                    </p>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Age *</label>
                                <input type="number" name="age" class="form-control" min="18" max="100" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender *</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">State *</label>
                                <select name="state" class="form-select" required>
                                    <option value="">Select State</option>
                                    <option value="Abia">Abia</option>
                                    <option value="Adamawa">Adamawa</option>
                                    <option value="Akwa Ibom">Akwa Ibom</option>
                                    <option value="Anambra">Anambra</option>
                                    <option value="Bauchi">Bauchi</option>
                                    <option value="Bayelsa">Bayelsa</option>
                                    <option value="Benue">Benue</option>
                                    <option value="Borno">Borno</option>
                                    <option value="Cross River">Cross River</option>
                                    <option value="Delta">Delta</option>
                                    <option value="Ebonyi">Ebonyi</option>
                                    <option value="Edo">Edo</option>
                                    <option value="Ekiti">Ekiti</option>
                                    <option value="Enugu">Enugu</option>
                                    <option value="FCT">FCT</option>
                                    <option value="Gombe">Gombe</option>
                                    <option value="Imo">Imo</option>
                                    <option value="Jigawa">Jigawa</option>
                                    <option value="Kaduna">Kaduna</option>
                                    <option value="Kano">Kano</option>
                                    <option value="Katsina">Katsina</option>
                                    <option value="Kebbi">Kebbi</option>
                                    <option value="Kogi">Kogi</option>
                                    <option value="Kwara">Kwara</option>
                                    <option value="Lagos">Lagos</option>
                                    <option value="Nasarawa">Nasarawa</option>
                                    <option value="Niger">Niger</option>
                                    <option value="Ogun">Ogun</option>
                                    <option value="Ondo">Ondo</option>
                                    <option value="Osun">Osun</option>
                                    <option value="Oyo">Oyo</option>
                                    <option value="Plateau">Plateau</option>
                                    <option value="Rivers">Rivers</option>
                                    <option value="Sokoto">Sokoto</option>
                                    <option value="Taraba">Taraba</option>
                                    <option value="Yobe">Yobe</option>
                                    <option value="Zamfara">Zamfara</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">LGA</label>
                                <input type="text" name="lga" class="form-control" placeholder="Local Government Area">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Education Level *</label>
                            <select name="education_level" class="form-select" required>
                                <option value="">Select Education Level</option>
                                <option value="primary">Primary School</option>
                                <option value="secondary">Secondary School</option>
                                <option value="tertiary">Tertiary (Diploma/Degree)</option>
                                <option value="postgraduate">Postgraduate</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Occupation</label>
                            <input type="text" name="occupation" class="form-control" placeholder="e.g., Student, Teacher, Trader">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Interests (Select all that apply)</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interests[]" value="politics">
                                        <label class="form-check-label">Politics</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interests[]" value="sports">
                                        <label class="form-check-label">Sports</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interests[]" value="technology">
                                        <label class="form-check-label">Technology</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interests[]" value="business">
                                        <label class="form-check-label">Business</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interests[]" value="health">
                                        <label class="form-check-label">Health</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interests[]" value="education">
                                        <label class="form-check-label">Education</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interests[]" value="entertainment">
                                        <label class="form-check-label">Entertainment</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interests[]" value="religion">
                                        <label class="form-check-label">Religion</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interests[]" value="fashion">
                                        <label class="form-check-label">Fashion</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interests[]" value="food">
                                        <label class="form-check-label">Food & Lifestyle</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Preferred Reward Type *</label>
                            <select name="reward_preference" class="form-select" required>
                                <option value="cash">Cash (₦1,000 per response)</option>
                                <option value="airtime">Airtime (₦1,000 per response)</option>
                                <option value="data">Data Bundle (per response)</option>
                            </select>
                            <small class="text-muted">You can change this preference later in your settings.</small>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Complete Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
