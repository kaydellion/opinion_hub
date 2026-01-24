<?php
include_once 'connect.php';
include_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "login.php");
    exit;
}

$page_title = "My Profile";
include_once 'header.php';

global $conn;
$user = getCurrentUser();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <h2 class="mb-4">My Profile</h2>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= SITE_URL ?>actions.php?action=update_profile"
                        enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name"
                                    value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name"
                                    value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>"
                                    disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone"
                                    value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth"
                                    value="<?= $user['date_of_birth'] ?? '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?= ($user['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male
                                    </option>
                                    <option value="Female" <?= ($user['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>
                                        Female</option>

                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address"
                                rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">State</label>
                                <select class="form-select" name="state" id="state">
                                    <option value="">Select State</option>
                                    <option value="Abia" <?= ($user['state'] ?? '') === 'Abia' ? 'selected' : '' ?>>Abia</option>
                                    <option value="Adamawa" <?= ($user['state'] ?? '') === 'Adamawa' ? 'selected' : '' ?>>Adamawa</option>
                                    <option value="Akwa Ibom" <?= ($user['state'] ?? '') === 'Akwa Ibom' ? 'selected' : '' ?>>Akwa Ibom</option>
                                    <option value="Anambra" <?= ($user['state'] ?? '') === 'Anambra' ? 'selected' : '' ?>>Anambra</option>
                                    <option value="Bauchi" <?= ($user['state'] ?? '') === 'Bauchi' ? 'selected' : '' ?>>Bauchi</option>
                                    <option value="Bayelsa" <?= ($user['state'] ?? '') === 'Bayelsa' ? 'selected' : '' ?>>Bayelsa</option>
                                    <option value="Benue" <?= ($user['state'] ?? '') === 'Benue' ? 'selected' : '' ?>>Benue</option>
                                    <option value="Borno" <?= ($user['state'] ?? '') === 'Borno' ? 'selected' : '' ?>>Borno</option>
                                    <option value="Cross River" <?= ($user['state'] ?? '') === 'Cross River' ? 'selected' : '' ?>>Cross River</option>
                                    <option value="Delta" <?= ($user['state'] ?? '') === 'Delta' ? 'selected' : '' ?>>Delta</option>
                                    <option value="Ebonyi" <?= ($user['state'] ?? '') === 'Ebonyi' ? 'selected' : '' ?>>Ebonyi</option>
                                    <option value="Edo" <?= ($user['state'] ?? '') === 'Edo' ? 'selected' : '' ?>>Edo</option>
                                    <option value="Ekiti" <?= ($user['state'] ?? '') === 'Ekiti' ? 'selected' : '' ?>>Ekiti</option>
                                    <option value="Enugu" <?= ($user['state'] ?? '') === 'Enugu' ? 'selected' : '' ?>>Enugu</option>
                                    <option value="FCT" <?= ($user['state'] ?? '') === 'FCT' ? 'selected' : '' ?>>Federal Capital Territory</option>
                                    <option value="Gombe" <?= ($user['state'] ?? '') === 'Gombe' ? 'selected' : '' ?>>Gombe</option>
                                    <option value="Imo" <?= ($user['state'] ?? '') === 'Imo' ? 'selected' : '' ?>>Imo</option>
                                    <option value="Jigawa" <?= ($user['state'] ?? '') === 'Jigawa' ? 'selected' : '' ?>>Jigawa</option>
                                    <option value="Kaduna" <?= ($user['state'] ?? '') === 'Kaduna' ? 'selected' : '' ?>>Kaduna</option>
                                    <option value="Kano" <?= ($user['state'] ?? '') === 'Kano' ? 'selected' : '' ?>>Kano</option>
                                    <option value="Katsina" <?= ($user['state'] ?? '') === 'Katsina' ? 'selected' : '' ?>>Katsina</option>
                                    <option value="Kebbi" <?= ($user['state'] ?? '') === 'Kebbi' ? 'selected' : '' ?>>Kebbi</option>
                                    <option value="Kogi" <?= ($user['state'] ?? '') === 'Kogi' ? 'selected' : '' ?>>Kogi</option>
                                    <option value="Kwara" <?= ($user['state'] ?? '') === 'Kwara' ? 'selected' : '' ?>>Kwara</option>
                                    <option value="Lagos" <?= ($user['state'] ?? '') === 'Lagos' ? 'selected' : '' ?>>Lagos</option>
                                    <option value="Nasarawa" <?= ($user['state'] ?? '') === 'Nasarawa' ? 'selected' : '' ?>>Nasarawa</option>
                                    <option value="Niger" <?= ($user['state'] ?? '') === 'Niger' ? 'selected' : '' ?>>Niger</option>
                                    <option value="Ogun" <?= ($user['state'] ?? '') === 'Ogun' ? 'selected' : '' ?>>Ogun</option>
                                    <option value="Ondo" <?= ($user['state'] ?? '') === 'Ondo' ? 'selected' : '' ?>>Ondo</option>
                                    <option value="Osun" <?= ($user['state'] ?? '') === 'Osun' ? 'selected' : '' ?>>Osun</option>
                                    <option value="Oyo" <?= ($user['state'] ?? '') === 'Oyo' ? 'selected' : '' ?>>Oyo</option>
                                    <option value="Plateau" <?= ($user['state'] ?? '') === 'Plateau' ? 'selected' : '' ?>>Plateau</option>
                                    <option value="Rivers" <?= ($user['state'] ?? '') === 'Rivers' ? 'selected' : '' ?>>Rivers</option>
                                    <option value="Sokoto" <?= ($user['state'] ?? '') === 'Sokoto' ? 'selected' : '' ?>>Sokoto</option>
                                    <option value="Taraba" <?= ($user['state'] ?? '') === 'Taraba' ? 'selected' : '' ?>>Taraba</option>
                                    <option value="Yobe" <?= ($user['state'] ?? '') === 'Yobe' ? 'selected' : '' ?>>Yobe</option>
                                    <option value="Zamfara" <?= ($user['state'] ?? '') === 'Zamfara' ? 'selected' : '' ?>>Zamfara</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">LGA</label>
                                <select class="form-select" name="lga" id="lga">
                                    <option value="">Select LGA</option>
                                    <!-- LGAs will be populated by JavaScript -->
                                </select>
                            </div>
                        </div>

                        <?php if (($user['role'] ?? '') === 'agent'): ?>
                        <!-- Agent-specific filtering fields -->
                        <hr class="my-4">
                        <h5 class="mb-3 text-primary"><i class="fas fa-user-tie"></i> Agent Profile Information</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Occupation</label>
                                <select class="form-select" name="occupation">
                                    <option value="">Select Occupation</option>
                                    <optgroup label="Healthcare and Medicine">
                                        <option value="doctor" <?= ($user['occupation'] ?? '') === 'doctor' ? 'selected' : '' ?>>Doctor</option>
                                        <option value="nurse" <?= ($user['occupation'] ?? '') === 'nurse' ? 'selected' : '' ?>>Nurse</option>
                                        <option value="pharmacist" <?= ($user['occupation'] ?? '') === 'pharmacist' ? 'selected' : '' ?>>Pharmacist</option>
                                        <option value="surgeon" <?= ($user['occupation'] ?? '') === 'surgeon' ? 'selected' : '' ?>>Surgeon</option>
                                        <option value="dentist" <?= ($user['occupation'] ?? '') === 'dentist' ? 'selected' : '' ?>>Dentist</option>
                                        <option value="medical_lab_tech" <?= ($user['occupation'] ?? '') === 'medical_lab_tech' ? 'selected' : '' ?>>Medical Laboratory Technician</option>
                                        <option value="physical_therapist" <?= ($user['occupation'] ?? '') === 'physical_therapist' ? 'selected' : '' ?>>Physical Therapist</option>
                                        <option value="radiologist" <?= ($user['occupation'] ?? '') === 'radiologist' ? 'selected' : '' ?>>Radiologist</option>
                                        <option value="optometrist" <?= ($user['occupation'] ?? '') === 'optometrist' ? 'selected' : '' ?>>Optometrist</option>
                                        <option value="psychiatrist" <?= ($user['occupation'] ?? '') === 'psychiatrist' ? 'selected' : '' ?>>Psychiatrist</option>
                                    </optgroup>
                                    <optgroup label="Education and Academia">
                                        <option value="teacher" <?= ($user['occupation'] ?? '') === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                        <option value="professor" <?= ($user['occupation'] ?? '') === 'professor' ? 'selected' : '' ?>>Professor</option>
                                        <option value="librarian" <?= ($user['occupation'] ?? '') === 'librarian' ? 'selected' : '' ?>>Librarian</option>
                                        <option value="school_principal" <?= ($user['occupation'] ?? '') === 'school_principal' ? 'selected' : '' ?>>School Principal</option>
                                        <option value="academic_advisor" <?= ($user['occupation'] ?? '') === 'academic_advisor' ? 'selected' : '' ?>>Academic Advisor</option>
                                        <option value="curriculum_developer" <?= ($user['occupation'] ?? '') === 'curriculum_developer' ? 'selected' : '' ?>>Curriculum Developer</option>
                                        <option value="research_scientist" <?= ($user['occupation'] ?? '') === 'research_scientist' ? 'selected' : '' ?>>Research Scientist</option>
                                        <option value="special_education_teacher" <?= ($user['occupation'] ?? '') === 'special_education_teacher' ? 'selected' : '' ?>>Special Education Teacher</option>
                                        <option value="educational_consultant" <?= ($user['occupation'] ?? '') === 'educational_consultant' ? 'selected' : '' ?>>Educational Consultant</option>
                                        <option value="school_counselor" <?= ($user['occupation'] ?? '') === 'school_counselor' ? 'selected' : '' ?>>School Counselor</option>
                                    </optgroup>
                                    <optgroup label="Engineering and Technology">
                                        <option value="software_engineer" <?= ($user['occupation'] ?? '') === 'software_engineer' ? 'selected' : '' ?>>Software Engineer</option>
                                        <option value="civil_engineer" <?= ($user['occupation'] ?? '') === 'civil_engineer' ? 'selected' : '' ?>>Civil Engineer</option>
                                        <option value="mechanical_engineer" <?= ($user['occupation'] ?? '') === 'mechanical_engineer' ? 'selected' : '' ?>>Mechanical Engineer</option>
                                        <option value="electrical_engineer" <?= ($user['occupation'] ?? '') === 'electrical_engineer' ? 'selected' : '' ?>>Electrical Engineer</option>
                                        <option value="computer_programmer" <?= ($user['occupation'] ?? '') === 'computer_programmer' ? 'selected' : '' ?>>Computer Programmer</option>
                                        <option value="network_administrator" <?= ($user['occupation'] ?? '') === 'network_administrator' ? 'selected' : '' ?>>Network Administrator</option>
                                        <option value="data_scientist" <?= ($user['occupation'] ?? '') === 'data_scientist' ? 'selected' : '' ?>>Data Scientist</option>
                                        <option value="it_support_specialist" <?= ($user['occupation'] ?? '') === 'it_support_specialist' ? 'selected' : '' ?>>IT Support Specialist</option>
                                        <option value="cybersecurity_analyst" <?= ($user['occupation'] ?? '') === 'cybersecurity_analyst' ? 'selected' : '' ?>>Cybersecurity Analyst</option>
                                        <option value="aerospace_engineer" <?= ($user['occupation'] ?? '') === 'aerospace_engineer' ? 'selected' : '' ?>>Aerospace Engineer</option>
                                    </optgroup>
                                    <optgroup label="Business and Finance">
                                        <option value="accountant" <?= ($user['occupation'] ?? '') === 'accountant' ? 'selected' : '' ?>>Accountant</option>
                                        <option value="financial_analyst" <?= ($user['occupation'] ?? '') === 'financial_analyst' ? 'selected' : '' ?>>Financial Analyst</option>
                                        <option value="marketing_manager" <?= ($user['occupation'] ?? '') === 'marketing_manager' ? 'selected' : '' ?>>Marketing Manager</option>
                                        <option value="hr_manager" <?= ($user['occupation'] ?? '') === 'hr_manager' ? 'selected' : '' ?>>Human Resources Manager</option>
                                        <option value="business_consultant" <?= ($user['occupation'] ?? '') === 'business_consultant' ? 'selected' : '' ?>>Business Consultant</option>
                                        <option value="sales_representative" <?= ($user['occupation'] ?? '') === 'sales_representative' ? 'selected' : '' ?>>Sales Representative</option>
                                        <option value="investment_banker" <?= ($user['occupation'] ?? '') === 'investment_banker' ? 'selected' : '' ?>>Investment Banker</option>
                                        <option value="real_estate_agent" <?= ($user['occupation'] ?? '') === 'real_estate_agent' ? 'selected' : '' ?>>Real Estate Agent</option>
                                        <option value="project_manager" <?= ($user['occupation'] ?? '') === 'project_manager' ? 'selected' : '' ?>>Project Manager</option>
                                        <option value="insurance_broker" <?= ($user['occupation'] ?? '') === 'insurance_broker' ? 'selected' : '' ?>>Insurance Broker</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Highest Educational Qualification</label>
                                <select class="form-select" name="education_qualification">
                                    <option value="">Select Education Level</option>
                                    <option value="ssc" <?= ($user['education_qualification'] ?? '') === 'ssc' ? 'selected' : '' ?>>Senior School Certificate</option>
                                    <option value="nd" <?= ($user['education_qualification'] ?? '') === 'nd' ? 'selected' : '' ?>>National Diploma</option>
                                    <option value="hnd" <?= ($user['education_qualification'] ?? '') === 'hnd' ? 'selected' : '' ?>>Higher National Diploma</option>
                                    <option value="bachelors" <?= ($user['education_qualification'] ?? '') === 'bachelors' ? 'selected' : '' ?>>Bachelor's Degree (Honours)</option>
                                    <option value="nce" <?= ($user['education_qualification'] ?? '') === 'nce' ? 'selected' : '' ?>>Nigeria Certificate in Education</option>
                                    <option value="bed" <?= ($user['education_qualification'] ?? '') === 'bed' ? 'selected' : '' ?>>Bachelor of Education</option>
                                    <option value="llb" <?= ($user['education_qualification'] ?? '') === 'llb' ? 'selected' : '' ?>>Bachelor of Law(s) (LLB)</option>
                                    <option value="mbbs" <?= ($user['education_qualification'] ?? '') === 'mbbs' ? 'selected' : '' ?>>Bachelor of Medicine and Bachelor of Surgery (MBBS)</option>
                                    <option value="bds" <?= ($user['education_qualification'] ?? '') === 'bds' ? 'selected' : '' ?>>Bachelor of Dental Surgery (BDS)</option>
                                    <option value="dvm" <?= ($user['education_qualification'] ?? '') === 'dvm' ? 'selected' : '' ?>>Doctor of Veterinary Medicine (DVM)</option>
                                    <option value="pgd" <?= ($user['education_qualification'] ?? '') === 'pgd' ? 'selected' : '' ?>>Postgraduate Diploma</option>
                                    <option value="masters" <?= ($user['education_qualification'] ?? '') === 'masters' ? 'selected' : '' ?>>Master's Degree</option>
                                    <option value="mphil" <?= ($user['education_qualification'] ?? '') === 'mphil' ? 'selected' : '' ?>>Master of Philosophy</option>
                                    <option value="phd" <?= ($user['education_qualification'] ?? '') === 'phd' ? 'selected' : '' ?>>Doctor of Philosophy</option>
                                    <option value="others" <?= ($user['education_qualification'] ?? '') === 'others' ? 'selected' : '' ?>>Others</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employment Status</label>
                                <select class="form-select" name="employment_status">
                                    <option value="">Select Employment Status</option>
                                    <option value="employed" <?= ($user['employment_status'] ?? '') === 'employed' ? 'selected' : '' ?>>Employed</option>
                                    <option value="unemployed" <?= ($user['employment_status'] ?? '') === 'unemployed' ? 'selected' : '' ?>>Unemployed</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Monthly Income Range</label>
                                <select class="form-select" name="income_range">
                                    <option value="">Select Income Range</option>
                                    <option value="1-30000" <?= ($user['income_range'] ?? '') === '1-30000' ? 'selected' : '' ?>>₦ 1 - ₦ 30,000</option>
                                    <option value="30000-80000" <?= ($user['income_range'] ?? '') === '30000-80000' ? 'selected' : '' ?>>₦ 30,000 - ₦ 80,000</option>
                                    <option value="80000-150000" <?= ($user['income_range'] ?? '') === '80000-150000' ? 'selected' : '' ?>>₦ 80,000 - ₦ 150,000</option>
                                    <option value="150000-250000" <?= ($user['income_range'] ?? '') === '150000-250000' ? 'selected' : '' ?>>₦ 150,000 - ₦ 250,000</option>
                                    <option value="250000-500000" <?= ($user['income_range'] ?? '') === '250000-500000' ? 'selected' : '' ?>>₦ 250,000 - ₦ 500,000</option>
                                    <option value="500000-1500000" <?= ($user['income_range'] ?? '') === '500000-1500000' ? 'selected' : '' ?>>₦ 500,000 - ₦ 1,500,000</option>
                                    <option value="1500000-5000000" <?= ($user['income_range'] ?? '') === '1500000-5000000' ? 'selected' : '' ?>>₦ 1,500,000 - ₦ 5,000,000</option>
                                    <option value="5000000+" <?= ($user['income_range'] ?? '') === '5000000+' ? 'selected' : '' ?>>₦ 5,000,000 – upwards</option>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Profile Image</label>
                            <input type="file" class="form-control" name="profile_image" accept="image/*">
                            <?php if ($user['profile_image']): ?>
                                <div class="mt-2">
                                    <img src="<?= SITE_URL ?>uploads/profiles/<?= $user['profile_image'] ?>" alt="Profile"
                                        class="img-thumbnail" style="max-width: 150px;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= SITE_URL ?>dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= SITE_URL ?>actions.php?action=change_password">
                        <div class="mb-3">
                            <label class="form-label">Current Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password"
                                    name="current_password" required>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye" id="current_password-icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password"
                                    required>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye" id="new_password-icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password" required>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="confirm_password-icon"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword(fieldId) {
        const passwordField = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + '-icon');

        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // LGA data for each state (simplified - you may want to load this from a database or API)
    const lgaData = {
        'Abia': ['Aba North', 'Aba South', 'Arochukwu', 'Bende', 'Ikwuano', 'Isiala Ngwa North', 'Isiala Ngwa South', 'Isuikwuato', 'Obi Ngwa', 'Ohafia', 'Osisioma Ngwa', 'Ugwunagbo', 'Ukwa East', 'Ukwa West', 'Umuahia North', 'Umuahia South', 'Umu Nneochi'],
        'Adamawa': ['Demsa', 'Fufore', 'Ganaye', 'Gireri', 'Gombi', 'Guyuk', 'Hong', 'Jada', 'Lamurde', 'Madagali', 'Maiha', 'Mayo-Belwa', 'Michika', 'Mubi North', 'Mubi South', 'Numan', 'Shelleng', 'Song', 'Toungo', 'Yola North', 'Yola South'],
        'Lagos': ['Agege', 'Ajeromi-Ifelodun', 'Alimosho', 'Amuwo-Odofin', 'Apapa', 'Badagry', 'Epe', 'Eti Osa', 'Ibeju-Lekki', 'Ifako-Ijaiye', 'Ikeja', 'Ikorodu', 'Kosofe', 'Lagos Island', 'Lagos Mainland', 'Mushin', 'Ojo', 'Oshodi-Isolo', 'Port Harcourt', 'Shomolu', 'Surulere']
        // Add more states and their LGAs as needed
    };

    // Populate LGA dropdown based on selected state
    document.addEventListener('DOMContentLoaded', function() {
        const stateSelect = document.getElementById('state');
        const lgaSelect = document.getElementById('lga');

        if (stateSelect && lgaSelect) {
            // Set initial LGA if user has one
            const currentLga = '<?= addslashes($user['lga'] ?? '') ?>';
            if (currentLga && stateSelect.value) {
                const selectedState = stateSelect.value;
                if (lgaData[selectedState]) {
                    lgaData[selectedState].forEach(function(lga) {
                        const option = document.createElement('option');
                        option.value = lga.toLowerCase().replace(/\s+/g, '_');
                        option.textContent = lga;
                        if (option.value === currentLga.toLowerCase().replace(/\s+/g, '_')) {
                            option.selected = true;
                        }
                        lgaSelect.appendChild(option);
                    });
                }
            }

            stateSelect.addEventListener('change', function() {
                const selectedState = this.value;
                lgaSelect.innerHTML = '<option value="">Select LGA</option>';

                if (selectedState && lgaData[selectedState]) {
                    lgaData[selectedState].forEach(function(lga) {
                        const option = document.createElement('option');
                        option.value = lga.toLowerCase().replace(/\s+/g, '_');
                        option.textContent = lga;
                        lgaSelect.appendChild(option);
                    });
                }
            });
        }
    });
</script>

<?php include_once 'footer.php'; ?>