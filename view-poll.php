<?php
$page_title = "View Poll";
include_once 'header.php';

global $conn;

$poll_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$poll_slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

// Capture tracking code if present (for agent referral tracking)
$tracking_code = isset($_GET['ref']) ? sanitize($_GET['ref']) : null;
if ($tracking_code) {
    $_SESSION['tracking_code'] = $tracking_code;
}

// If ID is provided but no slug, get the slug and redirect to pretty URL
if ($poll_id > 0 && empty($poll_slug)) {
    $slug_query = "SELECT slug FROM polls WHERE id = $poll_id";
    $slug_result = $conn->query($slug_query);
    if ($slug_result && $slug_result->num_rows > 0) {
        $slug_data = $slug_result->fetch_assoc();
        $redirect_url = SITE_URL . "view-poll/" . $slug_data['slug'];
        // Preserve tracking code and success parameter
        $params = [];
        if ($tracking_code) $params[] = "ref=" . urlencode($tracking_code);
        if (isset($_GET['success'])) $params[] = "success=true";
        if (!empty($params)) {
            $redirect_url .= "?" . implode("&", $params);
        }
        header("Location: " . $redirect_url);
        exit;
    }
}

if ($poll_slug) {
    $poll_query = "SELECT p.*, c.name as category_name, u.first_name, u.last_name FROM polls p LEFT JOIN categories c ON p.category_id = c.id JOIN users u ON p.created_by = u.id WHERE p.slug = '$poll_slug'";
} elseif ($poll_id > 0) {
    $poll_query = "SELECT p.*, c.name as category_name, u.first_name, u.last_name FROM polls p LEFT JOIN categories c ON p.category_id = c.id JOIN users u ON p.created_by = u.id WHERE p.id = $poll_id";
} else {
    header("Location: " . SITE_URL . "polls.php");
    exit;
}

$result = $conn->query($poll_query);
if (!$result || $result->num_rows === 0) {
    echo "<div class='container my-5'><div class='alert alert-danger'>Poll not found.</div></div>";
    include_once 'footer.php';
    exit;
}

$poll = $result->fetch_assoc();
$poll_id = $poll['id']; // For questions and responses

// Get poll questions
$questions = $conn->query("SELECT * FROM poll_questions WHERE poll_id = $poll_id ORDER BY question_order");

// Check if user already voted
$already_voted = false;
if (isLoggedIn()) {
    $user_id = getCurrentUser()['id'];
    $check = $conn->query("SELECT id FROM poll_responses WHERE poll_id = $poll_id AND respondent_id = $user_id");
    $already_voted = $check && $check->num_rows > 0;
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
    $check = $conn->query("SELECT id FROM poll_responses WHERE poll_id = $poll_id AND respondent_ip = '$ip'");
    $already_voted = $check && $check->num_rows > 0;
}

$success = isset($_GET['success']) ? true : false;
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
?>

<div class="container my-5">
    <!-- Advertisement: Poll Page Top -->
    <?php displayAd('poll_page_top', 'mb-4'); ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Thank you for your response! 
            <a href="<?php echo SITE_URL; ?>databank.php?poll=<?php echo $poll_id; ?>">View Results</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div>• <?php echo $error; ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Main Poll Content -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg">
                <!-- Poll Header -->
                <div class="card-header bg-primary text-white p-4">
                    <div class="mb-2">
                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars($poll['category_name'] ?? 'General'); ?></span>
                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars($poll['poll_type']); ?></span>
                    </div>
                    <h2 class="mb-2"><?php echo htmlspecialchars($poll['title']); ?></h2>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($poll['description'])); ?></p>
                </div>

                <div class="card-body p-4">
                    <?php if ($poll['status'] !== 'active'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> This poll is currently <?php echo $poll['status']; ?>.
                        </div>
                    <?php elseif ($already_voted): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-check-circle"></i> You have already participated in this poll.
                            <a href="<?php echo SITE_URL; ?>databank.php?poll=<?php echo $poll_id; ?>">View Results</a>
                        </div>
                    <?php else: ?>
                        <!-- Poll Form -->
                        <form method="POST" action="<?php echo SITE_URL; ?>actions.php?action=submit_response" id="pollForm">
                            <input type="hidden" name="poll_id" value="<?php echo $poll_id; ?>">
                            
                            <?php
                            $question_num = 1;
                            while ($question = $questions->fetch_assoc()):
                                // Get options for this question
                                $options = $conn->query("SELECT * FROM poll_question_options 
                                                        WHERE question_id = " . $question['id'] . " 
                                                        ORDER BY option_order");
                            ?>
                                <div class="mb-4 pb-4 border-bottom">
                                    <h5 class="mb-3">
                                        <span class="badge bg-primary"><?php echo $question_num; ?></span>
                                        <?php echo htmlspecialchars($question['question_text']); ?>
                                        <?php if ($question['is_required']): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </h5>

                                    <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                        <?php 
                                        // Check if poll allows multiple options for multiple choice questions
                                        $use_checkbox = $poll['allow_multiple_options'] == 1;
                                        $input_type = $use_checkbox ? 'checkbox' : 'radio';
                                        $input_name = $use_checkbox ? "responses[{$question['id']}][]" : "responses[{$question['id']}]";
                                        ?>
                                        <?php while ($option = $options->fetch_assoc()): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="<?php echo $input_type; ?>" 
                                                       name="<?php echo $input_name; ?>" 
                                                       value="<?php echo $option['id']; ?>" 
                                                       id="q<?php echo $question['id']; ?>_o<?php echo $option['id']; ?>"
                                                       <?php echo $question['is_required'] && !$use_checkbox ? 'required' : ''; ?>>
                                                <label class="form-check-label" for="q<?php echo $question['id']; ?>_o<?php echo $option['id']; ?>">
                                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                                </label>
                                            </div>
                                        <?php endwhile; ?>
                                        <?php if ($use_checkbox): ?>
                                            <small class="text-muted"><i class="fas fa-info-circle"></i> You can select multiple options</small>
                                        <?php endif; ?>

                                    <?php elseif ($question['question_type'] === 'multiple_answer'): ?>
                                        <?php while ($option = $options->fetch_assoc()): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="responses[<?php echo $question['id']; ?>][]" 
                                                       value="<?php echo $option['id']; ?>" 
                                                       id="q<?php echo $question['id']; ?>_o<?php echo $option['id']; ?>">
                                                <label class="form-check-label" for="q<?php echo $question['id']; ?>_o<?php echo $option['id']; ?>">
                                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                                </label>
                                            </div>
                                        <?php endwhile; ?>

                                    <?php elseif ($question['question_type'] === 'rating'): ?>
                                        <div class="rating-group" data-question-id="<?php echo $question['id']; ?>">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <label class="rating-star" data-rating="<?php echo $i; ?>">
                                                    <input type="radio" 
                                                           name="responses[<?php echo $question['id']; ?>]" 
                                                           value="<?php echo $i; ?>" 
                                                           id="rating_<?php echo $question['id']; ?>_<?php echo $i; ?>"
                                                           <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                                    <span class="star-icon" aria-hidden="true">★</span>
                                                    <span class="rating-value"><?php echo $i; ?></span>
                                                </label>
                                            <?php endfor; ?>
                                        </div>

                                    <?php elseif ($question['question_type'] === 'open_ended'): ?>
                                        <textarea class="form-control" name="responses[<?php echo $question['id']; ?>]" 
                                                  rows="4" placeholder="Type your answer here..." 
                                                  <?php echo $question['is_required'] ? 'required' : ''; ?>></textarea>

                                    <?php elseif ($question['question_type'] === 'word_cloud'): ?>
                                        <input type="text" class="form-control" 
                                               name="responses[<?php echo $question['id']; ?>]" 
                                               placeholder="Enter keywords separated by commas"
                                               <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                        <small class="text-muted">Separate multiple words with commas</small>

                                    <?php elseif ($question['question_type'] === 'quiz'): ?>
                                        <?php while ($option = $options->fetch_assoc()): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" 
                                                       name="responses[<?php echo $question['id']; ?>]" 
                                                       value="<?php echo $option['id']; ?>" 
                                                       id="q<?php echo $question['id']; ?>_o<?php echo $option['id']; ?>"
                                                       <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                                <label class="form-check-label" for="q<?php echo $question['id']; ?>_o<?php echo $option['id']; ?>">
                                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                                </label>
                                            </div>
                                        <?php endwhile; ?>
                                        <small class="text-muted"><i class="fas fa-info-circle"></i> Quiz question - your answer will be scored</small>

                                    <?php elseif ($question['question_type'] === 'assessment'): ?>
                                        <?php while ($option = $options->fetch_assoc()): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" 
                                                       name="responses[<?php echo $question['id']; ?>]" 
                                                       value="<?php echo $option['id']; ?>" 
                                                       id="q<?php echo $question['id']; ?>_o<?php echo $option['id']; ?>"
                                                       <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                                <label class="form-check-label" for="q<?php echo $question['id']; ?>_o<?php echo $option['id']; ?>">
                                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                                </label>
                                            </div>
                                        <?php endwhile; ?>

                                    <?php elseif ($question['question_type'] === 'yes_no'): ?>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" 
                                                   name="responses[<?php echo $question['id']; ?>]" value="Yes" 
                                                   id="q<?php echo $question['id']; ?>_yes" 
                                                   <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                            <label class="btn btn-outline-success" for="q<?php echo $question['id']; ?>_yes">
                                                <i class="fas fa-check"></i> Yes
                                            </label>
                                            
                                            <input type="radio" class="btn-check" 
                                                   name="responses[<?php echo $question['id']; ?>]" value="No" 
                                                   id="q<?php echo $question['id']; ?>_no">
                                            <label class="btn btn-outline-danger" for="q<?php echo $question['id']; ?>_no">
                                                <i class="fas fa-times"></i> No
                                            </label>
                                        </div>

                                    <?php elseif ($question['question_type'] === 'dichotomous'): ?>
                                        <?php 
                                        $opts = [];
                                        while ($option = $options->fetch_assoc()) {
                                            $opts[] = $option;
                                        }
                                        ?>
                                        <div class="btn-group w-100" role="group">
                                            <?php foreach ($opts as $option): ?>
                                                <input type="radio" class="btn-check" 
                                                       name="responses[<?php echo $question['id']; ?>]" 
                                                       value="<?php echo $option['id']; ?>" 
                                                       id="q<?php echo $question['id']; ?>_o<?php echo $option['id']; ?>"
                                                       <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                                <label class="btn btn-outline-primary" 
                                                       for="q<?php echo $question['id']; ?>_o<?php echo $option['id']; ?>">
                                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                    <?php elseif ($question['question_type'] === 'matrix'): ?>
                                        <?php 
                                        // Get matrix metadata (columns configuration)
                                        $metadata = json_decode($question['metadata'] ?? '{}', true);
                                        $matrix_columns = $metadata['columns'] ?? ['Poor', 'Fair', 'Good', 'Excellent'];
                                        
                                        // Get matrix rows (statements) from options
                                        $matrix_rows = [];
                                        while ($option = $options->fetch_assoc()) {
                                            $matrix_rows[] = $option;
                                        }
                                        
                                        if (count($matrix_rows) > 0):
                                        ?>
                                            <div class="alert alert-info mb-3">
                                                <i class="fas fa-info-circle"></i> Please select one option for each statement in the grid below.
                                            </div>
                                            
                                            <div class="table-responsive">
                                                <table class="table table-bordered matrix-table">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="min-width: 200px;">Statement</th>
                                                            <?php foreach ($matrix_columns as $col): ?>
                                                                <th class="text-center" style="min-width: 100px;">
                                                                    <?php echo htmlspecialchars($col); ?>
                                                                </th>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($matrix_rows as $row): ?>
                                                            <tr>
                                                                <td><strong><?php echo htmlspecialchars($row['option_text']); ?></strong></td>
                                                                <?php foreach ($matrix_columns as $col_idx => $col): ?>
                                                                    <td class="text-center">
                                                                        <input type="radio" 
                                                                               name="responses[<?php echo $question['id']; ?>][<?php echo $row['id']; ?>]" 
                                                                               value="<?php echo $col_idx; ?>"
                                                                               class="form-check-input"
                                                                               <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                                                    </td>
                                                                <?php endforeach; ?>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle"></i> This matrix question has no statements configured.
                                            </div>
                                        <?php endif; ?>

                                    <?php elseif ($question['question_type'] === 'date'): ?>
                                        <input type="date" class="form-control" 
                                               name="responses[<?php echo $question['id']; ?>]" 
                                               <?php echo $question['is_required'] ? 'required' : ''; ?>>

                                    <?php elseif ($question['question_type'] === 'date_range'): ?>
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">Start Date</label>
                                                <input type="date" class="form-control" 
                                                       name="responses[<?php echo $question['id']; ?>][start]" 
                                                       <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">End Date</label>
                                                <input type="date" class="form-control" 
                                                       name="responses[<?php echo $question['id']; ?>][end]" 
                                                       <?php echo $question['is_required'] ? 'required' : ''; ?>>
                                            </div>
                                        </div>

                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> 
                                            Question type "<?php echo htmlspecialchars($question['question_type']); ?>" is not supported yet.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php 
                                $question_num++;
                            endwhile; 
                            ?>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-paper-plane"></i> Submit Response
                                </button>
                                <a href="<?php echo SITE_URL; ?>polls.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-arrow-left"></i> Back to Polls
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Poll Info -->
                <div class="card-footer bg-light">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <small class="text-muted">Responses</small>
                            <div class="fw-bold"><?php echo $poll['total_responses']; ?></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Created By</small>
                            <div class="fw-bold"><?php echo htmlspecialchars($poll['first_name'] . ' ' . $poll['last_name']); ?></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Ends</small>
                            <div class="fw-bold">
                                <?php echo $poll['end_date'] ? date('M d, Y', strtotime($poll['end_date'])) : 'No end date'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar with Ads -->
        <div class="col-lg-4">
            <!-- Top Advertisement -->
            <div class="mb-4">
                <small class="text-muted d-block mb-2">Advertisement</small>
                <?php displayAd('poll_page_sidebar'); ?>
            </div>

            <!-- Agent Earning Info (if viewing as agent) -->
            <?php if (isLoggedIn() && getCurrentUser()['role'] === 'agent' && !$already_voted && $poll['status'] === 'active'): ?>
                <?php 
                $price_per_response = floatval($poll['price_per_response'] ?? 0);
                if ($price_per_response > 0): 
                ?>
                <div class="card border-0 shadow-sm mb-4 bg-success text-white">
                    <div class="card-body">
                        <h6 class="mb-2"><i class="fas fa-money-bill-wave"></i> Agent Earning</h6>
                        <h3 class="mb-2">₦<?php echo number_format($price_per_response, 2); ?></h3>
                        <div class="mb-0">
                            <p class="mb-2 small">
                                <i class="fas fa-check-circle"></i> <strong>Complete this poll and earn!</strong><br>
                                You'll receive ₦<?php echo number_format($price_per_response, 2); ?> for answering this poll.
                            </p>
                            <p class="mb-0 small">
                                <i class="fas fa-share-alt"></i> <strong>Share and earn more!</strong><br>
                                <a href="<?php echo SITE_URL; ?>agent/share-poll.php?poll_id=<?php echo $poll_id; ?>" class="text-white text-decoration-underline">
                                    Get your referral link
                                </a> and earn ₦<?php echo number_format($price_per_response, 2); ?> for each person who completes it!
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Poll Stats -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Poll Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Total Responses</small>
                        <h4 class="mb-0 text-primary"><?php echo number_format($poll['total_responses']); ?></h4>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Target Responses</small>
                        <div><?php echo number_format($poll['target_responders'] ?? 100); ?></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Created</small>
                        <div><?php echo date('M d, Y', strtotime($poll['created_at'])); ?></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Ends</small>
                        <div><?php echo $poll['end_date'] ? date('M d, Y', strtotime($poll['end_date'])) : 'No end date'; ?></div>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted">Status</small>
                        <div>
                            <?php if ($poll['status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo ucfirst($poll['status']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Advertisement -->
            <div class="mb-4">
                <small class="text-muted d-block mb-2">Advertisement</small>
                <?php displayAd('poll_page_sidebar'); ?>
            </div>

            <!-- Share Poll -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-share-alt"></i> Share This Poll</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . 'view-poll/' . $poll['slug']); ?>" 
                           target="_blank" class="btn btn-primary btn-sm">
                            <i class="fab fa-facebook"></i> Share on Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . 'view-poll/' . $poll['slug']); ?>&text=<?php echo urlencode($poll['title']); ?>" 
                           target="_blank" class="btn btn-info btn-sm text-white">
                            <i class="fab fa-twitter"></i> Share on Twitter
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode($poll['title'] . ' ' . SITE_URL . 'view-poll/' . $poll['slug']); ?>" 
                           target="_blank" class="btn btn-success btn-sm">
                            <i class="fab fa-whatsapp"></i> Share on WhatsApp
                        </a>
                        <button class="btn btn-secondary btn-sm" onclick="copyPollLink()">
                            <i class="fas fa-copy"></i> Copy Link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rating-group {
    display: flex;
    gap: 10px;
    justify-content: center;
    padding: 20px 0;
}
.rating-star {
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    position: relative;
    user-select: none;
    padding: 10px;
}
.rating-star input {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    cursor: pointer;
    margin: 0;
}
.rating-star .star-icon {
    font-size: 2.5rem;
    color: #ddd;
    line-height: 1;
    transition: color 0.2s;
    pointer-events: none;
}
.rating-star .rating-value {
    font-size: 12px;
    color: #666;
    pointer-events: none;
}
.rating-star:hover .star-icon,
.rating-star.active .star-icon,
.rating-star.hover-active .star-icon {
    color: #ff9800;
}

/* Yes/No and Dichotomous buttons */
.btn-group .btn-check:checked + .btn-outline-success {
    background-color: #10b981;
    border-color: #10b981;
    color: white;
}
.btn-group .btn-check:checked + .btn-outline-danger {
    background-color: #ef4444;
    border-color: #ef4444;
    color: white;
}
.btn-group .btn-check:checked + .btn-outline-primary {
    background-color: #ff6b35;
    border-color: #ff6b35;
    color: white;
}

/* Matrix table styling */
.matrix-table {
    border: 2px solid #dee2e6;
}
.matrix-table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    font-size: 13px;
    text-align: center;
    vertical-align: middle;
    padding: 12px 8px;
    border: 1px solid #dee2e6;
}
.matrix-table tbody td {
    vertical-align: middle;
    padding: 12px 8px;
    border: 1px solid #dee2e6;
}
.matrix-table tbody td:first-child {
    font-weight: 500;
    background-color: #f8f9fa;
}
.matrix-table td input[type="radio"] {
    width: 22px;
    height: 22px;
    cursor: pointer;
    margin: 0;
}
.matrix-table td input[type="radio"]:hover {
    transform: scale(1.1);
}
.matrix-table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Form styling */
.form-control:focus,
.form-check-input:focus {
    border-color: #ff6b35;
    box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
}

.form-check-input:checked {
    background-color: #ff6b35;
    border-color: #ff6b35;
}

/* Question container */
.mb-4.pb-4.border-bottom:last-of-type {
    border-bottom: none !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeRatingStars();
    initializePollFormValidation();
    initializeDateRangeValidation();
});

function initializeRatingStars() {
    const ratingGroups = document.querySelectorAll('.rating-group');
    if (!ratingGroups.length) return;
    
    ratingGroups.forEach(group => {
        const stars = group.querySelectorAll('.rating-star');
        if (!stars.length) return;
        
        const updateActiveState = (ratingValue) => {
            stars.forEach(star => {
                const starValue = parseInt(star.dataset.rating, 10);
                star.classList.toggle('active', starValue <= ratingValue);
            });
        };
        
        stars.forEach(star => {
            const input = star.querySelector('input[type="radio"]');
            const value = parseInt(star.dataset.rating, 10);
            
            star.addEventListener('click', () => {
                if (input) {
                    input.checked = true;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
                updateActiveState(value);
            });
            
            star.addEventListener('mouseenter', () => {
                stars.forEach(item => {
                    const itemValue = parseInt(item.dataset.rating, 10);
                    item.classList.toggle('hover-active', itemValue <= value);
                });
            });
            
            star.addEventListener('mouseleave', () => {
                stars.forEach(item => item.classList.remove('hover-active'));
            });
            
            if (input) {
                input.addEventListener('change', () => updateActiveState(value));
                if (input.checked) {
                    updateActiveState(value);
                }
            }
        });
    });
}

function initializePollFormValidation() {
    const pollForm = document.getElementById('pollForm');
    if (!pollForm) return;
    
    pollForm.addEventListener('submit', function(e) {
        const requiredQuestions = pollForm.querySelectorAll('[required]');
        let allAnswered = true;
        
        requiredQuestions.forEach(question => {
            if (question.type === 'radio' || question.type === 'checkbox') {
                const name = question.name;
                const checked = pollForm.querySelector(`[name="${name}"]:checked`);
                if (!checked) {
                    allAnswered = false;
                }
            } else if (!question.value.trim()) {
                allAnswered = false;
            }
        });
        
        if (!allAnswered) {
            e.preventDefault();
            alert('Please answer all required questions before submitting.');
            return false;
        }
        
        const submitBtn = pollForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        }
    });
}

function initializeDateRangeValidation() {
    const dateRangeInputs = document.querySelectorAll('input[type="date"][name*="[start]"]');
    if (!dateRangeInputs.length) return;
    
    dateRangeInputs.forEach(startInput => {
        const endInputName = startInput.name.replace('[start]', '[end]');
        const endInput = document.querySelector(`input[name="${endInputName}"]`);
        
        if (!endInput) return;
        
        startInput.addEventListener('change', () => {
            endInput.min = startInput.value;
        });
        
        endInput.addEventListener('change', () => {
            if (startInput.value && endInput.value < startInput.value) {
                alert('End date cannot be before start date');
                endInput.value = '';
            }
        });
    });
}

// Ad tracking functions
function trackAdView(adId) {
    fetch('<?php echo SITE_URL; ?>actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=track_ad_view&ad_id=' + adId
    });
}

function trackAdClick(adId) {
    fetch('<?php echo SITE_URL; ?>actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=track_ad_click&ad_id=' + adId
    });
}

// Copy poll link function
function copyPollLink() {
    const pollUrl = '<?php echo SITE_URL . "view-poll/" . $poll["slug"]; ?>';
    navigator.clipboard.writeText(pollUrl).then(() => {
        alert('Poll link copied to clipboard!');
    }).catch(err => {
        prompt('Copy this link:', pollUrl);
    });
}
</script>

<?php include_once 'footer.php'; ?>
