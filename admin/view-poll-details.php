<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$poll_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($poll_id <= 0) {
    die("Invalid poll ID");
}

// Get poll details
$poll_query = $conn->query("
    SELECT p.*,
           CONCAT(u.first_name, ' ', u.last_name) as creator_name,
           u.email as creator_email,
           c.name as category_name,
           COUNT(pr.id) as response_count
    FROM polls p
    LEFT JOIN users u ON p.created_by = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN poll_responses pr ON p.id = pr.poll_id
    WHERE p.id = $poll_id
    GROUP BY p.id
");

if (!$poll_query || $poll_query->num_rows === 0) {
    die("Poll not found");
}

$poll = $poll_query->fetch_assoc();

// Get poll questions
$questions_query = $conn->query("SELECT * FROM poll_questions WHERE poll_id = $poll_id ORDER BY question_order");

?>

    <div class="poll-details">
    <div class="row">
        <div class="col-md-8">
            <h5><?php echo htmlspecialchars($poll['title']); ?></h5>
            <p class="text-muted"><?php echo htmlspecialchars($poll['description']); ?></p>

            <?php if (!empty($poll['disclaimer'])): ?>
                <div class="alert alert-warning mt-3">
                    <h6><i class="fas fa-exclamation-triangle"></i> Disclaimer</h6>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($poll['disclaimer'])); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($poll['image'] && file_exists("../uploads/polls/" . $poll['image'])): ?>
                <img src="<?php echo SITE_URL; ?>uploads/polls/<?php echo $poll['image']; ?>" alt="Poll image" class="img-fluid mb-3" style="max-width: 300px;">
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Poll Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Creator:</strong></td>
                            <td><?php echo htmlspecialchars($poll['creator_name']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td><?php echo htmlspecialchars($poll['creator_email']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Category:</strong></td>
                            <td><?php echo htmlspecialchars($poll['category_name'] ?? 'No Category'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Type:</strong></td>
                            <td><?php echo htmlspecialchars($poll['poll_type']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                <span class="badge bg-<?php
                                    echo $poll['status'] === 'active' ? 'success' :
                                         ($poll['status'] === 'draft' ? 'warning' :
                                         ($poll['status'] === 'paused' ? 'danger' :
                                         ($poll['status'] === 'completed' ? 'info' : 'secondary')));
                                ?>">
                                    <?php echo ucfirst($poll['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Responses:</strong></td>
                            <td><?php echo number_format($poll['response_count']); ?></td>
                        </tr>
                        <?php if ($poll['start_date']): ?>
                        <tr>
                            <td><strong>Start Date:</strong></td>
                            <td><?php echo date('M d, Y H:i', strtotime($poll['start_date'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($poll['end_date']): ?>
                        <tr>
                            <td><strong>End Date:</strong></td>
                            <td><?php echo date('M d, Y H:i', strtotime($poll['end_date'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td><strong>Created:</strong></td>
                            <td><?php echo date('M d, Y', strtotime($poll['created_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <h6>Poll Settings</h6>
            <div class="row">
                <div class="col-md-6">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <strong>Comments:</strong> <?php echo $poll['allow_comments'] ? 'Allowed' : 'Not Allowed'; ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Multiple Votes:</strong> <?php echo $poll['allow_multiple_votes'] ? 'Allowed' : 'Not Allowed'; ?>
                        </li>
                        <li class="list-group-item">
                            <strong>IP Restriction:</strong> <?php echo $poll['one_vote_per_ip'] ? 'One vote per IP' : 'Multiple votes per IP'; ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Account Restriction:</strong> <?php echo $poll['one_vote_per_account'] ? 'One vote per account' : 'Multiple votes per account'; ?>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <strong>Results Visibility:</strong>
                            <?php
                            if ($poll['results_private']) echo 'Private';
                            elseif ($poll['results_public_after_vote']) echo 'Public after vote';
                            elseif ($poll['results_public_after_end']) echo 'Public after end';
                            else echo 'Unknown';
                            ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Agent Payment:</strong> <?php echo $poll['price_per_response'] > 0 ? '₦' . number_format($poll['price_per_response'], 2) . ' per response' : 'No payment'; ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Target Responses:</strong> <?php echo number_format($poll['target_responders']); ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Databank:</strong> <?php echo ($poll['results_for_sale'] ?? 0) ? 'Listed for sale' : 'Not listed'; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php if ($questions_query && $questions_query->num_rows > 0): ?>
    <div class="row mt-4">
        <div class="col-12">
            <h6>Poll Questions (<?php echo $questions_query->num_rows; ?>)</h6>
            <div class="accordion" id="questionsAccordion">
                <?php $question_num = 1; ?>
                <?php while ($question = $questions_query->fetch_assoc()): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?php echo $question_num > 1 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#question<?php echo $question['id']; ?>">
                            Question <?php echo $question_num; ?>: <?php echo htmlspecialchars(substr($question['question_text'], 0, 50)); ?><?php echo strlen($question['question_text']) > 50 ? '...' : ''; ?>
                        </button>
                    </h2>
                    <div id="question<?php echo $question['id']; ?>" class="accordion-collapse collapse <?php echo $question_num === 1 ? 'show' : ''; ?>">
                        <div class="accordion-body">
                            <p><strong>Question:</strong> <?php echo htmlspecialchars($question['question_text']); ?></p>

                            <?php if ($question['question_description']): ?>
                            <p><strong>Description:</strong> <?php echo htmlspecialchars($question['question_description']); ?></p>
                            <?php endif; ?>

                            <?php if ($question['question_image'] && file_exists("../uploads/questions/" . $question['question_image'])): ?>
                            <p><strong>Image:</strong></p>
                            <img src="<?php echo SITE_URL; ?>uploads/questions/<?php echo $question['question_image']; ?>" alt="Question image" class="img-fluid" style="max-width: 300px;">
                            <?php endif; ?>

                            <p><strong>Type:</strong> <?php echo ucfirst($question['question_type']); ?></p>

                            <?php if ($question['question_type'] === 'multiple_choice' || $question['question_type'] === 'checkbox'): ?>
                                <?php
                                $options = !empty($question['question_options']) ? json_decode($question['question_options'], true) : null;
                                if ($options):
                                ?>
                                <p><strong>Options:</strong></p>
                                <ul>
                                    <?php foreach ($options as $option): ?>
                                    <li><?php echo htmlspecialchars($option); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            <?php endif; ?>

                            <p><strong>Required:</strong> <?php echo $question['is_required'] ? 'Yes' : 'No'; ?></p>
                        </div>
                    </div>
                </div>
                <?php $question_num++; ?>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Show agent criteria if applicable
    if ($poll['price_per_response'] > 0):
    ?>
    <div class="row mt-4">
        <div class="col-12">
            <h6>Agent Targeting Criteria</h6>
            <div class="card">
                <div class="card-body">
                    <?php
                    $age_criteria = json_decode($poll['agent_age_criteria'], true);
                    $gender_criteria = json_decode($poll['agent_gender_criteria'], true);
                    $occupation_criteria = json_decode($poll['agent_occupation_criteria'], true);
                    $education_criteria = json_decode($poll['agent_education_criteria'], true);
                    $employment_criteria = json_decode($poll['agent_employment_criteria'], true);
                    $income_criteria = json_decode($poll['agent_income_criteria'], true);
                    ?>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Demographics</h6>
                            <p><strong>Age Groups:</strong> <?php echo $age_criteria ? implode(', ', $age_criteria) : 'All ages'; ?></p>
                            <p><strong>Gender:</strong> <?php echo $gender_criteria ? implode(', ', $gender_criteria) : 'All genders'; ?></p>
                            <p><strong>Location:</strong> <?php echo $poll['agent_state_criteria'] ?: 'All states'; ?><?php echo $poll['agent_lga_criteria'] ? ' (' . $poll['agent_lga_criteria'] . ')' : ''; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Socio-Economic</h6>
                            <p><strong>Employment:</strong> <?php echo $employment_criteria ? implode(', ', $employment_criteria) : 'All'; ?></p>
                            <p><strong>Education:</strong> <?php echo $education_criteria ? implode(', ', $education_criteria) : 'All levels'; ?></p>
                            <p><strong>Income Range:</strong> <?php echo $income_criteria ? implode(', ', $income_criteria) : 'All ranges'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
