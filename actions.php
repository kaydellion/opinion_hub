<?php
// ============================================================
// actions.php - Handle Form Submissions
// ============================================================

include_once 'connect.php';

// Ensure session is started for AJAX requests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Ensure required tables exist for registration process
 */
function ensureRegistrationTables() {
    global $conn;
    
    // Create notifications table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX(user_id),
        INDEX(is_read),
        INDEX(created_at)
    )");
    
    // Create messaging_credits table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS messaging_credits (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL UNIQUE,
        sms_credits INT DEFAULT 0,
        email_credits INT DEFAULT 0,
        whatsapp_credits INT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX(user_id)
    )");
}

/**
 * Ensure users table has all required columns
 * This function dynamically adds missing columns when called
 */
function ensureUsersTableColumns() {
    global $conn;
    
    // Define required columns with their definitions
    $required_columns = [
        'date_of_birth' => 'DATE DEFAULT NULL COMMENT \'Agent date of birth for age calculation\'',
        'gender' => 'ENUM(\'male\', \'female\') DEFAULT NULL COMMENT \'Agent gender\'',
        'state' => 'VARCHAR(50) DEFAULT NULL COMMENT \'Agent state of residence\'',
        'lga' => 'VARCHAR(100) DEFAULT NULL COMMENT \'Agent local government area\'',
        'occupation' => 'VARCHAR(100) DEFAULT NULL COMMENT \'Agent occupation/profession\'',
        'education_qualification' => 'VARCHAR(100) DEFAULT NULL COMMENT \'Agent highest education qualification\'',
        'employment_status' => 'ENUM(\'employed\', \'unemployed\') DEFAULT NULL COMMENT \'Agent employment status\'',
        'income_range' => 'VARCHAR(50) DEFAULT NULL COMMENT \'Agent monthly income range\'',
        'payment_preference' => 'ENUM(\'bank_transfer\', \'mobile_money\', \'airtime\', \'data\') DEFAULT NULL AFTER account_number',
        'mobile_money_provider' => 'VARCHAR(50) DEFAULT NULL COMMENT \'Mobile money provider for payouts\'',
        'mobile_money_number' => 'VARCHAR(15) DEFAULT NULL COMMENT \'Mobile money number for payouts\'',
        'total_earnings' => 'DECIMAL(10, 2) DEFAULT 0.00 AFTER payment_preference',
        'pending_earnings' => 'DECIMAL(10, 2) DEFAULT 0.00 AFTER total_earnings',
        'paid_earnings' => 'DECIMAL(10, 2) DEFAULT 0.00 AFTER pending_earnings',
        'referral_code' => 'VARCHAR(20) UNIQUE',
        'referred_by' => 'INT',
        'agent_approval_status' => 'ENUM(\'pending\', \'approved\', \'rejected\') DEFAULT NULL',
        'agent_approved_at' => 'TIMESTAMP NULL',
        'agent_approved_by' => 'INT',
        'sms_credits' => 'INT DEFAULT 0'
    ];
    
    // Get existing columns
    $existing_columns = [];
    $result = $conn->query("SHOW COLUMNS FROM users");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
        }
    }
    
    // Add missing columns
    foreach ($required_columns as $column => $definition) {
        if (!in_array($column, $existing_columns)) {
            $add_sql = "ALTER TABLE users ADD COLUMN $column $definition";
            if (!$conn->query($add_sql)) {
                error_log("Failed to add column $column to users table: " . $conn->error);
            }
        }
    }
}

/**
 * Ensure poll_questions table has all required columns
 * This function dynamically adds missing columns when called
 */
function ensurePollQuestionsTableColumns() {
    global $conn;
    
    // Define required columns with their definitions
    $required_columns = [
        'question_description' => 'TEXT NULL AFTER question_text',
        'question_image' => 'VARCHAR(255) NULL AFTER question_description'
    ];
    
    // Get existing columns
    $existing_columns = [];
    $result = $conn->query("SHOW COLUMNS FROM poll_questions");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
        }
    }
    
    // Add missing columns
    foreach ($required_columns as $column => $definition) {
        if (!in_array($column, $existing_columns)) {
            $add_sql = "ALTER TABLE poll_questions ADD COLUMN $column $definition";
            if (!$conn->query($add_sql)) {
                error_log("Failed to add column $column to poll_questions table: " . $conn->error);
            }
        }
    }
}

/**
 * Ensure polls table has all required columns
 * This function dynamically adds missing columns when called
 */
function ensurePollsTableColumns() {
    global $conn;
    
    // Define required columns with their definitions
    $required_columns = [
        'disclaimer' => 'TEXT NULL AFTER description',
        'agent_age_criteria' => 'TEXT DEFAULT \'[\"all\"]\' COMMENT \'JSON array of selected age groups\'',
        'agent_gender_criteria' => 'TEXT DEFAULT \'[\"both\"]\' COMMENT \'JSON array of selected genders\'',
        'agent_state_criteria' => 'VARCHAR(100) DEFAULT \'\' COMMENT \'Selected state for location filtering\'',
        'agent_lga_criteria' => 'VARCHAR(100) DEFAULT \'\' COMMENT \'Selected LGA for location filtering\'',
        'agent_location_all' => 'TINYINT(1) DEFAULT 1 COMMENT \'Whether to include all Nigeria locations\'',
        'agent_occupation_criteria' => 'TEXT DEFAULT \'[\"all\"]\' COMMENT \'JSON array of selected occupations\'',
        'agent_education_criteria' => 'TEXT DEFAULT \'[\"all\"]\' COMMENT \'JSON array of selected education levels\'',
        'agent_employment_criteria' => 'TEXT DEFAULT \'[\"both\"]\' COMMENT \'JSON array of selected employment status\'',
        'agent_income_criteria' => 'TEXT DEFAULT \'[\"all\"]\' COMMENT \'JSON array of selected income ranges\'',
            'poll_state' => "VARCHAR(100) DEFAULT ''",
            'agent_commission' => 'DECIMAL(10, 2) DEFAULT 1000',
        'results_for_sale' => 'BOOLEAN DEFAULT FALSE',
        'results_sale_price' => 'DECIMAL(10, 2) DEFAULT 0'
    ];
    
    // Get existing columns
    $existing_columns = [];
    $result = $conn->query("SHOW COLUMNS FROM polls");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
        }
    }
    
    // Add missing columns
    foreach ($required_columns as $column => $definition) {
        if (!in_array($column, $existing_columns)) {
            $add_sql = "ALTER TABLE polls ADD COLUMN $column $definition";
            if (!$conn->query($add_sql)) {
                error_log("Failed to add column $column to polls table: " . $conn->error);
            }
        }
    }
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'register':
        handleRegister();
        break;
    case 'login':
        handleLogin();
        break;
    case 'create_poll':
        handleCreatePoll();
        break;
    case 'update_poll':
        handleUpdatePoll();
        break;
    case 'add_question':
        handleAddQuestion();
        break;
    case 'delete_question':
        handleDeleteQuestion();
        break;
    case 'publish_poll':
        handlePublishPoll();
        break;
    case 'save_draft':
        handleSaveDraft();
        break;
    case 'pause_poll':
        handlePausePoll();
        break;
    case 'resume_poll':
        handleResumePoll();
        break;
    case 'add_comment':
        handleAddComment();
        break;
    case 'delete_comment':
        handleDeleteComment();
        break;
    case 'delete_poll':
        handleDeletePoll();
        break;
    case 'submit_response':
        handleSubmitResponse();
        break;
    case 'initialize_payment':
        handleInitializePayment();
        break;
    case 'track_ad_view':
        handleTrackAdView();
        break;
    case 'track_ad_click':
        handleTrackAdClick();
        break;
    case 'update_profile':
        handleUpdateProfile();
        break;
    case 'change_password':
        handleChangePassword();
        break;
    case 'requestPayout':
        handleRequestPayout();
        break;
    case 'updatePayoutStatus':
        handleUpdatePayoutStatus();
        break;
    case 'updateUserCredits':
        handleUpdateUserCredits();
        break;
    case 'addBulkCredits':
        handleAddBulkCredits();
        break;
    case 'follow_user':
        handleFollowUser();
        break;
    case 'unfollow_user':
        handleUnfollowUser();
        break;
    case 'follow_category':
        handleFollowCategory();
        break;
    case 'unfollow_category':
        handleUnfollowCategory();
        break;
    case 'bookmark_poll':
        handleBookmarkPoll();
        break;
    case 'unbookmark_poll':
        handleUnbookmarkPoll();
        break;
    case 'report_poll':
        handleReportPoll();
        break;
    case 'suspend_poll':
        handleSuspendPoll();
        break;
    case 'unsuspend_poll':
        handleUnsuspendPoll();
        break;
    case 'check_suspended_polls':
        handleCheckSuspendedPolls();
        break;
    case 'get_poll_status':
        handleGetPollStatus();
        break;
    case 'manual_suspend':
        handleManualSuspend();
        break;
    case 'manual_unsuspend':
        handleManualUnsuspend();
        break;
    case 'admin_delete_poll':
        handleAdminDeletePoll();
        break;
    case 'test':
        echo json_encode([
            'success' => true,
            'message' => 'Test successful',
            'timestamp' => time(),
            'user_logged_in' => isLoggedIn() ? 'yes' : 'no',
            'user_id' => isLoggedIn() ? getCurrentUser()['id'] : null
        ]);
        exit;
    default:
        header("Location: " . SITE_URL);
}

/**
 * Handle User Registration
 */
function handleRegister() {
    global $conn;
    
    // Ensure required columns and tables exist before proceeding
    ensureUsersTableColumns();
    ensureRegistrationTables();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        die("Invalid request");
    }
    
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $phone = sanitize($_POST['phone'] ?? '');

    // Agent-specific fields
    $agent_fields = [];
    if ($role === 'agent') {
        $agent_fields = [
            'date_of_birth' => $_POST['date_of_birth'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'state' => sanitize($_POST['state'] ?? ''),
            'lga' => sanitize($_POST['lga'] ?? ''),
            'occupation' => sanitize($_POST['occupation'] ?? ''),
            'education' => sanitize($_POST['education'] ?? ''),
            'employment_status' => sanitize($_POST['employment_status'] ?? ''),
            'income_range' => sanitize($_POST['income_range'] ?? '')
        ];
    }

    // Validation
    $errors = [];

    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email) || !validateEmail($email)) $errors[] = "Valid email is required";
    if (empty($password) || strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";

    // Agent field validation
    if ($role === 'agent') {
        if (empty($agent_fields['date_of_birth'])) $errors[] = "Date of birth is required for agents";
        if (empty($agent_fields['gender'])) $errors[] = "Gender is required for agents";
        if (empty($agent_fields['state'])) $errors[] = "State of residence is required for agents";
        if (empty($agent_fields['lga'])) $errors[] = "Local Government Area is required for agents";
        if (empty($agent_fields['occupation'])) $errors[] = "Occupation is required for agents";
        if (empty($agent_fields['education'])) $errors[] = "Education qualification is required for agents";
        if (empty($agent_fields['employment_status'])) $errors[] = "Employment status is required for agents";
        if (empty($agent_fields['income_range'])) $errors[] = "Income range is required for agents";
    }
    
    // Check if email exists
    $result = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($result && $result->num_rows > 0) {
        $errors[] = "Email already registered";
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: " . SITE_URL . "register.php");
        exit;
    }
    
    // Handle referral code from URL
    $referred_by = null;
    if (isset($_GET['ref']) || isset($_POST['ref'])) {
        $referral_code = sanitize($_GET['ref'] ?? $_POST['ref'] ?? '');
        
        if (!empty($referral_code)) {
            // Look up referrer by referral code
            $referrer_result = $conn->query("SELECT id FROM users WHERE referral_code = '$referral_code' LIMIT 1");
            if ($referrer_result && $referrer_result->num_rows > 0) {
                $referrer = $referrer_result->fetch_assoc();
                $referred_by = $referrer['id'];
            }
        }
    }
    
    // Create user
    $password_hash = hashPassword($password);
    $username = strtolower($first_name . $last_name . rand(100, 999));
    
    // Generate unique referral code for new user
    $referral_code = strtoupper(substr($first_name, 0, 2) . substr($last_name, 0, 2) . rand(1000, 9999));
    
    // Ensure referral code is unique
    $code_check = $conn->query("SELECT id FROM users WHERE referral_code = '$referral_code'");
    while ($code_check && $code_check->num_rows > 0) {
        $referral_code = strtoupper(substr($first_name, 0, 2) . substr($last_name, 0, 2) . rand(1000, 9999));
        $code_check = $conn->query("SELECT id FROM users WHERE referral_code = '$referral_code'");
    }
    
    // Build query based on role
    if ($role === 'agent') {
        $query = "INSERT INTO users
                  (username, email, password_hash, first_name, last_name, phone, role,
                   date_of_birth, gender, state, lga, occupation, education_qualification, employment_status, income_range,
                   referral_code, referred_by)
                  VALUES ('$username', '$email', '$password_hash', '$first_name', '$last_name', '$phone', '$role',
                   '{$agent_fields['date_of_birth']}', '{$agent_fields['gender']}', '{$agent_fields['state']}', '{$agent_fields['lga']}',
                   '{$agent_fields['occupation']}', '{$agent_fields['education']}', '{$agent_fields['employment_status']}', '{$agent_fields['income_range']}',
                   '$referral_code', " . ($referred_by ? $referred_by : 'NULL') . ")";
    } else {
        $query = "INSERT INTO users
                  (username, email, password_hash, first_name, last_name, phone, role, referral_code, referred_by)
                  VALUES ('$username', '$email', '$password_hash', '$first_name', '$last_name', '$phone', '$role', '$referral_code', " . ($referred_by ? $referred_by : 'NULL') . ")";
    }
    
    if ($conn->query($query)) {
        $user_id = $conn->insert_id;
        
        // Add to agents table if agent role
        if ($role === 'agent') {
            $agent_result = $conn->query("INSERT INTO agents (user_id, approval_status) VALUES ($user_id, 'pending')");
            if (!$agent_result) {
                error_log("Failed to add agent record: " . $conn->error);
            }
        }
        
        // Add messaging credits (with error handling)
        try {
            addMessagingCredits($user_id, 0, 0, 0);
        } catch (Exception $e) {
            error_log("Failed to add messaging credits: " . $e->getMessage());
        }
        
        // Award referral bonus to referrer if applicable
        if ($referred_by) {
            try {
                awardReferralBonus($referred_by, $user_id, 'referral_signup');
                // Log the referral
                error_log("Referral bonus awarded: Referrer ID=$referred_by, New User ID=$user_id, Code=$referral_code");
            } catch (Exception $e) {
                error_log("Failed to award referral bonus: " . $e->getMessage());
            }
        }
        
        // Send welcome notification (with error handling)
        try {
            createNotification(
                $user_id,
                'welcome',
                'Welcome to Opinion Hub NG!',
                "Thank you for joining Opinion Hub NG, $first_name! Explore our platform and start creating polls or sharing your opinions.",
                'dashboards/' . $role . '-dashboard.php'
            );
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
        }

        // Send registration confirmation email
        $welcome_subject = "Welcome to Opinion Hub NG - Account Created Successfully!";
        $welcome_message = "Dear $first_name $last_name,

Welcome to Opinion Hub NG! Your account has been successfully created.

Account Details:
- Email: $email
- Account Type: " . ucfirst($role) . "

You can now:
" . ($role === 'client' ? '- Create and manage polls
- Send invitations via SMS/Email/WhatsApp
- View detailed poll analytics
- Purchase messaging credits' : ($role === 'agent' ? '- Browse and accept poll tasks
- Earn commissions for completed responses
- Track your earnings and request payouts
- Share polls to earn more' : '- Participate in polls and surveys
- View poll results
- Become an agent to earn money')) . "

Get started: " . SITE_URL . "dashboard.php

If you have any questions, feel free to contact our support team.

Best regards,
Opinion Hub NG Team
hello@opinionhub.ng
+234 (0) 803 3782 777";

        // Send registration confirmation email (with error handling)
        try {
            sendTemplatedEmail($email, "$first_name $last_name", $welcome_subject, nl2br($welcome_message), "Go to Dashboard", SITE_URL . "dashboard.php");
        } catch (Exception $e) {
            error_log("Failed to send welcome email: " . $e->getMessage());
        }
        
        // Send welcome email (with error handling)
        try {
            sendTemplatedEmail(
                $email,
                $first_name . ' ' . $last_name,
                'Welcome to Opinion Hub NG!',
                "Welcome aboard, $first_name! We're excited to have you join our community. Start exploring and make your voice heard!",
                'Get Started',
                SITE_URL . 'signin.php'
            );
        } catch (Exception $e) {
            error_log("Failed to send second welcome email: " . $e->getMessage());
        }
        
        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: " . SITE_URL . "login.php");
    } else {
        $_SESSION['errors'] = ["Registration failed. Please try again."];
        header("Location: " . SITE_URL . "register.php");
    }
    exit;
}

/**
 * Handle Login
 */
function handleLogin() {
    global $conn;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        die("Invalid request");
    }
    
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $errors = [];
    
    if (empty($email) || empty($password)) {
        $errors[] = "Email and password are required";
    }
    
    $result = $conn->query("SELECT * FROM users WHERE email = '$email' AND status = 'active'");
    
    if (!$result || $result->num_rows === 0) {
        $errors[] = "Invalid email or password";
    } else {
        $user = $result->fetch_assoc();
        
        if (!verifyPassword($password, $user['password_hash'])) {
            $errors[] = "Invalid email or password";
        } else {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['role'] = $user['role']; // Add this for compatibility
            
            // Redirect based on role
            $_SESSION['success'] = "Welcome back!";
            
            if ($user['role'] === 'admin') {
                header("Location: " . SITE_URL . "admin/dashboard.php");
            } else {
                header("Location: " . SITE_URL . "dashboard.php");
            }
            exit;
        }
    }
    
    $_SESSION['errors'] = $errors;
    header("Location: " . SITE_URL . "login.php");
    exit;
}

/**
 * Handle Create Poll
 */
function handleCreatePoll() {
    global $conn;
    requireRole(['client', 'admin']);

    // Ensure required columns exist before proceeding
    ensurePollsTableColumns();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        die("Invalid request");
    }

    $user = getCurrentUser();
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $disclaimer = sanitize($_POST['disclaimer'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $poll_type = sanitize($_POST['poll_type'] ?? '');
    $poll_state = sanitize($_POST['poll_state'] ?? '');
    $poll_state = sanitize($_POST['poll_state'] ?? '');
    $start_date = sanitize($_POST['start_date'] ?? '');
    $end_date = sanitize($_POST['end_date'] ?? '');
    
    // Poll Settings - Radio buttons (values are 0 or 1 as strings)
    $allow_comments = intval($_POST['allow_comments'] ?? 0);
    $allow_multiple_votes = intval($_POST['allow_multiple_votes'] ?? 0);
    $one_vote_ip = intval($_POST['one_vote_per_ip'] ?? 0);
    $one_vote_account = intval($_POST['one_vote_per_account'] ?? 1); // Default to 1 (YES)

    // Results visibility logic
    $results_visibility = $_POST['results_visibility'] ?? 'private';
    $results_public_after_vote = ($results_visibility === 'after_vote') ? 1 : 0;
    $results_public_after_end = ($results_visibility === 'after_end') ? 1 : 0;
    $results_private = ($results_visibility === 'private') ? 1 : 0;

    // Agent payment settings
    $pay_agents = intval($_POST['pay_agents'] ?? 0);

    // Agent filtering criteria - only if paying agents
    $agent_age = $pay_agents ? ($_POST['agent_age'] ?? []) : [];
    $agent_gender = $pay_agents ? ($_POST['agent_gender'] ?? []) : [];
    $agent_state = $pay_agents ? sanitize($_POST['agent_state'] ?? '') : '';
    $agent_lga = $pay_agents ? sanitize($_POST['agent_lga'] ?? '') : '';
    $agent_location_all = $pay_agents ? intval($_POST['agent_location_all'] ?? 1) : 1;
    $agent_occupation = $pay_agents ? ($_POST['agent_occupation'] ?? []) : [];
    $agent_education = $pay_agents ? ($_POST['agent_education'] ?? []) : [];
    $agent_employment = $pay_agents ? ($_POST['agent_employment'] ?? []) : [];
    $agent_income = $pay_agents ? ($_POST['agent_income'] ?? []) : [];

    // Convert arrays to JSON for storage
    $agent_age_json = json_encode($agent_age);
    $agent_gender_json = json_encode($agent_gender);
    $agent_occupation_json = json_encode($agent_occupation);
    $agent_education_json = json_encode($agent_education);
    $agent_employment_json = json_encode($agent_employment);
    $agent_income_json = json_encode($agent_income);

    // Databank display settings - only if paying agents
    $display_in_databank = $pay_agents ? intval($_POST['display_in_databank'] ?? 0) : 0;
    $results_sale_price = $pay_agents ? floatval($_POST['results_sale_price'] ?? 5000) : 0;

    // Pricing fields - only set if paying agents
    $price_per_response = $pay_agents ? 1000 : 0; // Platform fee only if paying agents
    $target_responders = intval($_POST['target_responders'] ?? 100);
    
    $errors = [];
    if (empty($title)) $errors[] = "Poll title is required";
    if (empty($description)) $errors[] = "Poll description is required";
    if (empty($poll_type)) $errors[] = "Poll type is required";
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: " . SITE_URL . "client/create-poll.php");
        exit;
    }
    
    // Handle image upload
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = UPLOAD_DIR . 'polls/';
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = uniqid('poll_') . '.' . $file_extension;
        move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
    } else {
        // Use random default image if no image uploaded
        $default_images = ['default.png', 'default2.png', 'default3.png', 'default4.png', 'default5.png'];
        $image = $default_images[array_rand($default_images)];
    }
    
    // Generate unique slug from title
    $slug = generateUniquePollSlug($title);
    
    $query = "INSERT INTO polls
              (created_by, title, slug, description, disclaimer, category_id, poll_type, poll_state, image,
               start_date, end_date, allow_comments, allow_multiple_votes, one_vote_per_ip,
               one_vote_per_account, results_public_after_vote, results_public_after_end,
               results_private, status, price_per_response, target_responders,
               agent_age_criteria, agent_gender_criteria, agent_state_criteria, agent_lga_criteria,
               agent_location_all, agent_occupation_criteria, agent_education_criteria,
               agent_employment_criteria, agent_income_criteria)
              VALUES ({$user['id']}, '$title', '$slug', '$description', '$disclaimer', $category_id,
              '$poll_type', '$poll_state', '$image', '$start_date', '$end_date',
              $allow_comments, $allow_multiple_votes, $one_vote_ip, $one_vote_account,
              $results_public_after_vote, $results_public_after_end, $results_private, 'draft',
              $price_per_response, $target_responders,
              '$agent_age_json', '$agent_gender_json', '$agent_state', '$agent_lga',
              $agent_location_all, '$agent_occupation_json', '$agent_education_json',
              '$agent_employment_json', '$agent_income_json')";

    if ($conn->query($query)) {
        $poll_id = $conn->insert_id;
        
        // Send notification to poll creator
        createNotification(
            $user['id'],
            'poll_created',
            'Poll Created!',
            "Your poll '$title' has been created successfully. Add questions to complete your poll setup.",
            'client/add-questions.php?id=' . $poll_id
        );
        
        $_SESSION['success'] = "Poll created successfully! Now add questions.";
        // Notify followers of the creator about the new poll
        try {
            $followers_q = $conn->query("SELECT u.* FROM user_follows uf JOIN users u ON uf.follower_id = u.id WHERE uf.following_id = {$user['id']}");
            $poll_link = SITE_URL . 'view-poll/' . $slug;
            $subject = "New poll from {$user['first_name']} {$user['last_name']}";
            $plain_message = "{$user['first_name']} {$user['last_name']} just posted a new poll: $title\n\nView it here: $poll_link";

            if ($followers_q && $followers_q->num_rows > 0) {
                while ($f = $followers_q->fetch_assoc()) {
                    // Create in-app notification
                    try { createNotification($f['id'], 'new_poll', $subject, $plain_message, 'view-poll/' . $slug); } catch (Exception $e) { error_log('notify err: '.$e->getMessage()); }

                    // Send email (best-effort, don't block)
                    try {
                        sendTemplatedEmail(
                            $f['email'],
                            $f['first_name'] . ' ' . $f['last_name'],
                            $subject,
                            nl2br(htmlspecialchars($plain_message)),
                            'View Poll',
                            $poll_link
                        );
                    } catch (Exception $e) {
                        error_log('Failed to send follower email: ' . $e->getMessage());
                    }
                }
            }

        } catch (Exception $e) {
            error_log('Failed to notify followers: ' . $e->getMessage());
        }

        header("Location: " . SITE_URL . "client/add-questions.php?id=$poll_id");
    } else {
        error_log("Poll creation failed: " . $conn->error . " | Query: " . substr($query, 0, 200));
        $_SESSION['errors'] = ["Failed to create poll: " . $conn->error];
        header("Location: " . SITE_URL . "client/create-poll.php");
    }
    exit;
}

/**
 * Handle Update Poll
 */
function handleUpdatePoll() {
    global $conn;
    requireRole(['client', 'admin']);
    
    // Ensure required columns exist before proceeding
    ensurePollsTableColumns();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        die("Invalid request");
    }

    $user = getCurrentUser();
    $poll_id = (int)($_POST['poll_id'] ?? 0);

    // Verify ownership
    $existing = $conn->query("SELECT id FROM polls WHERE id = $poll_id AND created_by = {$user['id']}")->fetch_assoc();
    if (!$existing) {
        die('Poll not found or access denied');
    }

    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $disclaimer = sanitize($_POST['disclaimer'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $poll_type = sanitize($_POST['poll_type'] ?? '');
    $start_date = sanitize($_POST['start_date'] ?? '');
    $end_date = sanitize($_POST['end_date'] ?? '');

    // Poll Settings - Radio buttons (values are 0 or 1 as strings)
    $allow_comments = intval($_POST['allow_comments'] ?? 0);
    $allow_multiple_votes = intval($_POST['allow_multiple_votes'] ?? 0);
    $one_vote_ip = intval($_POST['one_vote_per_ip'] ?? 0);
    $one_vote_account = intval($_POST['one_vote_per_account'] ?? 1); // Default to 1 (YES)

    // Results visibility logic
    $results_visibility = $_POST['results_visibility'] ?? 'private';
    $results_public_after_vote = ($results_visibility === 'after_vote') ? 1 : 0;
    $results_public_after_end = ($results_visibility === 'after_end') ? 1 : 0;
    $results_private = ($results_visibility === 'private') ? 1 : 0;

    // Agent payment settings
    $pay_agents = intval($_POST['pay_agents'] ?? 0);

    // Agent filtering criteria - only if paying agents
    $agent_age = $pay_agents ? ($_POST['agent_age'] ?? []) : [];
    $agent_gender = $pay_agents ? ($_POST['agent_gender'] ?? []) : [];
    $agent_state = $pay_agents ? sanitize($_POST['agent_state'] ?? '') : '';
    $agent_lga = $pay_agents ? sanitize($_POST['agent_lga'] ?? '') : '';
    $agent_location_all = $pay_agents ? intval($_POST['agent_location_all'] ?? 1) : 1;
    $agent_occupation = $pay_agents ? ($_POST['agent_occupation'] ?? []) : [];
    $agent_education = $pay_agents ? ($_POST['agent_education'] ?? []) : [];
    $agent_employment = $pay_agents ? ($_POST['agent_employment'] ?? []) : [];
    $agent_income = $pay_agents ? ($_POST['agent_income'] ?? []) : [];

    // Convert arrays to JSON for storage
    $agent_age_json = json_encode($agent_age);
    $agent_gender_json = json_encode($agent_gender);
    $agent_occupation_json = json_encode($agent_occupation);
    $agent_education_json = json_encode($agent_education);
    $agent_employment_json = json_encode($agent_employment);
    $agent_income_json = json_encode($agent_income);

    // Databank display settings - only if paying agents
    $display_in_databank = $pay_agents ? intval($_POST['display_in_databank'] ?? 0) : 0;
    $results_sale_price = $pay_agents ? floatval($_POST['results_sale_price'] ?? 5000) : 0;

    // Validate databank requirement: minimum 500 responses
    if ($display_in_databank) {
        $current_responses = $conn->query("SELECT COUNT(*) as count FROM poll_responses WHERE poll_id = $poll_id")->fetch_assoc()['count'];
        if ($current_responses < 500) {
            $errors[] = "Polls must have at least 500 responses to be available in the databank. Current responses: $current_responses";
            $display_in_databank = 0; // Reset to prevent saving
        }
    }

    // Pricing fields - only set if paying agents
    $price_per_response = $pay_agents ? 500 : 0; // Platform fee only if paying agents
    $target_responders = intval($_POST['target_responders'] ?? 100);

    $errors = [];
    if (empty($title)) $errors[] = "Poll title is required";
    if (empty($description)) $errors[] = "Poll description is required";
    if (empty($poll_type)) $errors[] = "Poll type is required";
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: " . SITE_URL . "client/create-poll.php?id=$poll_id");
        exit;
    }
    
    // Handle image upload
    $image_update = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = UPLOAD_DIR . 'polls/';
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = uniqid('poll_') . '.' . $file_extension;
        move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
        $image_update = ", image = '$image'";
    }
    
    $query = "UPDATE polls SET
              title = '$title',
              poll_state = '$poll_state',
              description = '$description',
              disclaimer = '$disclaimer',
              category_id = $category_id,
              poll_type = '$poll_type',
              start_date = '$start_date',
              end_date = '$end_date',
              allow_comments = $allow_comments,
              allow_multiple_votes = $allow_multiple_votes,
              one_vote_per_ip = $one_vote_ip,
              one_vote_per_account = $one_vote_account,
              results_public_after_vote = $results_public_after_vote,
              results_public_after_end = $results_public_after_end,
              results_private = $results_private,
              results_for_sale = $display_in_databank,
              results_sale_price = $results_sale_price,
              price_per_response = $price_per_response,
              target_responders = $target_responders,
              agent_age_criteria = '$agent_age_json',
              agent_gender_criteria = '$agent_gender_json',
              agent_state_criteria = '$agent_state',
              agent_lga_criteria = '$agent_lga',
              agent_location_all = $agent_location_all,
              agent_occupation_criteria = '$agent_occupation_json',
              agent_education_criteria = '$agent_education_json',
              agent_employment_criteria = '$agent_employment_json',
              agent_income_criteria = '$agent_income_json'
              $image_update
              WHERE id = $poll_id";
    
    if ($conn->query($query)) {
        $_SESSION['success'] = "Poll updated successfully!";
        header("Location: " . SITE_URL . "client/add-questions.php?id=$poll_id");
    } else {
        $_SESSION['errors'] = ["Failed to update poll"];
        header("Location: " . SITE_URL . "client/create-poll.php?id=$poll_id");
    }
    exit;
}

/**
 * Handle Add Question
 */
function handleAddQuestion() {
    global $conn;
    requireRole(['client', 'admin']);
    
    // Ensure required columns exist before proceeding
    ensurePollQuestionsTableColumns();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        die("Invalid request");
    }
    
    $user = getCurrentUser();
    $poll_id = (int)($_POST['poll_id'] ?? 0);
    $question_text = sanitize($_POST['question_text'] ?? '');
    $question_description = sanitize($_POST['question_description'] ?? '');
    $question_image = sanitize($_POST['question_image'] ?? '');
    $question_type = sanitize($_POST['question_type'] ?? '');
    $is_required = (int)($_POST['is_required'] ?? 1);

    // Handle image upload if file was provided
    if (isset($_FILES['question_image_file']) && $_FILES['question_image_file']['error'] === UPLOAD_ERR_OK) {
        // Use absolute filesystem path for uploads directory
        $upload_dir = __DIR__ . '/uploads/questions/';

        $file_extension = strtolower(pathinfo($_FILES['question_image_file']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($file_extension, $allowed_extensions)) {
            $file_size = $_FILES['question_image_file']['size'];
            if ($file_size <= MAX_FILE_SIZE) {
                $new_filename = 'question_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['question_image_file']['tmp_name'], $upload_path)) {
                    // Store filename only in DB for consistency with other admin pages
                    $question_image = $new_filename;
                } else {
                    // Log detailed upload error for diagnostics
                    error_log("move_uploaded_file failed for question image. tmp_name=" . ($_FILES['question_image_file']['tmp_name'] ?? '') . ", upload_path=" . $upload_path);
                    $_SESSION['error'] = "Failed to upload image file";
                    header("Location: " . SITE_URL . "client/add-questions.php?id=$poll_id");
                    exit;
                }
            } else {
                $_SESSION['error'] = "Image file too large. Maximum size is " . (MAX_FILE_SIZE / 1024 / 1024) . "MB";
                header("Location: " . SITE_URL . "client/add-questions.php?id=$poll_id");
                exit;
            }
        } else {
            $_SESSION['error'] = "Invalid image file type. Allowed types: JPG, PNG, GIF, WebP";
            header("Location: " . SITE_URL . "client/add-questions.php?id=$poll_id");
            exit;
        }
    }
    
    // Verify ownership
    $poll = $conn->query("SELECT id FROM polls WHERE id = $poll_id AND created_by = {$user['id']}")->fetch_assoc();
    if (!$poll) {
        die('Poll not found or access denied');
    }
    
    if (empty($question_text)) {
        $_SESSION['error'] = "Question text is required";
        header("Location: " . SITE_URL . "client/add-questions.php?id=$poll_id");
        exit;
    }
    
    // Get next order
    $order_result = $conn->query("SELECT MAX(question_order) as max_order FROM poll_questions WHERE poll_id = $poll_id");
    $next_order = ($order_result->fetch_assoc()['max_order'] ?? 0) + 1;
    
    $query = "INSERT INTO poll_questions (poll_id, question_text, question_description, question_image, question_type, is_required, question_order)
              VALUES ($poll_id, '$question_text', '$question_description', '$question_image', '$question_type', $is_required, $next_order)";
    
    if ($conn->query($query)) {
        $question_id = $conn->insert_id;
        
        // Question types that need options
        $types_with_options = ['multiple_choice', 'multiple_answer', 'dichotomous', 'quiz', 'matrix'];
        
        if (in_array($question_type, $types_with_options) && isset($_POST['options'])) {
            $options = array_filter($_POST['options'], fn($opt) => !empty(trim($opt)));
            $opt_order = 1;
            
            foreach ($options as $option_text) {
                $option_text = sanitize($option_text);
                $is_correct = 0;
                
                // For quiz type, check if this is the correct answer
                if ($question_type === 'quiz' && isset($_POST['correct_answer']) && $_POST['correct_answer'] == $opt_order) {
                    $is_correct = 1;
                }
                
                $conn->query("INSERT INTO poll_question_options (question_id, option_text, option_order, is_correct_answer) 
                             VALUES ($question_id, '$option_text', $opt_order, $is_correct)");
                $opt_order++;
            }
        }
        
        $_SESSION['success'] = "Question added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add question";
    }
    
    header("Location: " . SITE_URL . "client/add-questions.php?id=$poll_id");
    exit;
}

/**
 * Handle Delete Question
 */
function handleDeleteQuestion() {
    global $conn;
    requireRole(['client', 'admin']);
    
    $user = getCurrentUser();
    $question_id = (int)($_GET['id'] ?? 0);
    $poll_id = (int)($_GET['poll_id'] ?? 0);
    
    // Verify ownership through poll
    $poll = $conn->query("SELECT p.id FROM polls p 
                          JOIN poll_questions q ON p.id = q.poll_id 
                          WHERE q.id = $question_id AND p.created_by = {$user['id']}")->fetch_assoc();
    if (!$poll) {
        die('Question not found or access denied');
    }
    
    // Delete options first
    $conn->query("DELETE FROM poll_question_options WHERE question_id = $question_id");
    
    // Delete question
    if ($conn->query("DELETE FROM poll_questions WHERE id = $question_id")) {
        $_SESSION['success'] = "Question deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete question";
    }
    
    header("Location: " . SITE_URL . "client/add-questions.php?id=$poll_id");
    exit;
}

/**
 * Handle Publish Poll
 */
function handlePublishPoll() {
    global $conn;
    requireRole(['client', 'admin']);
    
    $user = getCurrentUser();
    $poll_id = (int)($_GET['poll_id'] ?? 0);
    
    // Get poll details
    $poll = $conn->query("SELECT * FROM polls WHERE id = $poll_id AND created_by = {$user['id']}")->fetch_assoc();
    if (!$poll) {
        die('Poll not found or access denied');
    }
    
    // Check if has questions
    $question_count = $conn->query("SELECT COUNT(*) as count FROM poll_questions WHERE poll_id = $poll_id")->fetch_assoc()['count'];
    if ($question_count === 0) {
        $_SESSION['error'] = "Cannot publish poll without questions";
        header("Location: " . SITE_URL . "client/review-poll.php?id=$poll_id");
        exit;
    }
    
    // Calculate total cost (agent payment + admin commission)
    $price_per_response = floatval($poll['price_per_response'] ?? 100);
    $target_responders = intval($poll['target_responders'] ?? 100);
    $agent_total = $price_per_response * $target_responders;
    
    // Get admin commission percentage from settings (default 10%)
    $admin_commission_percent = floatval(getSetting('admin_commission_percent', '10'));
    $admin_commission = ($agent_total * $admin_commission_percent) / 100;
    $total_cost = $agent_total + $admin_commission;
    
    // Check if already paid
    $payment_check = $conn->query("SELECT id FROM transactions 
                                    WHERE user_id = {$user['id']} 
                                    AND poll_id = $poll_id 
                                    AND type = 'poll_payment' 
                                    AND status = 'completed'")->fetch_assoc();
    
    if (!$payment_check) {
        // Not paid - redirect to payment page
        $_SESSION['error'] = "Please complete payment before publishing poll";
        $_SESSION['payment_required'] = [
            'poll_id' => $poll_id,
            'agent_cost' => $agent_total,
            'admin_commission' => $admin_commission,
            'total_cost' => $total_cost,
            'description' => "Payment for poll: {$poll['title']}"
        ];
        header("Location: " . SITE_URL . "client/pay-for-poll.php?id=$poll_id");
        exit;
    }
    
    // Payment confirmed - publish poll
    if ($conn->query("UPDATE polls SET status = 'active', total_cost = $total_cost WHERE id = $poll_id")) {
        // Send notification to poll creator
        createNotification(
            $user['id'],
            'poll_published',
            'Poll Published Successfully!',
            'Your poll has been published and is now live. You can start sharing it with your audience.',
            'client/manage-polls.php'
        );
        
        // Send email confirmation
        $poll_details = $conn->query("SELECT title FROM polls WHERE id = $poll_id")->fetch_assoc();
        sendTemplatedEmail(
            $user['email'],
            $user['first_name'] . ' ' . $user['last_name'],
            'Your Poll is Live!',
            "Your poll '{$poll_details['title']}' has been successfully published and is now live. Start sharing it to collect responses!",
            'View Poll',
            SITE_URL . 'view-poll/' . generateSlug($poll_details['title'])
        );
        
        $_SESSION['success'] = "Poll published successfully!";
        header("Location: " . SITE_URL . "client/manage-polls.php");
    } else {
        $_SESSION['error'] = "Failed to publish poll";
        header("Location: " . SITE_URL . "client/review-poll.php?id=$poll_id");
    }
    exit;
}

/**
 * Handle Save Draft
 */
function handleSaveDraft() {
    global $conn;
    requireRole(['client', 'admin']);
    
    $user = getCurrentUser();
    $poll_id = (int)($_GET['poll_id'] ?? 0);
    
    // Verify ownership
    $poll = $conn->query("SELECT id FROM polls WHERE id = $poll_id AND created_by = {$user['id']}")->fetch_assoc();
    if (!$poll) {
        die('Poll not found or access denied');
    }
    
    $_SESSION['success'] = "Poll saved as draft!";
    header("Location: " . SITE_URL . "client/manage-polls.php");
    exit;
}

/**
 * Handle Submit Response
 */
function handleSubmitResponse() {
    global $conn;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        die("Invalid request");
    }
    
    $poll_id = (int)($_POST['poll_id'] ?? 0);
    $responses = $_POST['responses'] ?? [];
    $respondent_id = isLoggedIn() ? getCurrentUser()['id'] : null;
    
    if ($poll_id === 0 || empty($responses)) {
        $_SESSION['errors'] = ["Invalid poll or no responses"];
        header("Location: " . SITE_URL . "polls.php");
        exit;
    }
    
    $poll = getPoll($poll_id);
    
    if (!$poll) {
        $_SESSION['errors'] = ["Poll not found"];
        header("Location: " . SITE_URL . "polls.php");
        exit;
    }
    
    // Check if already voted
    $ip = $_SERVER['REMOTE_ADDR'];
    $session_id = session_id();
    
    $existing = $conn->query("SELECT id FROM poll_responses 
                             WHERE poll_id = $poll_id 
                             AND (respondent_ip = '$ip' OR session_id = '$session_id')");
    
    if ($existing && $existing->num_rows > 0 && $poll['one_vote_per_ip']) {
        // Get poll slug for redirect
        $poll_slug = $poll['slug'] ?? $poll_id;
        $_SESSION['errors'] = ["You have already voted on this poll"];
        header("Location: " . SITE_URL . "view-poll/" . $poll_slug);
        exit;
    }
    
    submitPollResponse($poll_id, $respondent_id, $responses);
    
    // Get poll slug for redirect
    $poll_slug = $poll['slug'] ?? $poll_id;
    $_SESSION['success'] = "Thank you for your response!";
    header("Location: " . SITE_URL . "view-poll/" . $poll_slug . "?success=true");
    exit;
}

/**
 * Handle Payment Initialization
 */
function handleInitializePayment() {
    requireRole(['client', 'user']);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        die("Invalid request");
    }
    
    $user = getCurrentUser();
    $amount = (float)($_POST['amount'] ?? 0);
    $type = sanitize($_POST['type'] ?? 'subscription');
    $plan_id = (int)($_POST['plan_id'] ?? 0);
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }
    
    $payment_response = initializePayment($user['id'], $amount, $type);
    
    if ($payment_response['status']) {
        echo json_encode([
            'success' => true,
            'authorization_url' => $payment_response['data']['authorization_url'],
            'access_code' => $payment_response['data']['access_code'],
            'reference' => $payment_response['data']['reference']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to initialize payment'
        ]);
    }
    exit;
}

/**
 * Handle Pause Poll
 */
function handlePausePoll() {
    global $conn;
    requireRole(['client', 'admin']);
    
    $user = getCurrentUser();
    $poll_id = (int)($_GET['id'] ?? 0);
    
    // Verify ownership
    $poll = $conn->query("SELECT id FROM polls WHERE id = $poll_id AND created_by = {$user['id']}")->fetch_assoc();
    if (!$poll) {
        die('Poll not found or access denied');
    }
    
    if ($conn->query("UPDATE polls SET status = 'paused' WHERE id = $poll_id")) {
        $_SESSION['success'] = "Poll paused successfully!";
    } else {
        $_SESSION['error'] = "Failed to pause poll";
    }
    
    header("Location: " . SITE_URL . "client/manage-polls.php");
    exit;
}

/**
 * Handle Resume Poll
 */
function handleResumePoll() {
    global $conn;
    requireRole(['client', 'admin']);
    
    $user = getCurrentUser();
    $poll_id = (int)($_GET['id'] ?? 0);
    
    // Verify ownership
    $poll = $conn->query("SELECT id FROM polls WHERE id = $poll_id AND created_by = {$user['id']}")->fetch_assoc();
    if (!$poll) {
        die('Poll not found or access denied');
    }
    
    if ($conn->query("UPDATE polls SET status = 'active' WHERE id = $poll_id")) {
        $_SESSION['success'] = "Poll resumed successfully!";
    } else {
        $_SESSION['error'] = "Failed to resume poll";
    }
    
    header("Location: " . SITE_URL . "client/manage-polls.php");
    exit;
}

/**
 * Handle Delete Poll
 */
function handleDeletePoll() {
    global $conn;
    requireRole(['client', 'admin']);
    
    $user = getCurrentUser();
    $poll_id = (int)($_GET['id'] ?? 0);
    
    // Verify ownership
    $poll = $conn->query("SELECT id FROM polls WHERE id = $poll_id AND created_by = {$user['id']}")->fetch_assoc();
    if (!$poll) {
        die('Poll not found or access denied');
    }
    
    // Delete poll (cascade will handle questions, options, and responses)
    if ($conn->query("DELETE FROM polls WHERE id = $poll_id")) {
        $_SESSION['success'] = "Poll deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete poll";
    }
    
    header("Location: " . SITE_URL . "client/manage-polls.php");
    exit;
}

/**
 * Track advertisement view
 */
function handleTrackAdView() {
    global $conn;
    
    $ad_id = (int)($_POST['ad_id'] ?? 0);
    
    if ($ad_id > 0) {
        $conn->query("UPDATE advertisements SET total_views = total_views + 1 WHERE id = $ad_id");
    }
    
    echo json_encode(['success' => true]);
    exit;
}

/**
 * Track advertisement click
 */
function handleTrackAdClick() {
    global $conn;
    
    $ad_id = (int)($_POST['ad_id'] ?? 0);
    
    if ($ad_id > 0) {
        $conn->query("UPDATE advertisements SET click_throughs = click_throughs + 1 WHERE id = $ad_id");
    }
    
    echo json_encode(['success' => true]);
    exit;
}

/**
 * Handle Agent Payout Request
 */
function handleRequestPayout() {
    global $conn;
    
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'agent') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $agent_id = $_SESSION['user_id'];
    $amount = (float)($_POST['amount'] ?? 0);
    $payout_method = sanitize($_POST['payout_method'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Get user stats to validate available balance
    $user_result = $conn->query("SELECT total_earnings, paid_earnings, pending_earnings 
                                 FROM users WHERE id = $agent_id");
    $user_stats = $user_result->fetch_assoc();
    
    $available_balance = ($user_stats['total_earnings'] ?? 0) - 
                        ($user_stats['paid_earnings'] ?? 0) - 
                        ($user_stats['pending_earnings'] ?? 0);
    
    // Validation
    if ($amount < 5000) {
        echo json_encode(['success' => false, 'message' => 'Minimum payout amount is ₦5,000']);
        exit;
    }
    
    if ($amount > $available_balance) {
        echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
        exit;
    }
    
    if (empty($payout_method)) {
        echo json_encode(['success' => false, 'message' => 'Please select a payout method']);
        exit;
    }
    
    // Build payout details based on method
    $payout_details = ['method' => $payout_method];
    
    switch ($payout_method) {
        case 'bank_transfer':
            $payout_details['bank_name'] = sanitize($_POST['bank_name'] ?? '');
            $payout_details['account_number'] = sanitize($_POST['account_number'] ?? '');
            $payout_details['account_name'] = sanitize($_POST['account_name'] ?? '');
            
            if (empty($payout_details['bank_name']) || empty($payout_details['account_number']) || empty($payout_details['account_name'])) {
                echo json_encode(['success' => false, 'message' => 'Please fill all bank details']);
                exit;
            }
            break;
            
        case 'mobile_money':
            $payout_details['mobile_provider'] = sanitize($_POST['mobile_provider'] ?? '');
            $payout_details['mobile_number'] = sanitize($_POST['mobile_number'] ?? '');
            
            if (empty($payout_details['mobile_provider']) || empty($payout_details['mobile_number'])) {
                echo json_encode(['success' => false, 'message' => 'Please fill all mobile money details']);
                exit;
            }
            break;
            
        case 'airtime':
            $payout_details['airtime_network'] = sanitize($_POST['airtime_network'] ?? '');
            $payout_details['airtime_number'] = sanitize($_POST['airtime_number'] ?? '');
            
            if (empty($payout_details['airtime_network']) || empty($payout_details['airtime_number'])) {
                echo json_encode(['success' => false, 'message' => 'Please fill all airtime details']);
                exit;
            }
            break;
            
        case 'data':
            $payout_details['data_network'] = sanitize($_POST['data_network'] ?? '');
            $payout_details['data_variation'] = sanitize($_POST['data_variation'] ?? '');
            $payout_details['data_number'] = sanitize($_POST['data_number'] ?? '');
            
            if (empty($payout_details['data_network']) || empty($payout_details['data_variation']) || empty($payout_details['data_number'])) {
                echo json_encode(['success' => false, 'message' => 'Please fill all data bundle details']);
                exit;
            }
            break;
    }
    
    $payout_details_json = json_encode($payout_details);
    
    // Check if metadata column exists
    $col_check = $conn->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'agent_earnings' AND COLUMN_NAME = 'metadata'");
    $has_metadata_column = $col_check && $col_check->num_rows > 0;
    
    $description = "Payout request - " . ucfirst(str_replace('_', ' ', $payout_method));
    if (!empty($notes)) {
        $description .= " - Notes: " . $notes;
    }
    // Add payment details to description if no metadata column
    if (!$has_metadata_column) {
        $description .= " | Details: " . $payout_details_json;
    }
    
    // Insert payout request into agent_earnings
    if ($has_metadata_column) {
        $stmt = $conn->prepare("INSERT INTO agent_earnings 
                               (agent_id, earning_type, amount, description, status, metadata, created_at) 
                               VALUES (?, 'payout_request', ?, ?, 'pending', ?, NOW())");
        $stmt->bind_param("idss", $agent_id, $amount, $description, $payout_details_json);
    } else {
        $stmt = $conn->prepare("INSERT INTO agent_earnings 
                               (agent_id, earning_type, amount, description, status, created_at) 
                               VALUES (?, 'payout_request', ?, ?, 'pending', NOW())");
        $stmt->bind_param("ids", $agent_id, $amount, $description);
    }
    
    if ($stmt->execute()) {
        // Update user's pending_earnings
        $conn->query("UPDATE users SET pending_earnings = pending_earnings + $amount WHERE id = $agent_id");
        
        echo json_encode(['success' => true, 'message' => 'Payout request submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit payout request']);
    }
    
    $stmt->close();
    exit;
}

/**
 * Process VTPass Payout (Airtime/Data)
 */
function processVTPassPayout($payout_details, $amount) {
    if (!isset($payout_details['method'])) {
        return ['success' => true, 'message' => 'Not a VTPass transaction'];
    }
    
    $method = $payout_details['method'];
    
    // Only process airtime and data via VTPass
    if ($method === 'airtime') {
        if (!VTPASS_ENABLED) {
            return ['success' => false, 'message' => 'VTPass is not enabled'];
        }
        
        $network = $payout_details['airtime_network'] ?? '';
        $phone = $payout_details['airtime_number'] ?? '';
        
        if (empty($network) || empty($phone)) {
            return ['success' => false, 'message' => 'Missing airtime details'];
        }
        
        // Format phone number
        $phone = formatNigerianPhone($phone);
        
        // Send airtime via VTPass
        $result = vtpass_send_airtime($phone, $network, $amount);
        
        error_log("Agent Payout - VTPass Airtime: " . json_encode($result));
        
        return $result;
        
    } elseif ($method === 'data') {
        if (!VTPASS_ENABLED) {
            return ['success' => false, 'message' => 'VTPass is not enabled'];
        }
        
        $variation_code = $payout_details['data_variation'] ?? '';
        $phone = $payout_details['data_number'] ?? '';
        
        if (empty($variation_code) || empty($phone)) {
            return ['success' => false, 'message' => 'Missing data bundle details'];
        }
        
        // Format phone number
        $phone = formatNigerianPhone($phone);
        
        // Send data via VTPass using the selected variation code
        $result = vtpass_send_data($phone, $variation_code);
        
        error_log("Agent Payout - VTPass Data: " . json_encode($result));
        
        return $result;
        
    } else {
        // Bank transfer or mobile money - manual processing required
        return ['success' => true, 'message' => 'Manual payout method - no automated processing'];
    }
}

/**
 * Handle Admin Update Payout Status
 */
function handleUpdatePayoutStatus() {
    global $conn;
    
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $payout_id = (int)($_POST['payout_id'] ?? 0);
    $new_status = sanitize($_POST['status'] ?? '');
    
    if ($payout_id === 0 || empty($new_status)) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    if (!in_array($new_status, ['pending', 'approved', 'paid', 'cancelled'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    // Get current payout details - check both with and without earning_type filter
    $payout_result = $conn->query("SELECT * FROM agent_earnings WHERE id = $payout_id");
    if (!$payout_result || $payout_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Payout request not found']);
        exit;
    }
    
    $payout = $payout_result->fetch_assoc();
    
    // Validate it's actually a payout request (not a regular earning)
    if (isset($payout['earning_type']) && $payout['earning_type'] !== 'payout_request') {
        echo json_encode(['success' => false, 'message' => 'This is not a payout request']);
        exit;
    }
    
    $payout = $payout_result->fetch_assoc();
    $old_status = $payout['status'];
    $amount = $payout['amount'];
    $agent_id = $payout['agent_id'];
    
    // Update payout status
    $conn->query("UPDATE agent_earnings SET status = '$new_status' WHERE id = $payout_id");
    
    // Update user earnings based on status change
    if ($old_status === 'pending' && $new_status === 'cancelled') {
        // Remove from pending earnings
        $conn->query("UPDATE users SET pending_earnings = pending_earnings - $amount WHERE id = $agent_id");
    } elseif ($old_status === 'pending' && $new_status === 'paid') {
        // Process VTPass transaction if applicable
        $payout_details = json_decode($payout['metadata'], true);
        $vtpass_result = processVTPassPayout($payout_details, $amount);
        
        if (!$vtpass_result['success']) {
            echo json_encode(['success' => false, 'message' => 'VTPass transaction failed: ' . $vtpass_result['message']]);
            exit;
        }
        
        // Move from pending to paid
        $conn->query("UPDATE users
                     SET pending_earnings = pending_earnings - $amount,
                         paid_earnings = paid_earnings + $amount
                     WHERE id = $agent_id");
                     
        // Log VTPass transaction if applicable
        if (isset($vtpass_result['request_id'])) {
            $conn->query("UPDATE agent_earnings 
                         SET metadata = JSON_SET(metadata, '$.vtpass_request_id', '{$vtpass_result['request_id']}')
                         WHERE id = $payout_id");
        }
    } elseif ($old_status === 'approved' && $new_status === 'paid') {
        // Process VTPass transaction if applicable
        $payout_details = json_decode($payout['metadata'], true);
        $vtpass_result = processVTPassPayout($payout_details, $amount);
        
        if (!$vtpass_result['success']) {
            echo json_encode(['success' => false, 'message' => 'VTPass transaction failed: ' . $vtpass_result['message']]);
            exit;
        }
        
        // Mark as paid (no pending adjustment needed if already approved)
        $conn->query("UPDATE users SET paid_earnings = paid_earnings + $amount WHERE user_id = $agent_id");
        
        // Log VTPass transaction if applicable
        if (isset($vtpass_result['request_id'])) {
            $conn->query("UPDATE agent_earnings 
                         SET metadata = JSON_SET(metadata, '$.vtpass_request_id', '{$vtpass_result['request_id']}')
                         WHERE id = $payout_id");
        }
    } elseif ($old_status === 'approved' && $new_status === 'cancelled') {
        // Cancelled from approved (already removed from pending)
        // No adjustment needed
    }
    
    $status_messages = [
        'approved' => 'Payout request approved',
        'paid' => 'Payout marked as paid',
        'cancelled' => 'Payout request cancelled'
    ];
    
    echo json_encode([
        'success' => true, 
        'message' => $status_messages[$new_status] ?? 'Payout status updated'
    ]);
    exit;
}

/**
 * Handle Admin Update User Credits
 */
function handleUpdateUserCredits() {
    global $conn;
    
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $user_id = (int)($_POST['user_id'] ?? 0);
    $sms_credits = (int)($_POST['sms_credits'] ?? 0);
    $whatsapp_credits = (int)($_POST['whatsapp_credits'] ?? 0);
    $email_credits = (int)($_POST['email_credits'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');
    
    if ($user_id === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    // Update user credits
    $stmt = $conn->prepare("UPDATE users 
                           SET sms_credits = ?, 
                               whatsapp_credits = ?, 
                               email_credits = ? 
                           WHERE user_id = ?");
    $stmt->bind_param("iiii", $sms_credits, $whatsapp_credits, $email_credits, $user_id);
    
    if ($stmt->execute()) {
        // Log the credit adjustment
        if (!empty($notes)) {
            $admin_id = $_SESSION['user_id'];
            $log_message = "Credits updated by admin. SMS: $sms_credits, WhatsApp: $whatsapp_credits, Email: $email_credits. Notes: $notes";
            $conn->query("INSERT INTO notifications 
                         (user_id, message, type, created_at) 
                         VALUES ($user_id, '$log_message', 'admin', NOW())");
        }
        
        echo json_encode(['success' => true, 'message' => 'Credits updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update credits']);
    }
    
    $stmt->close();
    exit;
}

/**
 * Handle Admin Add Bulk Credits
 */
function handleAddBulkCredits() {
    global $conn;
    
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $target_role = sanitize($_POST['target_role'] ?? 'all');
    $sms_credits = (int)($_POST['sms_credits'] ?? 0);
    $whatsapp_credits = (int)($_POST['whatsapp_credits'] ?? 0);
    $email_credits = (int)($_POST['email_credits'] ?? 0);
    
    if ($sms_credits === 0 && $whatsapp_credits === 0 && $email_credits === 0) {
        echo json_encode(['success' => false, 'message' => 'Please specify at least one credit type to add']);
        exit;
    }
    
    // Build WHERE clause based on target role
    $where_clause = "1=1";
    if ($target_role !== 'all') {
        $where_clause = "role = '$target_role'";
    }
    
    // Update credits for matching users
    $sql = "UPDATE users SET ";
    $updates = [];
    
    if ($sms_credits > 0) {
        $updates[] = "sms_credits = COALESCE(sms_credits, 0) + $sms_credits";
    }
    if ($whatsapp_credits > 0) {
        $updates[] = "whatsapp_credits = COALESCE(whatsapp_credits, 0) + $whatsapp_credits";
    }
    if ($email_credits > 0) {
        $updates[] = "email_credits = COALESCE(email_credits, 0) + $email_credits";
    }
    
    $sql .= implode(', ', $updates) . " WHERE $where_clause";
    
    if ($conn->query($sql)) {
        $affected = $conn->affected_rows;
        $role_text = $target_role === 'all' ? 'all users' : $target_role . 's';
        echo json_encode([
            'success' => true, 
            'message' => "Credits added to $affected $role_text successfully"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add credits']);
    }

    exit;
}

/**
 * Handle Poll Report
 */
function handleReportPoll() {
    global $conn;

    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to report polls']);
        exit;
    }

    $poll_id = (int)($_POST['poll_id'] ?? 0);
    $reason = sanitize($_POST['reason'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $user_id = getCurrentUser()['id'];

    if (!$poll_id || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Poll ID and reason are required']);
        exit;
    }

    // Check if poll exists
    $poll_check = $conn->query("SELECT id FROM polls WHERE id = $poll_id");
    if (!$poll_check || $poll_check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Poll not found']);
        exit;
    }

    // Check if user already reported this poll
    $existing_report = $conn->query("SELECT id FROM poll_reports WHERE poll_id = $poll_id AND reported_by = $user_id");
    if ($existing_report && $existing_report->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already reported this poll']);
        exit;
    }

    // Check if table exists first - if not, try to create it
    $table_check = $conn->query("SHOW TABLES LIKE 'poll_reports'");
    if (!$table_check || $table_check->num_rows === 0) {
        // Try to create the table automatically
        $create_sql = "CREATE TABLE poll_reports (
            id INT PRIMARY KEY AUTO_INCREMENT,
            poll_id INT NOT NULL,
            reported_by INT NOT NULL,
            reason VARCHAR(255) NOT NULL,
            description TEXT,
            status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reviewed_at TIMESTAMP NULL,
            reviewed_by INT NULL,
            FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
            FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX(poll_id),
            INDEX(reported_by),
            INDEX(status),
            INDEX(created_at)
        )";

        if (!$conn->query($create_sql)) {
            echo json_encode(['success' => false, 'message' => 'Reporting system not configured']);
            exit;
        }
    }

    // Insert report
    $stmt = $conn->prepare("INSERT INTO poll_reports (poll_id, reported_by, reason, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $poll_id, $user_id, $reason, $description);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit report']);
    }

    exit;
}

/**
 * Handle Poll Suspension
 */
function handleSuspendPoll() {
    global $conn;


    if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
        error_log("Access denied - not admin or not logged in");
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }

    $poll_id = (int)($_POST['poll_id'] ?? 0);
    $admin_id = getCurrentUser()['id'];

    error_log("Poll ID: $poll_id, Admin ID: $admin_id");

    if (!$poll_id) {
        error_log("No poll ID provided");
        echo json_encode(['success' => false, 'message' => 'Poll ID required']);
        exit;
    }

    // Check if poll exists
    $poll_check = $conn->query("SELECT id, status FROM polls WHERE id = $poll_id");
    if (!$poll_check || $poll_check->num_rows === 0) {
        error_log("Poll not found: $poll_id");
        echo json_encode(['success' => false, 'message' => 'Poll not found']);
        exit;
    }

    $current_status = $poll_check->fetch_assoc()['status'];

    // Update poll status to paused (used for suspension)
    $stmt = $conn->prepare("UPDATE polls SET status = 'paused', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $poll_id);

    if ($stmt->execute()) {
        // Update report status if it exists
        $conn->query("UPDATE poll_reports SET status = 'resolved', reviewed_by = $admin_id, reviewed_at = NOW() WHERE poll_id = $poll_id");
        echo json_encode(['success' => true, 'message' => 'Poll suspended successfully']);
    } else {
        error_log("Failed to execute suspend query: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to suspend poll: ' . $stmt->error]);
    }

    exit;
}

/**
 * Handle Poll Unsuspension
 */
function handleUnsuspendPoll() {
    global $conn;


    if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
        error_log("Access denied - not admin or not logged in");
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }

    $poll_id = (int)($_POST['poll_id'] ?? 0);

    error_log("Poll ID: $poll_id");

    if (!$poll_id) {
        error_log("No poll ID provided");
        echo json_encode(['success' => false, 'message' => 'Poll ID required']);
        exit;
    }

    // Update poll status to active (from paused/suspended)
    $stmt = $conn->prepare("UPDATE polls SET status = 'active', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $poll_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Poll unsuspended successfully']);
    } else {
        error_log("Failed to execute unsuspend query: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to unsuspend poll: ' . $stmt->error]);
    }

    exit;
}

/**
 * Handle Admin Poll Deletion
 */
function handleAdminDeletePoll() {
    global $conn;

    if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }

    $poll_id = (int)($_POST['poll_id'] ?? 0);
    $admin_id = getCurrentUser()['id'];

    if (!$poll_id) {
        echo json_encode(['success' => false, 'message' => 'Poll ID required']);
        exit;
    }

    // Update poll status to deleted (soft delete)
    $stmt = $conn->prepare("UPDATE polls SET status = 'deleted' WHERE id = ?");
    $stmt->bind_param("i", $poll_id);

    if ($stmt->execute()) {
        // Update report status if it exists
        $conn->query("UPDATE poll_reports SET status = 'resolved', reviewed_by = $admin_id, reviewed_at = NOW() WHERE poll_id = $poll_id");
        echo json_encode(['success' => true, 'message' => 'Poll deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete poll']);
    }

    exit;
}

function handleCheckSuspendedPolls() {
    global $conn;

    if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }

    $query = "SELECT p.id, p.title, p.status, p.created_at, p.updated_at,
                     CONCAT(u.first_name, ' ', u.last_name) as creator_name,
                     COUNT(pr.id) as report_count
              FROM polls p
              LEFT JOIN users u ON p.created_by = u.id
              LEFT JOIN poll_reports pr ON p.id = pr.poll_id AND pr.status IN ('pending', 'reviewed')
              WHERE p.status = 'paused'
              GROUP BY p.id
              ORDER BY p.updated_at DESC";

    $result = $conn->query($query);

    if ($result) {
        $suspended_polls = [];
        while ($poll = $result->fetch_assoc()) {
            $suspended_polls[] = $poll;
        }

        echo json_encode([
            'success' => true,
            'data' => $suspended_polls,
            'count' => count($suspended_polls)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch suspended polls: ' . $conn->error
        ]);
    }

    exit;
}

function handleGetPollStatus() {
    global $conn;

    if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }

    $poll_id = (int)($_POST['poll_id'] ?? 0);

    if (!$poll_id) {
        echo json_encode(['success' => false, 'message' => 'Poll ID required']);
        exit;
    }

    $query = "SELECT p.id, p.title, p.status, p.created_at, p.updated_at,
                     CONCAT(u.first_name, ' ', u.last_name) as creator_name
              FROM polls p
              LEFT JOIN users u ON p.created_by = u.id
              WHERE p.id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $poll_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $poll = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $poll]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Poll not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch poll status: ' . $conn->error]);
    }

    exit;
}

function handleManualSuspend() {
    global $conn;

    if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }

    $poll_id = (int)($_POST['poll_id'] ?? 0);
    $admin_id = getCurrentUser()['id'];

    if (!$poll_id) {
        echo json_encode(['success' => false, 'message' => 'Poll ID required']);
        exit;
    }

    // Update poll status to paused
    $stmt = $conn->prepare("UPDATE polls SET status = 'paused', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $poll_id);

    if ($stmt->execute()) {
        // Update report status if it exists
        $conn->query("UPDATE poll_reports SET status = 'resolved', reviewed_by = $admin_id, reviewed_at = NOW() WHERE poll_id = $poll_id AND status = 'pending'");
        echo json_encode(['success' => true, 'message' => 'Poll suspended successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to suspend poll: ' . $stmt->error]);
    }

    exit;
}

function handleManualUnsuspend() {
    global $conn;

    if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }

    $poll_id = (int)($_POST['poll_id'] ?? 0);

    if (!$poll_id) {
        echo json_encode(['success' => false, 'message' => 'Poll ID required']);
        exit;
    }

    // Update poll status to active
    $stmt = $conn->prepare("UPDATE polls SET status = 'active', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $poll_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Poll unsuspended successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to unsuspend poll: ' . $stmt->error]);
    }

    exit;
}

/**
 * Handle User Follow
 */
function handleFollowUser() {
    global $conn;


    if (!isLoggedIn()) {
        error_log("User not logged in");
        echo json_encode(['success' => false, 'message' => 'Please login to follow users']);
        exit;
    }

    $user_id = getCurrentUser()['id'];
    $following_id = (int)($_POST['following_id'] ?? 0);

    if ($user_id == $following_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot follow yourself']);
        exit;
    }

    // Check if table exists first - if not, try to create it
    $table_check = $conn->query("SHOW TABLES LIKE 'user_follows'");
    if (!$table_check || $table_check->num_rows === 0) {
        // Try to create the table automatically
        $create_sql = "CREATE TABLE user_follows (
            id INT PRIMARY KEY AUTO_INCREMENT,
            follower_id INT NOT NULL,
            following_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_follow (follower_id, following_id),
            INDEX(follower_id),
            INDEX(following_id),
            INDEX(created_at)
        )";

        if (!$conn->query($create_sql)) {
            echo json_encode(['success' => false, 'message' => 'Following system not configured. Table creation failed.']);
            exit;
        }
    }

    // Check if already following
    $check = $conn->query("SELECT id FROM user_follows WHERE follower_id = $user_id AND following_id = $following_id");
    if ($check && $check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Already following this user']);
        exit;
    }

    // Add follow relationship
    $stmt = $conn->prepare("INSERT INTO user_follows (follower_id, following_id) VALUES (?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("ii", $user_id, $following_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Successfully followed user', 'action' => 'followed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to follow user: ' . $stmt->error]);
    }

    exit;
}

/**
 * Handle User Unfollow
 */
function handleUnfollowUser() {
    global $conn;

    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to unfollow users']);
        exit;
    }

    $user_id = getCurrentUser()['id'];
    $following_id = (int)($_POST['following_id'] ?? 0);

    // Check if table exists first - if not, try to create it
    $table_check = $conn->query("SHOW TABLES LIKE 'user_follows'");
    if (!$table_check || $table_check->num_rows === 0) {
        // Try to create the table automatically
        $create_sql = "CREATE TABLE user_follows (
            id INT PRIMARY KEY AUTO_INCREMENT,
            follower_id INT NOT NULL,
            following_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_follow (follower_id, following_id),
            INDEX(follower_id),
            INDEX(following_id),
            INDEX(created_at)
        )";

        if (!$conn->query($create_sql)) {
            echo json_encode(['success' => false, 'message' => 'Following system not configured. Table creation failed.']);
            exit;
        }
    }

    // Remove follow relationship
    $stmt = $conn->prepare("DELETE FROM user_follows WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param("ii", $user_id, $following_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Successfully unfollowed user', 'action' => 'unfollowed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to unfollow user']);
    }

    exit;
}

/**
 * Handle Category Follow
 */
function handleFollowCategory() {
    global $conn;

    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to follow categories']);
        exit;
    }

    $user_id = getCurrentUser()['id'];
    $category_id = (int)($_POST['category_id'] ?? 0);

    // Check if table exists first - if not, try to create it
    $table_check = $conn->query("SHOW TABLES LIKE 'user_category_follows'");
    if (!$table_check || $table_check->num_rows === 0) {
        // Try to create the table automatically
        $create_sql = "CREATE TABLE user_category_follows (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            category_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            UNIQUE KEY unique_category_follow (user_id, category_id),
            INDEX(user_id),
            INDEX(category_id),
            INDEX(created_at)
        )";

        if (!$conn->query($create_sql)) {
            echo json_encode(['success' => false, 'message' => 'Following system not configured. Table creation failed.']);
            exit;
        }
    }

    // Check if already following
    $check = $conn->query("SELECT id FROM user_category_follows WHERE user_id = $user_id AND category_id = $category_id");
    if ($check && $check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Already following this category']);
        exit;
    }

    // Add follow relationship
    $stmt = $conn->prepare("INSERT INTO user_category_follows (user_id, category_id) VALUES (?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("ii", $user_id, $category_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Successfully followed category', 'action' => 'followed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to follow category: ' . $stmt->error]);
    }

    exit;
}

/**
 * Handle Category Unfollow
 */
function handleUnfollowCategory() {
    global $conn;

    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to unfollow categories']);
        exit;
    }

    $user_id = getCurrentUser()['id'];
    $category_id = (int)($_POST['category_id'] ?? 0);

    // Check if table exists first - if not, try to create it
    $table_check = $conn->query("SHOW TABLES LIKE 'user_category_follows'");
    if (!$table_check || $table_check->num_rows === 0) {
        // Try to create the table automatically
        $create_sql = "CREATE TABLE user_category_follows (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            category_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            UNIQUE KEY unique_category_follow (user_id, category_id),
            INDEX(user_id),
            INDEX(category_id),
            INDEX(created_at)
        )";

        if (!$conn->query($create_sql)) {
            echo json_encode(['success' => false, 'message' => 'Following system not configured. Table creation failed.']);
            exit;
        }
    }

    // Remove follow relationship
    $stmt = $conn->prepare("DELETE FROM user_category_follows WHERE user_id = ? AND category_id = ?");
    $stmt->bind_param("ii", $user_id, $category_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Successfully unfollowed category', 'action' => 'unfollowed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to unfollow category']);
    }

    exit;
}

/**
 * Handle Poll Bookmark
 */
function handleBookmarkPoll() {
    global $conn;

    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to bookmark polls']);
        exit;
    }

    $user_id = getCurrentUser()['id'];
    $poll_id = (int)($_POST['poll_id'] ?? 0);

    if (!$poll_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid poll ID']);
        exit;
    }

    // Check if table exists first - if not, try to create it
    $table_check = $conn->query("SHOW TABLES LIKE 'user_bookmarks'");
    if (!$table_check || $table_check->num_rows === 0) {
        // Try to create the table automatically
        $create_sql = "CREATE TABLE user_bookmarks (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            poll_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
            UNIQUE KEY unique_bookmark (user_id, poll_id),
            INDEX(user_id),
            INDEX(poll_id),
            INDEX(created_at)
        )";

        if (!$conn->query($create_sql)) {
            echo json_encode(['success' => false, 'message' => 'Bookmarks system not configured. Table creation failed.']);
            exit;
        }
    }

    // Check if already bookmarked
    $check = $conn->query("SELECT id FROM user_bookmarks WHERE user_id = $user_id AND poll_id = $poll_id");
    if ($check && $check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Poll already bookmarked']);
        exit;
    }

    // Add bookmark
    $stmt = $conn->prepare("INSERT INTO user_bookmarks (user_id, poll_id) VALUES (?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("ii", $user_id, $poll_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Poll bookmarked successfully', 'action' => 'bookmarked']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to bookmark poll: ' . $stmt->error]);
    }

    exit;
}

/**
 * Handle Poll Unbookmark
 */
function handleUnbookmarkPoll() {
    global $conn;

    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to unbookmark polls']);
        exit;
    }

    $user_id = getCurrentUser()['id'];
    $poll_id = (int)($_POST['poll_id'] ?? 0);

    if (!$poll_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid poll ID']);
        exit;
    }

    // Check if table exists first - if not, try to create it
    $table_check = $conn->query("SHOW TABLES LIKE 'user_bookmarks'");
    if (!$table_check || $table_check->num_rows === 0) {
        // Try to create the table automatically
        $create_sql = "CREATE TABLE user_bookmarks (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            poll_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
            UNIQUE KEY unique_bookmark (user_id, poll_id),
            INDEX(user_id),
            INDEX(poll_id),
            INDEX(created_at)
        )";

        if (!$conn->query($create_sql)) {
            echo json_encode(['success' => false, 'message' => 'Bookmarks system not configured. Table creation failed.']);
            exit;
        }
    }

    // Remove bookmark
    $stmt = $conn->prepare("DELETE FROM user_bookmarks WHERE user_id = ? AND poll_id = ?");
    $stmt->bind_param("ii", $user_id, $poll_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Poll unbookmarked successfully', 'action' => 'unbookmarked']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to unbookmark poll']);
    }

    exit;
}

/**
 * Handle Add Comment
 */
function handleAddComment() {
    global $conn;

    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to comment']);
        exit;
    }

    $user = getCurrentUser();
    $poll_id = (int)($_POST['poll_id'] ?? 0);
    $comment_text = trim(sanitize($_POST['comment_text'] ?? ''));

    if (empty($comment_text)) {
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        exit;
    }

    if (strlen($comment_text) > 1000) {
        echo json_encode(['success' => false, 'message' => 'Comment is too long (max 1000 characters)']);
        exit;
    }

    // Check if poll exists and allows comments
    $poll_check = $conn->query("SELECT id, allow_comments FROM polls WHERE id = $poll_id AND allow_comments = 1");
    if (!$poll_check || $poll_check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Comments are not allowed for this poll']);
        exit;
    }

    // Insert comment
    $stmt = $conn->prepare("INSERT INTO poll_comments (poll_id, user_id, comment_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $poll_id, $user['id'], $comment_text);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Comment added successfully']);
    } else {
        error_log("Comment insertion failed: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
    }

    exit;
}

/**
 * Handle Delete Comment
 */
function handleDeleteComment() {
    global $conn;

    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to delete comments']);
        exit;
    }

    $user = getCurrentUser();
    $comment_id = (int)($_POST['comment_id'] ?? 0);

    // Check if comment exists and belongs to user
    $comment_check = $conn->query("SELECT id FROM poll_comments WHERE id = $comment_id AND user_id = {$user['id']}");
    if (!$comment_check || $comment_check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Comment not found or access denied']);
        exit;
    }

    // Delete comment
    if ($conn->query("DELETE FROM poll_comments WHERE id = $comment_id")) {
        echo json_encode(['success' => true, 'message' => 'Comment deleted successfully']);
    } else {
        error_log("Comment deletion failed: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
    }

    exit;
}

/**
 * Handle Profile Update
 */
function handleUpdateProfile() {
    global $conn;

    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to update your profile";
        header("Location: " . SITE_URL . "login.php");
        exit;
    }

    $user = getCurrentUser();
    $user_id = $user['id'];

    // Get form data
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $date_of_birth = sanitize($_POST['date_of_birth'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $lga = sanitize($_POST['lga'] ?? '');

    // Agent-specific fields (only save if user is agent)
    $occupation = '';
    $education_qualification = '';
    $employment_status = '';
    $income_range = '';
    $payment_preference = '';
    $bank_name = '';
    $account_name = '';
    $account_number = '';
    $mobile_money_provider = '';
    $mobile_money_number = '';

    if ($user['role'] === 'agent') {
        $occupation = sanitize($_POST['occupation'] ?? '');
        $education_qualification = sanitize($_POST['education_qualification'] ?? '');
        $employment_status = sanitize($_POST['employment_status'] ?? '');
        $income_range = sanitize($_POST['income_range'] ?? '');
        
        // Payment information
        $payment_preference = sanitize($_POST['payment_preference'] ?? '');
        $bank_name = sanitize($_POST['bank_name'] ?? '');
        $account_name = sanitize($_POST['account_name'] ?? '');
        $account_number = sanitize($_POST['account_number'] ?? '');
        $mobile_money_provider = sanitize($_POST['mobile_money_provider'] ?? '');
        $mobile_money_number = sanitize($_POST['mobile_money_number'] ?? '');
    }

    // Validation
    $errors = [];
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";

    // Agent-specific validation
    if ($user['role'] === 'agent') {
        if (empty($date_of_birth)) $errors[] = "Date of birth is required";
        if (empty($gender)) $errors[] = "Gender is required";
        if (empty($state)) $errors[] = "State is required";
        if (empty($occupation)) $errors[] = "Occupation is required";
        if (empty($education_qualification)) $errors[] = "Education qualification is required";
        if (empty($employment_status)) $errors[] = "Employment status is required";
        if (empty($income_range)) $errors[] = "Income range is required";
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: " . SITE_URL . "profile.php");
        exit;
    }

    // Handle profile image upload
    $profile_image_update = "";
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = UPLOAD_DIR . 'profiles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($file_extension, $allowed_extensions)) {
            $file_size = $_FILES['profile_image']['size'];
            if ($file_size <= MAX_FILE_SIZE) {
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    $profile_image_update = ", profile_image = '$new_filename'";
                } else {
                    $_SESSION['error'] = "Failed to upload profile image";
                    header("Location: " . SITE_URL . "profile.php");
                    exit;
                }
            } else {
                $_SESSION['error'] = "Profile image too large. Maximum size is " . (MAX_FILE_SIZE / 1024 / 1024) . "MB";
                header("Location: " . SITE_URL . "profile.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Invalid profile image file type. Allowed types: JPG, PNG, GIF, WebP";
            header("Location: " . SITE_URL . "profile.php");
            exit;
        }
    }

    // Update user profile
    $query = "UPDATE users SET
              first_name = '$first_name',
              last_name = '$last_name',
              phone = '$phone',
              date_of_birth = " . ($date_of_birth ? "'$date_of_birth'" : "NULL") . ",
              gender = " . ($gender ? "'$gender'" : "NULL") . ",
              address = '$address',
              state = " . ($state ? "'$state'" : "NULL") . ",
              lga = " . ($lga ? "'$lga'" : "NULL") . ",
              occupation = " . ($occupation ? "'$occupation'" : "NULL") . ",
              education_qualification = " . ($education_qualification ? "'$education_qualification'" : "NULL") . ",
              employment_status = " . ($employment_status ? "'$employment_status'" : "NULL") . ",
              income_range = " . ($income_range ? "'$income_range'" : "NULL") . ",
              payment_preference = " . ($payment_preference ? "'$payment_preference'" : "NULL") . ",
              bank_name = " . ($bank_name ? "'$bank_name'" : "NULL") . ",
              account_name = " . ($account_name ? "'$account_name'" : "NULL") . ",
              account_number = " . ($account_number ? "'$account_number'" : "NULL") . "
              $profile_image_update
              WHERE id = $user_id";

    if ($conn->query($query)) {
        // Also update mobile money fields if columns exist
        $col_check = $conn->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
                                    WHERE TABLE_NAME = 'users' 
                                    AND COLUMN_NAME IN ('mobile_money_provider', 'mobile_money_number')");
        if ($col_check && $col_check->num_rows > 0) {
            $mobile_query = "UPDATE users SET
                           mobile_money_provider = " . ($mobile_money_provider ? "'$mobile_money_provider'" : "NULL") . ",
                           mobile_money_number = " . ($mobile_money_number ? "'$mobile_money_number'" : "NULL") . "
                           WHERE id = $user_id";
            $conn->query($mobile_query);
        }
        
        $_SESSION['success'] = "Profile updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update profile: " . $conn->error;
    }

    header("Location: " . SITE_URL . "profile.php");
    exit;
}

/**
 * Handle Change Password
 */
function handleChangePassword() {
    global $conn;

    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to change your password";
        header("Location: " . SITE_URL . "login.php");
        exit;
    }

    $user = getCurrentUser();
    $user_id = $user['id'];

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    $errors = [];
    if (empty($current_password)) $errors[] = "Current password is required";
    if (empty($new_password)) $errors[] = "New password is required";
    if (strlen($new_password) < 6) $errors[] = "New password must be at least 6 characters";
    if ($new_password !== $confirm_password) $errors[] = "New passwords do not match";

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $errors[] = "Current password is incorrect";
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: " . SITE_URL . "profile.php");
        exit;
    }

    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password
    $query = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";

    if ($conn->query($query)) {
        $_SESSION['success'] = "Password changed successfully!";
    } else {
        $_SESSION['error'] = "Failed to change password";
    }

    header("Location: " . SITE_URL . "profile.php");
    exit;
}
