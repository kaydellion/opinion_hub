<?php
// Include required files first
include_once 'connect.php';
include_once 'functions.php';

// Get filters first
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : '';
$location = isset($_GET['location']) ? sanitize($_GET['location']) : '';

// Pagination
$per_page = 4; // Show 4 polls per page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$page_title = ($filter === 'following') ? "Polls from People You Follow" : "Browse Polls";
include_once 'header.php';

// Function to build pagination URLs while preserving filters
function buildPaginationUrl($page_num) {
    global $category, $search, $filter, $location;
    $params = [];

    if ($category > 0) $params[] = "category=$category";
    if (!empty($search)) $params[] = "search=" . urlencode($search);
    if (!empty($filter)) $params[] = "filter=$filter";
    if (!empty($location)) $params[] = "location=" . urlencode($location);
    $params[] = "page=$page_num";

    $query_string = implode('&', $params);
    return SITE_URL . "polls.php?" . $query_string;
}

global $conn;

// Build query - respect suspension status for non-admins
$user_role = isLoggedIn() ? getCurrentUser()['role'] : null;
$base_status = ($user_role === 'admin') ? "status IN ('active', 'paused')" : "status = 'active'";
$where = [$base_status];

if ($category > 0) {
    $where[] = "category_id = $category";
}
if (!empty($search)) {
    $where[] = "(p.title LIKE '%$search%' OR p.description LIKE '%$search%')";
}
if (!empty($location)) {
    $where[] = "(agent_state_criteria = '$location' OR agent_location_all = 1)";
}

// Handle following filter for logged-in users
if ($filter === 'following' && isLoggedIn()) {
    $user_id = getCurrentUser()['id'];
    $where[] = "EXISTS (SELECT 1 FROM user_follows uf WHERE uf.follower_id = $user_id AND uf.following_id = p.created_by)";
}

$where_clause = implode(' AND ', $where);

// Get total count for pagination (exclude ended polls)
$total_query = "SELECT COUNT(*) as total FROM polls p WHERE $where_clause AND (p.end_date IS NULL OR DATE(p.end_date) >= CURDATE())";
$total_result = $conn->query($total_query);
$total_polls = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_polls / $per_page);

// Get polls with pagination - make sure to select slug column
// Exclude polls that have ended (past end_date)
$polls_query = "SELECT p.*, c.name as category_name,
                (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as total_responses
                FROM polls p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE $where_clause
                AND (p.end_date IS NULL OR DATE(p.end_date) >= CURDATE())
                ORDER BY p.created_at DESC
                LIMIT $per_page OFFSET $offset";
$polls = $conn->query($polls_query);

// Add question count and progress to polls data
$polls_data = [];
while ($poll = $polls->fetch_assoc()) {
    $poll['question_count'] = getPollQuestionCount($poll['id']);
    $poll['progress_percentage'] = getPollProgressPercentage($poll);
    $polls_data[] = $poll;
}

// Get latest 6 blog articles
$latest_articles = $conn->query("SELECT bp.*, CONCAT(u.first_name, ' ', u.last_name) as author_name,
                                 (SELECT COUNT(*) FROM blog_likes WHERE post_id = bp.id) as like_count,
                                 (SELECT COUNT(*) FROM blog_comments WHERE post_id = bp.id) as comment_count
                                 FROM blog_posts bp
                                 JOIN users u ON bp.user_id = u.id
                                 WHERE bp.status = 'approved'
                                 ORDER BY bp.created_at DESC
                                 LIMIT 6");

// Get categories for filter
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-3">
                <i class="fas fa-<?php echo $filter === 'following' ? 'star' : 'poll-h'; ?>"></i>
                <?php echo $filter === 'following' ? 'Polls from People You Follow' : 'Browse Polls & Surveys'; ?>
            </h1>
            <p class="text-muted">
                <?php echo $filter === 'following' ? 'Stay updated with polls from creators you follow' : 'Participate in polls and share your opinion'; ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <?php if (isLoggedIn() && getCurrentUser()['role'] === 'client'): ?>
                <a href="<?php echo SITE_URL; ?>client/create-poll.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>LIST A POLL
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Advertisement: Polls Top -->
    <?php displayAd('polls_top', 'mb-4'); ?>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="search"
                                   placeholder="Search polls..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
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
                        <div class="col-md-3">
                            <select class="form-select" name="location">
                                <option value="">All Locations</option>
                                <?php
                                $nigerian_states = [
                                    'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno',
                                    'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT', 'Gombe',
                                    'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara',
                                    'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau',
                                    'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara'
                                ];
                                foreach ($nigerian_states as $state): ?>
                                    <option value="<?php echo $state; ?>" <?php echo $location === $state ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($state); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Matched Polls for Agents -->
    <?php if (isLoggedIn() && getCurrentUser()['role'] === 'agent' && getCurrentUser()['agent_status'] === 'approved'): ?>
        <?php
        $agent = getCurrentUser();

        // Calculate agent age from date of birth
        $agent_age = null;
        if (!empty($agent['date_of_birth'])) {
            $birth_date = new DateTime($agent['date_of_birth']);
            $today = new DateTime();
            $agent_age = $today->diff($birth_date)->y;
        }

        // Build matched polls query based on agent criteria
        $matched_where = ["p.status = 'active'", "p.price_per_response > 0"];

        // Age matching
        if ($agent_age) {
            $age_criteria = json_decode($agent['agent_age_criteria'] ?? '["all"]', true);
            if (!in_array('all', $age_criteria)) {
                $age_conditions = [];
                foreach ($age_criteria as $age_range) {
                    if ($age_range === '18-25' && ($agent_age >= 18 && $agent_age <= 25)) {
                        $age_conditions[] = "JSON_CONTAINS(p.agent_age_criteria, '\"18-25\"')";
                    } elseif ($age_range === '25-40' && ($agent_age >= 25 && $agent_age <= 40)) {
                        $age_conditions[] = "JSON_CONTAINS(p.agent_age_criteria, '\"25-40\"')";
                    } elseif ($age_range === '40-65' && ($agent_age >= 40 && $agent_age <= 65)) {
                        $age_conditions[] = "JSON_CONTAINS(p.agent_age_criteria, '\"40-65\"')";
                    } elseif ($age_range === '65+' && $agent_age >= 65) {
                        $age_conditions[] = "JSON_CONTAINS(p.agent_age_criteria, '\"65+\"')";
                    }
                }
                if (!empty($age_conditions)) {
                    $matched_where[] = "(" . implode(" OR ", $age_conditions) . ")";
                }
            }
        }

        // Gender matching
        $gender_criteria = json_decode($agent['agent_gender_criteria'] ?? '["both"]', true);
        if (!in_array('both', $gender_criteria)) {
            if (in_array($agent['gender'], $gender_criteria)) {
                $matched_where[] = "JSON_CONTAINS(p.agent_gender_criteria, '\"{$agent['gender']}\"')";
            }
        }

        // Location matching
        if (!empty($agent['state'])) {
            $state_criteria = $agent['agent_state_criteria'] ?? '';
            $location_all = isset($agent['agent_location_all']) ? $agent['agent_location_all'] : 1;

            if ($location_all == 0 && !empty($state_criteria)) {
                if ($agent['state'] === $state_criteria) {
                    $matched_where[] = "p.agent_state_criteria = '{$agent['state']}'";
                    if (!empty($agent['lga']) && !empty($agent['agent_lga_criteria'] ?? '')) {
                        $matched_where[] = "p.agent_lga_criteria = '{$agent['lga']}'";
                    }
                }
            }
        }

        // Occupation matching
        if (!empty($agent['occupation'])) {
            $occupation_criteria = json_decode($agent['agent_occupation_criteria'] ?? '["all"]', true);
            if (!in_array('all', $occupation_criteria)) {
                if (in_array($agent['occupation'], $occupation_criteria)) {
                    $matched_where[] = "JSON_CONTAINS(p.agent_occupation_criteria, '\"{$agent['occupation']}\"')";
                }
            }
        }

        // Education matching
        if (!empty($agent['education_qualification'])) {
            $education_criteria = json_decode($agent['agent_education_criteria'] ?? '["all"]', true);
            if (!in_array('all', $education_criteria)) {
                if (in_array($agent['education_qualification'], $education_criteria)) {
                    $matched_where[] = "JSON_CONTAINS(p.agent_education_criteria, '\"{$agent['education_qualification']}\"')";
                }
            }
        }

        // Employment status matching
        $employment_criteria = json_decode($agent['agent_employment_criteria'] ?? '["both"]', true);
        if (!in_array('both', $employment_criteria)) {
            if (in_array($agent['employment_status'], $employment_criteria)) {
                $matched_where[] = "JSON_CONTAINS(p.agent_employment_criteria, '\"{$agent['employment_status']}\"')";
            }
        }

        // Income matching
        if (!empty($agent['income_range'])) {
            $income_criteria = json_decode($agent['agent_income_criteria'] ?? '["all"]', true);
            if (!in_array('all', $income_criteria)) {
                if (in_array($agent['income_range'], $income_criteria)) {
                    $matched_where[] = "JSON_CONTAINS(p.agent_income_criteria, '\"{$agent['income_range']}\"')";
                }
            }
        }

        $matched_where_clause = implode(' AND ', $matched_where);

        $matched_polls_query = "SELECT p.*, c.name as category_name,
                               (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as total_responses
                               FROM polls p
                               LEFT JOIN categories c ON p.category_id = c.id
                               WHERE $matched_where_clause
                               AND (p.end_date IS NULL OR DATE(p.end_date) >= CURDATE())
                               ORDER BY p.created_at DESC
                               LIMIT 6";

        $matched_polls = $conn->query($matched_polls_query);

        if ($matched_polls && $matched_polls->num_rows > 0):
        ?>
        <div class="card border-success shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>MATCHED POLLS</h5>
                <small class="text-white-50">Polls you qualify for based on your profile</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php while ($poll = $matched_polls->fetch_assoc()):
                        $poll['question_count'] = getPollQuestionCount($poll['id']);
                        $poll['progress_percentage'] = getPollProgressPercentage($poll);
                    ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-success">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-success">Matched</span>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> <?= date('M d', strtotime($poll['created_at'])) ?>
                                        </small>
                                    </div>
                                    <h6 class="card-title mb-2">
                                        <a href="view-poll/<?= $poll['slug'] ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars(substr($poll['title'], 0, 50)) ?>
                                            <?php if (strlen($poll['title']) > 50): ?>...<?php endif; ?>
                                        </a>
                                    </h6>
                                    <p class="card-text small text-muted mb-2">
                                        <?= htmlspecialchars(substr($poll['description'], 0, 80)) ?>
                                        <?php if (strlen($poll['description']) > 80): ?>...<?php endif; ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="small">
                                            <span class="badge bg-info me-1"><?= $poll['question_count'] ?> Qs</span>
                                            <span class="badge bg-success"><?= $poll['total_responses'] ?> Responses</span>
                                        </div>
                                        <div class="small text-success fw-bold">
                                            ₦<?= number_format(floatval($poll['price_per_response'] ?? 0), 0) ?>/response
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <a href="view-poll/<?= $poll['slug'] ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-play"></i> Take Poll
                                        </a>
                                        <a href="agent/share-poll.php?poll_id=<?= $poll['id'] ?>" class="btn btn-outline-success btn-sm ms-1">
                                            <i class="fas fa-share"></i> Share
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Polls Grid -->
    <div class="row">
        <!-- Left Column: Polls -->
        <div class="col-lg-9">
            <div class="row">
            <?php if (empty($polls_data)): ?>
                <div class="col-md-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i> No polls found. Try adjusting your filters.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($polls_data as $poll): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm hover-shadow">
                            <?php if (!empty($poll['image'])): ?>
                                <img src="<?php echo SITE_URL . 'uploads/polls/' . $poll['image']; ?>" 
                                     class="card-img-top" alt="Poll image" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-gradient text-white d-flex align-items-center justify-content-center" 
                                     style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <i class="fas fa-poll fa-4x"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <div class="mb-2">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($poll['category_name'] ?? 'General'); ?></span>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($poll['poll_type']); ?></span>
                                    <?php if (($poll['price_per_response'] ?? 0) > 0): ?>
                                        <span class="badge bg-success"><i class="fas fa-money-bill-wave"></i> Paid</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="fas fa-gift"></i> Free</span>
                                    <?php endif; ?>
                                </div>
                                
                                <h5 class="card-title"><?php echo htmlspecialchars($poll['title']); ?></h5>
                                
                                <p class="card-text text-muted">
                                    <?php echo substr(htmlspecialchars($poll['description']), 0, 120); ?>...
                                </p>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted">
                                            <i class="fas fa-users"></i> <?php echo $poll['total_responses']; ?> responses
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-question-circle"></i> <?php echo $poll['question_count']; ?> questions
                                        </small>
                                    </div>
                                    <div class="progress mb-2" style="height: 6px;">
                                        <div class="progress-bar bg-success" role="progressbar"
                                             style="width: <?php echo $poll['progress_percentage']; ?>%"
                                             aria-valuenow="<?php echo $poll['progress_percentage']; ?>"
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-success fw-bold">
                                            <?php echo $poll['progress_percentage']; ?>% complete
                                        </small>
                                        <?php if ($poll['end_date']): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> Ends <?php echo date('M d', strtotime($poll['end_date'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <a href="<?php echo SITE_URL; ?>view-poll/<?php echo $poll['slug']; ?>"
                                   class="btn btn-primary w-100">
                                    <i class="fas fa-vote-yea"></i> Participate
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Polls pagination">
                    <ul class="pagination pagination-lg">
                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo buildPaginationUrl($page - 1); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;</span>
                            </li>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        // Show first page if not in range
                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl(1) . '">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        // Show page numbers
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            if ($i == $page) {
                                echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                            } else {
                                echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl($i) . '">' . $i . '</a></li>';
                            }
                        }

                        // Show last page if not in range
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="' . buildPaginationUrl($total_pages) . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <!-- Next Button -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo buildPaginationUrl($page + 1); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>

            <!-- Pagination Info -->
            <div class="text-center text-muted mt-2">
                Showing <?php echo ($offset + 1) . '-' . min($offset + $per_page, $total_polls); ?> of <?php echo $total_polls; ?> polls
            </div>
            <?php endif; ?>
            </div>
        </div>

        <!-- Right Sidebar: Ads -->
        <div class="col-lg-3">
            <!-- Advertisement: Polls Top Sidebar -->
            <?php displayAd('polls_top_sidebar', 'mb-4'); ?>
            
            <!-- Advertisement: Polls Sidebar -->
            <?php displayAd('polls_sidebar', 'mb-4'); ?>
        </div>
    </div>

    <!-- Latest Blog Articles -->
    <?php if ($latest_articles && $latest_articles->num_rows > 0): ?>
    <div class="row mt-5">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h4 class="mb-0"><i class="fas fa-blog me-2"></i> Latest Blog Articles</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php while ($article = $latest_articles->fetch_assoc()): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 border-0 shadow-sm hover-shadow">
                                    <!-- Featured Image -->
                                    <?php if (!empty($article['featured_image'])): ?>
                                        <?php
                                        // Try different possible paths for the image
                                        $image_paths = [
                                            SITE_URL . 'uploads/blog/' . $article['featured_image'],
                                            SITE_URL . 'uploads/' . $article['featured_image'],
                                            SITE_URL . $article['featured_image']
                                        ];
                                        $image_src = $image_paths[0]; // Default to first path
                                        ?>
                                        <img src="<?php echo $image_src; ?>" 
                                             class="card-img-top" alt="<?php echo htmlspecialchars($article['title']); ?>" 
                                             style="height: 200px; object-fit: cover;"
                                             onerror="this.outerHTML='<div class=\\'card-img-top bg-secondary d-flex align-items-center justify-content-center\\' style=\\'height: 200px;\\'><i class=\\'fas fa-image text-white\\' style=\\'font-size: 3rem;\\'></i></div>'">
                                    <?php else: ?>
                                        <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" 
                                             style="height: 200px;">
                                            <i class="fas fa-image text-white" style="font-size: 3rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <a href="<?php echo SITE_URL; ?>blog/view.php?slug=<?php echo urlencode($article['slug']); ?>"
                                               class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars(substr($article['title'], 0, 60)); ?>
                                                <?php if (strlen($article['title']) > 60) echo '...'; ?>
                                            </a>
                                        </h6>
                                        <p class="card-text text-muted small mb-2">
                                            <?php echo htmlspecialchars(substr(strip_tags($article['content']), 0, 100)); ?>...
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center text-muted small">
                                            <span><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($article['author_name']); ?></span>
                                            <span><i class="fas fa-calendar me-1"></i> <?php echo date('M d, Y', strtotime($article['created_at'])); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <div class="text-muted small">
                                                <i class="fas fa-heart text-danger me-1"></i> <?php echo $article['like_count']; ?>
                                                <i class="fas fa-comment text-primary ms-2 me-1"></i> <?php echo $article['comment_count']; ?>
                                            </div>
                                            <a href="<?php echo SITE_URL; ?>blog/view.php?slug=<?php echo urlencode($article['slug']); ?>"
                                               class="btn btn-outline-secondary btn-sm">
                                                Read More <i class="fas fa-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?php echo SITE_URL; ?>blog.php" class="btn btn-secondary">
                            <i class="fas fa-blog me-2"></i> View All Articles
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.hover-shadow {
    transition: all 0.3s;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2) !important;
}
</style>

<?php include_once 'footer.php'; ?>
