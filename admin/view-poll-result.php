<?php
$page_title = "View Poll Results - Admin";
include_once '../header.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: ' . SITE_URL . 'signin.php');
    exit;
}

global $conn;
$current_user = getCurrentUser();

// Get poll ID
$poll_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$poll_id) {
    header('Location: ' . SITE_URL . 'admin/poll-results.php');
    exit;
}

// Admin has access to all polls - no purchase check needed

// Get poll details
$poll = $conn->query("SELECT p.*, c.name as category_name, u.first_name, u.last_name, u.email
                      FROM polls p
                      LEFT JOIN categories c ON p.category_id = c.id
                      LEFT JOIN users u ON p.created_by = u.id
                      WHERE p.id = $poll_id")->fetch_assoc();

if (!$poll) {
    $_SESSION['error'] = "Poll not found.";
    header('Location: ' . SITE_URL . 'admin/poll-results.php');
    exit;
}

// Get total responses
$response_count = $conn->query("SELECT COUNT(*) as count FROM poll_responses WHERE poll_id = $poll_id")->fetch_assoc()['count'];

// Get questions
$questions = $conn->query("SELECT * FROM poll_questions WHERE poll_id = $poll_id ORDER BY question_order");

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    // Basic PDF generation using HTML
    ob_clean();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="poll_results_' . $poll_id . '.pdf"');
    
    // Simple HTML to PDF conversion (you might want to use a library like TCPDF or mPDF for better results)
    echo "<html><head><style>body{font-family:Arial,sans-serif;}</style></head><body>";
    echo "<h1>" . htmlspecialchars($poll['title']) . "</h1>";
    echo "<p><strong>Description:</strong> " . htmlspecialchars($poll['description']) . "</p>";
    echo "<p><strong>Total Responses:</strong> " . number_format($response_count) . "</p>";
    echo "<p><strong>Admin View:</strong> " . date('M d, Y') . "</p>";
    echo "<hr>";
    
    while ($question = $questions->fetch_assoc()) {
        echo "<h3>Q" . $question['question_order'] . ": " . htmlspecialchars($question['question_text']) . "</h3>";
        
        if ($question['question_type'] == 'multiple_choice' || $question['question_type'] == 'single_choice') {
            $options = $conn->query("SELECT o.option_text, COUNT(a.id) as count 
                                    FROM poll_question_options o 
                                    LEFT JOIN poll_answers a ON o.id = a.option_id 
                                    WHERE o.question_id = {$question['id']} 
                                    GROUP BY o.id ORDER BY count DESC");
            
            echo "<ul>";
            while ($option = $options->fetch_assoc()) {
                $percentage = $response_count > 0 ? round(($option['count'] / $response_count) * 100, 1) : 0;
                echo "<li>" . htmlspecialchars($option['option_text']) . ": " . $option['count'] . " (" . $percentage . "%)</li>";
            }
            echo "</ul>";
        }
    }
    
    echo "</body></html>";
    exit;
}

?>

<div class="container my-5">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>admin/dashboard.php">Admin Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>admin/poll-results.php">All Poll Results</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($poll['title']); ?></li>
                </ol>
            </nav>
            <h2><?php echo htmlspecialchars($poll['title']); ?></h2>
            <p class="text-muted"><?php echo htmlspecialchars($poll['description']); ?></p>
        </div>
        <div class="col-md-4 text-end">
            <a href="?id=<?php echo $poll_id; ?>&export=pdf" class="btn btn-success" target="_blank">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
            <a href="<?php echo SITE_URL; ?>admin/poll-results.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Admin Info Banner -->
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-1"><i class="fas fa-user-shield"></i> Admin Access</h5>
                <small>Viewing as administrator • Full access to all poll results • No purchase required</small>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-info fs-6">
                    <i class="fas fa-crown"></i> Admin View
                </span>
            </div>
        </div>
    </div>

    <!-- Poll Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Total Responses</h6>
                    <h2 class="mb-0 text-primary"><?php echo number_format($response_count); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Category</h6>
                    <h5 class="mb-0"><span class="badge bg-primary"><?php echo htmlspecialchars($poll['category_name']); ?></span></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Created By</h6>
                    <h6 class="mb-0"><?php echo htmlspecialchars($poll['first_name'] . ' ' . $poll['last_name']); ?></h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Status</h6>
                    <span class="badge <?php echo $poll['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                        <?php echo ucfirst($poll['status']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Questions and Results -->
    <?php 
    $questions->data_seek(0); // Reset pointer
    while ($question = $questions->fetch_assoc()): 
    ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <span class="badge bg-primary me-2">Q<?php echo $question['question_order']; ?></span>
                <?php echo htmlspecialchars($question['question_text']); ?>
            </h5>
            <small class="text-muted">Type: <?php echo ucfirst(str_replace('_', ' ', $question['question_type'])); ?></small>
        </div>
        <div class="card-body">
            <?php if ($question['question_type'] == 'multiple_choice' || $question['question_type'] == 'single_choice'): ?>
                <?php
                // Get options with response counts
                $options = $conn->query("SELECT o.option_text, COUNT(a.id) as count 
                                        FROM poll_question_options o 
                                        LEFT JOIN poll_answers a ON o.id = a.option_id 
                                        WHERE o.question_id = {$question['id']} 
                                        GROUP BY o.id 
                                        ORDER BY count DESC");
                ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <?php while ($option = $options->fetch_assoc()): ?>
                            <?php 
                            $percentage = $response_count > 0 ? round(($option['count'] / $response_count) * 100, 1) : 0;
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo htmlspecialchars($option['option_text']); ?></span>
                                    <span><strong><?php echo $option['count']; ?></strong> (<?php echo $percentage; ?>%)</span>
                                </div>
                                <div class="progress" style="height: 30px;">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: <?php echo $percentage; ?>%;" 
                                         aria-valuenow="<?php echo $percentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo $percentage; ?>%
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="col-md-4">
                        <?php 
                        // Reset for chart
                        $options->data_seek(0);
                        $chart_data = [];
                        while ($opt = $options->fetch_assoc()) {
                            $chart_data[] = $opt;
                        }
                        ?>
                        <canvas id="chart_<?php echo $question['id']; ?>" height="250"></canvas>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('chart_<?php echo $question['id']; ?>').getContext('2d');
                    new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: <?php echo json_encode(array_column($chart_data, 'option_text')); ?>,
                            datasets: [{
                                data: <?php echo json_encode(array_column($chart_data, 'count')); ?>,
                                backgroundColor: [
                                    '#ff6b35', '#004e89', '#28a745', '#ffc107', 
                                    '#dc3545', '#17a2b8', '#6610f2', '#fd7e14'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                });
                </script>

            <?php elseif ($question['question_type'] == 'text'): ?>
                <?php
                // Get text responses
                $text_responses = $conn->query("SELECT answer_text, created_at 
                                               FROM poll_answers 
                                               WHERE question_id = {$question['id']} 
                                               ORDER BY created_at DESC 
                                               LIMIT 50");
                ?>
                <div class="row">
                    <?php if ($text_responses && $text_responses->num_rows > 0): ?>
                        <?php while ($response = $text_responses->fetch_assoc()): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <p class="mb-1">"<?php echo htmlspecialchars($response['answer_text']); ?>"</p>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($response['created_at'])); ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        <?php if ($text_responses->num_rows == 50): ?>
                        <div class="col-12">
                            <p class="text-muted text-center"><em>Showing first 50 responses</em></p>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <p class="text-muted text-center">No responses yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; ?>

    <!-- Demographics (if available) -->
    <?php
    // Get demographic breakdown if poll collected age/gender
    $demographics = $conn->query("SELECT 
                                  respondent_age, 
                                  respondent_gender, 
                                  COUNT(*) as count 
                                  FROM poll_responses 
                                  WHERE poll_id = $poll_id 
                                  AND respondent_age IS NOT NULL 
                                  GROUP BY respondent_age, respondent_gender")->num_rows;
    
    if ($demographics > 0):
    ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-users"></i> Demographics</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>By Age Group</h6>
                    <?php
                    $age_groups = $conn->query("SELECT respondent_age, COUNT(*) as count 
                                               FROM poll_responses 
                                               WHERE poll_id = $poll_id 
                                               AND respondent_age IS NOT NULL 
                                               GROUP BY respondent_age 
                                               ORDER BY respondent_age");
                    while ($age = $age_groups->fetch_assoc()):
                        $age_percentage = round(($age['count'] / $response_count) * 100, 1);
                    ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span><?php echo htmlspecialchars($age['respondent_age']); ?></span>
                            <span><?php echo $age['count']; ?> (<?php echo $age_percentage; ?>%)</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-info" style="width: <?php echo $age_percentage; ?>%;"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <div class="col-md-6">
                    <h6>By Gender</h6>
                    <?php
                    $genders = $conn->query("SELECT respondent_gender, COUNT(*) as count 
                                            FROM poll_responses 
                                            WHERE poll_id = $poll_id 
                                            AND respondent_gender IS NOT NULL 
                                            GROUP BY respondent_gender");
                    while ($gender = $genders->fetch_assoc()):
                        $gender_percentage = round(($gender['count'] / $response_count) * 100, 1);
                    ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span><?php echo ucfirst($gender['respondent_gender']); ?></span>
                            <span><?php echo $gender['count']; ?> (<?php echo $gender_percentage; ?>%)</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $gender_percentage; ?>%;"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<?php include_once 'footer.php'; ?>
