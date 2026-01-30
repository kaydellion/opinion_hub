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
$access_query = $conn->query("SELECT pra.*, dd.dataset_format, dd.time_period
                              FROM poll_results_access pra
                              LEFT JOIN dataset_downloads dd ON dd.user_id = pra.user_id AND dd.poll_id = pra.poll_id
                              WHERE pra.user_id = {$current_user['id']}
                              AND pra.poll_id = $poll_id
                              ORDER BY dd.download_date DESC
                              LIMIT 1");

$access_check = $access_query ? $access_query->fetch_assoc() : null;

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
if (!in_array($time_period, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])) {
    $time_period = 'monthly';
}

// Get poll details
$poll_query = $conn->query("SELECT p.*, c.name as category_name, u.first_name, u.last_name, u.email
                            FROM polls p
                            LEFT JOIN categories c ON p.category_id = c.id
                            LEFT JOIN users u ON p.created_by = u.id
                            WHERE p.id = $poll_id");

$poll = $poll_query ? $poll_query->fetch_assoc() : null;

if (!$poll) {
    $_SESSION['error'] = "Poll not found.";
    header('Location: ' . SITE_URL . 'my-purchased-results.php');
    exit;
}

// Get total responses
$response_query = $conn->query("SELECT COUNT(*) as count FROM poll_responses WHERE poll_id = $poll_id");
$response_count = $response_query ? $response_query->fetch_assoc()['count'] : 0;

// Get questions
$questions = $conn->query("SELECT * FROM poll_questions WHERE poll_id = $poll_id ORDER BY question_order");

// Handle PDF export BEFORE including header
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    // Include TCPDF library
    require_once 'tcpdf/tcpdf.php';
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('OpinionHub');
    $pdf->SetAuthor('OpinionHub');
    $pdf->SetTitle('Poll Results: ' . $poll['title']);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 12);
    
    // Title
    $pdf->Cell(0, 10, 'Poll Results: ' . $poll['title'], 0, 1, 'C');
    $pdf->Ln(5);
    
    // Poll details
    $pdf->Cell(0, 8, 'Category: ' . $poll['category_name'], 0, 1);
    $pdf->Cell(0, 8, 'Total Responses: ' . $response_count, 0, 1);
    $pdf->Cell(0, 8, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1);
    $pdf->Ln(10);
    
    // Questions and results
    $question_num = 1;
    while ($question = $questions->fetch_assoc()) {
        $q_type = $question['question_type'];
        
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, "Q{$question_num}: " . $question['question_text'], 0, 1);
        $pdf->SetFont('helvetica', '', 11);
        
        if (in_array($q_type, ['multiple_choice', 'multiple_answer', 'quiz', 'assessment', 'dichotomous'])) {
            // FIXED: Use poll_question_options instead of poll_options
            $options = $conn->query("SELECT o.option_text, o.is_correct, COUNT(qr.id) as count 
                                    FROM poll_question_options o 
                                    LEFT JOIN question_responses qr ON o.id = qr.option_id 
                                    WHERE o.question_id = {$question['id']} 
                                    GROUP BY o.id ORDER BY count DESC");
            
            while ($option = $options->fetch_assoc()) {
                $percentage = $response_count > 0 ? round(($option['count'] / $response_count) * 100, 1) : 0;
                $pdf->Cell(0, 6, "• " . $option['option_text'] . " ({$option['count']} responses, {$percentage}%)", 0, 1);
            }
        } elseif ($q_type === 'rating') {
            $avg_rating = $conn->query("SELECT AVG(rating) as avg FROM question_responses WHERE question_id = {$question['id']}")->fetch_assoc()['avg'];
            $pdf->Cell(0, 6, "Average Rating: " . round($avg_rating, 2), 0, 1);
        } elseif ($q_type === 'yes_no') {
            $yes_count = $conn->query("SELECT COUNT(*) as count FROM question_responses WHERE question_id = {$question['id']} AND response_text = 'Yes'")->fetch_assoc()['count'];
            $no_count = $conn->query("SELECT COUNT(*) as count FROM question_responses WHERE question_id = {$question['id']} AND response_text = 'No'")->fetch_assoc()['count'];
            $pdf->Cell(0, 6, "Yes: {$yes_count} (" . round(($yes_count/$response_count)*100, 1) . "%)", 0, 1);
            $pdf->Cell(0, 6, "No: {$no_count} (" . round(($no_count/$response_count)*100, 1) . "%)", 0, 1);
        } elseif ($q_type === 'open_ended') {
            $responses = $conn->query("SELECT response_text FROM question_responses WHERE question_id = {$question['id']} LIMIT 10");
            while ($response = $responses->fetch_assoc()) {
                $pdf->Cell(0, 6, "• " . substr($response['response_text'], 0, 100) . "...", 0, 1);
            }
        }
        
        $pdf->Ln(8);
        $question_num++;
    }
    
    // Close and output PDF document
    $pdf->Output('poll_results_' . $poll_id . '.pdf', 'D');
    exit;
}

require_once 'header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <h2><i class="fas fa-chart-bar"></i> Poll Results: <?= htmlspecialchars($poll['title']) ?></h2>
            <p class="text-muted">
                <i class="fas fa-folder"></i> <?= htmlspecialchars($poll['category_name']) ?> | 
                <i class="fas fa-users"></i> <?= $response_count ?> responses | 
                <i class="fas fa-calendar"></i> <?= date('M j, Y', strtotime($poll['created_at'])) ?>
            </p>
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
    
    <!-- Format and Period Selection -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6>View Format</h6>
                    <div class="btn-group" role="group">
                        <a href="?id=<?php echo $poll_id; ?>&format=combined&period=<?php echo $time_period; ?>" 
                           class="btn btn-outline-primary <?= $format === 'combined' ? 'active' : '' ?>">
                            Combined View
                        </a>
                        <a href="?id=<?php echo $poll_id; ?>&format=single&period=<?php echo $time_period; ?>" 
                           class="btn btn-outline-primary <?= $format === 'single' ? 'active' : '' ?>">
                            Individual Questions
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6>Time Period</h6>
                    <div class="btn-group" role="group">
                        <a href="?id=<?php echo $poll_id; ?>&format=<?php echo $format; ?>&period=daily" 
                           class="btn btn-outline-secondary <?= $time_period === 'daily' ? 'active' : '' ?>">
                            Daily
                        </a>
                        <a href="?id=<?php echo $poll_id; ?>&format=<?php echo $format; ?>&period=weekly" 
                           class="btn btn-outline-secondary <?= $time_period === 'weekly' ? 'active' : '' ?>">
                            Weekly
                        </a>
                        <a href="?id=<?php echo $poll_id; ?>&format=<?php echo $format; ?>&period=monthly" 
                           class="btn btn-outline-secondary <?= $time_period === 'monthly' ? 'active' : '' ?>">
                            Monthly
                        </a>
                        <a href="?id=<?php echo $poll_id; ?>&format=<?php echo $format; ?>&period=quarterly" 
                           class="btn btn-outline-secondary <?= $time_period === 'quarterly' ? 'active' : '' ?>">
                            Quarterly
                        </a>
                        <a href="?id=<?php echo $poll_id; ?>&format=<?php echo $format; ?>&period=yearly" 
                           class="btn btn-outline-secondary <?= $time_period === 'yearly' ? 'active' : '' ?>">
                            Yearly
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($format === 'combined'): ?>
        <!-- Combined View -->
        <div class="row">
            <div class="col-md-8">
                <h3>Question Results</h3>
                <?php
                $question_num = 1;
                $questions->data_seek(0); // Reset pointer
                while ($question = $questions->fetch_assoc()) {
                    $q_type = $question['question_type'];
                    
                    echo "<div class='card mb-4'>";
                    echo "<div class='card-body'>";
                    echo "<h3>Q" . $question['question_order'] . ": " . htmlspecialchars($question['question_text']) . "</h3>";
                    echo "<p><em>Type: " . ucfirst(str_replace('_', ' ', $q_type)) . "</em></p>";
                    
                    if (in_array($q_type, ['multiple_choice', 'multiple_answer', 'quiz', 'assessment', 'dichotomous'])) {
                        // FIXED: Use poll_question_options instead of poll_options
                        $options = $conn->query("SELECT o.option_text, o.is_correct, COUNT(qr.id) as count 
                                                FROM poll_question_options o 
                                                LEFT JOIN question_responses qr ON o.id = qr.option_id 
                                                WHERE o.question_id = {$question['id']} 
                                                GROUP BY o.id ORDER BY count DESC");
                        
                        while ($option = $options->fetch_assoc()) {
                            $percentage = $response_count > 0 ? round(($option['count'] / $response_count) * 100, 1) : 0;
                            $is_correct = ($q_type == 'quiz' || $q_type == 'assessment') && $option['is_correct'];
                            
                            echo "<div class='option'>";
                            echo "<div class='d-flex justify-content-between align-items-center'>";
                            echo "<span>" . htmlspecialchars($option['option_text']) . ($is_correct ? " ✓" : "") . "</span>";
                            echo "<span class='badge bg-primary'>{$option['count']} ({$percentage}%)</span>";
                            echo "</div>";
                            echo "<div class='progress mt-1'>";
                            echo "<div class='progress-bar' style='width: {$percentage}%'></div>";
                            echo "</div>";
                            echo "</div>";
                        }
                    } elseif ($q_type === 'rating') {
                        $avg_rating = $conn->query("SELECT AVG(rating) as avg FROM question_responses WHERE question_id = {$question['id']}")->fetch_assoc()['avg'];
                        echo "<div class='alert alert-info'>";
                        echo "<strong>Average Rating:</strong> " . round($avg_rating, 2) . " / 5";
                        echo "</div>";
                    } elseif ($q_type === 'yes_no') {
                        $yes_count = $conn->query("SELECT COUNT(*) as count FROM question_responses WHERE question_id = {$question['id']} AND response_text = 'Yes'")->fetch_assoc()['count'];
                        $no_count = $conn->query("SELECT COUNT(*) as count FROM question_responses WHERE question_id = {$question['id']} AND response_text = 'No'")->fetch_assoc()['count'];
                        
                        $yes_pct = round(($yes_count / $response_count) * 100, 1);
                        $no_pct = round(($no_count / $response_count) * 100, 1);
                        
                        echo "<div class='row'>";
                        echo "<div class='col-md-6'>";
                        echo "<div class='card text-center'>";
                        echo "<div class='card-body'>";
                        echo "<h4 class='text-success'>Yes</h4>";
                        echo "<h2>{$yes_count}</h2>";
                        echo "<small class='text-muted'>{$yes_pct}%</small>";
                        echo "</div>";
                        echo "</div>";
                        echo "</div>";
                        echo "<div class='col-md-6'>";
                        echo "<div class='card text-center'>";
                        echo "<div class='card-body'>";
                        echo "<h4 class='text-danger'>No</h4>";
                        echo "<h2>{$no_count}</h2>";
                        echo "<small class='text-muted'>{$no_pct}%</small>";
                        echo "</div>";
                        echo "</div>";
                        echo "</div>";
                        echo "</div>";
                    } elseif ($q_type === 'open_ended') {
                        $responses = $conn->query("SELECT response_text FROM question_responses WHERE question_id = {$question['id']} LIMIT 5");
                        echo "<div class='responses-list'>";
                        while ($response = $responses->fetch_assoc()) {
                            echo "<div class='alert alert-light'>";
                            echo "<p>" . htmlspecialchars($response['response_text']) . "</p>";
                            echo "</div>";
                        }
                        echo "</div>";
                    }
                    
                    echo "</div>";
                    echo "</div>";
                    $question_num++;
                }
                ?>
            </div>
            
            <div class="col-md-4">
                <h3>Demographics</h3>
                <div class="card">
                    <div class="card-body">
                        <h6>By Age and Gender</h6>
                        <?php
                        $demographics = $conn->query("SELECT respondent_age, 
                                                     respondent_gender, 
                                                     COUNT(*) as count 
                                                     FROM poll_responses 
                                                     WHERE poll_id = $poll_id 
                                                     AND respondent_age IS NOT NULL 
                                                     GROUP BY respondent_age, respondent_gender");
                        
                        if ($demographics && $demographics->num_rows > 0) {
                            while ($demo = $demographics->fetch_assoc()) {
                                echo "<p><strong>Age {$demo['respondent_age']} - {$demo['respondent_gender']}:</strong> {$demo['count']}</p>";
                            }
                        } else {
                            echo "<p class='text-muted'>No demographic data available</p>";
                        }
                        ?>
                        
                        <h6 class="mt-4">By Age Group</h6>
                        <?php
                        $age_groups = $conn->query("SELECT respondent_age, COUNT(*) as count 
                                                   FROM poll_responses 
                                                   WHERE poll_id = $poll_id 
                                                   AND respondent_age IS NOT NULL 
                                                   GROUP BY respondent_age 
                                                   ORDER BY respondent_age");
                        
                        if ($age_groups && $age_groups->num_rows > 0) {
                            while ($age = $age_groups->fetch_assoc()) {
                                $percentage = round(($age['count'] / $response_count) * 100, 1);
                                echo "<p><strong>Age {$age['respondent_age']}:</strong> {$age['count']} ({$percentage}%)</p>";
                            }
                        } else {
                            echo "<p class='text-muted'>No age data available</p>";
                        }
                        ?>
                        
                        <h6 class="mt-4">By Gender</h6>
                        <?php
                        $genders = $conn->query("SELECT respondent_gender, COUNT(*) as count 
                                                 FROM poll_responses 
                                                 WHERE poll_id = $poll_id 
                                                 AND respondent_gender IS NOT NULL 
                                                 GROUP BY respondent_gender");
                        
                        if ($genders && $genders->num_rows > 0) {
                            while ($gender = $genders->fetch_assoc()) {
                                $percentage = round(($gender['count'] / $response_count) * 100, 1);
                                echo "<p><strong>{$gender['respondent_gender']}:</strong> {$gender['count']} ({$percentage}%)</p>";
                            }
                        } else {
                            echo "<p class='text-muted'>No gender data available</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Individual Questions View -->
        <div class="row">
            <div class="col-12">
                <?php
                $question_num = 1;
                $questions->data_seek(0); // Reset pointer
                while ($question = $questions->fetch_assoc()) {
                    $q_type = $question['question_type'];
                    
                    echo "<div class='card mb-4'>";
                    echo "<div class='card-header'>";
                    echo "<h4 class='mb-0'>Q" . $question['question_order'] . ": " . htmlspecialchars($question['question_text']) . "</h4>";
                    echo "<small class='text-muted'>Type: " . ucfirst(str_replace('_', ' ', $q_type)) . "</small>";
                    echo "</div>";
                    echo "<div class='card-body'>";
                    
                    // Chart container
                    echo "<div class='chart-container mb-4'>";
                    echo "<canvas id='chart_{$question['id']}' width='400' height='200'></canvas>";
                    echo "</div>";
                    
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
                        // FIXED: Use poll_question_options instead of poll_options
                        $options = $conn->query("SELECT o.option_text, o.is_correct, COUNT(qr.id) as count
                                                FROM poll_question_options o
                                                LEFT JOIN question_responses qr ON o.id = qr.option_id
                                                WHERE o.question_id = {$question['id']}
                                                GROUP BY o.id
                                                ORDER BY count DESC");
                        
                        $chart_labels = [];
                        $chart_data = [];
                        $chart_colors = [];
                        
                        $color_palette = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];
                        $color_index = 0;
                        
                        while ($option = $options->fetch_assoc()) {
                            $chart_labels[] = '"' . addslashes($option['option_text']) . '"';
                            $chart_data[] = $option['count'];
                            $chart_colors[] = '"' . $color_palette[$color_index % count($color_palette)] . '"';
                            $color_index++;
                        }
                        
                        echo "<script>";
                        echo "var ctx_{$question['id']} = document.getElementById('chart_{$question['id']}').getContext('2d');";
                        echo "var chart_{$question['id']} = new Chart(ctx_{$question['id']}, {";
                        echo "    type: '{$chart_type}',";
                        echo "    data: {";
                        echo "        labels: [" . implode(', ', $chart_labels) . "],";
                        echo "        datasets: [{";
                        echo "            data: [" . implode(', ', $chart_data) . "],";
                        echo "            backgroundColor: [" . implode(', ', $chart_colors) . "]";
                        echo "        }]";
                        echo "    },";
                        echo "    options: {";
                        echo "        responsive: true,";
                        echo "        plugins: {";
                        echo "            legend: {";
                        echo "                position: 'bottom'";
                        echo "            }";
                        echo "        }";
                        echo "    }";
                        echo "});";
                        echo "</script>";
                        
                    } elseif ($q_type === 'rating') {
                        $ratings = $conn->query("SELECT rating, COUNT(*) as count 
                                                 FROM question_responses 
                                                 WHERE question_id = {$question['id']} 
                                                 GROUP BY rating 
                                                 ORDER BY rating");
                        
                        $chart_labels = [];
                        $chart_data = [];
                        
                        for ($i = 1; $i <= 5; $i++) {
                            $chart_labels[] = '"' . $i . ' Star' . ($i > 1 ? 's' : '') . '"';
                            $chart_data[] = 0;
                        }
                        
                        while ($rating = $ratings->fetch_assoc()) {
                            $chart_data[$rating['rating'] - 1] = $rating['count'];
                        }
                        
                        echo "<script>";
                        echo "var ctx_{$question['id']} = document.getElementById('chart_{$question['id']}').getContext('2d');";
                        echo "var chart_{$question['id']} = new Chart(ctx_{$question['id']}, {";
                        echo "    type: 'bar',";
                        echo "    data: {";
                        echo "        labels: [" . implode(', ', $chart_labels) . "],";
                        echo "        datasets: [{";
                        echo "            label: 'Number of Ratings',";
                        echo "            data: [" . implode(', ', $chart_data) . "],";
                        echo "            backgroundColor: '#36A2EB'";
                        echo "        }]";
                        echo "    },";
                        echo "    options: {";
                        echo "        responsive: true,";
                        echo "        scales: {";
                        echo "            y: {";
                        echo "                beginAtZero: true";
                        echo "            }";
                        echo "        }";
                        echo "    }";
                        echo "});";
                        echo "</script>";
                        
                    } elseif ($q_type === 'yes_no') {
                        $yes_count = $conn->query("SELECT COUNT(*) as count FROM question_responses WHERE question_id = {$question['id']} AND response_text = 'Yes'")->fetch_assoc()['count'];
                        $no_count = $conn->query("SELECT COUNT(*) as count FROM question_responses WHERE question_id = {$question['id']} AND response_text = 'No'")->fetch_assoc()['count'];
                        
                        echo "<script>";
                        echo "var ctx_{$question['id']} = document.getElementById('chart_{$question['id']}').getContext('2d');";
                        echo "var chart_{$question['id']} = new Chart(ctx_{$question['id']}, {";
                        echo "    type: 'doughnut',";
                        echo "    data: {";
                        echo "        labels: ['Yes', 'No'],";
                        echo "        datasets: [{";
                        echo "            data: [{$yes_count}, {$no_count}],";
                        echo "            backgroundColor: ['#4BC0C0', '#FF6384']";
                        echo "        }]";
                        echo "    },";
                        echo "    options: {";
                        echo "        responsive: true,";
                        echo "        plugins: {";
                        echo "            legend: {";
                        echo "                position: 'bottom'";
                        echo "            }";
                        echo "        }";
                        echo "    }";
                        echo "});";
                        echo "</script>";
                    }
                    
                    echo "</div>";
                    echo "</div>";
                    $question_num++;
                }
                ?>
            </div>
        </div>
        
        <!-- Individual Responses -->
        <div class="row mt-4">
            <div class="col-12">
                <h3>Individual Responses</h3>
                <div class="card">
                    <div class="card-body">
                        <?php
                        $responses_per_page = 10;
                        $response_page = isset($_GET['response_page']) ? (int)$_GET['response_page'] : 1;
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
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Response Time</th>
                                            <th>Age</th>
                                            <th>Gender</th>
                                            <th>Location</th>
                                            <th>Responses</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($response = $individual_responses->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= date('M j, Y H:i', strtotime($response['response_time'])) ?></td>
                                                <td><?= $response['respondent_age'] ?? 'N/A' ?></td>
                                                <td><?= $response['respondent_gender'] ?? 'N/A' ?></td>
                                                <td><?= $response['respondent_location'] ?? 'N/A' ?></td>
                                                <td>
                                                    <?php
                                                    $question_responses = $conn->query("SELECT qr.*, pq.question_text, pq.question_type, pqo.option_text
                                                                                         FROM question_responses qr
                                                                                         LEFT JOIN poll_questions pq ON qr.question_id = pq.id
                                                                                         LEFT JOIN poll_question_options pqo ON qr.option_id = pqo.id
                                                                                         WHERE qr.response_id = {$response['id']}");
                                                    
                                                    while ($qr = $question_responses->fetch_assoc()) {
                                                        if ($qr['question_type'] === 'open_ended') {
                                                            echo "<strong>" . htmlspecialchars($qr['question_text']) . ":</strong> " . htmlspecialchars($qr['response_text']) . "<br>";
                                                        } else {
                                                            echo "<strong>" . htmlspecialchars($qr['question_text']) . ":</strong> " . htmlspecialchars($qr['option_text']) . "<br>";
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_response_pages > 1): ?>
                                <nav aria-label="Response pagination">
                                    <ul class="pagination justify-content-center">
                                        <?php for ($i = 1; $i <= $total_response_pages; $i++): ?>
                                            <li class="page-item <?= $i === $response_page ? 'active' : '' ?>">
                                                <a class="page-link" href="?id=<?php echo $poll_id; ?>&format=<?php echo $format; ?>&period=<?php echo $time_period; ?>&response_page=<?php echo $i; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted">No individual responses found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Time-based Analytics -->
    <?php if ($format === 'combined'): ?>
        <div class="row mt-4">
            <div class="col-12">
                <h3>Response Trends Over Time</h3>
                <div class="card">
                    <div class="card-body">
                        <canvas id="trendsChart" width="400" height="200"></canvas>
                        
                        <?php
                        // Prepare time-based data based on selected period
                        $group_by = '';
                        $date_format = '';
                        
                        switch ($time_period) {
                            case 'daily':
                                $group_by = "DATE(responded_at)";
                                $date_format = 'M j, Y';
                                break;
                            case 'weekly':
                                $group_by = "CONCAT(YEAR(responded_at), '-W', WEEK(responded_at, 1))";
                                $date_format = 'Y [W]W';
                                break;
                            case 'monthly':
                                $group_by = "DATE_FORMAT(responded_at, '%Y-%m')";
                                $date_format = 'M Y';
                                break;
                            case 'quarterly':
                                $group_by = "CONCAT(YEAR(responded_at), '-Q', QUARTER(responded_at))";
                                $date_format = 'Y [Q]Q';
                                break;
                            case 'yearly':
                                $group_by = "YEAR(responded_at)";
                                $date_format = 'Y';
                                break;
                        }
                        
                        $trends_data = $conn->query("SELECT $group_by as period,
                                                     COUNT(*) as total_responses,
                                                     DATE_FORMAT(MIN(pr.responded_at), '%M %d, %Y') as period_start,
                                                     DATE_FORMAT(MAX(pr.responded_at), '%M %d, %Y') as period_end
                                                     FROM poll_responses pr
                                                     WHERE pr.poll_id = $poll_id
                                                     GROUP BY {$group_by}
                                                     ORDER BY period DESC");
                        
                        $period_labels = [];
                        $response_counts = [];
                        
                        if ($trends_data && $trends_data->num_rows > 0) {
                            while ($trend = $trends_data->fetch_assoc()) {
                                $period_labels[] = '"' . htmlspecialchars($trend['period']) . '"';
                                $response_counts[] = $trend['total_responses'];
                            }
                        }
                        ?>
                        
                        <script>
                        var trendsCtx = document.getElementById('trendsChart').getContext('2d');
                        var trendsChart = new Chart(trendsCtx, {
                            type: 'line',
                            data: {
                                labels: [<?php echo implode(', ', array_reverse($period_labels)); ?>],
                                datasets: [{
                                    label: 'Number of Responses',
                                    data: [<?php echo implode(', ', array_reverse($response_counts)); ?>],
                                    borderColor: '#36A2EB',
                                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Response Trends (<?php echo ucfirst($time_period); ?>)'
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                        </script>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php require_once 'footer.php'; ?>
