<?php
// Include required files for authentication
require_once '../connect.php';
require_once '../functions.php';

// Authentication and authorization checks BEFORE any output
if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "login.php");
    exit;
}

// Temporarily allow all logged-in users for testing
// requireRole(['client', 'admin']);

global $conn;
$user = getCurrentUser();

// Check subscription limits for new polls
$is_editing = isset($_GET['id']) && $_GET['id'] > 0;
// Temporarily disable subscription limit check for testing
// if (!$is_editing) {
//     $poll_limit = checkPollCreationLimit($user['id']);
//     if (!$poll_limit['allowed']) {
//         $_SESSION['error_message'] = $poll_limit['message'];
//         header("Location: " . SITE_URL . "client/manage-polls.php");
//         exit;
//     }
// }

$page_title = "Create Poll";
include_once '../header.php';

// Check if editing existing poll
$poll_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$poll = null;

if ($poll_id > 0) {
    // Load existing poll for editing
    $result = $conn->query("SELECT * FROM polls WHERE id = $poll_id AND created_by = {$user['id']}");
    if ($result && $result->num_rows > 0) {
        $poll = $result->fetch_assoc();
        $page_title = "Edit Poll";
    }
}

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-plus-circle"></i> Create New Poll/Survey</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div>• <?php echo $error; ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo SITE_URL; ?>actions.php?action=<?= $poll ? 'update_poll' : 'create_poll' ?>" enctype="multipart/form-data" id="createPollForm">
                        <?php if ($poll): ?>
                            <input type="hidden" name="poll_id" value="<?= $poll['id'] ?>">
                        <?php endif; ?>
                        
                        <!-- Step 1: Project Details -->
                        <div class="poll-step active" id="step1">
                            <h5 class="mb-4"><?= $poll ? 'Edit' : 'Step 1:' ?> Project Details</h5>
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">Poll Title *</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($poll['title'] ?? '') ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="poll_state" class="form-label">Target State (Optional)</label>
                                    <select class="form-select" id="poll_state" name="poll_state">
                                        <option value="">All Nigeria</option>
                                        <?php
                                        $nigerian_states = [
                                            'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno',
                                            'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT', 'Gombe',
                                            'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara',
                                            'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau',
                                            'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara'
                                        ];
                                        foreach ($nigerian_states as $state): ?>
                                            <option value="<?= $state ?>" <?= ($poll && ($poll['poll_state'] ?? '') === $state) ? 'selected' : '' ?>><?= htmlspecialchars($state) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Limit this poll to a specific state (optional)</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Poll Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($poll['description'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="disclaimer" class="form-label">Disclaimer (Optional)</label>
                                <textarea class="form-control" id="disclaimer" name="disclaimer" rows="3" placeholder="Add any important disclaimers, terms, or conditions for participants..."><?= htmlspecialchars($poll['disclaimer'] ?? '') ?></textarea>
                                <small class="text-muted">This will be displayed to participants before they start the poll</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category_id" class="form-label">Category *</label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php 
                                        $categories->data_seek(0); // Reset pointer
                                        while ($cat = $categories->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $cat['id']; ?>" <?= ($poll && $poll['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="poll_type" class="form-label">Poll Type *</label>
                                    <select class="form-select" id="poll_type" name="poll_type" required>
                                        <optgroup label="Political Polling">
                                            <option value="Approval Poll">Approval Poll</option>
                                            <option value="Favourability Poll">Favourability Poll</option>
                                            <option value="Head-to-Head Poll">Head-to-Head Poll</option>
                                            <option value="Issue Poll">Issue Poll</option>
                                            <option value="Benchmark Poll">Benchmark Poll</option>
                                            <option value="Tracking Poll">Tracking Poll</option>
                                            <option value="Exit Poll">Exit Poll</option>
                                            <option value="Push Poll">Push Poll</option>
                                            <option value="Deliberative Poll">Deliberative Poll</option>
                                            <option value="Flash Poll">Flash Poll</option>
                                            <option value="Opinion Poll">Opinion Poll</option>
                                            <option value="Straw Poll">Straw Poll</option>
                                            <option value="Referendum Poll">Referendum Poll</option>
                                            <option value="Omnibus Poll">Omnibus Poll</option>
                                            <option value="Sentiment Poll">Sentiment Poll</option>
                                            <option value="Ballot Test Poll">Ballot Test Poll</option>
                                            <option value="Engagement Poll">Engagement Poll</option>
                                            <option value="Satisfaction Poll">Satisfaction Poll</option>
                                            <option value="Electability Poll">Electability Poll</option>
                                            <option value="Priority Poll">Priority Poll</option>
                                            <option value="Awareness Poll">Awareness Poll</option>
                                        </optgroup>
                                        <optgroup label="Business & Market Research">
                                            <option value="Customer Satisfaction Poll">Customer Satisfaction Poll</option>
                                            <option value="Brand Awareness Poll">Brand Awareness Poll</option>
                                            <option value="Market Segmentation Poll">Market Segmentation Poll</option>
                                            <option value="Product Development Poll">Product Development Poll</option>
                                            <option value="Pricing Poll">Pricing Poll</option>
                                            <option value="Advertising Effectiveness Poll">Advertising Effectiveness Poll</option>
                                            <option value="Employee Satisfaction Poll">Employee Satisfaction Poll</option>
                                            <option value="Competitor Analysis Poll">Competitor Analysis Poll</option>
                                            <option value="Purchase Intent Poll">Purchase Intent Poll</option>
                                            <option value="Market Trend Poll">Market Trend Poll</option>
                                            <option value="Customer Experience Poll">Customer Experience Poll</option>
                                            <option value="Product Usage Poll">Product Usage Poll</option>
                                            <option value="Demand Forecasting Poll">Demand Forecasting Poll</option>
                                            <option value="Concept Testing Poll">Concept Testing Poll</option>
                                            <option value="Brand Loyalty Poll">Brand Loyalty Poll</option>
                                            <option value="Economic Outlook Poll">Economic Outlook Poll</option>
                                            <option value="Crisis Management Poll">Crisis Management Poll</option>
                                        </optgroup>
                                        <optgroup label="Social Research">
                                            <option value="Community Feedback Poll">Community Feedback Poll</option>
                                            <option value="Cross-Sectional Poll">Cross-Sectional Poll</option>
                                            <option value="Longitudinal Poll">Longitudinal Poll</option>
                                            <option value="Attitudinal Poll">Attitudinal Poll</option>
                                            <option value="Behavioural Poll">Behavioural Poll</option>
                                            <option value="Demographic Poll">Demographic Poll</option>
                                            <option value="Social Network Poll">Social Network Poll</option>
                                            <option value="Experimental Poll">Experimental Poll</option>
                                            <option value="Qualitative Poll">Qualitative Poll</option>
                                            <option value="Cultural Poll">Cultural Poll</option>
                                            <option value="Social Mobility Poll">Social Mobility Poll</option>
                                            <option value="Policy Impact Poll">Policy Impact Poll</option>
                                            <option value="Social Norms Poll">Social Norms Poll</option>
                                            <option value="Life Satisfaction Poll">Life Satisfaction and Well-being Poll</option>
                                        </optgroup>
                                        <optgroup label="Environment">
                                            <option value="Climate Change Poll">Climate Change Poll</option>
                                            <option value="Environmental Awareness Poll">Environmental Awareness Poll</option>
                                            <option value="Sustainability Poll">Sustainability Poll</option>
                                            <option value="Conservation Poll">Conservation Poll</option>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date & Time</label>
                                    <input type="datetime-local" class="form-control" id="start_date" name="start_date" value="<?= $poll && $poll['start_date'] ? date('Y-m-d\TH:i', strtotime($poll['start_date'])) : '' ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date & Time</label>
                                    <input type="datetime-local" class="form-control" id="end_date" name="end_date" value="<?= $poll && $poll['end_date'] ? date('Y-m-d\TH:i', strtotime($poll['end_date'])) : '' ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Poll Image (Optional)</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <?php if ($poll && $poll['image']): ?>
                                    <small class="text-muted">Current: <?= htmlspecialchars($poll['image']) ?></small>
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
                                                       <?= ($poll && ($poll['allow_comments'] ?? 0)) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="comments_yes">
                                                    <strong>YES</strong> - Allow comments
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="allow_comments" id="comments_no" value="0"
                                                       <?= (!$poll || !($poll['allow_comments'] ?? 0)) ? 'checked' : '' ?>>
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
                                                       <?= ($poll && ($poll['allow_multiple_votes'] ?? 0)) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="multiple_votes_yes">
                                                    <strong>YES</strong> - Allow multiple votes per person
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="allow_multiple_votes" id="multiple_votes_no" value="0"
                                                       <?= (!$poll || !($poll['allow_multiple_votes'] ?? 0)) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="multiple_votes_no">
                                                    <strong>NO</strong> - Single vote per person
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="voting_restrictions" style="display: <?= ($poll && ($poll['allow_multiple_votes'] ?? 0)) ? 'none' : 'block' ?>;">
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="radio" name="one_vote_per_ip" id="ip_vote_yes" value="1"
                                                           <?= ($poll && ($poll['one_vote_per_ip'] ?? 0)) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="ip_vote_yes">
                                                        <strong>YES</strong> - One vote per IP address
                                                    </label>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="radio" name="one_vote_per_ip" id="ip_vote_no" value="0"
                                                           <?= (!$poll || !($poll['one_vote_per_ip'] ?? 0)) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="ip_vote_no">
                                                        <strong>NO</strong> - Multiple votes per IP address
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="radio" name="one_vote_per_account" id="account_vote_yes" value="1"
                                                           <?= (!$poll || ($poll['one_vote_per_account'] ?? 1)) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="account_vote_yes">
                                                        <strong>YES</strong> - One vote per Opinion Hub NG Account
                                                    </label>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="radio" name="one_vote_per_account" id="account_vote_no" value="0"
                                                           <?= ($poll && !($poll['one_vote_per_account'] ?? 1)) ? 'checked' : '' ?>>
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
                                                       <?= ($poll && ($poll['results_public_after_vote'] ?? 0)) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="public_after_vote">
                                                    <strong>Public after vote</strong> - Display results after user votes
                                                </label>
                                            </div>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="results_visibility" id="public_after_end" value="after_end"
                                                       <?= ($poll && ($poll['results_public_after_end'] ?? 0)) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="public_after_end">
                                                    <strong>Public after end of voting</strong> - Display results in databank after completion date
                                                </label>
                                            </div>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="results_visibility" id="private_results" value="private"
                                                       <?= (!$poll || ($poll['results_private'] ?? 1)) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="private_results">
                                                    <strong>Private</strong> - Don't display results in databank
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Display Poll in Public Listing (Databank) Section -->
                            <div class="card border-success mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Display Poll in Public Listing (Databank)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="display_in_databank" id="databank_yes" value="1"
                                                       <?= ($poll && ($poll['results_for_sale'] ?? 0)) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="databank_yes">
                                                    <strong>YES</strong> - Display in databank
                                                    <br><small class="text-muted">Requires minimum 500 responses</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="display_in_databank" id="databank_no" value="0"
                                                       <?= (!$poll || !($poll['results_for_sale'] ?? 0)) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="databank_no">
                                                    <strong>NO</strong> - Do not display in databank
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="databank_settings_section" style="display: <?= ($poll && ($poll['results_for_sale'] ?? 0)) ? 'block' : 'none' ?>;">
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Databank Sale Price</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₦</span>
                                                    <input type="number" class="form-control" name="results_sale_price" id="databank_price"
                                                           value="<?= $poll['results_sale_price'] ?? 5000 ?>" min="0" step="0.01">
                                                </div>
                                                <small class="text-muted">Price users pay to access poll results</small>
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
                                                       <?= ($poll && floatval($poll['price_per_response'] ?? 0) > 0) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="pay_agents_yes">
                                                    <strong>YES</strong> - Pay agents ₦1,000 per response to help collect responses
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="pay_agents" id="pay_agents_no" value="0"
                                                       <?= (!$poll || floatval($poll['price_per_response'] ?? 0) == 0) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="pay_agents_no">
                                                    <strong>NO</strong> - I will collect responses myself or through other means
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Poll Pricing & Agent Commission -->
                            <div id="pricing_section" style="display: <?= ($poll && floatval($poll['price_per_response'] ?? 0) > 0) ? 'block' : 'none' ?>;">
                            <h5 class="mt-4 mb-3"><!-- <i class="fas fa-money-bill-wave text-success"></i> --> Pricing & Agent Commission</h5>
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
                                               value="<?= $poll['target_responders'] ?? 100 ?>" min="1">
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

                            <!-- Agent Filtering Criteria -->
                            <div id="agent_filtering_section" class="mt-4">
                                <h6 class="mb-3"><i class="fas fa-filter text-primary"></i> Agent Filtering Criteria</h6>
                                <p class="text-muted small mb-3">Select the criteria for agents who can work on this poll. Only agents matching these criteria will be able to apply.</p>

                                <div class="row">
                                    <!-- Age Groups -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Age Groups</label>
                                        <div class="border rounded p-3">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_age[]" value="18-25" id="age_18_25">
                                                <label class="form-check-label" for="age_18_25">Young adults (18-25 years old)</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_age[]" value="25-40" id="age_25_40">
                                                <label class="form-check-label" for="age_25_40">Adults (25-40 years old)</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_age[]" value="40-65" id="age_40_65">
                                                <label class="form-check-label" for="age_40_65">Middle-aged adults (40-65 years old)</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_age[]" value="65+" id="age_65_plus">
                                                <label class="form-check-label" for="age_65_plus">Senior citizens (65+ years old)</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_age[]" value="all" id="age_all" checked>
                                                <label class="form-check-label fw-bold" for="age_all">SELECT ALL (ALL AGES)</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Gender -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Gender</label>
                                        <div class="border rounded p-3">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_gender[]" value="female" id="gender_female">
                                                <label class="form-check-label" for="gender_female">Female</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_gender[]" value="male" id="gender_male">
                                                <label class="form-check-label" for="gender_male">Male</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_gender[]" value="both" id="gender_both" checked>
                                                <label class="form-check-label fw-bold" for="gender_both">SELECT BOTH</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Location -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Location</label>
                                        <div class="border rounded p-3">
                                            <div class="mb-3">
                                                <label class="form-label small">State</label>
                                                <select class="form-select" name="agent_state" id="agent_state">
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
                                            <div class="mb-3">
                                                <label class="form-label small">LGA (Optional)</label>
                                                <select class="form-select" name="agent_lga" id="agent_lga" disabled>
                                                    <option value="">Select State First</option>
                                                </select>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="agent_location_all" value="1" id="location_all" checked>
                                                <label class="form-check-label fw-bold" for="location_all">SELECT ALL (NIGERIA)</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Employment Status -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Employment Status</label>
                                        <div class="border rounded p-3">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_employment[]" value="employed" id="employment_employed">
                                                <label class="form-check-label" for="employment_employed">Employed</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_employment[]" value="unemployed" id="employment_unemployed">
                                                <label class="form-check-label" for="employment_unemployed">Unemployed</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_employment[]" value="both" id="employment_both" checked>
                                                <label class="form-check-label fw-bold" for="employment_both">SELECT BOTH</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Occupation -->
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-bold">Occupation</label>
                                        <div class="border rounded p-3">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <h6 class="text-primary mb-2">Healthcare and Medicine</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="doctor" id="occ_doctor"><label class="form-check-label small" for="occ_doctor">Doctor</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="nurse" id="occ_nurse"><label class="form-check-label small" for="occ_nurse">Nurse</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="pharmacist" id="occ_pharmacist"><label class="form-check-label small" for="occ_pharmacist">Pharmacist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="surgeon" id="occ_surgeon"><label class="form-check-label small" for="occ_surgeon">Surgeon</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="dentist" id="occ_dentist"><label class="form-check-label small" for="occ_dentist">Dentist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="medical_lab_tech" id="occ_medical_lab_tech"><label class="form-check-label small" for="occ_medical_lab_tech">Medical Laboratory Technician</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="physical_therapist" id="occ_physical_therapist"><label class="form-check-label small" for="occ_physical_therapist">Physical Therapist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="radiologist" id="occ_radiologist"><label class="form-check-label small" for="occ_radiologist">Radiologist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="optometrist" id="occ_optometrist"><label class="form-check-label small" for="occ_optometrist">Optometrist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="psychiatrist" id="occ_psychiatrist"><label class="form-check-label small" for="occ_psychiatrist">Psychiatrist</label></div>
                                                    <!-- Education and Academia -->
                                                    <h6 class="text-primary mt-3 mb-2">Education and Academia</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="teacher" id="occ_teacher"><label class="form-check-label small" for="occ_teacher">Teacher</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="professor" id="occ_professor"><label class="form-check-label small" for="occ_professor">Professor</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="librarian" id="occ_librarian"><label class="form-check-label small" for="occ_librarian">Librarian</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="school_principal" id="occ_school_principal"><label class="form-check-label small" for="occ_school_principal">School Principal</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="academic_advisor" id="occ_academic_advisor"><label class="form-check-label small" for="occ_academic_advisor">Academic Advisor</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="curriculum_developer" id="occ_curriculum_developer"><label class="form-check-label small" for="occ_curriculum_developer">Curriculum Developer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="research_scientist" id="occ_research_scientist"><label class="form-check-label small" for="occ_research_scientist">Research Scientist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="special_education_teacher" id="occ_special_education_teacher"><label class="form-check-label small" for="occ_special_education_teacher">Special Education Teacher</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="educational_consultant" id="occ_educational_consultant"><label class="form-check-label small" for="occ_educational_consultant">Educational Consultant</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="school_counselor" id="occ_school_counselor"><label class="form-check-label small" for="occ_school_counselor">School Counselor</label></div>
                                                    <!-- Engineering and Technology -->
                                                    <h6 class="text-primary mt-3 mb-2">Engineering and Technology</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="software_engineer" id="occ_software_engineer"><label class="form-check-label small" for="occ_software_engineer">Software Engineer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="civil_engineer" id="occ_civil_engineer"><label class="form-check-label small" for="occ_civil_engineer">Civil Engineer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="mechanical_engineer" id="occ_mechanical_engineer"><label class="form-check-label small" for="occ_mechanical_engineer">Mechanical Engineer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="electrical_engineer" id="occ_electrical_engineer"><label class="form-check-label small" for="occ_electrical_engineer">Electrical Engineer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="computer_programmer" id="occ_computer_programmer"><label class="form-check-label small" for="occ_computer_programmer">Computer Programmer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="network_administrator" id="occ_network_administrator"><label class="form-check-label small" for="occ_network_administrator">Network Administrator</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="data_scientist" id="occ_data_scientist"><label class="form-check-label small" for="occ_data_scientist">Data Scientist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="it_support_specialist" id="occ_it_support_specialist"><label class="form-check-label small" for="occ_it_support_specialist">IT Support Specialist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="cybersecurity_analyst" id="occ_cybersecurity_analyst"><label class="form-check-label small" for="occ_cybersecurity_analyst">Cybersecurity Analyst</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="aerospace_engineer" id="occ_aerospace_engineer"><label class="form-check-label small" for="occ_aerospace_engineer">Aerospace Engineer</label></div>
                                                    <!-- Business and Finance -->
                                                    <h6 class="text-primary mt-3 mb-2">Business and Finance</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="accountant" id="occ_accountant"><label class="form-check-label small" for="occ_accountant">Accountant</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="financial_analyst" id="occ_financial_analyst"><label class="form-check-label small" for="occ_financial_analyst">Financial Analyst</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="marketing_manager" id="occ_marketing_manager"><label class="form-check-label small" for="occ_marketing_manager">Marketing Manager</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="hr_manager" id="occ_hr_manager"><label class="form-check-label small" for="occ_hr_manager">Human Resources Manager</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="business_consultant" id="occ_business_consultant"><label class="form-check-label small" for="occ_business_consultant">Business Consultant</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="sales_representative" id="occ_sales_representative"><label class="form-check-label small" for="occ_sales_representative">Sales Representative</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="investment_banker" id="occ_investment_banker"><label class="form-check-label small" for="occ_investment_banker">Investment Banker</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="real_estate_agent" id="occ_real_estate_agent"><label class="form-check-label small" for="occ_real_estate_agent">Real Estate Agent</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="project_manager" id="occ_project_manager"><label class="form-check-label small" for="occ_project_manager">Project Manager</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="insurance_broker" id="occ_insurance_broker"><label class="form-check-label small" for="occ_insurance_broker">Insurance Broker</label></div>
                                                    <!-- Arts and Entertainment -->
                                                    <h6 class="text-primary mt-3 mb-2">Arts and Entertainment</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="graphic_designer" id="occ_graphic_designer"><label class="form-check-label small" for="occ_graphic_designer">Graphic Designer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="actor" id="occ_actor"><label class="form-check-label small" for="occ_actor">Actor</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="musician" id="occ_musician"><label class="form-check-label small" for="occ_musician">Musician</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="photographer" id="occ_photographer"><label class="form-check-label small" for="occ_photographer">Photographer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="film_director" id="occ_film_director"><label class="form-check-label small" for="occ_film_director">Film Director</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="author" id="occ_author"><label class="form-check-label small" for="occ_author">Author</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="dancer" id="occ_dancer"><label class="form-check-label small" for="occ_dancer">Dancer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="art_curator" id="occ_art_curator"><label class="form-check-label small" for="occ_art_curator">Art Curator</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="fashion_designer" id="occ_fashion_designer"><label class="form-check-label small" for="occ_fashion_designer">Fashion Designer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="animator" id="occ_animator"><label class="form-check-label small" for="occ_animator">Animator</label></div>
                                                    <!-- Law and Public Safety -->
                                                    <h6 class="text-primary mt-3 mb-2">Law and Public Safety</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="lawyer" id="occ_lawyer"><label class="form-check-label small" for="occ_lawyer">Lawyer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="police_officer" id="occ_police_officer"><label class="form-check-label small" for="occ_police_officer">Police Officer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="firefighter" id="occ_firefighter"><label class="form-check-label small" for="occ_firefighter">Firefighter</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="paralegal" id="occ_paralegal"><label class="form-check-label small" for="occ_paralegal">Paralegal</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="judge" id="occ_judge"><label class="form-check-label small" for="occ_judge">Judge</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="probation_officer" id="occ_probation_officer"><label class="form-check-label small" for="occ_probation_officer">Probation Officer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="correctional_officer" id="occ_correctional_officer"><label class="form-check-label small" for="occ_correctional_officer">Correctional Officer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="detective" id="occ_detective"><label class="form-check-label small" for="occ_detective">Detective</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="security_guard" id="occ_security_guard"><label class="form-check-label small" for="occ_security_guard">Security Guard</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="legal_secretary" id="occ_legal_secretary"><label class="form-check-label small" for="occ_legal_secretary">Legal Secretary</label></div>
                                                    <!-- Trades and Construction -->
                                                    <h6 class="text-primary mt-3 mb-2">Trades and Construction</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="carpenter" id="occ_carpenter"><label class="form-check-label small" for="occ_carpenter">Carpenter</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="electrician" id="occ_electrician"><label class="form-check-label small" for="occ_electrician">Electrician</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="plumber" id="occ_plumber"><label class="form-check-label small" for="occ_plumber">Plumber</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="welder" id="occ_welder"><label class="form-check-label small" for="occ_welder">Welder</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="mason" id="occ_mason"><label class="form-check-label small" for="occ_mason">Mason</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="hvac_technician" id="occ_hvac_technician"><label class="form-check-label small" for="occ_hvac_technician">HVAC Technician</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="painter" id="occ_painter"><label class="form-check-label small" for="occ_painter">Painter</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="heavy_equipment_operator" id="occ_heavy_equipment_operator"><label class="form-check-label small" for="occ_heavy_equipment_operator">Heavy Equipment Operator</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="roofer" id="occ_roofer"><label class="form-check-label small" for="occ_roofer">Roofer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="landscaper" id="occ_landscaper"><label class="form-check-label small" for="occ_landscaper">Landscaper</label></div>
                                                    <!-- Science and Research -->
                                                    <h6 class="text-primary mt-3 mb-2">Science and Research</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="biologist" id="occ_biologist"><label class="form-check-label small" for="occ_biologist">Biologist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="chemist" id="occ_chemist"><label class="form-check-label small" for="occ_chemist">Chemist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="physicist" id="occ_physicist"><label class="form-check-label small" for="occ_physicist">Physicist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="environmental_scientist" id="occ_environmental_scientist"><label class="form-check-label small" for="occ_environmental_scientist">Environmental Scientist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="geologist" id="occ_geologist"><label class="form-check-label small" for="occ_geologist">Geologist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="astronomer" id="occ_astronomer"><label class="form-check-label small" for="occ_astronomer">Astronomer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="marine_biologist" id="occ_marine_biologist"><label class="form-check-label small" for="occ_marine_biologist">Marine Biologist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="geneticist" id="occ_geneticist"><label class="form-check-label small" for="occ_geneticist">Geneticist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="meteorologist" id="occ_meteorologist"><label class="form-check-label small" for="occ_meteorologist">Meteorologist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="ecologist" id="occ_ecologist"><label class="form-check-label small" for="occ_ecologist">Ecologist</label></div>
                                                    <!-- Hospitality and Tourism -->
                                                    <h6 class="text-primary mt-3 mb-2">Hospitality and Tourism</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="hotel_manager" id="occ_hotel_manager"><label class="form-check-label small" for="occ_hotel_manager">Hotel Manager</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="travel_agent" id="occ_travel_agent"><label class="form-check-label small" for="occ_travel_agent">Travel Agent</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="chef" id="occ_chef"><label class="form-check-label small" for="occ_chef">Chef</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="restaurant_manager" id="occ_restaurant_manager"><label class="form-check-label small" for="occ_restaurant_manager">Restaurant Manager</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="tour_guide" id="occ_tour_guide"><label class="form-check-label small" for="occ_tour_guide">Tour Guide</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="event_planner" id="occ_event_planner"><label class="form-check-label small" for="occ_event_planner">Event Planner</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="bartender" id="occ_bartender"><label class="form-check-label small" for="occ_bartender">Bartender</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="concierge" id="occ_concierge"><label class="form-check-label small" for="occ_concierge">Concierge</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="cruise_ship_staff" id="occ_cruise_ship_staff"><label class="form-check-label small" for="occ_cruise_ship_staff">Cruise Ship Staff</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="flight_attendant" id="occ_flight_attendant"><label class="form-check-label small" for="occ_flight_attendant">Flight Attendant</label></div>
                                                    <!-- Media and Communication -->
                                                    <h6 class="text-primary mt-3 mb-2">Media and Communication</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="journalist" id="occ_journalist"><label class="form-check-label small" for="occ_journalist">Journalist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="public_relations_specialist" id="occ_public_relations_specialist"><label class="form-check-label small" for="occ_public_relations_specialist">Public Relations Specialist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="editor" id="occ_editor"><label class="form-check-label small" for="occ_editor">Editor</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="television_producer" id="occ_television_producer"><label class="form-check-label small" for="occ_television_producer">Television Producer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="radio_host" id="occ_radio_host"><label class="form-check-label small" for="occ_radio_host">Radio Host</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="social_media_manager" id="occ_social_media_manager"><label class="form-check-label small" for="occ_social_media_manager">Social Media Manager</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="content_writer" id="occ_content_writer"><label class="form-check-label small" for="occ_content_writer">Content Writer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="videographer" id="occ_videographer"><label class="form-check-label small" for="occ_videographer">Videographer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="translator" id="occ_translator"><label class="form-check-label small" for="occ_translator">Translator</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="copywriter" id="occ_copywriter"><label class="form-check-label small" for="occ_copywriter">Copywriter</label></div>
                                                    <!-- Agriculture and Environment -->
                                                    <h6 class="text-primary mt-3 mb-2">Agriculture and Environment</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="farmer" id="occ_farmer"><label class="form-check-label small" for="occ_farmer">Farmer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="agricultural_scientist" id="occ_agricultural_scientist"><label class="form-check-label small" for="occ_agricultural_scientist">Agricultural Scientist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="horticulturist" id="occ_horticulturist"><label class="form-check-label small" for="occ_horticulturist">Horticulturist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="forester" id="occ_forester"><label class="form-check-label small" for="occ_forester">Forester</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="fishery_manager" id="occ_fishery_manager"><label class="form-check-label small" for="occ_fishery_manager">Fishery Manager</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="wildlife_biologist" id="occ_wildlife_biologist"><label class="form-check-label small" for="occ_wildlife_biologist">Wildlife Biologist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="soil_scientist" id="occ_soil_scientist"><label class="form-check-label small" for="occ_soil_scientist">Soil Scientist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="environmental_consultant" id="occ_environmental_consultant"><label class="form-check-label small" for="occ_environmental_consultant">Environmental Consultant</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="landscape_architect" id="occ_landscape_architect"><label class="form-check-label small" for="occ_landscape_architect">Landscape Architect</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="agronomist" id="occ_agronomist"><label class="form-check-label small" for="occ_agronomist">Agronomist</label></div>
                                                    <!-- Transportation and Logistics -->
                                                    <h6 class="text-primary mt-3 mb-2">Transportation and Logistics</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="truck_driver" id="occ_truck_driver"><label class="form-check-label small" for="occ_truck_driver">Truck Driver</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="airline_pilot" id="occ_airline_pilot"><label class="form-check-label small" for="occ_airline_pilot">Airline Pilot</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="ship_captain" id="occ_ship_captain"><label class="form-check-label small" for="occ_ship_captain">Ship Captain</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="train_conductor" id="occ_train_conductor"><label class="form-check-label small" for="occ_train_conductor">Train Conductor</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="logistics_coordinator" id="occ_logistics_coordinator"><label class="form-check-label small" for="occ_logistics_coordinator">Logistics Coordinator</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="warehouse_manager" id="occ_warehouse_manager"><label class="form-check-label small" for="occ_warehouse_manager">Warehouse Manager</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="delivery_driver" id="occ_delivery_driver"><label class="form-check-label small" for="occ_delivery_driver">Delivery Driver</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="air_traffic_controller" id="occ_air_traffic_controller"><label class="form-check-label small" for="occ_air_traffic_controller">Air Traffic Controller</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="freight_forwarder" id="occ_freight_forwarder"><label class="form-check-label small" for="occ_freight_forwarder">Freight Forwarder</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="customs_broker" id="occ_customs_broker"><label class="form-check-label small" for="occ_customs_broker">Customs Broker</label></div>
                                                    <!-- Retail and Customer Service -->
                                                    <h6 class="text-primary mt-3 mb-2">Retail and Customer Service</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="retail_sales_associate" id="occ_retail_sales_associate"><label class="form-check-label small" for="occ_retail_sales_associate">Retail Sales Associate</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="store_manager" id="occ_store_manager"><label class="form-check-label small" for="occ_store_manager">Store Manager</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="customer_service_representative" id="occ_customer_service_representative"><label class="form-check-label small" for="occ_customer_service_representative">Customer Service Representative</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="cashier" id="occ_cashier"><label class="form-check-label small" for="occ_cashier">Cashier</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="visual_merchandiser" id="occ_visual_merchandiser"><label class="form-check-label small" for="occ_visual_merchandiser">Visual Merchandiser</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="inventory_specialist" id="occ_inventory_specialist"><label class="form-check-label small" for="occ_inventory_specialist">Inventory Specialist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="call_center_agent" id="occ_call_center_agent"><label class="form-check-label small" for="occ_call_center_agent">Call Center Agent</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="personal_shopper" id="occ_personal_shopper"><label class="form-check-label small" for="occ_personal_shopper">Personal Shopper</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="e_commerce_manager" id="occ_e_commerce_manager"><label class="form-check-label small" for="occ_e_commerce_manager">E-commerce Manager</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="retail_buyer" id="occ_retail_buyer"><label class="form-check-label small" for="occ_retail_buyer">Retail Buyer</label></div>
                                                    <!-- Sports and Fitness -->
                                                    <h6 class="text-primary mt-3 mb-2">Sports and Fitness</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="personal_trainer" id="occ_personal_trainer"><label class="form-check-label small" for="occ_personal_trainer">Personal Trainer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="coach" id="occ_coach"><label class="form-check-label small" for="occ_coach">Coach</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="professional_athlete" id="occ_professional_athlete"><label class="form-check-label small" for="occ_professional_athlete">Professional Athlete</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="sports_commentator" id="occ_sports_commentator"><label class="form-check-label small" for="occ_sports_commentator">Sports Commentator</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="sports_agent" id="occ_sports_agent"><label class="form-check-label small" for="occ_sports_agent">Sports Agent</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="fitness_instructor" id="occ_fitness_instructor"><label class="form-check-label small" for="occ_fitness_instructor">Fitness Instructor</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="sports_medicine_physician" id="occ_sports_medicine_physician"><label class="form-check-label small" for="occ_sports_medicine_physician">Sports Medicine Physician</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="athletic_trainer" id="occ_athletic_trainer"><label class="form-check-label small" for="occ_athletic_trainer">Athletic Trainer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="referee" id="occ_referee"><label class="form-check-label small" for="occ_referee">Referee</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="sports_marketing_manager" id="occ_sports_marketing_manager"><label class="form-check-label small" for="occ_sports_marketing_manager">Sports Marketing Manager</label></div>
                                                    <!-- Government and Public Administration -->
                                                    <h6 class="text-primary mt-3 mb-2">Government and Public Administration</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="diplomat" id="occ_diplomat"><label class="form-check-label small" for="occ_diplomat">Diplomat</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="urban_planner" id="occ_urban_planner"><label class="form-check-label small" for="occ_urban_planner">Urban Planner</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="policy_analyst" id="occ_policy_analyst"><label class="form-check-label small" for="occ_policy_analyst">Policy Analyst</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="public_relations_officer" id="occ_public_relations_officer"><label class="form-check-label small" for="occ_public_relations_officer">Public Relations Officer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="legislator" id="occ_legislator"><label class="form-check-label small" for="occ_legislator">Legislator</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="city_manager" id="occ_city_manager"><label class="form-check-label small" for="occ_city_manager">City Manager</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="social_worker" id="occ_social_worker"><label class="form-check-label small" for="occ_social_worker">Social Worker</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="tax_examiner" id="occ_tax_examiner"><label class="form-check-label small" for="occ_tax_examiner">Tax Examiner</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="customs_officer" id="occ_customs_officer"><label class="form-check-label small" for="occ_customs_officer">Customs Officer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="intelligence_analyst" id="occ_intelligence_analyst"><label class="form-check-label small" for="occ_intelligence_analyst">Intelligence Analyst</label></div>
                                                    <!-- Manufacturing and Production -->
                                                    <h6 class="text-primary mt-3 mb-2">Manufacturing and Production</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="factory_worker" id="occ_factory_worker"><label class="form-check-label small" for="occ_factory_worker">Factory Worker</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="quality_control_inspector" id="occ_quality_control_inspector"><label class="form-check-label small" for="occ_quality_control_inspector">Quality Control Inspector</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="production_manager" id="occ_production_manager"><label class="form-check-label small" for="occ_production_manager">Production Manager</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="assembly_line_worker" id="occ_assembly_line_worker"><label class="form-check-label small" for="occ_assembly_line_worker">Assembly Line Worker</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="machinist" id="occ_machinist"><label class="form-check-label small" for="occ_machinist">Machinist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="maintenance_technician" id="occ_maintenance_technician"><label class="form-check-label small" for="occ_maintenance_technician">Maintenance Technician</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="industrial_engineer" id="occ_industrial_engineer"><label class="form-check-label small" for="occ_industrial_engineer">Industrial Engineer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="production_planner" id="occ_production_planner"><label class="form-check-label small" for="occ_production_planner">Production Planner</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="operations_manager" id="occ_operations_manager"><label class="form-check-label small" for="occ_operations_manager">Operations Manager</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="manufacturing_engineer" id="occ_manufacturing_engineer"><label class="form-check-label small" for="occ_manufacturing_engineer">Manufacturing Engineer</label></div>
                                                    <!-- Miscellaneous -->
                                                    <h6 class="text-primary mt-3 mb-2">Miscellaneous</h6>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="entrepreneur" id="occ_entrepreneur"><label class="form-check-label small" for="occ_entrepreneur">Entrepreneur</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="real_estate_developer" id="occ_real_estate_developer"><label class="form-check-label small" for="occ_real_estate_developer">Real Estate Developer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="nonprofit_manager" id="occ_nonprofit_manager"><label class="form-check-label small" for="occ_nonprofit_manager">Nonprofit Manager</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="auctioneer" id="occ_auctioneer"><label class="form-check-label small" for="occ_auctioneer">Auctioneer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="archivist" id="occ_archivist"><label class="form-check-label small" for="occ_archivist">Archivist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="antiques_dealer" id="occ_antiques_dealer"><label class="form-check-label small" for="occ_antiques_dealer">Antiques Dealer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="dog_trainer" id="occ_dog_trainer"><label class="form-check-label small" for="occ_dog_trainer">Dog Trainer</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="florist" id="occ_florist"><label class="form-check-label small" for="occ_florist">Florist</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="funeral_director" id="occ_funeral_director"><label class="form-check-label small" for="occ_funeral_director">Funeral Director</label></div>
                                                    <div class="form-check mb-1"><input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="tattoo_artist" id="occ_tattoo_artist"><label class="form-check-label small" for="occ_tattoo_artist">Tattoo Artist</label></div>
                                                </div>
                                                <div class="col-md-3">
                                                    <h6 class="text-primary mb-2">Education and Academia</h6>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="teacher" id="occ_teacher">
                                                        <label class="form-check-label small" for="occ_teacher">Teacher</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="professor" id="occ_professor">
                                                        <label class="form-check-label small" for="occ_professor">Professor</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="librarian" id="occ_librarian">
                                                        <label class="form-check-label small" for="occ_librarian">Librarian</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="school_principal" id="occ_school_principal">
                                                        <label class="form-check-label small" for="occ_school_principal">School Principal</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="academic_advisor" id="occ_academic_advisor">
                                                        <label class="form-check-label small" for="occ_academic_advisor">Academic Advisor</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="curriculum_developer" id="occ_curriculum_developer">
                                                        <label class="form-check-label small" for="occ_curriculum_developer">Curriculum Developer</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="research_scientist" id="occ_research_scientist">
                                                        <label class="form-check-label small" for="occ_research_scientist">Research Scientist</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="special_education_teacher" id="occ_special_education_teacher">
                                                        <label class="form-check-label small" for="occ_special_education_teacher">Special Education Teacher</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="educational_consultant" id="occ_educational_consultant">
                                                        <label class="form-check-label small" for="occ_educational_consultant">Educational Consultant</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="school_counselor" id="occ_school_counselor">
                                                        <label class="form-check-label small" for="occ_school_counselor">School Counselor</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <h6 class="text-primary mb-2">Engineering and Technology</h6>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="software_engineer" id="occ_software_engineer">
                                                        <label class="form-check-label small" for="occ_software_engineer">Software Engineer</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="civil_engineer" id="occ_civil_engineer">
                                                        <label class="form-check-label small" for="occ_civil_engineer">Civil Engineer</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="mechanical_engineer" id="occ_mechanical_engineer">
                                                        <label class="form-check-label small" for="occ_mechanical_engineer">Mechanical Engineer</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="electrical_engineer" id="occ_electrical_engineer">
                                                        <label class="form-check-label small" for="occ_electrical_engineer">Electrical Engineer</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="computer_programmer" id="occ_computer_programmer">
                                                        <label class="form-check-label small" for="occ_computer_programmer">Computer Programmer</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="network_administrator" id="occ_network_administrator">
                                                        <label class="form-check-label small" for="occ_network_administrator">Network Administrator</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="data_scientist" id="occ_data_scientist">
                                                        <label class="form-check-label small" for="occ_data_scientist">Data Scientist</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="it_support_specialist" id="occ_it_support_specialist">
                                                        <label class="form-check-label small" for="occ_it_support_specialist">IT Support Specialist</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="cybersecurity_analyst" id="occ_cybersecurity_analyst">
                                                        <label class="form-check-label small" for="occ_cybersecurity_analyst">Cybersecurity Analyst</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="aerospace_engineer" id="occ_aerospace_engineer">
                                                        <label class="form-check-label small" for="occ_aerospace_engineer">Aerospace Engineer</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <h6 class="text-primary mb-2">Business and Finance</h6>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="accountant" id="occ_accountant">
                                                        <label class="form-check-label small" for="occ_accountant">Accountant</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="financial_analyst" id="occ_financial_analyst">
                                                        <label class="form-check-label small" for="occ_financial_analyst">Financial Analyst</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="marketing_manager" id="occ_marketing_manager">
                                                        <label class="form-check-label small" for="occ_marketing_manager">Marketing Manager</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="hr_manager" id="occ_hr_manager">
                                                        <label class="form-check-label small" for="occ_hr_manager">Human Resources Manager</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="business_consultant" id="occ_business_consultant">
                                                        <label class="form-check-label small" for="occ_business_consultant">Business Consultant</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="sales_representative" id="occ_sales_representative">
                                                        <label class="form-check-label small" for="occ_sales_representative">Sales Representative</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="investment_banker" id="occ_investment_banker">
                                                        <label class="form-check-label small" for="occ_investment_banker">Investment Banker</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="real_estate_agent" id="occ_real_estate_agent">
                                                        <label class="form-check-label small" for="occ_real_estate_agent">Real Estate Agent</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="project_manager" id="occ_project_manager">
                                                        <label class="form-check-label small" for="occ_project_manager">Project Manager</label>
                                                    </div>
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="insurance_broker" id="occ_insurance_broker">
                                                        <label class="form-check-label small" for="occ_insurance_broker">Insurance Broker</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="form-check">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_occupation[]" value="all" id="occupation_all" checked>
                                                <label class="form-check-label fw-bold" for="occupation_all">SELECT ALL</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Education Qualification -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Highest Educational Qualification</label>
                                        <div class="border rounded p-3">
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="ssc" id="edu_ssc">
                                                <label class="form-check-label small" for="edu_ssc">Senior School Certificate</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="nd" id="edu_nd">
                                                <label class="form-check-label small" for="edu_nd">National Diploma</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="hnd" id="edu_hnd">
                                                <label class="form-check-label small" for="edu_hnd">Higher National Diploma</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="bachelors" id="edu_bachelors">
                                                <label class="form-check-label small" for="edu_bachelors">Bachelor's Degree (Honours)</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="nce" id="edu_nce">
                                                <label class="form-check-label small" for="edu_nce">Nigeria Certificate in Education</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="bed" id="edu_bed">
                                                <label class="form-check-label small" for="edu_bed">Bachelor of Education</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="llb" id="edu_llb">
                                                <label class="form-check-label small" for="edu_llb">Bachelor of Law(s) (LLB)</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="mbbs" id="edu_mbbs">
                                                <label class="form-check-label small" for="edu_mbbs">Bachelor of Medicine and Bachelor of Surgery (MBBS)</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="bds" id="edu_bds">
                                                <label class="form-check-label small" for="edu_bds">Bachelor of Dental Surgery (BDS)</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="dvm" id="edu_dvm">
                                                <label class="form-check-label small" for="edu_dvm">Doctor of Veterinary Medicine (DVM)</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="pgd" id="edu_pgd">
                                                <label class="form-check-label small" for="edu_pgd">Postgraduate Diploma</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="masters" id="edu_masters">
                                                <label class="form-check-label small" for="edu_masters">Master's Degree</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="mphil" id="edu_mphil">
                                                <label class="form-check-label small" for="edu_mphil">Master of Philosophy</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="phd" id="edu_phd">
                                                <label class="form-check-label small" for="edu_phd">Doctor of Philosophy</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="others" id="edu_others">
                                                <label class="form-check-label small" for="edu_others">Others</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_education[]" value="all" id="education_all" checked>
                                                <label class="form-check-label fw-bold" for="education_all">SELECT ALL</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Monthly Income Range -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Monthly Income Range</label>
                                        <div class="border rounded p-3">
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_income[]" value="1-30000" id="income_1_30000">
                                                <label class="form-check-label small" for="income_1_30000">₦ 1 - ₦ 30,000</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_income[]" value="30000-80000" id="income_30000_80000">
                                                <label class="form-check-label small" for="income_30000_80000">₦ 30,000 - ₦ 80,000</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_income[]" value="80000-150000" id="income_80000_150000">
                                                <label class="form-check-label small" for="income_80000_150000">₦ 80,000 - ₦ 150,000</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_income[]" value="150000-250000" id="income_150000_250000">
                                                <label class="form-check-label small" for="income_150000_250000">₦ 150,000 - ₦ 250,000</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_income[]" value="250000-500000" id="income_250000_500000">
                                                <label class="form-check-label small" for="income_250000_500000">₦ 250,000 - ₦ 500,000</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_income[]" value="500000-1500000" id="income_500000_1500000">
                                                <label class="form-check-label small" for="income_500000_1500000">₦ 500,000 - ₦ 1,500,000</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_income[]" value="1500000-5000000" id="income_1500000_5000000">
                                                <label class="form-check-label small" for="income_1500000_5000000">₦ 1,500,000 - ₦ 5,000,000</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_income[]" value="5000000+" id="income_5000000_plus">
                                                <label class="form-check-label small" for="income_5000000_plus">₦ 5,000,000 – upwards</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input agent-filter" type="checkbox" name="agent_income[]" value="all" id="income_all" checked>
                                                <label class="form-check-label fw-bold" for="income_all">SELECT ALL</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            </div> <!-- End pricing section -->

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Create Poll & Continue
                                </button>
                                <a href="<?php echo SITE_URL; ?>dashboard.php" class="btn btn-outline-secondary btn-lg">
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

<?php include_once '../footer.php'; ?>

<script>

// Agent filtering functionality
function initializeAgentFilters() {
    // Handle "SELECT ALL" checkboxes
    document.querySelectorAll('.agent-filter').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const groupName = this.name;
            const isSelectAll = this.value === 'all' || this.id.includes('_all');

            if (isSelectAll) {
                // If "SELECT ALL" is checked/unchecked, update all in the group
                const groupCheckboxes = document.querySelectorAll(`input[name="${groupName}"]`);
                groupCheckboxes.forEach(cb => {
                    if (cb !== this) {
                        cb.checked = this.checked;
                    }
                });
            } else {
                // If individual checkbox is unchecked, uncheck "SELECT ALL"
                const selectAllCheckbox = document.querySelector(`input[name="${groupName}"][value="all"]`) ||
                                         document.querySelector(`input[name="${groupName}"][id*="_all"]`);
                if (selectAllCheckbox && !this.checked) {
                    selectAllCheckbox.checked = false;
                }

                // If all individual checkboxes are checked, check "SELECT ALL"
                const groupCheckboxes = document.querySelectorAll(`input[name="${groupName}"]:not([value="all"]):not([id*="_all"])`);
                const allChecked = Array.from(groupCheckboxes).every(cb => cb.checked);
                if (selectAllCheckbox && allChecked && groupCheckboxes.length > 0) {
                    selectAllCheckbox.checked = true;
                }
            }
        });
    });

    // Show/hide agent filtering section based on agent selection
    const payAgentsRadios = document.querySelectorAll('input[name="pay_agents"]');
    payAgentsRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const agentFilteringSection = document.getElementById('agent_filtering_section');
            if (this.value === '1') {
                agentFilteringSection.style.display = 'block';
            } else {
                agentFilteringSection.style.display = 'none';
            }
        });
    });
}

// Initialize agent filters when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeAgentFilters();
    
    // Load and populate LGAs for agent state selection
    const agentStateSelect = document.getElementById('agent_state');
    const agentLgaSelect = document.getElementById('agent_lga');
    const locationAllCheckbox = document.getElementById('location_all');
    let lgasData = {};
    
    // Load LGA data from JSON file
    fetch('<?php echo SITE_URL; ?>assets/data/nigeria-states-lgas.json')
        .then(response => response.json())
        .then(data => {
            lgasData = data;
            
            // Handle state selection change
            if (agentStateSelect && agentLgaSelect) {
                agentStateSelect.addEventListener('change', function() {
                    const selectedState = this.value;
                    agentLgaSelect.innerHTML = '<option value="">Select LGA</option>';
                    
                    if (selectedState && lgasData[selectedState]) {
                        // Enable LGA select
                        agentLgaSelect.disabled = false;
                        
                        // Populate LGA options
                        lgasData[selectedState].forEach(function(lga) {
                            const option = document.createElement('option');
                            option.value = lga;
                            option.textContent = lga;
                            agentLgaSelect.appendChild(option);
                        });
                    } else {
                        // Disable LGA select if no state selected
                        agentLgaSelect.disabled = true;
                    }
                });
            }
            
            // Handle "SELECT ALL (NIGERIA)" checkbox
            if (locationAllCheckbox) {
                locationAllCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        // Disable state and LGA fields when "SELECT ALL" is checked
                        if (agentStateSelect) {
                            agentStateSelect.disabled = true;
                            agentStateSelect.value = '';
                        }
                        if (agentLgaSelect) {
                            agentLgaSelect.disabled = true;
                            agentLgaSelect.innerHTML = '<option value="">Select LGA</option>';
                        }
                    } else {
                        // Enable state field when "SELECT ALL" is unchecked
                        if (agentStateSelect) {
                            agentStateSelect.disabled = false;
                        }
                        // LGA will be enabled when a state is selected
                    }
                });
                
                // Initial state: if checkbox is checked, disable fields
                if (locationAllCheckbox.checked) {
                    if (agentStateSelect) {
                        agentStateSelect.disabled = true;
                    }
                    if (agentLgaSelect) {
                        agentLgaSelect.disabled = true;
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error loading LGA data:', error);
        });
});

// Calculate estimated cost (Platform Fee + Agent Commission per response)
function updateEstimatedCost() {
    const platformFee = 500; // Fixed platform fee
    const agentCommission = 1000; // Fixed agent commission
    const totalPerResponse = platformFee + agentCommission; // Total cost per response
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
            if (pricingSection) {
                pricingSection.style.display = this.value === '1' ? 'block' : 'none';
            }
        });
    });

    // Toggle databank settings section
    document.querySelectorAll('input[name="display_in_databank"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const databankSection = document.getElementById('databank_settings_section');
            if (databankSection) {
                databankSection.style.display = this.value === '1' ? 'block' : 'none';
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
