<?php
// ============================================================
// actions.php - Handle Form Submissions
// ============================================================

include_once 'connect.php';

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
    default:
        header("Location: " . SITE_URL);
}

/**
 * Handle User Registration
 */
function handleRegister() {
    global $conn;
    
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
    
    // Validation
    $errors = [];
    
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email) || !validateEmail($email)) $errors[] = "Valid email is required";
    if (empty($password) || strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    
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
    
    // Create user
    $password_hash = hashPassword($password);
    $username = strtolower($first_name . $last_name . rand(100, 999));
    
    $query = "INSERT INTO users 
              (username, email, password_hash, first_name, last_name, phone, role) 
              VALUES ('$username', '$email', '$password_hash', '$first_name', '$last_name', '$phone', '$role')";
    
    if ($conn->query($query)) {
        $user_id = $conn->insert_id;
        
        // Add to agents table if agent role
        if ($role === 'agent') {
            $conn->query("INSERT INTO agents (user_id, approval_status) VALUES ($user_id, 'pending')");
        }
        
        // Add messaging credits
        addMessagingCredits($user_id, 0, 0, 0);
        
        // Send welcome notification
        createNotification(
            $user_id,
            'welcome',
            'Welcome to Opinion Hub NG!',
            "Thank you for joining Opinion Hub NG, $first_name! Explore our platform and start creating polls or sharing your opinions.",
            'dashboards/' . $role . '-dashboard.php'
        );
        
        // Send welcome email
        sendTemplatedEmail(
            $email,
            $first_name . ' ' . $last_name,
            'Welcome to Opinion Hub NG!',
            "Welcome aboard, $first_name! We're excited to have you join our community. Start exploring and make your voice heard!",
            'Get Started',
            SITE_URL . 'signin.php'
        );
        
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
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        die("Invalid request");
    }
    
    $user = getCurrentUser();
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $poll_type = sanitize($_POST['poll_type'] ?? '');
    $start_date = sanitize($_POST['start_date'] ?? '');
    $end_date = sanitize($_POST['end_date'] ?? '');
    
    // Settings checkboxes
    $allow_multiple = isset($_POST['allow_multiple_options']) ? 1 : 0;
    $require_names = isset($_POST['require_participant_names']) ? 1 : 0;
    $allow_comments = isset($_POST['allow_comments']) ? 1 : 0;
    $one_vote_ip = isset($_POST['one_vote_per_ip']) ? 1 : 0;
    $results_public = isset($_POST['results_public_after_vote']) ? 1 : 0;
    
    // Pricing and commission fields
    $price_per_response = floatval($_POST['cost_per_response'] ?? 100);
    $target_responders = intval($_POST['target_responders'] ?? 100);
    $results_for_sale = isset($_POST['results_for_sale']) ? 1 : 0;
    $results_sale_price = floatval($_POST['results_sale_price'] ?? 5000);
    
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
              (created_by, title, slug, description, category_id, poll_type, image,
               start_date, end_date, allow_multiple_options, require_participant_names,
               allow_comments, one_vote_per_session, results_public_after_vote, status,
               price_per_response, target_responders,
               results_for_sale, results_sale_price) 
              VALUES ({$user['id']}, '$title', '$slug', '$description', $category_id, 
              '$poll_type', '$image', '$start_date', '$end_date', 
              $allow_multiple, $require_names, $allow_comments, $one_vote_ip, $results_public, 'draft',
              $price_per_response, $target_responders,
              $results_for_sale, $results_sale_price)";
    
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
    $category_id = (int)($_POST['category_id'] ?? 0);
    $poll_type = sanitize($_POST['poll_type'] ?? '');
    $start_date = sanitize($_POST['start_date'] ?? '');
    $end_date = sanitize($_POST['end_date'] ?? '');
    
    // Settings checkboxes
    $allow_multiple = isset($_POST['allow_multiple_options']) ? 1 : 0;
    $require_names = isset($_POST['require_participant_names']) ? 1 : 0;
    $allow_comments = isset($_POST['allow_comments']) ? 1 : 0;
    $one_vote_ip = isset($_POST['one_vote_per_ip']) ? 1 : 0;
    $results_public = isset($_POST['results_public_after_vote']) ? 1 : 0;
    
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
              description = '$description',
              category_id = $category_id,
              poll_type = '$poll_type',
              start_date = '$start_date',
              end_date = '$end_date',
              allow_multiple_options = $allow_multiple,
              require_participant_names = $require_names,
              allow_comments = $allow_comments,
              one_vote_per_session = $one_vote_ip,
              results_public_after_vote = $results_public
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
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        die("Invalid request");
    }
    
    $user = getCurrentUser();
    $poll_id = (int)($_POST['poll_id'] ?? 0);
    $question_text = sanitize($_POST['question_text'] ?? '');
    $question_type = sanitize($_POST['question_type'] ?? '');
    $is_required = (int)($_POST['is_required'] ?? 1);
    
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
    
    $query = "INSERT INTO poll_questions (poll_id, question_text, question_type, is_required, question_order) 
              VALUES ($poll_id, '$question_text', '$question_type', $is_required, $next_order)";
    
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
                                    AND transaction_type = 'poll_payment' 
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
                                 FROM users WHERE user_id = $agent_id");
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
    
    // Insert payout request into agent_earnings
    $stmt = $conn->prepare("INSERT INTO agent_earnings 
                           (agent_id, earning_type, amount, description, status, metadata, created_at) 
                           VALUES (?, 'payout_request', ?, ?, 'pending', ?, NOW())");
    
    $description = "Payout request - " . ucfirst(str_replace('_', ' ', $payout_method));
    if (!empty($notes)) {
        $description .= " - Notes: " . $notes;
    }
    
    $stmt->bind_param("idss", $agent_id, $amount, $description, $payout_details_json);
    
    if ($stmt->execute()) {
        // Update user's pending_earnings
        $conn->query("UPDATE users SET pending_earnings = pending_earnings + $amount WHERE user_id = $agent_id");
        
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
    
    // Get current payout details
    $payout_result = $conn->query("SELECT * FROM agent_earnings WHERE id = $payout_id AND earning_type = 'payout_request'");
    if (!$payout_result || $payout_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Payout request not found']);
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
        $conn->query("UPDATE users SET pending_earnings = pending_earnings - $amount WHERE user_id = $agent_id");
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
                     WHERE user_id = $agent_id");
                     
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
