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
$access_check = $conn->query("SELECT * FROM poll_results_access 
                              WHERE user_id = {$current_user['id']} 
                              AND poll_id = $poll_id")->fetch_assoc();

if (!$access_check) {
    $_SESSION['error'] = "You don't have access to these results. Please purchase from the databank.";
    header('Location: ' . SITE_URL . 'databank.php');
    exit;
}

// Get poll details
$poll = $conn->query("SELECT p.*, c.name as category_name, u.first_name, u.last_name, u.email
                      FROM polls p
                      LEFT JOIN categories c ON p.category_id = c.id
                      LEFT JOIN users u ON p.created_by = u.id
                      WHERE p.id = $poll_id")->fetch_assoc();

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
            $options = $conn->query("SELECT o.option_text, o.is_correct, COUNT(qr.id) as count 
                                    FROM poll_options o 
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
        </div>
        <div class="col-md-4 text-end">
            <a href="?id=<?php echo $poll_id; ?>&export=pdf" class="btn btn-success" target="_blank">
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

    <!-- Questions and Results -->
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
            <small class="text-muted">Type: <?php echo ucfirst(str_replace('_', ' ', $q_type)); ?></small>
        </div>
        <div class="card-body">
            <?php 
            // Question types with options: multiple_choice, multiple_answer, quiz, assessment, dichotomous, yes_no
            if (in_array($q_type, ['multiple_choice', 'multiple_answer', 'quiz', 'assessment', 'dichotomous'])): 
            ?>
                <?php
                // Get options with response counts
                $options = $conn->query("SELECT o.option_text, o.is_correct, COUNT(qr.id) as count 
                                        FROM poll_options o 
                                        LEFT JOIN question_responses qr ON o.id = qr.option_id 
                                        WHERE o.question_id = {$question['id']} 
                                        GROUP BY o.id 
                                        ORDER BY count DESC");
                
                if (!$options || $options->num_rows == 0) {
                    echo '<p class="text-muted">No options configured for this question.</p>';
                } else {
                    $chart_data = [];
                    while ($opt = $options->fetch_assoc()) {
                        $chart_data[] = $opt;
                    }
                ?>
                
                <div class="row">
                    <div class="col-md-8">
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
                                <div class="progress" style="height: 30px;">
                                    <div class="progress-bar <?php echo $is_correct ? 'bg-success' : 'bg-primary'; ?>" role="progressbar" 
                                         style="width: <?php echo $percentage; ?>%;" 
                                         aria-valuenow="<?php echo $percentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo $percentage; ?>%
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-md-4">
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
                <?php } ?>

            <?php elseif ($q_type == 'yes_no'): ?>
                <?php
                // Get Yes/No counts from text_response
                $yes_result = $conn->query("SELECT COUNT(*) as cnt FROM question_responses 
                                          WHERE question_id = {$question['id']} 
                                          AND text_response = 'Yes'");
                $no_result = $conn->query("SELECT COUNT(*) as cnt FROM question_responses 
                                         WHERE question_id = {$question['id']} 
                                         AND text_response = 'No'");
                $yes_count = $yes_result ? $yes_result->fetch_assoc()['cnt'] : 0;
                $no_count = $no_result ? $no_result->fetch_assoc()['cnt'] : 0;
                $total = $yes_count + $no_count;
                $yes_pct = $total > 0 ? round(($yes_count / $total) * 100, 1) : 0;
                $no_pct = $total > 0 ? round(($no_count / $total) * 100, 1) : 0;
                ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><i class="fas fa-check text-success"></i> Yes</span>
                                <span><strong><?php echo $yes_count; ?></strong> (<?php echo $yes_pct; ?>%)</span>
                            </div>
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $yes_pct; ?>%;">
                                    <?php echo $yes_pct; ?>%
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><i class="fas fa-times text-danger"></i> No</span>
                                <span><strong><?php echo $no_count; ?></strong> (<?php echo $no_pct; ?>%)</span>
                            </div>
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar bg-danger" role="progressbar" 
                                     style="width: <?php echo $no_pct; ?>%;">
                                    <?php echo $no_pct; ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <canvas id="chart_<?php echo $question['id']; ?>" height="200"></canvas>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('chart_<?php echo $question['id']; ?>').getContext('2d');
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Yes', 'No'],
                            datasets: [{
                                data: [<?php echo $yes_count; ?>, <?php echo $no_count; ?>],
                                backgroundColor: ['#28a745', '#dc3545']
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

            <?php elseif ($q_type == 'rating'): ?>
                <?php
                // Get rating distribution (1-5 stars)
                $rating_data = [];
                for ($i = 1; $i <= 5; $i++) {
                    $result = $conn->query("SELECT COUNT(*) as cnt FROM question_responses 
                                          WHERE question_id = {$question['id']} 
                                          AND rating_value = $i");
                    $rating_data[$i] = $result ? $result->fetch_assoc()['cnt'] : 0;
                }
                $total_ratings = array_sum($rating_data);
                $avg_rating = $total_ratings > 0 ? round(array_sum(array_map(function($star, $cnt) { return $star * $cnt; }, array_keys($rating_data), $rating_data)) / $total_ratings, 2) : 0;
                ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="text-center mb-4">
                            <h2 class="display-4 text-warning"><?php echo $avg_rating; ?> <i class="fas fa-star"></i></h2>
                            <p class="text-muted">Average Rating from <?php echo $total_ratings; ?> responses</p>
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
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-warning" role="progressbar" 
                                         style="width: <?php echo $pct; ?>%;">
                                        <?php echo $pct; ?>%
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="col-md-4">
                        <canvas id="chart_<?php echo $question['id']; ?>" height="250"></canvas>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('chart_<?php echo $question['id']; ?>').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['1★', '2★', '3★', '4★', '5★'],
                            datasets: [{
                                label: 'Ratings',
                                data: <?php echo json_encode(array_values($rating_data)); ?>,
                                backgroundColor: '#ffc107'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                });
                </script>

            <?php elseif (in_array($q_type, ['open_ended', 'text'])): ?>
                <?php
                // Get text responses
                $text_responses = $conn->query("SELECT text_response, responded_at 
                                               FROM question_responses 
                                               WHERE question_id = {$question['id']} 
                                               AND text_response IS NOT NULL
                                               AND text_response != ''
                                               ORDER BY responded_at DESC 
                                               LIMIT 50");
                ?>
                <div class="row">
                    <?php if ($text_responses && $text_responses->num_rows > 0): ?>
                        <?php while ($response = $text_responses->fetch_assoc()): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <p class="mb-1"><i class="fas fa-quote-left text-muted me-2"></i><?php echo nl2br(htmlspecialchars($response['text_response'])); ?></p>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($response['responded_at'])); ?></small>
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

            <?php elseif ($q_type == 'word_cloud'): ?>
                <?php
                // Get word cloud data
                $words_responses = $conn->query("SELECT text_response 
                                                FROM question_responses 
                                                WHERE question_id = {$question['id']} 
                                                AND text_response IS NOT NULL");
                
                $word_freq = [];
                if ($words_responses && $words_responses->num_rows > 0) {
                    while ($resp = $words_responses->fetch_assoc()) {
                        $words = array_map('trim', explode(',', $resp['text_response']));
                        foreach ($words as $word) {
                            $word = strtolower($word);
                            if (!empty($word)) {
                                $word_freq[$word] = ($word_freq[$word] ?? 0) + 1;
                            }
                        }
                    }
                    arsort($word_freq);
                }
                $top_words = array_slice($word_freq, 0, 20, true);
                ?>
                
                <?php if (!empty($top_words)): ?>
                <div class="text-center mb-3">
                    <?php foreach ($top_words as $word => $count): ?>
                        <?php 
                        $size = 12 + ($count * 4); // Scale font size based on frequency
                        $size = min($size, 48); // Max 48px
                        ?>
                        <span style="font-size: <?php echo $size; ?>px; margin: 10px; display: inline-block; color: <?php echo sprintf('#%06X', mt_rand(0, 0xFFFFFF)); ?>;">
                            <?php echo htmlspecialchars($word); ?> <small class="text-muted">(<?php echo $count; ?>)</small>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <p class="text-muted text-center">No word cloud data yet</p>
                <?php endif; ?>

            <?php else: ?>
                <p class="text-muted">Results for this question type (<?php echo $q_type; ?>) are not yet implemented.</p>
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
