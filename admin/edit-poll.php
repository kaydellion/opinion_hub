<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$user = getCurrentUser();

// Check if editing existing poll
$poll_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$poll = null;

if ($poll_id > 0) {
    $result = $conn->query("SELECT * FROM polls WHERE id = $poll_id");
    if ($result && $result->num_rows > 0) {
        $poll = $result->fetch_assoc();
        $page_title = "Edit Poll: " . htmlspecialchars($poll['title']);
    } else {
        $_SESSION['error_message'] = "Poll not found";
        header("Location: polls.php");
        exit;
    }
} else {
    $_SESSION['error_message'] = "No poll ID provided";
    header("Location: polls.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $disclaimer = sanitize($_POST['disclaimer'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $poll_type = sanitize($_POST['poll_type'] ?? '');
    $start_date = sanitize($_POST['start_date'] ?? '');
    $end_date = sanitize($_POST['end_date'] ?? '');
    $status = sanitize($_POST['status'] ?? 'draft');

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
    $price_per_response = $pay_agents ? 500 : 0; // Platform fee only if paying agents
    $target_responders = intval($_POST['target_responders'] ?? 100);

    $errors = [];
    if (empty($title)) $errors[] = "Poll title is required";
    if (empty($description)) $errors[] = "Poll description is required";
    if (empty($poll_type)) $errors[] = "Poll type is required";

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: edit-poll.php?id=$poll_id");
        exit;
    }

    // Handle image upload
    $image = $poll['image']; // Keep existing image by default
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/polls/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = uniqid('poll_') . '.' . $file_extension;
        move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
    }

    // Generate unique slug from title (only if title changed)
    $slug = $poll['slug'];
    if ($title !== $poll['title']) {
        $slug = generateUniquePollSlug($title, $poll_id);
    }

    // Update poll
    $query = "UPDATE polls SET
              title = '$title',
              slug = '$slug',
              description = '$description',
              disclaimer = '$disclaimer',
              category_id = $category_id,
              poll_type = '$poll_type',
              image = '$image',
              start_date = " . ($start_date ? "'$start_date'" : "NULL") . ",
              end_date = " . ($end_date ? "'$end_date'" : "NULL") . ",
              allow_comments = $allow_comments,
              allow_multiple_votes = $allow_multiple_votes,
              one_vote_per_ip = $one_vote_ip,
              one_vote_per_account = $one_vote_account,
              results_public_after_vote = $results_public_after_vote,
              results_public_after_end = $results_public_after_end,
              results_private = $results_private,
              status = '$status',
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
              agent_income_criteria = '$agent_income_json',
              updated_at = NOW()
              WHERE id = $poll_id";

    if ($conn->query($query)) {
        $_SESSION['success_message'] = "Poll updated successfully";
        header("Location: polls.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Failed to update poll: " . $conn->error;
    }
}

// Get categories and poll types with error checking
$categories_query = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$categories = $categories_query && $categories_query->num_rows > 0 ? $categories_query : null;

$poll_types_query = $conn->query("SELECT * FROM poll_types WHERE status = 'active' ORDER BY name");
$poll_types = $poll_types_query && $poll_types_query->num_rows > 0 ? $poll_types_query : null;

$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);

$page_title = "Edit Poll";
include_once '../header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-edit"></i> Edit Poll: <?php echo htmlspecialchars($poll['title']); ?></h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div>• <?php echo $error; ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data" id="editPollForm">
                        <input type="hidden" name="poll_id" value="<?php echo $poll['id']; ?>">

                        <!-- Step 1: Project Details -->
                        <div class="poll-step active" id="step1">
                            <h5 class="mb-4">Project Details</h5>

                            <div class="mb-3">
                                <label for="title" class="form-label">Poll Title *</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($poll['title']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Poll Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($poll['description']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="disclaimer" class="form-label">Disclaimer (Optional)</label>
                                <textarea class="form-control" id="disclaimer" name="disclaimer" rows="3" placeholder="Add any important disclaimers, terms, or conditions for participants..."><?php echo htmlspecialchars($poll['disclaimer'] ?? ''); ?></textarea>
                                <small class="text-muted">This will be displayed to participants before they start the poll</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category_id" class="form-label">Category *</label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php if ($categories): ?>
                                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo ($poll['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <option disabled>No categories available</option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="poll_type" class="form-label">Poll Type *</label>
                                    <select class="form-select" id="poll_type" name="poll_type" required>
                                        <option value="">Select Poll Type</option>
                                        <?php if ($poll_types): ?>
                                            <?php while ($poll_type = $poll_types->fetch_assoc()): ?>
                                                <option value="<?php echo htmlspecialchars($poll_type['name']); ?>" <?php echo ($poll['poll_type'] == $poll_type['name']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($poll_type['name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <option disabled>No poll types available</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date & Time</label>
                                    <input type="datetime-local" class="form-control" id="start_date" name="start_date" value="<?php echo $poll['start_date'] ? date('Y-m-d\TH:i', strtotime($poll['start_date'])) : ''; ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date & Time</label>
                                    <input type="datetime-local" class="form-control" id="end_date" name="end_date" value="<?php echo $poll['end_date'] ? date('Y-m-d\TH:i', strtotime($poll['end_date'])) : ''; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Poll Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?php echo ($poll['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                    <option value="active" <?php echo ($poll['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="paused" <?php echo ($poll['status'] === 'paused') ? 'selected' : ''; ?>>Paused</option>
                                    <option value="completed" <?php echo ($poll['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Poll Image (Optional)</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <?php if ($poll['image']): ?>
                                    <small class="text-muted">Current: <?php echo htmlspecialchars($poll['image']); ?></small>
                                    <?php if (file_exists("../uploads/polls/" . $poll['image'])): ?>
                                        <br><img src="<?php echo SITE_URL; ?>uploads/polls/<?php echo $poll['image']; ?>" alt="Current image" style="max-width: 200px; max-height: 200px;" class="mt-2">
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Poll Settings Sections -->
                            <h5 class="mt-4 mb-4">Poll Settings</h5>

                            <!-- Regulations Section -->
                            <div class="card border-primary mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Regulations</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="allow_comments" id="comments_yes" value="1"
                                                       <?php echo ($poll['allow_comments'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="comments_yes">
                                                    <strong>YES</strong> - Allow comments
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="allow_comments" id="comments_no" value="0"
                                                       <?php echo !($poll['allow_comments'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="comments_no">
                                                    <strong>NO</strong> - Do not allow comments
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Voting Security Section -->
                            <div class="card border-warning mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Voting Security</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="allow_multiple_votes" id="multiple_votes_yes" value="1"
                                                       <?php echo ($poll['allow_multiple_votes'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="multiple_votes_yes">
                                                    <strong>YES</strong> - Allow multiple votes per person
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="allow_multiple_votes" id="multiple_votes_no" value="0"
                                                       <?php echo !($poll['allow_multiple_votes'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="multiple_votes_no">
                                                    <strong>NO</strong> - Single vote per person
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="voting_restrictions" style="display: <?php echo ($poll['allow_multiple_votes'] ?? 0) ? 'none' : 'block' ?>;">
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="radio" name="one_vote_per_ip" id="ip_vote_yes" value="1"
                                                           <?php echo ($poll['one_vote_per_ip'] ?? 0) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="ip_vote_yes">
                                                        <strong>YES</strong> - One vote per IP address
                                                    </label>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="radio" name="one_vote_per_ip" id="ip_vote_no" value="0"
                                                           <?php echo !($poll['one_vote_per_ip'] ?? 0) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="ip_vote_no">
                                                        <strong>NO</strong> - Multiple votes per IP address
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="radio" name="one_vote_per_account" id="account_vote_yes" value="1"
                                                           <?php echo ($poll['one_vote_per_account'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="account_vote_yes">
                                                        <strong>YES</strong> - One vote per Opinion Hub NG Account
                                                    </label>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="radio" name="one_vote_per_account" id="account_vote_no" value="0"
                                                           <?php echo !($poll['one_vote_per_account'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="account_vote_no">
                                                        <strong>NO</strong> - Multiple votes per account
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Results Visibility Section -->
                            <div class="card border-info mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Results Visibility</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="results_visibility" id="public_after_vote" value="after_vote"
                                                       <?php echo ($poll['results_public_after_vote'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="public_after_vote">
                                                    <strong>Public after vote</strong> - Display results after user votes
                                                </label>
                                            </div>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="results_visibility" id="public_after_end" value="after_end"
                                                       <?php echo ($poll['results_public_after_end'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="public_after_end">
                                                    <strong>Public after end of voting</strong> - Display results in databank after completion date
                                                </label>
                                            </div>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="results_visibility" id="private_results" value="private"
                                                       <?php echo ($poll['results_private'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="private_results">
                                                    <strong>Private</strong> - Don't display results in databank
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pay Agents Question -->
                            <div class="card border-warning mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Agent Payment</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <p class="mb-3"><strong>Do you want to pay agents/responders for collecting responses?</strong></p>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="pay_agents" id="pay_agents_yes" value="1"
                                                       <?php echo ($poll['price_per_response'] > 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="pay_agents_yes">
                                                    <strong>YES</strong> - Pay agents ₦1,000 per response to help collect responses
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="pay_agents" id="pay_agents_no" value="0"
                                                       <?php echo ($poll['price_per_response'] == 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="pay_agents_no">
                                                    <strong>NO</strong> - I will collect responses myself or through other means
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Poll Pricing & Agent Commission -->
                            <div id="pricing_section" style="display: <?php echo ($poll['price_per_response'] > 0) ? 'block' : 'none' ?>;">
                            <h5 class="mt-4 mb-3">Pricing & Agent Commission</h5>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Platform fees and agent commissions for this poll.
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Platform Fee <small class="text-muted">(Fixed fee per response)</small></label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="text" class="form-control bg-light" value="500" readonly>
                                        </div>
                                        <small class="text-muted">Fixed: ₦500 per response</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Agent Commission <small class="text-muted">(Amount agents earn per response)</small></label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="text" class="form-control bg-light" value="1,000" readonly>
                                        </div>
                                        <small class="text-muted">Fixed: ₦1,000 per response</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Target Responses</label>
                                        <input type="number" class="form-control" name="target_responders" id="target_responders"
                                               value="<?php echo $poll['target_responders'] ?? 100; ?>" min="1">
                                        <small class="text-muted">Number of responses you need</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Estimated Total Cost</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="text" class="form-control bg-light" id="estimated_cost" readonly value="150,000.00">
                                        </div>
                                        <small class="text-muted">Platform Fee + Agent Commission × Target responses</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Agent Filtering Criteria (simplified for admin editing) -->
                            <div id="agent_filtering_section" class="mt-4" style="display: <?php echo ($poll['price_per_response'] > 0) ? 'block' : 'none' ?>;">
                                <h6 class="mb-3"><i class="fas fa-filter text-primary"></i> Agent Filtering Criteria</h6>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Agent filtering criteria editing is simplified. Use the original create-poll form for detailed agent criteria management.
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Location</label>
                                        <div class="border rounded p-3">
                                            <div class="mb-3">
                                                <label class="form-label small">State</label>
                                                <select class="form-select" name="agent_state" id="agent_state">
                                                    <option value="">Select State</option>
                                                    <option value="Abia" <?php echo ($poll['agent_state_criteria'] === 'Abia') ? 'selected' : ''; ?>>Abia</option>
                                                    <option value="Adamawa" <?php echo ($poll['agent_state_criteria'] === 'Adamawa') ? 'selected' : ''; ?>>Adamawa</option>
                                                    <option value="Akwa Ibom" <?php echo ($poll['agent_state_criteria'] === 'Akwa Ibom') ? 'selected' : ''; ?>>Akwa Ibom</option>
                                                    <option value="Anambra" <?php echo ($poll['agent_state_criteria'] === 'Anambra') ? 'selected' : ''; ?>>Anambra</option>
                                                    <option value="Bauchi" <?php echo ($poll['agent_state_criteria'] === 'Bauchi') ? 'selected' : ''; ?>>Bauchi</option>
                                                    <option value="Bayelsa" <?php echo ($poll['agent_state_criteria'] === 'Bayelsa') ? 'selected' : ''; ?>>Bayelsa</option>
                                                    <option value="Benue" <?php echo ($poll['agent_state_criteria'] === 'Benue') ? 'selected' : ''; ?>>Benue</option>
                                                    <option value="Borno" <?php echo ($poll['agent_state_criteria'] === 'Borno') ? 'selected' : ''; ?>>Borno</option>
                                                    <option value="Cross River" <?php echo ($poll['agent_state_criteria'] === 'Cross River') ? 'selected' : ''; ?>>Cross River</option>
                                                    <option value="Delta" <?php echo ($poll['agent_state_criteria'] === 'Delta') ? 'selected' : ''; ?>>Delta</option>
                                                    <option value="Ebonyi" <?php echo ($poll['agent_state_criteria'] === 'Ebonyi') ? 'selected' : ''; ?>>Ebonyi</option>
                                                    <option value="Edo" <?php echo ($poll['agent_state_criteria'] === 'Edo') ? 'selected' : ''; ?>>Edo</option>
                                                    <option value="Ekiti" <?php echo ($poll['agent_state_criteria'] === 'Ekiti') ? 'selected' : ''; ?>>Ekiti</option>
                                                    <option value="Enugu" <?php echo ($poll['agent_state_criteria'] === 'Enugu') ? 'selected' : ''; ?>>Enugu</option>
                                                    <option value="FCT" <?php echo ($poll['agent_state_criteria'] === 'FCT') ? 'selected' : ''; ?>>Federal Capital Territory</option>
                                                    <option value="Gombe" <?php echo ($poll['agent_state_criteria'] === 'Gombe') ? 'selected' : ''; ?>>Gombe</option>
                                                    <option value="Imo" <?php echo ($poll['agent_state_criteria'] === 'Imo') ? 'selected' : ''; ?>>Imo</option>
                                                    <option value="Jigawa" <?php echo ($poll['agent_state_criteria'] === 'Jigawa') ? 'selected' : ''; ?>>Jigawa</option>
                                                    <option value="Kaduna" <?php echo ($poll['agent_state_criteria'] === 'Kaduna') ? 'selected' : ''; ?>>Kaduna</option>
                                                    <option value="Kano" <?php echo ($poll['agent_state_criteria'] === 'Kano') ? 'selected' : ''; ?>>Kano</option>
                                                    <option value="Katsina" <?php echo ($poll['agent_state_criteria'] === 'Katsina') ? 'selected' : ''; ?>>Katsina</option>
                                                    <option value="Kebbi" <?php echo ($poll['agent_state_criteria'] === 'Kebbi') ? 'selected' : ''; ?>>Kebbi</option>
                                                    <option value="Kogi" <?php echo ($poll['agent_state_criteria'] === 'Kogi') ? 'selected' : ''; ?>>Kogi</option>
                                                    <option value="Kwara" <?php echo ($poll['agent_state_criteria'] === 'Kwara') ? 'selected' : ''; ?>>Kwara</option>
                                                    <option value="Lagos" <?php echo ($poll['agent_state_criteria'] === 'Lagos') ? 'selected' : ''; ?>>Lagos</option>
                                                    <option value="Nasarawa" <?php echo ($poll['agent_state_criteria'] === 'Nasarawa') ? 'selected' : ''; ?>>Nasarawa</option>
                                                    <option value="Niger" <?php echo ($poll['agent_state_criteria'] === 'Niger') ? 'selected' : ''; ?>>Niger</option>
                                                    <option value="Ogun" <?php echo ($poll['agent_state_criteria'] === 'Ogun') ? 'selected' : ''; ?>>Ogun</option>
                                                    <option value="Ondo" <?php echo ($poll['agent_state_criteria'] === 'Ondo') ? 'selected' : ''; ?>>Ondo</option>
                                                    <option value="Osun" <?php echo ($poll['agent_state_criteria'] === 'Osun') ? 'selected' : ''; ?>>Osun</option>
                                                    <option value="Oyo" <?php echo ($poll['agent_state_criteria'] === 'Oyo') ? 'selected' : ''; ?>>Oyo</option>
                                                    <option value="Plateau" <?php echo ($poll['agent_state_criteria'] === 'Plateau') ? 'selected' : ''; ?>>Plateau</option>
                                                    <option value="Rivers" <?php echo ($poll['agent_state_criteria'] === 'Rivers') ? 'selected' : ''; ?>>Rivers</option>
                                                    <option value="Sokoto" <?php echo ($poll['agent_state_criteria'] === 'Sokoto') ? 'selected' : ''; ?>>Sokoto</option>
                                                    <option value="Taraba" <?php echo ($poll['agent_state_criteria'] === 'Taraba') ? 'selected' : ''; ?>>Taraba</option>
                                                    <option value="Yobe" <?php echo ($poll['agent_state_criteria'] === 'Yobe') ? 'selected' : ''; ?>>Yobe</option>
                                                    <option value="Zamfara" <?php echo ($poll['agent_state_criteria'] === 'Zamfara') ? 'selected' : ''; ?>>Zamfara</option>
                                                </select>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="agent_location_all" value="1" id="location_all" <?php echo ($poll['agent_location_all'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label fw-bold" for="location_all">SELECT ALL (NIGERIA)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Update Poll
                                </button>
                                <a href="polls.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate estimated cost
function updateEstimatedCost() {
    const platformFee = 500;
    const agentCommission = 1000;
    const totalPerResponse = platformFee + agentCommission;
    const targetResponders = parseFloat(document.getElementById('target_responders').value) || 0;
    const total = totalPerResponse * targetResponders;
    document.getElementById('estimated_cost').value = total.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Initialize page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Setup input listener for target responders
    const targetRespondersInput = document.getElementById('target_responders');
    if (targetRespondersInput) {
        targetRespondersInput.addEventListener('input', updateEstimatedCost);
    }

    // Toggle pricing section based on agent payment selection
    document.querySelectorAll('input[name="pay_agents"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const pricingSection = document.getElementById('pricing_section');
            const agentFilteringSection = document.getElementById('agent_filtering_section');
            if (pricingSection) {
                pricingSection.style.display = this.value === '1' ? 'block' : 'none';
            }
            if (agentFilteringSection) {
                agentFilteringSection.style.display = this.value === '1' ? 'block' : 'none';
            }
        });
    });

    // Toggle voting restrictions based on multiple votes setting
    document.querySelectorAll('input[name="allow_multiple_votes"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const votingRestrictions = document.getElementById('voting_restrictions');
            if (votingRestrictions) {
                votingRestrictions.style.display = this.value === '1' ? 'none' : 'block';
            }
        });
    });

    // Initial calculation
    updateEstimatedCost();
});
</script>

<?php include_once '../footer.php'; ?>
