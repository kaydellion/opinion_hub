<?php
$page_title = "Databank - Poll Results";
include_once 'header.php';

global $conn;

// Get filters
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$poll_id = isset($_GET['poll']) ? (int)$_GET['poll'] : 0;

// Get categories for filter
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

if ($poll_id > 0) {
    // Show specific poll results
    $poll = $conn->query("SELECT p.*, c.name as category_name 
                          FROM polls p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.id = $poll_id")->fetch_assoc();
    
    if (!$poll) {
        echo "<div class='container my-5'><div class='alert alert-danger'>Poll not found.</div></div>";
        include_once 'footer.php';
        exit;
    }
    ?>
    
    <div class="container my-5">
        <div class="row mb-4">
            <div class="col-md-12">
                <a href="<?php echo SITE_URL; ?>databank.php" class="btn btn-outline-secondary mb-3">
                    <i class="fas fa-arrow-left"></i> Back to Databank
                </a>
                
                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-primary text-white p-4">
                        <div class="mb-2">
                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($poll['category_name']); ?></span>
                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($poll['poll_type']); ?></span>
                        </div>
                        <h2 class="mb-0"><?php echo htmlspecialchars($poll['title']); ?></h2>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-md-3 text-center">
                                <h5 class="text-muted">Total Responses</h5>
                                <h2 class="text-primary"><?php echo $poll['total_responses']; ?></h2>
                            </div>
                            <div class="col-md-3 text-center">
                                <h5 class="text-muted">Status</h5>
                                <h4><span class="badge bg-<?php echo $poll['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($poll['status']); ?>
                                </span></h4>
                            </div>
                            <div class="col-md-3 text-center">
                                <h5 class="text-muted">Started</h5>
                                <p><?php echo date('M d, Y', strtotime($poll['start_date'] ?? $poll['created_at'])); ?></p>
                            </div>
                            <div class="col-md-3 text-center">
                                <h5 class="text-muted">Ends</h5>
                                <p><?php echo $poll['end_date'] ? date('M d, Y', strtotime($poll['end_date'])) : 'Ongoing'; ?></p>
                            </div>
                        </div>

                        <!-- Questions and Results -->
                        <?php
                        $questions = $conn->query("SELECT * FROM poll_questions WHERE poll_id = $poll_id ORDER BY question_order");
                        $q_num = 1;
                        while ($question = $questions->fetch_assoc()):
                            $question_id = $question['id'];
                            $q_type = $question['question_type'];
                            
                            // Get response count for this question
                            $total_responses_q = $conn->query("SELECT COUNT(DISTINCT response_id) as count FROM question_responses WHERE question_id = $question_id")->fetch_assoc()['count'];
                        ?>
                        
                        <div class="mb-5 pb-4 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h4>
                                    <span class="badge bg-primary me-2"><?php echo $q_num; ?></span>
                                    <?php echo htmlspecialchars($question['question_text']); ?>
                                </h4>
                                <span class="badge bg-secondary"><?php echo ucwords(str_replace('_', ' ', $q_type)); ?></span>
                            </div>
                            <p class="text-muted mb-4">
                                <i class="fas fa-users"></i> <?php echo $total_responses_q; ?> responses
                            </p>
                            
                            <?php if ($q_type === 'multiple_choice' || $q_type === 'quiz' || $q_type === 'assessment' || $q_type === 'dichotomous'): ?>
                                <?php
                                // Get options and their counts
                                $options = $conn->query("SELECT pqo.*, 
                                                         COUNT(qr.id) as vote_count 
                                                         FROM poll_question_options pqo 
                                                         LEFT JOIN question_responses qr ON pqo.id = qr.option_id AND qr.question_id = $question_id
                                                         WHERE pqo.question_id = $question_id 
                                                         GROUP BY pqo.id 
                                                         ORDER BY vote_count DESC");
                                
                                $chart_data = [];
                                $total_votes = 0;
                                
                                while ($option = $options->fetch_assoc()) {
                                    $chart_data[] = $option;
                                    $total_votes += $option['vote_count'];
                                }
                                ?>
                                
                                <div class="row">
                                    <div class="col-md-7">
                                        <?php foreach ($chart_data as $option): 
                                            $percentage = $total_votes > 0 ? ($option['vote_count'] / $total_votes) * 100 : 0;
                                        ?>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span><?php echo htmlspecialchars($option['option_text']); ?></span>
                                                <span class="fw-bold"><?php echo $option['vote_count']; ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                                            </div>
                                            <div class="progress" style="height: 30px;">
                                                <div class="progress-bar bg-primary" 
                                                     style="width: <?php echo $percentage; ?>%"
                                                     aria-valuenow="<?php echo $percentage; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <?php if ($percentage > 10): ?>
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="col-md-5">
                                        <canvas id="chart_<?php echo $question_id; ?>"></canvas>
                                        <script>
                                        new Chart(document.getElementById('chart_<?php echo $question_id; ?>'), {
                                            type: 'doughnut',
                                            data: {
                                                labels: <?php echo json_encode(array_column($chart_data, 'option_text')); ?>,
                                                datasets: [{
                                                    data: <?php echo json_encode(array_column($chart_data, 'vote_count')); ?>,
                                                    backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16']
                                                }]
                                            },
                                            options: {
                                                responsive: true,
                                                plugins: {
                                                    legend: { position: 'bottom' }
                                                }
                                            }
                                        });
                                        </script>
                                    </div>
                                </div>
                                
                            <?php elseif ($q_type === 'multiple_answer'): ?>
                                <?php
                                // Get options and their counts (can have multiple per response)
                                $options = $conn->query("SELECT pqo.*, 
                                                         COUNT(qr.id) as vote_count 
                                                         FROM poll_question_options pqo 
                                                         LEFT JOIN question_responses qr ON pqo.id = qr.option_id AND qr.question_id = $question_id
                                                         WHERE pqo.question_id = $question_id 
                                                         GROUP BY pqo.id 
                                                         ORDER BY vote_count DESC");
                                
                                $chart_data = [];
                                while ($option = $options->fetch_assoc()) {
                                    $chart_data[] = $option;
                                }
                                ?>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <?php foreach ($chart_data as $option): 
                                            $percentage = $total_responses_q > 0 ? ($option['vote_count'] / $total_responses_q) * 100 : 0;
                                        ?>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span><i class="fas fa-check-square text-primary"></i> <?php echo htmlspecialchars($option['option_text']); ?></span>
                                                <span class="fw-bold"><?php echo $option['vote_count']; ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                                            </div>
                                            <div class="progress" style="height: 30px;">
                                                <div class="progress-bar bg-success" 
                                                     style="width: <?php echo $percentage; ?>%">
                                                    <?php if ($percentage > 10): ?>
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                            <?php elseif ($q_type === 'rating'): ?>
                                <?php
                                // Get rating distribution
                                $ratings = $conn->query("SELECT text_response as rating_value, COUNT(*) as count 
                                                        FROM question_responses 
                                                        WHERE question_id = $question_id AND text_response IS NOT NULL
                                                        GROUP BY text_response 
                                                        ORDER BY text_response DESC");
                                
                                $avg_result = $conn->query("SELECT AVG(CAST(text_response AS UNSIGNED)) as avg 
                                                           FROM question_responses 
                                                           WHERE question_id = $question_id AND text_response IS NOT NULL")->fetch_assoc();
                                $avg_rating = $avg_result['avg'] ?? 0;
                                
                                $rating_counts = array_fill(1, 5, 0);
                                while ($rating = $ratings->fetch_assoc()) {
                                    $rating_counts[(int)$rating['rating_value']] = (int)$rating['count'];
                                }
                                ?>
                                
                                <div class="text-center mb-4 p-4 bg-light rounded">
                                    <h2 class="display-3 text-warning mb-2">
                                        <?php echo number_format($avg_rating, 1); ?> 
                                        <i class="fas fa-star"></i>
                                    </h2>
                                    <p class="text-muted mb-0">Average Rating</p>
                                </div>
                                
                                <?php for ($i = 5; $i >= 1; $i--): 
                                    $count = $rating_counts[$i];
                                    $percentage = $total_responses_q > 0 ? ($count / $total_responses_q) * 100 : 0;
                                ?>
                                <div class="mb-2">
                                    <div class="d-flex align-items-center">
                                        <span class="me-2" style="width: 80px;">
                                            <?php echo $i; ?> 
                                            <?php for ($s = 0; $s < $i; $s++): ?>
                                                <i class="fas fa-star text-warning"></i>
                                            <?php endfor; ?>
                                        </span>
                                        <div class="progress flex-grow-1" style="height: 25px;">
                                            <div class="progress-bar bg-warning" 
                                                 style="width: <?php echo $percentage; ?>%">
                                                <?php echo $count; ?>
                                            </div>
                                        </div>
                                        <span class="ms-2" style="width: 60px; text-align: right;">
                                            <?php echo number_format($percentage, 1); ?>%
                                        </span>
                                    </div>
                                </div>
                                <?php endfor; ?>
                                
                            <?php elseif ($q_type === 'yes_no'): ?>
                                <?php
                                $yes_count = $conn->query("SELECT COUNT(*) as count FROM question_responses WHERE question_id = $question_id AND text_response = 'Yes'")->fetch_assoc()['count'];
                                $no_count = $conn->query("SELECT COUNT(*) as count FROM question_responses WHERE question_id = $question_id AND text_response = 'No'")->fetch_assoc()['count'];
                                $total_yn = $yes_count + $no_count;
                                $yes_pct = $total_yn > 0 ? ($yes_count / $total_yn) * 100 : 0;
                                $no_pct = $total_yn > 0 ? ($no_count / $total_yn) * 100 : 0;
                                ?>
                                
                                <div class="row text-center">
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-success">
                                            <div class="card-body p-4">
                                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                                <h3 class="text-success">Yes</h3>
                                                <h2 class="display-4"><?php echo $yes_count; ?></h2>
                                                <p class="text-muted"><?php echo number_format($yes_pct, 1); ?>%</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-danger">
                                            <div class="card-body p-4">
                                                <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                                                <h3 class="text-danger">No</h3>
                                                <h2 class="display-4"><?php echo $no_count; ?></h2>
                                                <p class="text-muted"><?php echo number_format($no_pct, 1); ?>%</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            <?php elseif ($q_type === 'word_cloud'): ?>
                                <?php
                                $text_responses = $conn->query("SELECT text_response 
                                                                FROM question_responses 
                                                                WHERE question_id = $question_id 
                                                                AND text_response IS NOT NULL
                                                                AND text_response != ''");
                                
                                if (!$text_responses) {
                                    echo '<div class="alert alert-danger">Error loading responses: ' . $conn->error . '</div>';
                                } elseif ($text_responses->num_rows === 0) {
                                    echo '<div class="alert alert-info">No word cloud data yet.</div>';
                                } else {
                                    $all_words = [];
                                    while ($response = $text_responses->fetch_assoc()) {
                                        $words = explode(',', $response['text_response']);
                                        foreach ($words as $word) {
                                            $word = trim(strtolower($word));
                                            if (!empty($word)) {
                                                $all_words[] = $word;
                                            }
                                        }
                                    }
                                    
                                    if (empty($all_words)) {
                                        echo '<div class="alert alert-info">No words found in responses.</div>';
                                    } else {
                                        $word_counts = array_count_values($all_words);
                                        arsort($word_counts);
                                        $max_count = max(array_values($word_counts));
                                ?>
                                
                                <div class="word-cloud-container p-4 bg-light rounded text-center">
                                    <?php foreach (array_slice($word_counts, 0, 50) as $word => $count): 
                                        $size = 12 + (($count / $max_count) * 48); // 12px to 60px
                                        $opacity = 0.5 + (($count / $max_count) * 0.5); // 0.5 to 1.0
                                    ?>
                                        <span class="word-cloud-word" 
                                              style="font-size: <?php echo $size; ?>px; 
                                                     opacity: <?php echo $opacity; ?>;
                                                     color: hsl(<?php echo rand(200, 280); ?>, 70%, 50%);
                                                     margin: 0 8px 8px 0;
                                                     display: inline-block;
                                                     font-weight: <?php echo 400 + ($count / $max_count) * 300; ?>;"
                                              title="<?php echo $count; ?> mentions">
                                            <?php echo htmlspecialchars($word); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <p class="text-muted text-center mt-2">
                                    <small>Word size represents frequency • Showing top 50 words • Total unique words: <?php echo count($word_counts); ?></small>
                                </p>
                                <?php 
                                    } 
                                } 
                                ?>
                                
                            <?php elseif ($q_type === 'open_ended'): ?>
                                <?php
                                $text_responses = $conn->query("SELECT qr.text_response, pr.responded_at 
                                                                FROM question_responses qr
                                                                JOIN poll_responses pr ON qr.response_id = pr.id
                                                                WHERE qr.question_id = $question_id 
                                                                AND qr.text_response IS NOT NULL 
                                                                AND qr.text_response != ''
                                                                ORDER BY pr.responded_at DESC 
                                                                LIMIT 50");
                                
                                if (!$text_responses) {
                                    echo '<div class="alert alert-danger">Error loading responses: ' . $conn->error . '</div>';
                                } elseif ($text_responses->num_rows === 0) {
                                    echo '<div class="alert alert-info">No text responses yet.</div>';
                                } else {
                                ?>
                                
                                <div class="open-ended-responses" style="max-height: 400px; overflow-y: auto;">
                                    <?php $resp_num = 1; ?>
                                    <?php while ($response = $text_responses->fetch_assoc()): ?>
                                        <div class="card mb-2">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="badge bg-secondary">Response #<?php echo $resp_num++; ?></span>
                                                    <small class="text-muted">
                                                        <?php echo date('M d, Y H:i', strtotime($response['responded_at'])); ?>
                                                    </small>
                                                </div>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($response['text_response'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                <?php } ?>
                                
                            <?php elseif ($q_type === 'date'): ?>
                                <?php
                                $dates = $conn->query("SELECT text_response, COUNT(*) as count 
                                                      FROM question_responses 
                                                      WHERE question_id = $question_id AND text_response IS NOT NULL
                                                      AND text_response != ''
                                                      GROUP BY text_response 
                                                      ORDER BY text_response DESC 
                                                      LIMIT 20");
                                
                                if (!$dates || $dates->num_rows === 0) {
                                    echo '<div class="alert alert-info">No date responses yet.</div>';
                                } else {
                                ?>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th><i class="fas fa-calendar"></i> Date</th>
                                                <th>Responses</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($date = $dates->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo date('F d, Y', strtotime($date['text_response'])); ?></td>
                                                    <td><span class="badge bg-primary"><?php echo $date['count']; ?></span></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php } ?>
                                
                            <?php elseif ($q_type === 'date_range'): ?>
                                <?php
                                $date_ranges = $conn->query("SELECT text_response 
                                                            FROM question_responses 
                                                            WHERE question_id = $question_id AND text_response IS NOT NULL
                                                            AND text_response != ''
                                                            LIMIT 20");
                                
                                if (!$date_ranges || $date_ranges->num_rows === 0) {
                                    echo '<div class="alert alert-info">No date range responses yet.</div>';
                                } else {
                                ?>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th><i class="fas fa-calendar-alt"></i> Start Date</th>
                                                <th><i class="fas fa-calendar-alt"></i> End Date</th>
                                                <th>Duration</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($dr = $date_ranges->fetch_assoc()): 
                                                $range = json_decode($dr['text_response'], true);
                                                if ($range && isset($range['start']) && isset($range['end'])):
                                                    $start = new DateTime($range['start']);
                                                    $end = new DateTime($range['end']);
                                                    $diff = $start->diff($end);
                                            ?>
                                                <tr>
                                                    <td><?php echo $start->format('M d, Y'); ?></td>
                                                    <td><?php echo $end->format('M d, Y'); ?></td>
                                                    <td><?php echo $diff->days; ?> days</td>
                                                </tr>
                                            <?php endif; endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php } ?>
                                
                            <?php elseif ($q_type === 'matrix'): ?>
                                <?php
                                // Matrix is complex - show simplified results
                                $matrix_responses = $conn->query("SELECT option_id, text_response, COUNT(*) as count 
                                                                  FROM question_responses 
                                                                  WHERE question_id = $question_id 
                                                                  GROUP BY option_id, text_response");
                                ?>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Matrix question results are displayed in the detailed analytics view.
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Selection</th>
                                                <th>Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($mr = $matrix_responses->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($mr['text_response'] ?? 'Option ' . $mr['option_id']); ?></td>
                                                    <td><span class="badge bg-primary"><?php echo $mr['count']; ?></span></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    Results visualization for "<?php echo htmlspecialchars($q_type); ?>" is not yet available.
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php 
                            $q_num++;
                        endwhile; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
} else {
    // Show all polls
    $where = [];
    if ($category > 0) {
        $where[] = "category_id = $category";
    }
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $polls = $conn->query("SELECT p.*, c.name as category_name 
                          FROM polls p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          $where_clause
                          ORDER BY p.total_responses DESC, p.created_at DESC");
    ?>
    
    <div class="container my-5">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1 class="mb-3"><i class="fas fa-database"></i> Poll Databank</h1>
                <p class="text-muted">Explore poll results and insights</p>
            </div>
        </div>

        <!-- Filter -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-10">
                                <select class="form-select" name="category">
                                    <option value="">All Categories</option>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Polls List -->
        <div class="row">
            <?php while ($poll = $polls->fetch_assoc()): ?>
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="mb-2">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($poll['category_name']); ?></span>
                                <span class="badge bg-info"><?php echo htmlspecialchars($poll['poll_type']); ?></span>
                            </div>
                            
                            <h5 class="card-title"><?php echo htmlspecialchars($poll['title']); ?></h5>
                            
                            <p class="card-text text-muted">
                                <?php echo substr(htmlspecialchars($poll['description']), 0, 150); ?>...
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <i class="fas fa-users text-primary"></i>
                                    <span class="fw-bold"><?php echo $poll['total_responses']; ?></span> responses
                                </div>
                                <div>
                                    <span class="badge bg-<?php echo $poll['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($poll['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <a href="<?php echo SITE_URL; ?>databank.php?poll=<?php echo $poll['id']; ?>" 
                               class="btn btn-primary w-100">
                                <i class="fas fa-chart-bar"></i> View Results
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    
<?php } ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<style>
.word-cloud-container {
    line-height: 2;
    min-height: 200px;
}
.word-cloud-word {
    transition: all 0.3s;
    cursor: default;
}
.word-cloud-word:hover {
    transform: scale(1.1);
}
.open-ended-responses {
    scrollbar-width: thin;
}
.open-ended-responses::-webkit-scrollbar {
    width: 8px;
}
.open-ended-responses::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}
.open-ended-responses::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}
.open-ended-responses::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

<?php include_once 'footer.php'; ?>
