<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole(['client', 'admin']);

$user = getCurrentUser();
$poll_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($poll_id === 0) {
    header('Location: create-poll.php');
    exit;
}

// Verify ownership
$query = "SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) as creator_name 
          FROM polls p 
          JOIN users u ON p.created_by = u.id 
          WHERE p.id = $poll_id AND p.created_by = {$user['id']}";

$result = $conn->query($query);

if (!$result) {
    die('Database error: ' . $conn->error);
}

$poll = $result->fetch_assoc();

if (!$poll) {
    die('Poll not found or access denied');
}

// Get category name if exists
if ($poll['category_id']) {
    $cat_result = $conn->query("SELECT name FROM categories WHERE id = {$poll['category_id']}");
    if ($cat_result && $cat_row = $cat_result->fetch_assoc()) {
        $poll['category_name'] = $cat_row['name'];
    }
}

$questions = $conn->query("SELECT * FROM poll_questions WHERE poll_id = $poll_id ORDER BY question_order");

$page_title = 'Review & Publish Poll';
include '../header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Progress Steps -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="create-poll.php?id=<?= $poll_id ?>" class="step-item completed text-decoration-none" title="Back to Poll Details">
                            <div class="step-circle"><i class="fas fa-check"></i></div>
                            <div class="step-label">Poll Details</div>
                        </a>
                        <div class="step-line completed"></div>
                        <a href="add-questions.php?id=<?= $poll_id ?>" class="step-item completed text-decoration-none" title="Back to Questions">
                            <div class="step-circle"><i class="fas fa-check"></i></div>
                            <div class="step-label">Add Questions</div>
                        </a>
                        <div class="step-line completed"></div>
                        <div class="step-item active">
                            <div class="step-circle">3</div>
                            <div class="step-label">Review & Publish</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Poll Review -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Review Your Poll</h5>
                </div>
                <div class="card-body">
                    <!-- Poll Details -->
                    <div class="mb-4">
                        <h4><?= htmlspecialchars($poll['title']) ?></h4>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($poll['description'])) ?></p>
                        
                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Category</small>
                                <span class="badge bg-primary"><?= htmlspecialchars($poll['category_name'] ?? 'Uncategorized') ?></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Type</small>
                                <span class="badge bg-secondary"><?= ucwords($poll['poll_type']) ?></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Start Date</small>
                                <strong><?= date('M d, Y', strtotime($poll['start_date'])) ?></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">End Date</small>
                                <strong><?= date('M d, Y', strtotime($poll['end_date'])) ?></strong>
                            </div>
                            <?php if ($poll['target_responders']): ?>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Target Responses</small>
                                <strong><?= number_format($poll['target_responders']) ?></strong>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Settings</small>
                                <div>
                                    <?php if ($poll['require_participant_names']): ?>
                                        <span class="badge bg-info">Requires Names</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Anonymous</span>
                                    <?php endif; ?>
                                    <?php if ($poll['allow_multiple_options']): ?>
                                        <span class="badge bg-warning">Multiple Options</span>
                                    <?php endif; ?>
                                    <?php if ($poll['allow_comments']): ?>
                                        <span class="badge bg-secondary">Comments Allowed</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Questions Preview -->
                    <h6 class="mb-3">Questions (<?= $questions->num_rows ?>)</h6>
                    <?php $q_num = 1; while ($q = $questions->fetch_assoc()): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6>Q<?= $q_num++ ?>. <?= htmlspecialchars($q['question_text']) ?></h6>
                                <small class="text-muted">
                                    Type: <span class="badge bg-secondary"><?= ucwords(str_replace('_', ' ', $q['question_type'])) ?></span>
                                    <?= $q['is_required'] ? '<span class="badge bg-warning">Required</span>' : '' ?>
                                </small>
                                
                                <?php if ($q['question_type'] === 'multiple_choice'): ?>
                                    <?php $options = $conn->query("SELECT * FROM poll_question_options WHERE question_id = {$q['id']} ORDER BY option_order"); ?>
                                    <div class="mt-3">
                                        <?php while ($opt = $options->fetch_assoc()): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" disabled>
                                                <label class="form-check-label"><?= htmlspecialchars($opt['option_text']) ?></label>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php elseif ($q['question_type'] === 'text'): ?>
                                    <textarea class="form-control mt-3" rows="2" placeholder="Respondent's text answer will appear here..." disabled></textarea>
                                <?php elseif ($q['question_type'] === 'rating'): ?>
                                    <div class="mt-3">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star text-warning"></i>
                                        <?php endfor; ?>
                                    </div>
                                <?php elseif ($q['question_type'] === 'yes_no'): ?>
                                    <div class="mt-3">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" disabled>
                                            <label class="form-check-label">Yes</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" disabled>
                                            <label class="form-check-label">No</label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="add-questions.php?id=<?= $poll_id ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Questions
                        </a>
                        <div>
                            <a href="../actions.php?action=save_draft&poll_id=<?= $poll_id ?>" class="btn btn-outline-primary me-2">
                                <i class="fas fa-save"></i> Save as Draft
                            </a>
                            <a href="../actions.php?action=publish_poll&poll_id=<?= $poll_id ?>" 
                               class="btn btn-success"
                               onclick="return confirm('Are you sure you want to publish this poll? It will be visible to agents.')">
                                <i class="fas fa-rocket"></i> Publish Poll
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="mb-3">After Publishing</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Poll will be visible in the marketplace</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Agents can start responding based on your criteria</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> You'll receive real-time notifications</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Analytics will be available on your dashboard</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.step-item {
    text-align: center;
    flex: 0 0 auto;
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--gray-200);
    color: var(--gray-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin: 0 auto 8px;
    font-size: 14px;
}

.step-item.active .step-circle {
    background: var(--primary);
    color: white;
}

.step-item.completed .step-circle {
    background: var(--success);
    color: white;
}

.step-item.completed:hover .step-circle {
    background: var(--primary);
    transform: scale(1.1);
    transition: all 0.2s;
}

.step-item.completed:hover .step-label {
    color: var(--primary);
}

.step-label {
    font-size: 12px;
    color: var(--gray-600);
    font-weight: 500;
}

.step-line {
    flex: 1;
    height: 2px;
    background: var(--gray-200);
    margin: 0 15px;
    align-self: center;
    margin-bottom: 28px;
}

.step-line.completed {
    background: var(--success);
}
</style>

<?php include '../footer.php'; ?>
