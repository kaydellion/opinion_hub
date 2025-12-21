<?php
$page_title = "Create Poll";
include_once '../header.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "login.php");
    exit;
}

requireRole(['client', 'admin']);

global $conn;
$user = getCurrentUser();

// Check subscription limits for new polls
$is_editing = isset($_GET['id']) && $_GET['id'] > 0;
if (!$is_editing) {
    $poll_limit = checkPollCreationLimit($user['id']);
    if (!$poll_limit['allowed']) {
        $_SESSION['error_message'] = $poll_limit['message'];
        header("Location: " . SITE_URL . "client/manage-polls.php");
        exit;
    }
}

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

                            <div class="mb-3">
                                <label for="description" class="form-label">Poll Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($poll['description'] ?? '') ?></textarea>
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

                            <h6 class="mt-4 mb-3">Poll Settings</h6>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="allow_multiple_options" id="allow_multiple" <?= ($poll && $poll['allow_multiple_options']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="allow_multiple">
                                            Allow multiple option selection
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="require_participant_names" id="require_names" <?= ($poll && $poll['require_participant_names']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="require_names">
                                            Require participants' names
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="allow_comments" id="allow_comments" <?= ($poll && $poll['allow_comments']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="allow_comments">
                                            Allow comments
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="one_vote_per_ip" id="one_vote_ip" <?= ($poll && $poll['one_vote_per_ip']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="one_vote_ip">
                                            One vote per IP address
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="results_public_after_vote" id="results_public" <?= ($poll && $poll['results_public_after_vote']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="results_public">
                                            Show results after voting
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Poll Pricing & Agent Commission -->
                            <h5 class="mt-4 mb-3"><i class="fas fa-money-bill-wave text-success"></i> Pricing & Agent Commission</h5>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Set pricing for this poll and commission for agents who help collect responses.
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Cost Per Response <small class="text-muted">(Amount you pay per response)</small></label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="number" class="form-control" name="cost_per_response" id="cost_per_response" 
                                                   value="<?= $poll['cost_per_response'] ?? 100 ?>" min="0" step="0.01">
                                        </div>
                                        <small class="text-muted">Default: ₦100 per response</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Agent Commission <small class="text-muted">(Amount agents earn per response)</small></label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="number" class="form-control" name="agent_commission" id="agent_commission" 
                                                   value="<?= $poll['agent_commission'] ?? 1000 ?>" min="0" step="0.01">
                                        </div>
                                        <small class="text-muted">Default: ₦1,000 per response (agents earn this)</small>
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
                                            <input type="text" class="form-control bg-light" id="estimated_cost" readonly value="10,000.00">
                                        </div>
                                        <small class="text-muted">Cost per response × Target responses</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Databank Settings -->
                            <h5 class="mt-4 mb-3"><i class="fas fa-database text-primary"></i> Databank Settings</h5>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Sell access to your poll results in the databank for additional revenue.
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="results_for_sale" id="results_for_sale" 
                                               <?= ($poll && ($poll['results_for_sale'] ?? 0)) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="results_for_sale">
                                            <strong>Make results available for sale in databank</strong>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3" id="sale_price_container" style="display: <?= ($poll && ($poll['results_for_sale'] ?? 0)) ? 'block' : 'none' ?>;">
                                        <label class="form-label">Results Sale Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="number" class="form-control" name="results_sale_price" id="results_sale_price" 
                                                   value="<?= $poll['results_sale_price'] ?? 5000 ?>" min="0" step="0.01">
                                        </div>
                                        <small class="text-muted">Price users pay to access results</small>
                                    </div>
                                </div>
                            </div>

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
// Calculate estimated cost
function updateEstimatedCost() {
    const costPerResponse = parseFloat(document.getElementById('cost_per_response').value) || 0;
    const targetResponders = parseFloat(document.getElementById('target_responders').value) || 0;
    const total = costPerResponse * targetResponders;
    document.getElementById('estimated_cost').value = total.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Update on input
document.getElementById('cost_per_response').addEventListener('input', updateEstimatedCost);
document.getElementById('target_responders').addEventListener('input', updateEstimatedCost);

// Toggle sale price field
document.getElementById('results_for_sale').addEventListener('change', function() {
    document.getElementById('sale_price_container').style.display = this.checked ? 'block' : 'none';
});

// Initial calculation
updateEstimatedCost();
</script>
