<?php
// ============================================================
// 2. register.php
// Check authentication BEFORE including header
include_once 'connect.php';

if (isLoggedIn()) {
    header("Location: " . SITE_URL . "dashboard.php");
    exit;
}

$page_title = "Register";
include_once 'header.php';

$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">
                        <i class="fas fa-user-plus"></i> Create Your Opinion Hub Account
                    </h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div>• <?php echo $error; ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="actions.php?action=register">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">I am a *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user">Regular User / Respondent</option>
                                <option value="client">Client / Researcher</option>
                                <option value="agent">Agent / Data Collector</option>
                            </select>
                        </div>

                        <!-- Agent-specific fields -->
                        <div id="agent_fields" style="display: none;">
                            <hr class="my-4">
                            <h5 class="mb-3 text-primary"><i class="fas fa-user-tie"></i> Agent Profile Information</h5>

                            <!-- Date of Birth -->
                            <div class="mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth *</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                                <small class="text-muted">Required for age verification and matching polls</small>
                            </div>

                            <!-- Gender -->
                            <div class="mb-3">
                                <label for="gender" class="form-label">Gender *</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>

                            <!-- State -->
                            <div class="mb-3">
                                <label for="state" class="form-label">State of Residence *</label>
                                <select class="form-select" id="state" name="state">
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
                                    <option value="FCT">Federal Capital Territory</option>
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

                            <!-- LGA -->
                            <div class="mb-3">
                                <label for="lga" class="form-label">Local Government Area</label>
                                <select class="form-select" id="lga" name="lga" disabled>
                                    <option value="">Select State First</option>
                                </select>
                            </div>

                            <!-- Occupation -->
                            <div class="mb-3">
                                <label for="occupation" class="form-label">Occupation *</label>
                                <select class="form-select" id="occupation" name="occupation">
                                    <option value="">Select Occupation</option>
                                    <optgroup label="Healthcare and Medicine">
                                        <option value="doctor">Doctor</option>
                                        <option value="nurse">Nurse</option>
                                        <option value="pharmacist">Pharmacist</option>
                                        <option value="surgeon">Surgeon</option>
                                        <option value="dentist">Dentist</option>
                                        <option value="medical_lab_tech">Medical Laboratory Technician</option>
                                        <option value="physical_therapist">Physical Therapist</option>
                                        <option value="radiologist">Radiologist</option>
                                        <option value="optometrist">Optometrist</option>
                                        <option value="psychiatrist">Psychiatrist</option>
                                    </optgroup>
                                    <optgroup label="Education and Academia">
                                        <option value="teacher">Teacher</option>
                                        <option value="professor">Professor</option>
                                        <option value="librarian">Librarian</option>
                                        <option value="school_principal">School Principal</option>
                                        <option value="academic_advisor">Academic Advisor</option>
                                        <option value="curriculum_developer">Curriculum Developer</option>
                                        <option value="research_scientist">Research Scientist</option>
                                        <option value="special_education_teacher">Special Education Teacher</option>
                                        <option value="educational_consultant">Educational Consultant</option>
                                        <option value="school_counselor">School Counselor</option>
                                    </optgroup>
                                    <optgroup label="Engineering and Technology">
                                        <option value="software_engineer">Software Engineer</option>
                                        <option value="civil_engineer">Civil Engineer</option>
                                        <option value="mechanical_engineer">Mechanical Engineer</option>
                                        <option value="electrical_engineer">Electrical Engineer</option>
                                        <option value="computer_programmer">Computer Programmer</option>
                                        <option value="network_administrator">Network Administrator</option>
                                        <option value="data_scientist">Data Scientist</option>
                                        <option value="it_support_specialist">IT Support Specialist</option>
                                        <option value="cybersecurity_analyst">Cybersecurity Analyst</option>
                                        <option value="aerospace_engineer">Aerospace Engineer</option>
                                    </optgroup>
                                    <optgroup label="Business and Finance">
                                        <option value="accountant">Accountant</option>
                                        <option value="financial_analyst">Financial Analyst</option>
                                        <option value="marketing_manager">Marketing Manager</option>
                                        <option value="hr_manager">Human Resources Manager</option>
                                        <option value="business_consultant">Business Consultant</option>
                                        <option value="sales_representative">Sales Representative</option>
                                        <option value="investment_banker">Investment Banker</option>
                                        <option value="real_estate_agent">Real Estate Agent</option>
                                        <option value="project_manager">Project Manager</option>
                                        <option value="insurance_broker">Insurance Broker</option>
                                    </optgroup>
                                    <optgroup label="Arts and Entertainment">
                                        <option value="graphic_designer">Graphic Designer</option>
                                        <option value="actor">Actor</option>
                                        <option value="musician">Musician</option>
                                        <option value="photographer">Photographer</option>
                                        <option value="film_director">Film Director</option>
                                        <option value="author">Author</option>
                                        <option value="dancer">Dancer</option>
                                        <option value="art_curator">Art Curator</option>
                                        <option value="fashion_designer">Fashion Designer</option>
                                        <option value="animator">Animator</option>
                                    </optgroup>
                                    <optgroup label="Law and Public Safety">
                                        <option value="lawyer">Lawyer</option>
                                        <option value="police_officer">Police Officer</option>
                                        <option value="firefighter">Firefighter</option>
                                        <option value="paralegal">Paralegal</option>
                                        <option value="judge">Judge</option>
                                        <option value="probation_officer">Probation Officer</option>
                                        <option value="correctional_officer">Correctional Officer</option>
                                        <option value="detective">Detective</option>
                                        <option value="security_guard">Security Guard</option>
                                        <option value="legal_secretary">Legal Secretary</option>
                                    </optgroup>
                                    <optgroup label="Trades and Construction">
                                        <option value="carpenter">Carpenter</option>
                                        <option value="electrician">Electrician</option>
                                        <option value="plumber">Plumber</option>
                                        <option value="welder">Welder</option>
                                        <option value="mason">Mason</option>
                                        <option value="hvac_technician">HVAC Technician</option>
                                        <option value="painter">Painter</option>
                                        <option value="heavy_equipment_operator">Heavy Equipment Operator</option>
                                        <option value="roofer">Roofer</option>
                                        <option value="landscaper">Landscaper</option>
                                    </optgroup>
                                    <optgroup label="Science and Research">
                                        <option value="biologist">Biologist</option>
                                        <option value="chemist">Chemist</option>
                                        <option value="physicist">Physicist</option>
                                        <option value="environmental_scientist">Environmental Scientist</option>
                                        <option value="geologist">Geologist</option>
                                        <option value="astronomer">Astronomer</option>
                                        <option value="marine_biologist">Marine Biologist</option>
                                        <option value="geneticist">Geneticist</option>
                                        <option value="meteorologist">Meteorologist</option>
                                        <option value="ecologist">Ecologist</option>
                                    </optgroup>
                                    <optgroup label="Hospitality and Tourism">
                                        <option value="hotel_manager">Hotel Manager</option>
                                        <option value="travel_agent">Travel Agent</option>
                                        <option value="chef">Chef</option>
                                        <option value="restaurant_manager">Restaurant Manager</option>
                                        <option value="tour_guide">Tour Guide</option>
                                        <option value="event_planner">Event Planner</option>
                                        <option value="bartender">Bartender</option>
                                        <option value="concierge">Concierge</option>
                                        <option value="cruise_ship_staff">Cruise Ship Staff</option>
                                        <option value="flight_attendant">Flight Attendant</option>
                                    </optgroup>
                                    <optgroup label="Media and Communication">
                                        <option value="journalist">Journalist</option>
                                        <option value="public_relations_specialist">Public Relations Specialist</option>
                                        <option value="editor">Editor</option>
                                        <option value="television_producer">Television Producer</option>
                                        <option value="radio_host">Radio Host</option>
                                        <option value="social_media_manager">Social Media Manager</option>
                                        <option value="content_writer">Content Writer</option>
                                        <option value="videographer">Videographer</option>
                                        <option value="translator">Translator</option>
                                        <option value="copywriter">Copywriter</option>
                                    </optgroup>
                                    <optgroup label="Agriculture and Environment">
                                        <option value="farmer">Farmer</option>
                                        <option value="agricultural_scientist">Agricultural Scientist</option>
                                        <option value="horticulturist">Horticulturist</option>
                                        <option value="forester">Forester</option>
                                        <option value="fishery_manager">Fishery Manager</option>
                                        <option value="wildlife_biologist">Wildlife Biologist</option>
                                        <option value="soil_scientist">Soil Scientist</option>
                                        <option value="environmental_consultant">Environmental Consultant</option>
                                        <option value="landscape_architect">Landscape Architect</option>
                                        <option value="agronomist">Agronomist</option>
                                    </optgroup>
                                    <optgroup label="Transportation and Logistics">
                                        <option value="truck_driver">Truck Driver</option>
                                        <option value="airline_pilot">Airline Pilot</option>
                                        <option value="ship_captain">Ship Captain</option>
                                        <option value="train_conductor">Train Conductor</option>
                                        <option value="logistics_coordinator">Logistics Coordinator</option>
                                        <option value="warehouse_manager">Warehouse Manager</option>
                                        <option value="delivery_driver">Delivery Driver</option>
                                        <option value="air_traffic_controller">Air Traffic Controller</option>
                                        <option value="freight_forwarder">Freight Forwarder</option>
                                        <option value="customs_broker">Customs Broker</option>
                                    </optgroup>
                                    <optgroup label="Retail and Customer Service">
                                        <option value="retail_sales_associate">Retail Sales Associate</option>
                                        <option value="store_manager">Store Manager</option>
                                        <option value="customer_service_representative">Customer Service Representative</option>
                                        <option value="cashier">Cashier</option>
                                        <option value="visual_merchandiser">Visual Merchandiser</option>
                                        <option value="inventory_specialist">Inventory Specialist</option>
                                        <option value="call_center_agent">Call Center Agent</option>
                                        <option value="personal_shopper">Personal Shopper</option>
                                        <option value="e_commerce_manager">E-commerce Manager</option>
                                        <option value="retail_buyer">Retail Buyer</option>
                                    </optgroup>
                                    <optgroup label="Sports and Fitness">
                                        <option value="personal_trainer">Personal Trainer</option>
                                        <option value="coach">Coach</option>
                                        <option value="professional_athlete">Professional Athlete</option>
                                        <option value="sports_commentator">Sports Commentator</option>
                                        <option value="sports_agent">Sports Agent</option>
                                        <option value="fitness_instructor">Fitness Instructor</option>
                                        <option value="sports_medicine_physician">Sports Medicine Physician</option>
                                        <option value="athletic_trainer">Athletic Trainer</option>
                                        <option value="referee">Referee</option>
                                        <option value="sports_marketing_manager">Sports Marketing Manager</option>
                                    </optgroup>
                                    <optgroup label="Government and Public Administration">
                                        <option value="diplomat">Diplomat</option>
                                        <option value="urban_planner">Urban Planner</option>
                                        <option value="policy_analyst">Policy Analyst</option>
                                        <option value="public_relations_officer">Public Relations Officer</option>
                                        <option value="legislator">Legislator</option>
                                        <option value="city_manager">City Manager</option>
                                        <option value="social_worker">Social Worker</option>
                                        <option value="tax_examiner">Tax Examiner</option>
                                        <option value="customs_officer">Customs Officer</option>
                                        <option value="intelligence_analyst">Intelligence Analyst</option>
                                    </optgroup>
                                    <optgroup label="Manufacturing and Production">
                                        <option value="factory_worker">Factory Worker</option>
                                        <option value="quality_control_inspector">Quality Control Inspector</option>
                                        <option value="production_manager">Production Manager</option>
                                        <option value="assembly_line_worker">Assembly Line Worker</option>
                                        <option value="machinist">Machinist</option>
                                        <option value="maintenance_technician">Maintenance Technician</option>
                                        <option value="industrial_engineer">Industrial Engineer</option>
                                        <option value="production_planner">Production Planner</option>
                                        <option value="operations_manager">Operations Manager</option>
                                        <option value="manufacturing_engineer">Manufacturing Engineer</option>
                                    </optgroup>
                                    <optgroup label="Miscellaneous">
                                        <option value="entrepreneur">Entrepreneur</option>
                                        <option value="real_estate_developer">Real Estate Developer</option>
                                        <option value="nonprofit_manager">Nonprofit Manager</option>
                                        <option value="auctioneer">Auctioneer</option>
                                        <option value="archivist">Archivist</option>
                                        <option value="antiques_dealer">Antiques Dealer</option>
                                        <option value="dog_trainer">Dog Trainer</option>
                                        <option value="florist">Florist</option>
                                        <option value="funeral_director">Funeral Director</option>
                                        <option value="tattoo_artist">Tattoo Artist</option>
                                    </optgroup>
                                </select>
                            </div>

                            <!-- Education Qualification -->
                            <div class="mb-3">
                                <label for="education" class="form-label">Highest Educational Qualification *</label>
                                <select class="form-select" id="education" name="education">
                                    <option value="">Select Education Level</option>
                                    <option value="ssc">Senior School Certificate</option>
                                    <option value="nd">National Diploma</option>
                                    <option value="hnd">Higher National Diploma</option>
                                    <option value="bachelors">Bachelor's Degree (Honours)</option>
                                    <option value="nce">Nigeria Certificate in Education</option>
                                    <option value="bed">Bachelor of Education</option>
                                    <option value="llb">Bachelor of Law(s) (LLB)</option>
                                    <option value="mbbs">Bachelor of Medicine and Bachelor of Surgery (MBBS)</option>
                                    <option value="bds">Bachelor of Dental Surgery (BDS)</option>
                                    <option value="dvm">Doctor of Veterinary Medicine (DVM)</option>
                                    <option value="pgd">Postgraduate Diploma</option>
                                    <option value="masters">Master's Degree</option>
                                    <option value="mphil">Master of Philosophy</option>
                                    <option value="phd">Doctor of Philosophy</option>
                                    <option value="others">Others</option>
                                </select>
                            </div>

                            <!-- Employment Status -->
                            <div class="mb-3">
                                <label for="employment_status" class="form-label">Employment Status *</label>
                                <select class="form-select" id="employment_status" name="employment_status">
                                    <option value="">Select Employment Status</option>
                                    <option value="employed">Employed</option>
                                    <option value="unemployed">Unemployed</option>
                                </select>
                            </div>

                            <!-- Monthly Income Range -->
                            <div class="mb-3">
                                <label for="income_range" class="form-label">Monthly Income Range *</label>
                                <select class="form-select" id="income_range" name="income_range">
                                    <option value="">Select Income Range</option>
                                    <option value="1-30000">₦ 1 - ₦ 30,000</option>
                                    <option value="30000-80000">₦ 30,000 - ₦ 80,000</option>
                                    <option value="80000-150000">₦ 80,000 - ₦ 150,000</option>
                                    <option value="150000-250000">₦ 150,000 - ₦ 250,000</option>
                                    <option value="250000-500000">₦ 250,000 - ₦ 500,000</option>
                                    <option value="500000-1500000">₦ 500,000 - ₦ 1,500,000</option>
                                    <option value="1500000-5000000">₦ 1,500,000 - ₦ 5,000,000</option>
                                    <option value="5000000+">₦ 5,000,000 – upwards</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="password-icon"></i>
                                </button>
                            </div>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="confirm_password-icon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">Create Account</button>
                    </form>
                    
                    <hr>
                    
                    <p class="text-center">
                        Already have an account? 
                        <a href="<?php echo SITE_URL; ?>login.php">Login here</a>
                    </p>
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

// Agent fields toggle and LGA population
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const agentFields = document.getElementById('agent_fields');
    const stateSelect = document.getElementById('state');
    const lgaSelect = document.getElementById('lga');

    // Toggle agent fields based on role selection
    if (roleSelect && agentFields) {
        roleSelect.addEventListener('change', function() {
            if (this.value === 'agent') {
                agentFields.style.display = 'block';
                // Make agent fields required when agent is selected
                document.getElementById('date_of_birth').required = true;
                document.getElementById('gender').required = true;
                document.getElementById('state').required = true;
                // LGA will be enabled when state is selected
                document.getElementById('occupation').required = true;
                document.getElementById('education').required = true;
                document.getElementById('employment_status').required = true;
                document.getElementById('income_range').required = true;
            } else {
                agentFields.style.display = 'none';
                // Remove required attributes for non-agent roles
                document.getElementById('date_of_birth').required = false;
                document.getElementById('gender').required = false;
                document.getElementById('state').required = false;
                if (document.getElementById('lga')) {
                    document.getElementById('lga').required = false;
                    document.getElementById('lga').disabled = true;
                }
                document.getElementById('occupation').required = false;
                document.getElementById('education').required = false;
                document.getElementById('employment_status').required = false;
                document.getElementById('income_range').required = false;
            }
        });
    }

    // Load LGA data from JSON file
    let lgasData = {};
    
    fetch('<?php echo SITE_URL; ?>assets/data/nigeria-states-lgas.json')
        .then(response => response.json())
        .then(data => {
            lgasData = data;
            
            // Populate LGA dropdown based on selected state
            if (stateSelect && lgaSelect) {
                stateSelect.addEventListener('change', function() {
                    const selectedState = this.value;
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
            }
        })
        .catch(error => {
            console.error('Error loading LGA data:', error);
            // Fallback: enable manual input if JSON fails to load
            if (lgaSelect) {
                const fallbackLga = document.createElement('input');
                fallbackLga.type = 'text';
                fallbackLga.className = 'form-select';
                fallbackLga.id = 'lga';
                fallbackLga.name = 'lga';
                fallbackLga.placeholder = 'Local Government Area';
                lgaSelect.parentNode.replaceChild(fallbackLga, lgaSelect);
            }
        });
});
</script>

<?php include_once 'footer.php'; ?>