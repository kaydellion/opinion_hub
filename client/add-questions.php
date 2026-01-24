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
$poll = $conn->query("SELECT * FROM polls WHERE id = $poll_id AND created_by = {$user['id']}")->fetch_assoc();
if (!$poll) {
    die('Poll not found or access denied');
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Get existing questions
$questions = $conn->query("SELECT * FROM poll_questions WHERE poll_id = $poll_id ORDER BY question_order");

$page_title = 'Add Questions';
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
                        <div class="step-item active">
                            <div class="step-circle">2</div>
                            <div class="step-label">Add Questions</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <div class="step-label">Review & Publish</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Add Questions to: <?= htmlspecialchars($poll['title']) ?></h5>
                    <span class="badge bg-primary"><?= $questions->num_rows ?> Questions</span>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <!-- Add Question Form -->
                    <form method="POST" action="../actions.php?action=add_question" id="questionForm">
                        <input type="hidden" name="poll_id" value="<?= $poll_id ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Question Text *</label>
                            <textarea name="question_text" class="form-control" rows="3" required placeholder="Enter your question..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description (Optional)</label>
                            <textarea name="question_description" class="form-control" rows="2" placeholder="Provide additional context or instructions for this question..."></textarea>
                            <small class="text-muted">This description will be shown below the question text to provide additional context.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Question Image (Optional)</label>
                            <div class="input-group">
                                <input type="url" name="question_image" class="form-control" placeholder="https://example.com/image.jpg" id="imageUrlInput">
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleImageInput()">
                                    <i class="fas fa-upload"></i> Upload
                                </button>
                            </div>
                            <small class="text-muted">Add an image URL or click Upload to select a file from your device.</small>
                            <div id="imagePreview" class="mt-2" style="display: none;">
                                <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 150px;">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Question Type *</label>
                                <select name="question_type" id="questionType" class="form-select" required>
                                    <option value="multiple_choice">Multiple Choice</option>
                                    <option value="rating">Rating (1-5 Stars)</option>
                                    <option value="open_ended">Open-ended (Text)</option>
                                    <option value="word_cloud">Word Cloud</option>
                                    <option value="quiz">Quiz (with correct answer)</option>
                                    <option value="assessment">Assessment</option>
                                    <option value="yes_no">Yes/No</option>
                                    <option value="multiple_answer">Multiple Answer (Select all that apply)</option>
                                    <option value="dichotomous">Dichotomous (Two options)</option>
                                    <option value="matrix">Matrix (Grid)</option>
                                    <option value="date">Date</option>
                                    <option value="date_range">Date Range</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Required</label>
                                <select name="is_required" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Options for Multiple Choice -->
                        <div id="optionsContainer" class="mb-3">
                            <label class="form-label">Answer Options</label>
                            <div id="optionsList">
                                <div class="input-group mb-2">
                                    <input type="text" name="options[]" class="form-control" placeholder="Option 1">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="input-group mb-2">
                                    <input type="text" name="options[]" class="form-control" placeholder="Option 2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addOption()">
                                <i class="fas fa-plus"></i> Add Option
                            </button>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Add Question
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <!-- Existing Questions -->
                    <h6 class="mb-3">Added Questions</h6>
                    <?php if ($questions->num_rows > 0): ?>
                        <div class="list-group">
                            <?php $q_num = 1; while ($q = $questions->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">Q<?= $q_num++ ?>. <?= htmlspecialchars($q['question_text']) ?></h6>
                                            <?php if (!empty($q['question_description'])): ?>
                                                <p class="mb-2 small text-muted"><?= htmlspecialchars($q['question_description']) ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($q['question_image'])): ?>
                                                <div class="mb-2">
                                                    <img src="<?= htmlspecialchars($q['question_image']) ?>" alt="Question image" class="img-thumbnail" style="max-width: 100px; max-height: 75px;">
                                                </div>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                Type: <span class="badge bg-secondary"><?= ucwords(str_replace('_', ' ', $q['question_type'])) ?></span>
                                                <?= $q['is_required'] ? '<span class="badge bg-warning">Required</span>' : '' ?>
                                            </small>
                                            
                                            <?php if (in_array($q['question_type'], ['multiple_choice', 'multiple_answer', 'quiz'])): ?>
                                                <?php
                                                $options = $conn->query("SELECT * FROM poll_question_options WHERE question_id = {$q['id']} ORDER BY option_order");
                                                ?>
                                                <div class="mt-2">
                                                    <?php while ($opt = $options->fetch_assoc()): ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="<?= $q['question_type'] === 'multiple_answer' ? 'checkbox' : 'radio' ?>" disabled>
                                                            <label class="form-check-label small">
                                                                <?= htmlspecialchars($opt['option_text']) ?>
                                                                <?php if ($q['question_type'] === 'quiz' && $opt['is_correct_answer']): ?>
                                                                    <i class="fas fa-check-circle text-success"></i>
                                                                <?php endif; ?>
                                                            </label>
                                                        </div>
                                                    <?php endwhile; ?>
                                                </div>
                                            <?php elseif ($q['question_type'] === 'dichotomous'): ?>
                                                <?php
                                                $options = $conn->query("SELECT * FROM poll_question_options WHERE question_id = {$q['id']} ORDER BY option_order LIMIT 2");
                                                ?>
                                                <div class="mt-2">
                                                    <?php while ($opt = $options->fetch_assoc()): ?>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" disabled>
                                                            <label class="form-check-label small"><?= htmlspecialchars($opt['option_text']) ?></label>
                                                        </div>
                                                    <?php endwhile; ?>
                                                </div>
                                            <?php elseif ($q['question_type'] === 'open_ended' || $q['question_type'] === 'word_cloud'): ?>
                                                <textarea class="form-control mt-3" rows="2" placeholder="<?= $q['question_type'] === 'word_cloud' ? 'Enter a word or phrase...' : 'Respondent\'s text answer will appear here...' ?>" disabled></textarea>
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
                                            <?php elseif ($q['question_type'] === 'date'): ?>
                                                <input type="date" class="form-control mt-3" disabled>
                                            <?php elseif ($q['question_type'] === 'date_range'): ?>
                                                <div class="mt-3 row">
                                                    <div class="col-md-6">
                                                        <label class="form-label small">Start Date</label>
                                                        <input type="date" class="form-control" disabled>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small">End Date</label>
                                                        <input type="date" class="form-control" disabled>
                                                    </div>
                                                </div>
                                            <?php elseif ($q['question_type'] === 'matrix'): ?>
                                                <?php
                                                $options = $conn->query("SELECT * FROM poll_question_options WHERE question_id = {$q['id']} ORDER BY option_order");
                                                ?>
                                                <div class="table-responsive mt-3">
                                                    <table class="table table-sm table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th></th>
                                                                <th>Poor</th>
                                                                <th>Fair</th>
                                                                <th>Good</th>
                                                                <th>Excellent</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php while ($opt = $options->fetch_assoc()): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($opt['option_text']) ?></td>
                                                                    <td><input type="radio" disabled></td>
                                                                    <td><input type="radio" disabled></td>
                                                                    <td><input type="radio" disabled></td>
                                                                    <td><input type="radio" disabled></td>
                                                                </tr>
                                                            <?php endwhile; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php elseif ($q['question_type'] === 'assessment'): ?>
                                                <div class="mt-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" disabled>
                                                        <label class="form-check-label">Not Confident</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" disabled>
                                                        <label class="form-check-label">Slightly Confident</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" disabled>
                                                        <label class="form-check-label">Moderately Confident</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" disabled>
                                                        <label class="form-check-label">Very Confident</label>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <a href="../actions.php?action=delete_question&id=<?= $q['id'] ?>&poll_id=<?= $poll_id ?>" 
                                           class="btn btn-outline-danger btn-sm"
                                           onclick="return confirm('Delete this question?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No questions added yet. Add at least one question to proceed.
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between">
                        <a href="create-poll.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <?php if ($questions->num_rows > 0): ?>
                            <a href="review-poll.php?id=<?= $poll_id ?>" class="btn btn-primary">
                                Continue <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                Add questions to continue
                            </button>
                        <?php endif; ?>
                    </div>
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

<script>
const questionType = document.getElementById('questionType');
const optionsContainer = document.getElementById('optionsContainer');

questionType.addEventListener('change', function() {
    // Show options for question types that need them
    const typesNeedingOptions = ['multiple_choice', 'multiple_answer', 'dichotomous', 'quiz', 'matrix'];
    if (typesNeedingOptions.includes(this.value)) {
        optionsContainer.style.display = 'block';

        // For dichotomous, limit to 2 options
        if (this.value === 'dichotomous') {
            const optionsList = document.getElementById('optionsList');
            while (optionsList.children.length > 2) {
                optionsList.removeChild(optionsList.lastChild);
            }
        }
    } else {
        optionsContainer.style.display = 'none';
    }
});

function addOption() {
    const optionsList = document.getElementById('optionsList');
    const count = optionsList.children.length + 1;
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" name="options[]" class="form-control" placeholder="Option ${count}">
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    optionsList.appendChild(div);
}

function toggleImageInput() {
    const urlInput = document.getElementById('imageUrlInput');
    const currentValue = urlInput.value;

    if (urlInput.type === 'url') {
        // Switch to file upload
        urlInput.type = 'file';
        urlInput.name = 'question_image_file';
        urlInput.accept = 'image/*';
        urlInput.placeholder = 'Choose image file...';
        urlInput.value = '';
        updateImagePreview('');
    } else {
        // Switch back to URL input
        urlInput.type = 'url';
        urlInput.name = 'question_image';
        urlInput.placeholder = 'https://example.com/image.jpg';
        urlInput.value = currentValue;
    }
}

function updateImagePreview(src) {
    const preview = document.getElementById('imagePreview');
    const img = document.getElementById('previewImg');

    if (src) {
        img.src = src;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
        img.src = '';
    }
}

// Handle URL input changes
document.getElementById('imageUrlInput').addEventListener('input', function() {
    updateImagePreview(this.value);
});

// Handle file input changes for preview
document.addEventListener('change', function(e) {
    if (e.target.id === 'imageUrlInput' && e.target.type === 'file') {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                updateImagePreview(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    }
});
</script>

<?php include '../footer.php'; ?>
