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

// Check follow status for button states
$is_following_creator = false;
$is_following_category = false;
$is_bookmarked = false;

// Get creator profile information
$creator_info = null;
$creator_followers_count = 0;
$creator_following_count = 0;
$creator_polls_count = 0;

if (isLoggedIn()) {
    $current_user_id = getCurrentUser()['id'];

    // Check if following creator
    $creator_check = $conn->query("SELECT id FROM user_follows WHERE follower_id = $current_user_id AND following_id = {$poll['created_by']}");
    $is_following_creator = $creator_check && $creator_check->num_rows > 0;

    // Check if following category
    if ($poll['category_id']) {
        $category_check = $conn->query("SELECT id FROM user_category_follows WHERE user_id = $current_user_id AND category_id = {$poll['category_id']}");
        $is_following_category = $category_check && $category_check->num_rows > 0;
    }

    // Check if poll is bookmarked
    $bookmark_check = $conn->query("SELECT id FROM user_bookmarks WHERE user_id = $current_user_id AND poll_id = {$poll['id']}");
    $is_bookmarked = $bookmark_check && $bookmark_check->num_rows > 0;
}

// Get creator detailed information
$creator_query = $conn->query("SELECT * FROM users WHERE id = {$poll['created_by']}");
if ($creator_query && $creator_query->num_rows > 0) {
    $creator_info = $creator_query->fetch_assoc();

    // Get followers count (people following this creator)
    $followers_query = $conn->query("SELECT COUNT(*) as count FROM user_follows WHERE following_id = {$poll['created_by']}");
    $creator_followers_count = $followers_query ? $followers_query->fetch_assoc()['count'] : 0;

    // Get following count (people this creator follows)
    $following_query = $conn->query("SELECT COUNT(*) as count FROM user_follows WHERE follower_id = {$poll['created_by']}");
    $creator_following_count = $following_query ? $following_query->fetch_assoc()['count'] : 0;

    // Get polls count (polls created by this user)
    $polls_query = $conn->query("SELECT COUNT(*) as count FROM polls WHERE created_by = {$poll['created_by']}");
    $creator_polls_count = $polls_query ? $polls_query->fetch_assoc()['count'] : 0;
}

// Get poll questions
$questions = $conn->query("SELECT * FROM poll_questions WHERE poll_id = $poll_id ORDER BY question_order");

// Related polls now only show polls from the same category as the current poll

// Build related polls query - prioritize same category, fallback to popular polls
$where_conditions = ["p.status = 'active'", "p.id != $poll_id"];
$order_parts = [];

// Prioritize polls from the same category, but don't exclude others completely
if ($poll['category_id']) {
    // First try to get polls from same category
    $same_category_query = "SELECT DISTINCT p.*, c.name as category_name, u.first_name, u.last_name
                           FROM polls p
                           LEFT JOIN categories c ON p.category_id = c.id
                           JOIN users u ON p.created_by = u.id
                           WHERE p.status = 'active' AND p.id != $poll_id AND p.category_id = {$poll['category_id']}
                           ORDER BY p.total_responses DESC, p.created_at DESC
                           LIMIT 5";

    $same_category_result = $conn->query($same_category_query);

    if ($same_category_result && $same_category_result->num_rows >= 3) {
        // If we have at least 3 polls from same category, use them
        $related_polls = $same_category_result;
    } else {
        // Otherwise, get a mix of same category + popular polls
        $where_conditions[] = "(p.category_id = {$poll['category_id']} OR p.category_id IS NOT NULL)";
        // Sort by category match first, then by popularity
        $order_parts[] = "CASE WHEN p.category_id = {$poll['category_id']} THEN 0 ELSE 1 END";
        $order_parts[] = "p.total_responses DESC";
        $order_parts[] = "p.created_at DESC";

        $related_polls_query = "SELECT DISTINCT p.*, c.name as category_name, u.first_name, u.last_name
                               FROM polls p
                               LEFT JOIN categories c ON p.category_id = c.id
                               JOIN users u ON p.created_by = u.id
                               WHERE " . implode(' AND ', array_unique($where_conditions)) . "
                               ORDER BY " . implode(', ', $order_parts) . "
                               LIMIT 5";

        $related_polls = $conn->query($related_polls_query);
    }
} else {
    // If current poll has no category, show popular polls from any category
    $order_parts[] = "p.total_responses DESC";
    $order_parts[] = "p.created_at DESC";

    $related_polls_query = "SELECT DISTINCT p.*, c.name as category_name, u.first_name, u.last_name
                           FROM polls p
                           LEFT JOIN categories c ON p.category_id = c.id
                           JOIN users u ON p.created_by = u.id
                           WHERE " . implode(' AND ', array_unique($where_conditions)) . "
                           ORDER BY " . implode(', ', $order_parts) . "
                           LIMIT 5";

    $related_polls = $conn->query($related_polls_query);
}

$related_polls = $conn->query($related_polls_query);

// Get comments count for this poll
$comments_count = 0;
if ($poll['allow_comments']) {
    $comments_result = $conn->query("SELECT COUNT(*) as count FROM poll_comments WHERE poll_id = $poll_id");
    $comments_count = $comments_result ? $comments_result->fetch_assoc()['count'] : 0;
}

// Check if user already voted
$already_voted = false;
if (isLoggedIn()) {
    $user_id = getCurrentUser()['id'];
    $check = $conn->query("SELECT id FROM poll_responses WHERE poll_id = $poll_id AND respondent_id = $user_id");
    $already_voted = $check && $check->num_rows > 0;
} elseif ($poll['one_vote_per_ip']) {
    // Only restrict non-logged-in users if poll has IP restriction enabled
    $ip = $_SERVER['REMOTE_ADDR'];
    $check = $conn->query("SELECT id FROM poll_responses WHERE poll_id = $poll_id AND respondent_ip = '$ip'");
    $already_voted = $check && $check->num_rows > 0;
}
// Non-logged-in users can participate freely unless poll has IP restriction

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
                    <div class="mb-2 d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark">
                            <?php echo htmlspecialchars($poll['category_name'] ?? 'General'); ?>
                        </span>
                        
                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars($poll['poll_type']); ?></span>

                        <?php if (isLoggedIn() && $poll['category_id']): ?>
                                <button type="button"
                                        class="btn btn-sm <?php echo $is_following_category ? 'btn-outline-secondary' : 'btn-outline-primary'; ?> follow-category-btn"
                                        data-category-id="<?php echo $poll['category_id']; ?>"
                                        <?php echo $is_following_category ? 'disabled' : ''; ?>>
                                    <i class="fas fa-<?php echo $is_following_category ? 'star' : 'plus'; ?> me-1"></i>
                                    <?php echo $is_following_category ? 'Following' : 'Follow Category'; ?>
                                </button>
                        <?php endif; ?>

                        <?php if (isLoggedIn()): ?>
                                <button type="button"
                                        class="btn btn-sm <?php echo $is_bookmarked ? 'btn-warning' : 'btn-outline-warning'; ?> bookmark-poll-btn ms-2"
                                        data-poll-id="<?php echo $poll['id']; ?>">
                                    <i class="fa<?php echo $is_bookmarked ? 's' : 'r'; ?> fa-bookmark me-1"></i>
                                    <?php echo $is_bookmarked ? 'Bookmarked' : 'Bookmark'; ?>
                                </button>
                        <?php endif; ?>
                    </div>
                    <h2 class="mb-2"><?php echo htmlspecialchars($poll['title']); ?></h2>
                    <p class="mb-3"><?php echo nl2br(htmlspecialchars($poll['description'])); ?></p>

                    <!-- Poll Image -->
                    <?php if (!empty($poll['image']) && file_exists('uploads/polls/' . $poll['image'])): ?>
                        <div class="mb-3">
                            <img src="<?php echo SITE_URL; ?>uploads/polls/<?php echo $poll['image']; ?>"
                                 alt="Poll Image" class="img-fluid rounded shadow-sm" style="max-height: 400px; width: auto; display: block; margin: 0 auto;">
                        </div>
                    <?php endif; ?>
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
                                <?php if (isLoggedIn()): ?>
                                    <button type="button" class="btn btn-outline-danger btn-lg ms-2" data-bs-toggle="modal" data-bs-target="#reportModal">
                                        <i class="fas fa-flag"></i> Report Poll
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Poll Info -->
                <div class="card-footer bg-light">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <small class="text-muted">Responses</small>
                            <div class="fw-bold"><?php echo $poll['total_responses']; ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Questions</small>
                            <div class="fw-bold"><?php echo getPollQuestionCount($poll['id']); ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Created By</small>
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <span class="fw-bold"><?php echo htmlspecialchars($poll['first_name'] . ' ' . $poll['last_name']); ?></span>
                                <?php if (isLoggedIn() && getCurrentUser()['id'] != $poll['created_by']): ?>
                                    <button type="button"
                                            class="btn btn-sm <?php echo $is_following_creator ? 'btn-outline-secondary' : 'btn-outline-primary'; ?> follow-user-btn"
                                            data-user-id="<?php echo $poll['created_by']; ?>"
                                            <?php echo $is_following_creator ? 'disabled' : ''; ?>>
                                        <i class="fas fa-<?php echo $is_following_creator ? 'user-check' : 'user-plus'; ?> me-1"></i>
                                        <?php echo $is_following_creator ? 'Following' : 'Follow Creator'; ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
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

            <!-- Creator Profile -->
            <?php if ($creator_info): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-user"></i> Poll Creator</h6>
                </div>
                <div class="card-body text-center">
                    <!-- Creator Photo -->
                    <div class="mb-3">
                        <?php if (!empty($creator_info['profile_image']) && file_exists('uploads/profiles/' . $creator_info['profile_image'])): ?>
                            <img src="<?php echo SITE_URL; ?>uploads/profiles/<?php echo $creator_info['profile_image']; ?>"
                                 alt="Creator Photo" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                 style="width: 80px; height: 80px; font-size: 32px;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Creator Name -->
                    <h6 class="mb-1"><?php echo htmlspecialchars($creator_info['first_name'] . ' ' . $creator_info['last_name']); ?></h6>

                    <!-- Creator Role -->
                    <?php if (!empty($creator_info['role'])): ?>
                        <p class="text-muted small mb-3">
                            <span class="badge bg-secondary"><?php echo ucfirst($creator_info['role']); ?></span>
                        </p>
                    <?php endif; ?>

                    <!-- Creator Stats -->
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="fw-bold text-primary"><?php echo number_format($creator_followers_count); ?></div>
                            <small class="text-muted">Followers</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-primary"><?php echo number_format($creator_following_count); ?></div>
                            <small class="text-muted">Following</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold text-primary"><?php echo number_format($creator_polls_count); ?></div>
                            <small class="text-muted">Polls</small>
                        </div>
                    </div>

                    <!-- Follow Button (only if not the creator themselves) -->
                    <?php if (isLoggedIn() && getCurrentUser()['id'] != $poll['created_by']): ?>
                        <button type="button"
                                class="btn btn-sm <?php echo $is_following_creator ? 'btn-outline-secondary' : 'btn-outline-primary'; ?> follow-user-btn w-100"
                                data-user-id="<?php echo $poll['created_by']; ?>"
                                <?php echo $is_following_creator ? 'disabled' : ''; ?>>
                            <i class="fas fa-<?php echo $is_following_creator ? 'user-check' : 'user-plus'; ?> me-1"></i>
                            <?php echo $is_following_creator ? 'Following' : 'Follow Creator'; ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
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

            <!-- Comments Section -->
            <?php if ($poll['allow_comments']): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-comments"></i> Comments (<?php echo $comments_count ?? 0; ?>)</h6>
                </div>
                <div class="card-body">
                    <?php if (isLoggedIn()): ?>
                        <!-- Add Comment Form -->
                        <form id="commentForm" class="mb-4">
                            <input type="hidden" name="action" value="add_comment">
                            <input type="hidden" name="poll_id" value="<?php echo $poll_id; ?>">
                            <div class="mb-3">
                                <label for="comment_text" class="form-label">Share your thoughts</label>
                                <textarea class="form-control" id="comment_text" name="comment_text" rows="3" placeholder="Write a comment..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-paper-plane"></i> Post Comment
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <a href="<?php echo SITE_URL; ?>login.php">Login</a> to leave a comment.
                        </div>
                    <?php endif; ?>

                    <!-- Comments List -->
                    <div id="comments-list">
                        <?php
                        $comments_query = $conn->query("SELECT c.*, u.first_name, u.last_name, u.profile_image
                                                       FROM poll_comments c
                                                       JOIN users u ON c.user_id = u.id
                                                       WHERE c.poll_id = $poll_id
                                                       ORDER BY c.created_at DESC");
                        if ($comments_query && $comments_query->num_rows > 0):
                            while ($comment = $comments_query->fetch_assoc()):
                        ?>
                            <div class="comment-item border-bottom pb-3 mb-3">
                                <div class="d-flex align-items-start">
                                    <img src="<?php echo $comment['profile_image'] ? SITE_URL . 'uploads/profiles/' . $comment['profile_image'] : SITE_URL . 'assets/images/default-avatar.png'; ?>"
                                         class="rounded-circle me-3" alt="Avatar" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?></strong>
                                                <small class="text-muted ms-2"><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></small>
                                            </div>
                                            <?php if (isLoggedIn() && getCurrentUser()['id'] == $comment['user_id']): ?>
                                                <button class="btn btn-sm btn-outline-danger delete-comment-btn" data-comment-id="<?php echo $comment['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php
                            endwhile;
                        else:
                        ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-comments fa-2x mb-2"></i>
                                <p>No comments yet. Be the first to share your thoughts!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Related Polls -->
            <?php if ($related_polls && $related_polls->num_rows > 0): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-list"></i> Related Polls</h6>
                </div>
                <div class="card-body p-0">
                    <?php while ($related_poll = $related_polls->fetch_assoc()): ?>
                        <div class="border-bottom p-3">
                            <div class="d-flex align-items-start">
                                <?php if (!empty($related_poll['image'])): ?>
                                    <img src="<?php echo SITE_URL . 'uploads/polls/' . $related_poll['image']; ?>"
                                         class="rounded me-3" alt="Poll image" style="width: 60px; height: 60px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-gradient text-white rounded me-3 d-flex align-items-center justify-content-center"
                                         style="width: 60px; height: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                        <i class="fas fa-poll"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="flex-grow-1 min-w-0">
                                    <h6 class="mb-1 text-truncate">
                                        <a href="<?php echo SITE_URL; ?>view-poll/<?php echo $related_poll['slug']; ?>"
                                           class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars(substr($related_poll['title'], 0, 50)); ?>
                                            <?php if (strlen($related_poll['title']) > 50): ?>...<?php endif; ?>
                                        </a>
                                    </h6>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-primary badge-sm me-1"><?php echo htmlspecialchars($related_poll['category_name'] ?? 'General'); ?></span>
                                        <small class="text-muted">
                                            <i class="fas fa-users"></i> <?php echo $related_poll['total_responses']; ?>
                                        </small>
                                    </div>
                                    <p class="mb-2 small text-muted">
                                        <?php echo htmlspecialchars(substr($related_poll['description'], 0, 80)); ?>
                                        <?php if (strlen($related_poll['description']) > 80): ?>...<?php endif; ?>
                                    </p>
                                    <a href="<?php echo SITE_URL; ?>view-poll/<?php echo $related_poll['slug']; ?>"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-vote-yea"></i> Participate
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

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

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportModalLabel">Report Poll</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reportForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="report_poll">
                    <input type="hidden" name="poll_id" value="<?php echo $poll_id; ?>">
                    <div class="mb-3">
                        <label for="reportReason" class="form-label">Reason for reporting</label>
                        <select class="form-select" id="reportReason" name="reason" required>
                            <option value="">Select a reason...</option>
                            <option value="spam">Spam or misleading content</option>
                            <option value="inappropriate">Inappropriate content</option>
                            <option value="harassment">Harassment or hate speech</option>
                            <option value="copyright">Copyright violation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="reportDescription" class="form-label">Additional details (optional)</label>
                        <textarea class="form-control" id="reportDescription" name="description" rows="3" placeholder="Please provide more details about why you're reporting this poll..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit Report</button>
                </div>
            </form>
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

/* Related polls styling */
.badge-sm {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

/* Follow system styling */
.follow-user-btn,
.follow-category-btn {
    font-size: 0.75rem;
    padding: 0.2rem 0.5rem;
    border-radius: 20px;
    transition: all 0.2s ease;
}

.follow-user-btn:hover,
.follow-category-btn:hover {
    transform: translateY(-1px);
}

.follow-btn {
    font-size: 0.75rem;
    padding: 0.2rem 0.5rem;
    border-radius: 20px;
    transition: all 0.2s ease;
}

.follow-btn:hover {
    transform: translateY(-1px);
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

// Simple follow functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle user follow buttons (only if not already following)
    document.querySelectorAll('.follow-user-btn:not([disabled])').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            console.log('Follow button clicked, userId:', userId);

            // Send AJAX request
            fetch('<?php echo SITE_URL; ?>actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=follow_user&following_id=${userId}`
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text(); // First get as text to check if it's valid JSON
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    if (data.success) {
                        // Update button appearance to show followed state
                        btn.classList.remove('btn-outline-primary');
                        btn.classList.add('btn-outline-secondary');
                        btn.innerHTML = '<i class="fas fa-user-check me-1"></i>Following';
                        btn.disabled = true; // Prevent multiple clicks
                        alert('You have followed this creator!');
                    } else {
                        alert(data.message || 'Failed to follow creator');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    alert('Server returned invalid response. Check console for details.');
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                alert('Network error: ' + error.message + '. Please check console for details.');
            });
        });
    });

    // Handle category follow buttons (only if not already following)
    document.querySelectorAll('.follow-category-btn:not([disabled])').forEach(btn => {
        btn.addEventListener('click', function() {
            const categoryId = this.dataset.categoryId;
            console.log('Category follow button clicked, categoryId:', categoryId);

            // Send AJAX request
            fetch('<?php echo SITE_URL; ?>actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=follow_category&category_id=${categoryId}`
            })
            .then(response => {
                console.log('Category response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Category raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Category parsed data:', data);
                    if (data.success) {
                        // Update button appearance to show followed state
                        btn.classList.remove('btn-outline-primary');
                        btn.classList.add('btn-outline-secondary');
                        btn.innerHTML = '<i class="fas fa-star me-1"></i>Following';
                        btn.disabled = true; // Prevent multiple clicks
                        alert('You have followed this category!');
                    } else {
                        alert(data.message || 'Failed to follow category');
                    }
                } catch (e) {
                    console.error('Category JSON parse error:', e);
                    alert('Server returned invalid response. Check console for details.');
                }
            })
            .catch(error => {
                console.error('Category network error:', error);
                alert('Network error: ' + error.message + '. Please check console for details.');
            });
        });
    });

    // Handle bookmark poll buttons
    document.querySelectorAll('.bookmark-poll-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const pollId = this.dataset.pollId;
            const isCurrentlyBookmarked = this.classList.contains('btn-warning');
            const action = isCurrentlyBookmarked ? 'unbookmark_poll' : 'bookmark_poll';

            console.log('Bookmark button clicked, pollId:', pollId, 'action:', action);

            // Send AJAX request
            fetch('<?php echo SITE_URL; ?>actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=${action}&poll_id=${pollId}`
            })
            .then(response => {
                console.log('Bookmark response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Bookmark raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Bookmark parsed data:', data);
                    if (data.success) {
                        // Update button appearance
                        if (data.action === 'bookmarked') {
                            // Now bookmarked
                            btn.classList.remove('btn-outline-warning');
                            btn.classList.add('btn-warning');
                            btn.innerHTML = '<i class="fas fa-bookmark me-1"></i>Bookmarked';
                        } else if (data.action === 'unbookmarked') {
                            // Now unbookmarked
                            btn.classList.remove('btn-warning');
                            btn.classList.add('btn-outline-warning');
                            btn.innerHTML = '<i class="far fa-bookmark me-1"></i>Bookmark';
                        }
                        alert(data.message);
                    } else {
                        alert(data.message || 'Failed to update bookmark');
                    }
                } catch (e) {
                    console.error('Bookmark JSON parse error:', e);
                    alert('Server returned invalid response. Check console for details.');
                }
            })
            .catch(error => {
                console.error('Bookmark network error:', error);
                alert('Network error: ' + error.message + '. Please check console for details.');
            });
        });
    });
});

// Report functionality
document.getElementById('reportForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const form = this;
    const reasonSelect = form.querySelector('#reportReason');

    // Check if form is valid
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    // Check if reason is selected
    if (!reasonSelect.value) {
        alert('Please select a reason for reporting.');
        reasonSelect.focus();
        return;
    }

    const formData = new FormData(this);
    console.log('Report form data:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
    }

    fetch('<?php echo SITE_URL; ?>actions.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        console.log('Report response status:', response.status);
        console.log('Report response headers:', response.headers);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('Report raw response:', text);
        try {
            const data = JSON.parse(text);
            console.log('Report parsed data:', data);
                if (data.success) {
                    alert('Report submitted successfully. Thank you for helping keep our community safe!');
                    // Close modal safely
                    try {
                        const modalElement = document.getElementById('reportModal');
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        } else {
                            // Fallback: hide using jQuery or direct method
                            modalElement.style.display = 'none';
                            document.body.classList.remove('modal-open');
                            const backdrop = document.querySelector('.modal-backdrop');
                            if (backdrop) backdrop.remove();
                        }
                        // Reset form
                        document.getElementById('reportForm').reset();
                    } catch (e) {
                        console.error('Modal close error:', e);
                        // Still reset form even if modal close fails
                        document.getElementById('reportForm').reset();
                    }
            } else {
                alert(data.message || 'Failed to submit report');
            }
        } catch (e) {
            console.error('Report JSON parse error:', e);
            alert('Server returned invalid response. Check console for details.');
        }
    })
    .catch(error => {
        console.error('Report network error:', error);
        alert('Network error: ' + error.message + '. Please check console for details.');
    });
});

// Comment form handling
document.getElementById('commentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';

    fetch('<?php echo SITE_URL; ?>actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to show the new comment
            location.reload();
        } else {
            alert(data.message || 'Failed to add comment');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Comment error:', error);
        alert('Network error. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// Delete comment handling
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('delete-comment-btn') || e.target.closest('.delete-comment-btn')) {
        const btn = e.target.classList.contains('delete-comment-btn') ? e.target : e.target.closest('.delete-comment-btn');
        const commentId = btn.getAttribute('data-comment-id');

        if (confirm('Are you sure you want to delete this comment?')) {
            const formData = new FormData();
            formData.append('action', 'delete_comment');
            formData.append('comment_id', commentId);

            fetch('<?php echo SITE_URL; ?>actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the comment from the DOM
                    btn.closest('.comment-item').remove();
                    // Update comment count if it exists
                    const commentCountEl = document.querySelector('h6 i.fa-comments').parentElement;
                    if (commentCountEl) {
                        const currentCount = parseInt(commentCountEl.textContent.match(/\d+/)[0]);
                        commentCountEl.innerHTML = `<i class="fas fa-comments"></i> Comments (${currentCount - 1})`;
                    }
                } else {
                    alert(data.message || 'Failed to delete comment');
                }
            })
            .catch(error => {
                console.error('Delete comment error:', error);
                alert('Network error. Please try again.');
            });
        }
    }
});

// Simple follow functions - no complex state management needed
</script>

<?php include_once 'footer.php'; ?>
