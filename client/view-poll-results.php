<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole(['client', 'admin']);

$user = getCurrentUser();
$poll_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($poll_id === 0) {
    header('Location: manage-polls.php');
    exit;
}

// Verify ownership
$poll_query = "SELECT p.* FROM polls p WHERE p.id = $poll_id AND p.created_by = {$user['id']}";
$result = $conn->query($poll_query);

if (!$result) {
    die('Database error: ' . $conn->error);
}

$poll = $result->fetch_assoc();

if (!$poll) {
    die('Poll not found or access denied');
}

// Get questions with their options and response counts
$questions = $conn->query("SELECT * FROM poll_questions WHERE poll_id = $poll_id ORDER BY question_order");

$page_title = 'Poll Results - ' . $poll['title'];
include '../header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="manage-polls.php">My Polls</a></li>
                    <li class="breadcrumb-item active">Results</li>
                </ol>
            </nav>
            <h2><?= htmlspecialchars($poll['title']) ?></h2>
            <p class="text-muted"><?= htmlspecialchars($poll['description']) ?></p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="manage-polls.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <button class="btn btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="../export.php?type=poll_responses&poll_id=<?= $poll_id ?>" class="btn btn-outline-success">
                    <i class="fas fa-download"></i> Export CSV
                </a>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Responses</h6>
                    <h3 class="mb-0">
                        <?php
                        $response_count = $conn->query("SELECT COUNT(DISTINCT respondent_id) as count FROM poll_responses WHERE poll_id = $poll_id")->fetch_assoc()['count'];
                        echo number_format($response_count);
                        ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Poll Type</h6>
                    <h6 class="mb-0"><?= htmlspecialchars($poll['poll_type']) ?></h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Status</h6>
                    <?php
                    $status_colors = [
                        'draft' => 'warning',
                        'active' => 'success',
                        'paused' => 'secondary',
                        'closed' => 'danger'
                    ];
                    $color = $status_colors[$poll['status']] ?? 'secondary';
                    ?>
                    <h6 class="mb-0">
                        <span class="badge bg-<?= $color ?>"><?= ucfirst($poll['status']) ?></span>
                    </h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Created</h6>
                    <h6 class="mb-0"><?= date('M d, Y', strtotime($poll['created_at'])) ?></h6>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Question Results -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Question Results</h5>
        </div>
        <div class="card-body">
            <?php if ($questions->num_rows > 0): ?>
                <?php $q_num = 1; while ($question = $questions->fetch_assoc()): ?>
                    <div class="mb-5 <?= $q_num > 1 ? 'pt-4 border-top' : '' ?>">
                        <h5 class="mb-3">
                            Q<?= $q_num++ ?>. <?= htmlspecialchars($question['question_text']) ?>
                            <small class="text-muted">(<?= ucwords(str_replace('_', ' ', $question['question_type'])) ?>)</small>
                        </h5>
                        
                        <?php
                        // Get response count for this question
                        $question_responses = $conn->query("SELECT COUNT(*) as count FROM question_responses WHERE question_id = {$question['id']}")->fetch_assoc()['count'];
                        ?>
                        
                        <?php if (in_array($question['question_type'], ['multiple_choice', 'multiple_answer', 'quiz', 'dichotomous'])): ?>
                            <?php
                            // Get options with response counts
                            $options_query = "SELECT o.*, 
                                            (SELECT COUNT(*) FROM question_responses WHERE question_id = {$question['id']} AND option_id = o.id) as response_count
                                            FROM poll_question_options o 
                                            WHERE o.question_id = {$question['id']} 
                                            ORDER BY o.option_order";
                            $options = $conn->query($options_query);
                            $total_responses = $question_responses > 0 ? $question_responses : 1;
                            ?>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <?php while ($option = $options->fetch_assoc()): ?>
                                        <?php
                                        $percentage = ($option['response_count'] / $total_responses) * 100;
                                        ?>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span><?= htmlspecialchars($option['option_text']) ?></span>
                                                <span class="text-muted"><?= $option['response_count'] ?> (<?= number_format($percentage, 1) ?>%)</span>
                                            </div>
                                            <div class="progress" style="height: 25px;">
                                                <div class="progress-bar bg-primary" role="progressbar" 
                                                     style="width: <?= $percentage ?>%;" 
                                                     aria-valuenow="<?= $percentage ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <?= number_format($percentage, 1) ?>%
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                <div class="col-md-4">
                                    <canvas id="chart-<?= $question['id'] ?>" width="200" height="200"></canvas>
                                </div>
                            </div>
                            
                            <script>
                            // Chart for question <?= $question['id'] ?>
                            <?php
                            $options->data_seek(0); // Reset pointer
                            $labels = [];
                            $data = [];
                            while ($opt = $options->fetch_assoc()) {
                                $labels[] = $opt['option_text'];
                                $data[] = $opt['response_count'];
                            }
                            ?>
                            new Chart(document.getElementById('chart-<?= $question['id'] ?>'), {
                                type: 'doughnut',
                                data: {
                                    labels: <?= json_encode($labels) ?>,
                                    datasets: [{
                                        data: <?= json_encode($data) ?>,
                                        backgroundColor: [
                                            '#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', 
                                            '#10b981', '#3b82f6', '#ef4444', '#06b6d4'
                                        ]
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true,
                                    plugins: {
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                font: {
                                                    size: 12
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                            </script>
                            
                        <?php elseif ($question['question_type'] === 'rating'): ?>
                            <?php
                            // Get rating distribution
                            $ratings = [];
                            for ($i = 1; $i <= 5; $i++) {
                                $count = $conn->query("SELECT COUNT(*) as count FROM question_responses 
                                                      WHERE question_id = {$question['id']} 
                                                      AND rating_value = $i")->fetch_assoc()['count'];
                                $ratings[$i] = $count;
                            }
                            $total_ratings = array_sum($ratings);
                            $avg_rating = $total_ratings > 0 ? array_sum(array_map(fn($k, $v) => $k * $v, array_keys($ratings), $ratings)) / $total_ratings : 0;
                            ?>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <h4>Average Rating: <?= number_format($avg_rating, 2) ?> / 5.0</h4>
                                        <div class="mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= round($avg_rating) ? 'text-warning' : 'text-muted' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <?php $percentage = $total_ratings > 0 ? ($ratings[$i] / $total_ratings) * 100 : 0; ?>
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span><?= $i ?> Stars</span>
                                                <span class="text-muted"><?= $ratings[$i] ?> (<?= number_format($percentage, 1) ?>%)</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar bg-warning" style="width: <?= $percentage ?>%;"></div>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                        <?php elseif ($question['question_type'] === 'open_ended'): ?>
                            <?php
                            $text_responses = $conn->query("SELECT text_response, responded_at FROM question_responses 
                                                           WHERE question_id = {$question['id']} 
                                                           AND text_response IS NOT NULL
                                                           ORDER BY responded_at DESC LIMIT 50");
                            ?>
                            
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-comment-dots"></i> Showing latest <?= min(50, $text_responses->num_rows) ?> of <?= $question_responses ?> text responses
                            </div>
                            
                            <?php if ($text_responses->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php while ($resp = $text_responses->fetch_assoc()): ?>
                                        <div class="list-group-item">
                                            <p class="mb-1"><?= nl2br(htmlspecialchars($resp['text_response'])) ?></p>
                                            <small class="text-muted"><i class="fas fa-clock"></i> <?= date('M d, Y H:i', strtotime($resp['responded_at'])) ?></small>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-inbox"></i> No responses yet
                                </div>
                            <?php endif; ?>
                            
                        <?php elseif ($question['question_type'] === 'word_cloud'): ?>
                            <?php
                            // Get all word cloud responses and create word frequency
                            $word_responses = $conn->query("SELECT text_response FROM question_responses 
                                                           WHERE question_id = {$question['id']} 
                                                           AND text_response IS NOT NULL");
                            
                            $all_words = [];
                            while ($wr = $word_responses->fetch_assoc()) {
                                // Split by commas and clean up
                                $words = explode(',', $wr['text_response']);
                                foreach ($words as $word) {
                                    $word = trim(strtolower($word));
                                    if (!empty($word)) {
                                        $all_words[] = $word;
                                    }
                                }
                            }
                            
                            $word_freq = array_count_values($all_words);
                            arsort($word_freq);
                            $top_words = array_slice($word_freq, 0, 20, true);
                            ?>
                            
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-cloud"></i> Word Cloud - Showing top <?= count($top_words) ?> words from <?= $question_responses ?> responses
                            </div>
                            
                            <?php if (count($top_words) > 0): ?>
                                <div class="word-cloud-display mb-4 p-4 bg-light rounded text-center">
                                    <?php 
                                    $max_count = max($word_freq);
                                    foreach ($top_words as $word => $count): 
                                        $size = 12 + (($count / $max_count) * 40); // Size between 12px and 52px
                                        $opacity = 0.5 + (($count / $max_count) * 0.5); // Opacity between 0.5 and 1.0
                                    ?>
                                        <span class="word-cloud-word me-2 mb-2" 
                                              style="font-size: <?= $size ?>px; opacity: <?= $opacity ?>; color: #6366f1; font-weight: bold; display: inline-block;">
                                            <?= htmlspecialchars($word) ?>
                                            <small class="text-muted" style="font-size: 10px;">(<?= $count ?>)</small>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                
                                <h6 class="mt-4">Word Frequency</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Word</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                                <th>Distribution</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_words as $word => $count): 
                                                $percentage = (count($all_words) > 0) ? ($count / count($all_words)) * 100 : 0;
                                            ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($word) ?></strong></td>
                                                    <td><?= $count ?></td>
                                                    <td><?= number_format($percentage, 1) ?>%</td>
                                                    <td>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar bg-primary" style="width: <?= $percentage ?>%;"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-inbox"></i> No word cloud data yet
                                </div>
                            <?php endif; ?>
                            
                        <?php elseif ($question['question_type'] === 'yes_no'): ?>
                            <?php
                            // Get Yes option
                            $yes_option = $conn->query("SELECT id FROM poll_question_options WHERE question_id = {$question['id']} AND option_text = 'Yes'")->fetch_assoc();
                            $no_option = $conn->query("SELECT id FROM poll_question_options WHERE question_id = {$question['id']} AND option_text = 'No'")->fetch_assoc();
                            
                            $yes_count = $conn->query("SELECT COUNT(*) as count FROM question_responses 
                                                      WHERE question_id = {$question['id']} 
                                                      AND option_id = {$yes_option['id']}")->fetch_assoc()['count'];
                            $no_count = $conn->query("SELECT COUNT(*) as count FROM question_responses 
                                                     WHERE question_id = {$question['id']} 
                                                     AND option_id = {$no_option['id']}")->fetch_assoc()['count'];
                            $total = $yes_count + $no_count;
                            $yes_pct = $total > 0 ? ($yes_count / $total) * 100 : 0;
                            $no_pct = $total > 0 ? ($no_count / $total) * 100 : 0;
                            ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card bg-success bg-opacity-10 border-success">
                                        <div class="card-body text-center">
                                            <h3 class="text-success"><?= $yes_count ?></h3>
                                            <p class="mb-0">Yes (<?= number_format($yes_pct, 1) ?>%)</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-danger bg-opacity-10 border-danger">
                                        <div class="card-body text-center">
                                            <h3 class="text-danger"><?= $no_count ?></h3>
                                            <p class="mb-0">No (<?= number_format($no_pct, 1) ?>%)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        <?php elseif (in_array($question['question_type'], ['date', 'date_range'])): ?>
                            <?php
                            $date_responses = $conn->query("SELECT text_response, responded_at FROM question_responses 
                                                           WHERE question_id = {$question['id']} 
                                                           AND text_response IS NOT NULL
                                                           ORDER BY text_response");
                            ?>
                            
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date Response</th>
                                            <th>Submitted At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($resp = $date_responses->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($resp['text_response']) ?></td>
                                                <td><?= date('M d, Y H:i', strtotime($resp['responded_at'])) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                        <?php elseif (in_array($question['question_type'], ['slider', 'number_input'])): ?>
                            <?php
                            // Get numeric responses
                            $numeric_responses = $conn->query("SELECT rating_value FROM question_responses 
                                                              WHERE question_id = {$question['id']} 
                                                              AND rating_value IS NOT NULL");
                            $values = [];
                            while ($nr = $numeric_responses->fetch_assoc()) {
                                $values[] = $nr['rating_value'];
                            }
                            
                            if (count($values) > 0) {
                                $avg = array_sum($values) / count($values);
                                $min = min($values);
                                $max = max($values);
                                
                                // Count frequency distribution
                                $distribution = array_count_values($values);
                                ksort($distribution);
                            ?>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="alert alert-info mb-3">
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <strong>Average:</strong> <?= number_format($avg, 2) ?>
                                            </div>
                                            <div class="col-4">
                                                <strong>Min:</strong> <?= $min ?>
                                            </div>
                                            <div class="col-4">
                                                <strong>Max:</strong> <?= $max ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h5>Distribution</h5>
                                    <?php foreach ($distribution as $value => $count): ?>
                                        <?php $pct = ($count / count($values)) * 100; ?>
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Value: <?= $value ?></span>
                                                <span class="text-muted"><?= $count ?> (<?= number_format($pct, 1) ?>%)</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar bg-info" style="width: <?= $pct ?>%;"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="col-md-4">
                                    <canvas id="chart-<?= $question['id'] ?>" width="200" height="200"></canvas>
                                </div>
                            </div>
                            
                            <script>
                            new Chart(document.getElementById('chart-<?= $question['id'] ?>'), {
                                type: 'line',
                                data: {
                                    labels: <?= json_encode(array_keys($distribution)) ?>,
                                    datasets: [{
                                        label: 'Frequency',
                                        data: <?= json_encode(array_values($distribution)) ?>,
                                        borderColor: '#17a2b8',
                                        backgroundColor: 'rgba(23, 162, 184, 0.2)',
                                        tension: 0.4
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true
                                }
                            });
                            </script>
                            
                            <?php } else { ?>
                                <p class="text-muted">No numeric responses yet</p>
                            <?php } ?>
                        
                        <?php elseif ($question['question_type'] == 'assessment'): ?>
                            <?php
                            // Assessment is like quiz/multiple_choice - radio button options
                            $options = json_decode($question['options'], true);
                            $assessment_responses = $conn->query("SELECT selected_option FROM question_responses 
                                                                 WHERE question_id = {$question['id']} 
                                                                 AND selected_option IS NOT NULL");
                            
                            $option_counts = [];
                            while ($ar = $assessment_responses->fetch_assoc()) {
                                $option = $ar['selected_option'];
                                $option_counts[$option] = ($option_counts[$option] ?? 0) + 1;
                            }
                            
                            $option_labels = [];
                            foreach ($options as $idx => $option) {
                                $option_labels[] = is_array($option) ? $option['text'] : $option;
                            }
                            ?>
                            
                            <div class="mb-3">
                                <?php foreach ($option_labels as $idx => $label): 
                                    $count = $option_counts[$idx] ?? 0;
                                    $pct = $question_responses > 0 ? ($count / $question_responses) * 100 : 0;
                                ?>
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span><?= htmlspecialchars($label) ?></span>
                                            <span class="badge bg-primary"><?= $count ?> (<?= number_format($pct, 1) ?>%)</span>
                                        </div>
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar" style="width: <?= $pct ?>%">
                                                <?= number_format($pct, 1) ?>%
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <canvas id="chart-<?= $question['id'] ?>" width="400" height="200"></canvas>
                            <script>
                            new Chart(document.getElementById('chart-<?= $question['id'] ?>'), {
                                type: 'bar',
                                data: {
                                    labels: <?= json_encode($option_labels) ?>,
                                    datasets: [{
                                        label: 'Responses',
                                        data: <?= json_encode(array_values($option_counts)) ?>,
                                        backgroundColor: '#28a745'
                                    }]
                                },
                                options: {
                                    indexAxis: 'y',
                                    responsive: true,
                                    maintainAspectRatio: true
                                }
                            });
                            </script>
                        
                        <?php elseif ($question['question_type'] == 'matrix'): ?>
                            <?php
                            // Get matrix metadata
                            $metadata = json_decode($question['metadata'] ?? '{}', true);
                            $matrix_columns = $metadata['columns'] ?? ['Poor', 'Fair', 'Good', 'Excellent'];
                            
                            // Get matrix rows (statements) from options
                            $matrix_rows_query = "SELECT * FROM poll_question_options 
                                                 WHERE question_id = {$question['id']} 
                                                 ORDER BY option_order";
                            $matrix_rows_result = $conn->query($matrix_rows_query);
                            
                            if (!$matrix_rows_result) {
                                echo '<div class="alert alert-danger">Error loading matrix: ' . $conn->error . '</div>';
                            } else {
                                $matrix_rows = [];
                                while ($row = $matrix_rows_result->fetch_assoc()) {
                                    $matrix_rows[] = $row;
                                }
                                
                                // Get all matrix responses - stored in text_response as JSON
                                // Format: responses[question_id][row_id] = column_index
                                $matrix_responses_query = "SELECT text_response FROM question_responses 
                                                          WHERE question_id = {$question['id']}";
                                $matrix_responses = $conn->query($matrix_responses_query);
                                
                                // Initialize counts: [row_id][column_index] = count
                                $matrix_counts = [];
                                if ($matrix_responses) {
                                    while ($mr = $matrix_responses->fetch_assoc()) {
                                        $response_data = json_decode($mr['text_response'], true);
                                        if (is_array($response_data)) {
                                            foreach ($response_data as $row_id => $col_idx) {
                                                if (!isset($matrix_counts[$row_id])) {
                                                    $matrix_counts[$row_id] = [];
                                                }
                                                $matrix_counts[$row_id][$col_idx] = ($matrix_counts[$row_id][$col_idx] ?? 0) + 1;
                                            }
                                        }
                                    }
                                }
                            ?>
                            
                            <?php if (empty($matrix_rows)): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> No statements configured for this matrix question.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Statement</th>
                                                <?php foreach ($matrix_columns as $col): ?>
                                                    <th class="text-center"><?= htmlspecialchars($col) ?></th>
                                                <?php endforeach; ?>
                                                <th class="text-center">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($matrix_rows as $row): 
                                                $row_total = 0;
                                                foreach ($matrix_columns as $col_idx => $col) {
                                                    $row_total += $matrix_counts[$row['id']][$col_idx] ?? 0;
                                                }
                                            ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($row['option_text']) ?></strong></td>
                                                    <?php foreach ($matrix_columns as $col_idx => $col): 
                                                        $count = $matrix_counts[$row['id']][$col_idx] ?? 0;
                                                        $pct = $row_total > 0 ? ($count / $row_total) * 100 : 0;
                                                        $opacity = $pct > 0 ? max(0.15, $pct / 100) : 0;
                                                    ?>
                                                        <td class="text-center" style="background-color: rgba(13, 110, 253, <?= $opacity ?>);">
                                                            <strong><?= $count ?></strong>
                                                            <?php if ($pct > 0): ?>
                                                                <br><small>(<?= number_format($pct, 1) ?>%)</small>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                    <td class="text-center"><strong><?= $row_total ?></strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="alert alert-info">
                                    <small><i class="fas fa-info-circle"></i> Cell color intensity represents the percentage within each row. Darker blue = higher percentage.</small>
                                </div>
                            <?php endif; ?>
                            <?php } // Close the if block for successful query ?>
                        
                        <?php else: ?>
                            <p class="text-muted">Results for this question type (<?= $question['question_type'] ?>) will be displayed here.</p>
                            <p><small>Total Responses: <?= $question_responses ?></small></p>
                            
                            <?php if ($question_responses > 0): ?>
                                <div class="alert alert-info">
                                    <strong>Raw Data:</strong><br>
                                    <?php
                                    $raw_data = $conn->query("SELECT text_response, rating_value FROM question_responses 
                                                             WHERE question_id = {$question['id']} 
                                                             LIMIT 10");
                                    while ($rd = $raw_data->fetch_assoc()) {
                                        echo htmlspecialchars($rd['text_response'] ?? $rd['rating_value']) . "<br>";
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No questions found in this poll.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Analytics Overview -->
    <?php
    // Get response statistics
    $total_respondents = $conn->query("SELECT COUNT(DISTINCT id) as count FROM poll_responses WHERE poll_id = $poll_id")->fetch_assoc()['count'];
    
    // Get responses by date
    $responses_by_date = $conn->query("SELECT DATE(responded_at) as date, COUNT(DISTINCT id) as count 
                                      FROM poll_responses 
                                      WHERE poll_id = $poll_id 
                                      GROUP BY DATE(responded_at) 
                                      ORDER BY date ASC");
    
    $dates = [];
    $counts = [];
    while ($rbd = $responses_by_date->fetch_assoc()) {
        $dates[] = date('M d', strtotime($rbd['date']));
        $counts[] = $rbd['count'];
    }
    
    // Get demographic data if available
    $demographics = $conn->query("SELECT respondent_name, respondent_email, responded_at 
                                 FROM poll_responses 
                                 WHERE poll_id = $poll_id 
                                 ORDER BY responded_at DESC 
                                 LIMIT 10");
    ?>
    
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Response Timeline</h5>
                </div>
                <div class="card-body">
                    <canvas id="responseTimeline" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Recent Respondents</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if ($demographics->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($demo = $demographics->fetch_assoc()): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between">
                                        <strong><?= htmlspecialchars($demo['respondent_name'] ?? 'Anonymous') ?></strong>
                                        <small class="text-muted"><?= date('M d, H:i', strtotime($demo['responded_at'])) ?></small>
                                    </div>
                                    <?php if ($demo['respondent_email']): ?>
                                        <small class="text-muted"><?= htmlspecialchars($demo['respondent_email']) ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No respondents yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Response Timeline Chart
    new Chart(document.getElementById('responseTimeline'), {
        type: 'line',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [{
                label: 'Responses',
                data: <?= json_encode($counts) ?>,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true
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
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    </script>
</div>

<?php include '../footer.php'; ?>
