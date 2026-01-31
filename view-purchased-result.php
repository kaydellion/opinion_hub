<?php
// Handle export BEFORE any output
require_once 'connect.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    $_SESSION['error'] = "Please login to view poll results.";
    header('Location: ' . SITE_URL . 'signin.php');
    exit;
}

global $conn;
$current_user = getCurrentUser();

// Get poll ID
$poll_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$poll_id) {
    header('Location: ' . SITE_URL . 'my-purchased-results.php');
    exit;
}

// Verify user has purchased access
$access_check = $conn->query("SELECT pra.*, dd.dataset_format, dd.time_period
                              FROM poll_results_access pra
                              LEFT JOIN dataset_downloads dd ON dd.user_id = pra.user_id AND dd.poll_id = pra.poll_id
                              WHERE pra.user_id = {$current_user['id']}
                              AND pra.poll_id = $poll_id
                              ORDER BY dd.download_date DESC
                              LIMIT 1")->fetch_assoc();

if (!$access_check) {
    $_SESSION['error'] = "You don't have access to these results. Please purchase from the databank.";
    header('Location: ' . SITE_URL . 'databank.php');
    exit;
}

// Update access count
$conn->query("UPDATE poll_results_access SET access_count = access_count + 1 WHERE id = {$access_check['id']}");

// Get format parameter (combined or single)
$format = isset($_GET['format']) ? sanitize($_GET['format']) : 'combined';
$time_period = isset($_GET['period']) ? sanitize($_GET['period']) : 'monthly';

// Validate format
if (!in_array($format, ['combined', 'single'])) {
    $format = 'combined';
}

// Validate time period
if (!in_array($time_period, ['daily', 'weekly', 'monthly', 'annually'])) {
    $time_period = 'monthly';
}

// Get poll details
$poll = $conn->query("SELECT p.*, c.name as category_name, u.first_name, u.last_name, u.email
                      FROM polls p
                      LEFT JOIN categories c ON p.category_id = c.id
                      LEFT JOIN users u ON p.created_by = u.id
                      WHERE p.id = $poll_id")->fetch_assoc();

// Record dataset download/access
if ($format && $time_period) {
    // Check if this format/time_period combination already exists
    $existing_download = $conn->query("SELECT id FROM dataset_downloads
                                       WHERE user_id = {$current_user['id']}
                                       AND poll_id = $poll_id
                                       AND dataset_format = '$format'
                                       AND time_period = '$time_period'")->fetch_assoc();

    if ($existing_download) {
        // Update download count
        $conn->query("UPDATE dataset_downloads SET download_count = download_count + 1, download_date = NOW()
                      WHERE id = {$existing_download['id']}");
    } else {
        // Insert new download record
        $conn->query("INSERT INTO dataset_downloads (user_id, poll_id, dataset_format, time_period)
                      VALUES ({$current_user['id']}, $poll_id, '$format', '$time_period')");
    }
}

if (!$poll) {
    $_SESSION['error'] = "Poll not found.";
    header('Location: ' . SITE_URL . 'my-purchased-results.php');
    exit;
}

// Get total responses
$response_count = $conn->query("SELECT COUNT(*) as count FROM poll_responses WHERE poll_id = $poll_id")->fetch_assoc()['count'];

// Get questions
$questions = $conn->query("SELECT * FROM poll_questions WHERE poll_id = $poll_id ORDER BY question_order");

// Handle PDF export BEFORE including header
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    // Basic PDF generation using HTML
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="poll_results_' . $poll_id . '.html"');
    
    // Simple HTML export (you can use a library like TCPDF or mPDF for better PDF results)
    echo "<!DOCTYPE html>";
    echo "<html><head>";
    echo "<meta charset='UTF-8'>";
    echo "<title>" . htmlspecialchars($poll['title']) . " - Results</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        .info { background: #f0f0f0; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .question { margin: 30px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .option { margin: 10px 0; padding: 10px; background: #f9f9f9; }
        .progress { background: #e0e0e0; height: 25px; border-radius: 3px; overflow: hidden; }
        .progress-bar { background: #007bff; height: 100%; color: white; text-align: center; line-height: 25px; }
        .text-response { background: #f0f8ff; padding: 10px; margin: 5px 0; border-left: 3px solid #007bff; }
    </style>";
    echo "</head><body>";
    
    echo "<h1>" . htmlspecialchars($poll['title']) . "</h1>";
    
    echo "<div class='info'>";
    echo "<p><strong>Description:</strong> " . htmlspecialchars($poll['description']) . "</p>";
    echo "<p><strong>Total Responses:</strong> " . number_format($response_count) . "</p>";
    echo "<p><strong>Purchased on:</strong> " . date('F j, Y', strtotime($access_check['purchased_at'])) . "</p>";
    echo "<p><strong>Amount Paid:</strong> ₦" . number_format($access_check['amount_paid'], 2) . "</p>";
    echo "</div>";
    
    while ($question = $questions->fetch_assoc()) {
        $q_type = $question['question_type'];
        
        echo "<div class='question'>";
        echo "<h3>Q" . $question['question_order'] . ": " . htmlspecialchars($question['question_text']) . "</h3>";
        echo "<p><em>Type: " . ucfirst(str_replace('_', ' ', $q_type)) . "</em></p>";
        
        if (in_array($q_type, ['multiple_choice', 'multiple_answer', 'quiz', 'assessment', 'dichotomous'])) {
            $options = $conn->query("SELECT o.option_text, o.is_correct_answer, COUNT(qr.id) as count 
                                    FROM poll_question_options o 
                                    LEFT JOIN question_responses qr ON o.id = qr.option_id 
                                    WHERE o.question_id = {$question['id']} 
                                    GROUP BY o.id ORDER BY count DESC");
            
            while ($option = $options->fetch_assoc()) {
                $percentage = $response_count > 0 ? round(($option['count'] / $response_count) * 100, 1) : 0;
                $is_correct = ($q_type == 'quiz' || $q_type == 'assessment') && $option['is_correct'];
                
                echo "<div class='option'>";
                echo "<strong>" . htmlspecialchars($option['option_text']) . "</strong>";
                if ($is_correct) echo " <span style='color: green;'>[CORRECT]</span>";
                echo "<br>";
                echo "<div class='progress'>";
                echo "<div class='progress-bar' style='width: " . $percentage . "%;'>" . $option['count'] . " (" . $percentage . "%)</div>";
                echo "</div>";
                echo "</div>";
            }
        } elseif ($q_type == 'yes_no') {
            $yes_result = $conn->query("SELECT COUNT(*) as cnt FROM question_responses WHERE question_id = {$question['id']} AND text_response = 'Yes'");
            $no_result = $conn->query("SELECT COUNT(*) as cnt FROM question_responses WHERE question_id = {$question['id']} AND text_response = 'No'");
            $yes_count = $yes_result ? $yes_result->fetch_assoc()['cnt'] : 0;
            $no_count = $no_result ? $no_result->fetch_assoc()['cnt'] : 0;
            $total = $yes_count + $no_count;
            
            echo "<div class='option'>";
            echo "<strong>Yes:</strong> " . $yes_count . " (" . ($total > 0 ? round(($yes_count/$total)*100, 1) : 0) . "%)<br>";
            echo "<strong>No:</strong> " . $no_count . " (" . ($total > 0 ? round(($no_count/$total)*100, 1) : 0) . "%)";
            echo "</div>";
        } elseif ($q_type == 'rating') {
            $rating_data = [];
            for ($i = 1; $i <= 5; $i++) {
                $result = $conn->query("SELECT COUNT(*) as cnt FROM question_responses WHERE question_id = {$question['id']} AND rating_value = $i");
                $rating_data[$i] = $result ? $result->fetch_assoc()['cnt'] : 0;
            }
            $total_ratings = array_sum($rating_data);
            $avg_rating = $total_ratings > 0 ? round(array_sum(array_map(function($star, $cnt) { return $star * $cnt; }, array_keys($rating_data), $rating_data)) / $total_ratings, 2) : 0;
            
            echo "<p><strong>Average Rating: " . $avg_rating . " ★</strong> (from " . $total_ratings . " responses)</p>";
            for ($i = 5; $i >= 1; $i--) {
                $pct = $total_ratings > 0 ? round(($rating_data[$i]/$total_ratings)*100, 1) : 0;
                echo "<div class='option'>" . $i . " ★: " . $rating_data[$i] . " (" . $pct . "%)</div>";
            }
        } elseif (in_array($q_type, ['open_ended', 'text'])) {
            $text_responses = $conn->query("SELECT text_response, responded_at FROM question_responses WHERE question_id = {$question['id']} AND text_response IS NOT NULL AND text_response != '' ORDER BY responded_at DESC LIMIT 30");
            
            if ($text_responses && $text_responses->num_rows > 0) {
                while ($resp = $text_responses->fetch_assoc()) {
                    echo "<div class='text-response'>";
                    echo nl2br(htmlspecialchars($resp['text_response']));
                    echo "<br><small>" . date('M d, Y', strtotime($resp['responded_at'])) . "</small>";
                    echo "</div>";
                }
            } else {
                echo "<p><em>No responses yet</em></p>";
            }
        } elseif ($q_type == 'word_cloud') {
            $words_responses = $conn->query("SELECT text_response FROM question_responses WHERE question_id = {$question['id']} AND text_response IS NOT NULL");
            
            $word_freq = [];
            if ($words_responses && $words_responses->num_rows > 0) {
                while ($resp = $words_responses->fetch_assoc()) {
                    $words = array_map('trim', explode(',', $resp['text_response']));
                    foreach ($words as $word) {
                        $word = strtolower(trim($word));
                        if (!empty($word)) {
                            $word_freq[$word] = ($word_freq[$word] ?? 0) + 1;
                        }
                    }
                }
                arsort($word_freq);
            }
            
            if (!empty($word_freq)) {
                echo "<div style='text-align: center; padding: 20px;'>";
                $top_words = array_slice($word_freq, 0, 20, true);
                foreach ($top_words as $word => $count) {
                    $size = 12 + ($count * 3);
                    $size = min($size, 36);
                    $colors = ['#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6610f2'];
                    $color = $colors[array_rand($colors)];
                    echo "<span style='font-size: {$size}px; margin: 8px; display: inline-block; color: {$color}; font-weight: bold;'>";
                    echo htmlspecialchars($word) . " <small>(" . $count . ")</small>";
                    echo "</span> ";
                }
                echo "</div>";
            } else {
                echo "<p><em>No word cloud data yet</em></p>";
            }
        }
        
        echo "</div>";
    }
    
    echo "</body></html>";
    exit;
}

// Now include header for normal page view
$page_title = "View Poll Results";
include_once 'header.php';
?>

<div class="container my-5">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>my-purchased-results.php">My Purchased Results</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($poll['title']); ?></li>
                </ol>
            </nav>
            <h2><?php echo htmlspecialchars($poll['title']); ?></h2>
            <p class="text-muted"><?php echo htmlspecialchars($poll['description']); ?></p>

            <!-- Format Selector -->
            <div class="mt-3">
                <div class="btn-group" role="group" aria-label="Dataset format">
                    <a href="?id=<?php echo $poll_id; ?>&format=combined&period=<?php echo $time_period; ?>"
                       class="btn <?php echo $format === 'combined' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="fas fa-chart-line"></i> COMBINED (Trends & Patterns)
                    </a>
                    <a href="?id=<?php echo $poll_id; ?>&format=single&period=<?php echo $time_period; ?>"
                       class="btn <?php echo $format === 'single' ? 'btn-info' : 'btn-outline-info'; ?>">
                        <i class="fas fa-users"></i> SINGLE (Individual Responses)
                    </a>
                </div>

                <?php if ($format === 'combined'): ?>
                <!-- Time Period Selector (only for combined format) -->
                <div class="mt-2">
                    <small class="text-muted">View trends by:</small>
                    <div class="btn-group ms-2" role="group" aria-label="Time period">
                        <a href="?id=<?php echo $poll_id; ?>&format=combined&period=daily"
                           class="btn btn-sm <?php echo $time_period === 'daily' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                            Daily
                        </a>
                        <a href="?id=<?php echo $poll_id; ?>&format=combined&period=weekly"
                           class="btn btn-sm <?php echo $time_period === 'weekly' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                            Weekly
                        </a>
                        <a href="?id=<?php echo $poll_id; ?>&format=combined&period=monthly"
                           class="btn btn-sm <?php echo $time_period === 'monthly' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                            Monthly
                        </a>
                        <a href="?id=<?php echo $poll_id; ?>&format=combined&period=annually"
                           class="btn btn-sm <?php echo $time_period === 'annually' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                            Annually
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="?id=<?php echo $poll_id; ?>&format=<?php echo $format; ?>&period=<?php echo $time_period; ?>&export=pdf" class="btn btn-success" target="_blank">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
            <a href="<?php echo SITE_URL; ?>my-purchased-results.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Purchase Info Banner -->
    <div class="alert alert-success border-0 shadow-sm mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-1"><i class="fas fa-check-circle"></i> You Own This Report</h5>
                <small>Purchased on <?php echo date('F j, Y', strtotime($access_check['purchased_at'])); ?> 
                for ₦<?php echo number_format($access_check['amount_paid'], 2); ?> • Lifetime Access</small>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-success fs-6">
                    <i class="fas fa-infinity"></i> Forever Access
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

    <?php if ($format === 'combined'): ?>
        <!-- COMBINED FORMAT: Trend Analysis and Patterns -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Trend Analysis - <?php echo ucfirst($time_period); ?> View</h5>
                <small>Combined response patterns and trends over time</small>
            </div>
            <div class="card-body">
                <?php
                // Get response trends based on time period
                $date_format = '';
                $group_by = '';

                switch ($time_period) {
                    case 'daily':
                        $date_format = '%Y-%m-%d';
                        $group_by = 'DATE(pr.responded_at)';
                        break;
                    case 'weekly':
                        $date_format = '%Y-%u'; // Year-week format
                        $group_by = 'YEARWEEK(pr.responded_at)';
                        break;
                    case 'monthly':
                        $date_format = '%Y-%m';
                        $group_by = 'DATE_FORMAT(pr.responded_at, "%Y-%m")';
                        break;
                    case 'annually':
                        $date_format = '%Y';
                        $group_by = 'YEAR(pr.responded_at)';
                        break;
                }

                $trend_query = $conn->query("SELECT
                    {$group_by} as period,
                    COUNT(*) as total_responses,
                    DATE_FORMAT(MIN(pr.responded_at), '%M %d, %Y') as period_start,
                    DATE_FORMAT(MAX(pr.responded_at), '%M %d, %Y') as period_end
                    FROM poll_responses pr
                    WHERE pr.poll_id = $poll_id
                    GROUP BY {$group_by}
                    ORDER BY period DESC");

                $trend_data = [];
                if ($trend_query && $trend_query->num_rows > 0) {
                    while ($row = $trend_query->fetch_assoc()) {
                        $trend_data[] = $row;
                    }
                }
                ?>

                <?php if (!empty($trend_data)): ?>
                    <!-- Response Trends Chart -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-center mb-3">Response Trends Over Time</h6>
                            <canvas id="responseTrendsChart" height="200"></canvas>
                        </div>
                    </div>

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const ctx = document.getElementById('responseTrendsChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: <?php echo json_encode(array_column($trend_data, 'period')); ?>,
                                datasets: [{
                                    label: 'Total Responses',
                                    data: <?php echo json_encode(array_column($trend_data, 'total_responses')); ?>,
                                    borderColor: '#007bff',
                                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Number of Responses'
                                        }
                                    },
                                    x: {
                                        title: {
                                            display: true,
                                            text: '<?php echo ucfirst($time_period); ?> Period'
                                        }
                                    }
                                }
                            }
                        });
                    });
                    </script>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i> No trend data available for the selected time period.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Questions with Enhanced Charts -->
        <?php
        $questions->data_seek(0); // Reset pointer
        while ($question = $questions->fetch_assoc()):
            $q_type = $question['question_type'];
        ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <span class="badge bg-primary me-2">Q<?php echo $question['question_order']; ?></span>
                    <?php echo htmlspecialchars($question['question_text']); ?>
                </h5>
                <small class="text-muted">Type: <?php echo ucfirst(str_replace('_', ' ', $q_type)); ?> | Combined Analysis</small>
            </div>
            <div class="card-body">
                <?php
                // Different chart types based on question type
                $chart_type = 'pie'; // default

                if ($q_type == 'rating') {
                    $chart_type = 'bar';
                } elseif ($q_type == 'yes_no') {
                    $chart_type = 'doughnut';
                } elseif (in_array($q_type, ['multiple_choice', 'multiple_answer', 'quiz', 'assessment'])) {
                    $chart_type = 'pie';
                }

                // Get response data
                if (in_array($q_type, ['multiple_choice', 'multiple_answer', 'quiz', 'assessment', 'dichotomous'])) {
                    $options = $conn->query("SELECT o.option_text, o.is_correct_answer, COUNT(qr.id) as count
                                            FROM poll_question_options o
                                            LEFT JOIN question_responses qr ON o.id = qr.option_id
                                            WHERE o.question_id = {$question['id']}
                                            GROUP BY o.id
                                            ORDER BY count DESC");

                    if ($options && $options->num_rows > 0) {
                        $chart_data = [];
                        while ($opt = $options->fetch_assoc()) {
                            $chart_data[] = $opt;
                        }
                    }
                } elseif ($q_type == 'yes_no') {
                    $yes_result = $conn->query("SELECT COUNT(*) as cnt FROM question_responses
                                              WHERE question_id = {$question['id']}
                                              AND text_response = 'Yes'");
                    $no_result = $conn->query("SELECT COUNT(*) as cnt FROM question_responses
                                             WHERE question_id = {$question['id']}
                                             AND text_response = 'No'");
                    $yes_count = $yes_result ? $yes_result->fetch_assoc()['cnt'] : 0;
                    $no_count = $no_result ? $no_result->fetch_assoc()['cnt'] : 0;
                } elseif ($q_type == 'rating') {
                    $rating_data = [];
                    for ($i = 1; $i <= 5; $i++) {
                        $result = $conn->query("SELECT COUNT(*) as cnt FROM question_responses
                                              WHERE question_id = {$question['id']}
                                              AND rating_value = $i");
                        $rating_data[$i] = $result ? $result->fetch_assoc()['cnt'] : 0;
                    }
                }
                ?>

                <div class="row">
                    <div class="col-md-6">
                        <?php if (isset($chart_data) && !empty($chart_data)): ?>
                            <?php foreach ($chart_data as $option): ?>
                                <?php
                                $percentage = $response_count > 0 ? round(($option['count'] / $response_count) * 100, 1) : 0;
                                $is_correct = ($q_type == 'quiz' || $q_type == 'assessment') && $option['is_correct'];
                                ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>
                                            <?php echo htmlspecialchars($option['option_text']); ?>
                                            <?php if ($is_correct): ?>
                                                <span class="badge bg-success ms-2"><i class="fas fa-check"></i> Correct</span>
                                            <?php endif; ?>
                                        </span>
                                        <span><strong><?php echo $option['count']; ?></strong> (<?php echo $percentage; ?>%)</span>
                                    </div>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar <?php echo $is_correct ? 'bg-success' : 'bg-primary'; ?>" role="progressbar"
                                             style="width: <?php echo $percentage; ?>%;">
                                            <?php echo $percentage; ?>%
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php elseif ($q_type == 'yes_no'): ?>
                            <?php
                            $total = $yes_count + $no_count;
                            $yes_pct = $total > 0 ? round(($yes_count / $total) * 100, 1) : 0;
                            $no_pct = $total > 0 ? round(($no_count / $total) * 100, 1) : 0;
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><i class="fas fa-check text-success"></i> Yes</span>
                                    <span><strong><?php echo $yes_count; ?></strong> (<?php echo $yes_pct; ?>%)</span>
                                </div>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $yes_pct; ?>%;">
                                        <?php echo $yes_pct; ?>%
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><i class="fas fa-times text-danger"></i> No</span>
                                    <span><strong><?php echo $no_count; ?></strong> (<?php echo $no_pct; ?>%)</span>
                                </div>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-danger" style="width: <?php echo $no_pct; ?>%;">
                                        <?php echo $no_pct; ?>%
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($q_type == 'rating'): ?>
                            <?php
                            $total_ratings = array_sum($rating_data);
                            $avg_rating = $total_ratings > 0 ? round(array_sum(array_map(function($star, $cnt) { return $star * $cnt; }, array_keys($rating_data), $rating_data)) / $total_ratings, 2) : 0;
                            ?>
                            <div class="text-center mb-4">
                                <h3 class="text-warning"><?php echo $avg_rating; ?> <i class="fas fa-star"></i></h3>
                                <p class="text-muted">Average Rating (<?php echo $total_ratings; ?> responses)</p>
                            </div>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <?php
                                $count = $rating_data[$i];
                                $pct = $total_ratings > 0 ? round(($count / $total_ratings) * 100, 1) : 0;
                                ?>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><?php echo $i; ?> <i class="fas fa-star text-warning"></i></span>
                                        <span><strong><?php echo $count; ?></strong> (<?php echo $pct; ?>%)</span>
                                    </div>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-warning" style="width: <?php echo $pct; ?>%;">
                                            <?php echo $pct; ?>%
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> This question type (<?php echo ucfirst(str_replace('_', ' ', $q_type)); ?>) shows aggregated patterns in the chart view.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <canvas id="chart_combined_<?php echo $question['id']; ?>" height="300"></canvas>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('chart_combined_<?php echo $question['id']; ?>').getContext('2d');

                    <?php if (isset($chart_data) && !empty($chart_data)): ?>
                        new Chart(ctx, {
                            type: '<?php echo $chart_type; ?>',
                            data: {
                                labels: <?php echo json_encode(array_column($chart_data, 'option_text')); ?>,
                                datasets: [{
                                    data: <?php echo json_encode(array_column($chart_data, 'count')); ?>,
                                    backgroundColor: [
                                        '#ff6b35', '#004e89', '#28a745', '#ffc107',
                                        '#dc3545', '#17a2b8', '#6610f2', '#fd7e14',
                                        '#20c997', '#e83e8c', '#6610f2', '#fd7e14'
                                    ]
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            boxWidth: 12,
                                            padding: 15
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Response Distribution',
                                        font: {
                                            size: 14
                                        }
                                    }
                                }
                            }
                        });
                    <?php elseif ($q_type == 'yes_no'): ?>
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Yes', 'No'],
                                datasets: [{
                                    data: [<?php echo $yes_count; ?>, <?php echo $no_count; ?>],
                                    backgroundColor: ['#28a745', '#dc3545'],
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    },
                                    title: {
                                        display: true,
                                        text: 'Yes/No Distribution',
                                        font: {
                                            size: 14
                                        }
                                    }
                                }
                            }
                        });
                    <?php elseif ($q_type == 'rating'): ?>
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: ['1★', '2★', '3★', '4★', '5★'],
                                datasets: [{
                                    label: 'Number of Ratings',
                                    data: <?php echo json_encode(array_values($rating_data)); ?>,
                                    backgroundColor: '#ffc107',
                                    borderColor: '#e0a800',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    title: {
                                        display: true,
                                        text: 'Rating Distribution',
                                        font: {
                                            size: 14
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Number of Responses'
                                        }
                                    },
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Rating'
                                        }
                                    }
                                }
                            }
                        });
                    <?php endif; ?>
                });
                </script>
            </div>
        </div>
        <?php endwhile; ?>

    <?php elseif ($format === 'single'): ?>
        <!-- SINGLE FORMAT: Individual Responses -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-users"></i> Individual Responses</h5>
                <small>View each response submitted by individual participants</small>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Individual Responses:</strong>
                    This view shows each response submitted by individual participants.
                    Use the pagination to navigate through responses.
                </div>

                <?php
                // Pagination for individual responses
                $response_page = isset($_GET['response_page']) ? (int)$_GET['response_page'] : 1;
                $responses_per_page = 10;
                $response_offset = ($response_page - 1) * $responses_per_page;

                // Get individual responses with pagination
                $individual_responses = $conn->query("SELECT pr.*, pr.responded_at as response_time
                                                     FROM poll_responses pr
                                                     WHERE pr.poll_id = $poll_id
                                                     ORDER BY pr.responded_at DESC
                                                     LIMIT $responses_per_page OFFSET $response_offset");

                $total_individual_responses = $conn->query("SELECT COUNT(*) as count FROM poll_responses WHERE poll_id = $poll_id")->fetch_assoc()['count'];
                $total_response_pages = ceil($total_individual_responses / $responses_per_page);
                ?>

                <?php if ($individual_responses && $individual_responses->num_rows > 0): ?>
                    <div class="row">
                        <?php while ($response = $individual_responses->fetch_assoc()): ?>
                            <div class="col-12 mb-3">
                                <div class="card border-left-primary">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-user-circle text-primary me-2"></i>
                                                Respondent #<?php echo $response['id']; ?>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo date('M d, Y H:i', strtotime($response['response_time'])); ?>
                                            </small>
                                        </div>

                                        <div class="row">
                                            <?php
                                            $questions->data_seek(0);
                                            $q_num = 1;
                                            while ($question = $questions->fetch_assoc()):
                                                $q_id = $question['id'];
                                                $q_type = $question['question_type'];

                                                // Get this respondent's answer for this question
                                                $answer = null;
                                                if (in_array($q_type, ['multiple_choice', 'multiple_answer', 'quiz', 'assessment', 'dichotomous'])) {
                                                    $answer_query = $conn->query("SELECT o.option_text
                                                                                 FROM question_responses qr
                                                                                 JOIN poll_question_options o ON qr.option_id = o.id
                                                                                 WHERE qr.poll_response_id = {$response['id']}
                                                                                 AND qr.question_id = $q_id");
                                                    if ($answer_query && $answer_query->num_rows > 0) {
                                                        $answers = [];
                                                        while ($ans = $answer_query->fetch_assoc()) {
                                                            $answers[] = $ans['option_text'];
                                                        }
                                                        $answer = implode(', ', $answers);
                                                    }
                                                } elseif ($q_type == 'yes_no') {
                                                    $answer_query = $conn->query("SELECT text_response
                                                                                 FROM question_responses
                                                                                 WHERE poll_response_id = {$response['id']}
                                                                                 AND question_id = $q_id
                                                                                 AND text_response IN ('Yes', 'No')");
                                                    if ($answer_query && $answer_query->num_rows > 0) {
                                                        $answer = $answer_query->fetch_assoc()['text_response'];
                                                    }
                                                } elseif ($q_type == 'rating') {
                                                    $answer_query = $conn->query("SELECT rating_value
                                                                                 FROM question_responses
                                                                                 WHERE poll_response_id = {$response['id']}
                                                                                 AND question_id = $q_id");
                                                    if ($answer_query && $answer_query->num_rows > 0) {
                                                        $rating = $answer_query->fetch_assoc()['rating_value'];
                                                        $answer = $rating . ' <i class="fas fa-star text-warning"></i>';
                                                    }
                                                } elseif (in_array($q_type, ['open_ended', 'text'])) {
                                                    $answer_query = $conn->query("SELECT text_response
                                                                                 FROM question_responses
                                                                                 WHERE poll_response_id = {$response['id']}
                                                                                 AND question_id = $q_id");
                                                    if ($answer_query && $answer_query->num_rows > 0) {
                                                        $answer = $answer_query->fetch_assoc()['text_response'];
                                                    }
                                                } elseif ($q_type == 'word_cloud') {
                                                    $answer_query = $conn->query("SELECT text_response
                                                                                 FROM question_responses
                                                                                 WHERE poll_response_id = {$response['id']}
                                                                                 AND question_id = $q_id");
                                                    if ($answer_query && $answer_query->num_rows > 0) {
                                                        $answer = $answer_query->fetch_assoc()['text_response'];
                                                    }
                                                }
                                            ?>
                                                <div class="col-md-6 mb-2">
                                                    <strong class="text-primary">Q<?php echo $q_num; ?>:</strong>
                                                    <span class="ms-2"><?php echo htmlspecialchars(substr($question['question_text'], 0, 40)); ?><?php if (strlen($question['question_text']) > 40) echo '...'; ?></span><br>
                                                    <span class="text-muted small ms-4">
                                                        <?php if ($answer): ?>
                                                            <i class="fas fa-reply me-1"></i><?php echo $answer; ?>
                                                        <?php else: ?>
                                                            <em>No answer</em>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            <?php
                                                $q_num++;
                                            endwhile;
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Pagination for individual responses -->
                    <?php if ($total_response_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($response_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?id=<?php echo $poll_id; ?>&format=single&period=<?php echo $time_period; ?>&response_page=<?php echo $response_page - 1; ?>">
                                            Previous
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php
                                $start = max(1, $response_page - 3);
                                $end = min($total_response_pages, $response_page + 3);
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                    <li class="page-item <?php echo $i === $response_page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?id=<?php echo $poll_id; ?>&format=single&period=<?php echo $time_period; ?>&response_page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($response_page < $total_response_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?id=<?php echo $poll_id; ?>&format=single&period=<?php echo $time_period; ?>&response_page=<?php echo $response_page + 1; ?>">
                                            Next
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle"></i> No individual responses available yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

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
                                  GROUP BY respondent_age, respondent_gender");
    
    if ($demographics && $demographics->num_rows > 0):
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
                    if ($age_groups && $age_groups->num_rows > 0):
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
                    <?php else: ?>
                        <p class="text-muted">No age data available</p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h6>By Gender</h6>
                    <?php
                    $genders = $conn->query("SELECT respondent_gender, COUNT(*) as count 
                                            FROM poll_responses 
                                            WHERE poll_id = $poll_id 
                                            AND respondent_gender IS NOT NULL 
                                            GROUP BY respondent_gender");
                    if ($genders && $genders->num_rows > 0):
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
                    <?php else: ?>
                        <p class="text-muted">No gender data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<?php include_once 'footer.php'; ?>
