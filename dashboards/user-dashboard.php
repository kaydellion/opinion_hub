<?php
// User Dashboard
global $conn;
$user = getCurrentUser();

// Get stats
$total_responses = $conn->query("SELECT COUNT(*) as count FROM poll_responses WHERE respondent_id = " . $user['id'])->fetch_assoc()['count'];
$recent_responses = $conn->query("SELECT COUNT(*) as count FROM poll_responses WHERE respondent_id = " . $user['id'] . " AND responded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-3">
                <i class="fas fa-user"></i> User Dashboard
            </h1>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
        </div>
    </div>

    <!-- Advertisement: Dashboard Top -->
    <?php displayAd('dashboard', 'mb-4'); ?>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Polls Participated</h6>
                            <h2 class="mb-0"><?php echo $total_responses; ?></h2>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-poll-h fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">This Week</h6>
                            <h2 class="mb-0"><?php echo $recent_responses; ?></h2>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-chart-line fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-compass"></i> Explore</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="<?php echo SITE_URL; ?>polls.php" class="btn btn-primary w-100 py-3">
                                <i class="fas fa-poll fa-2x d-block mb-2"></i>
                                Browse All Polls
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="<?php echo SITE_URL; ?>agent/register-agent.php" class="btn btn-success w-100 py-3">
                                <i class="fas fa-user-tie fa-2x d-block mb-2"></i>
                                Become an Agent
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="<?php echo SITE_URL; ?>databank.php" class="btn btn-info w-100 py-3">
                                <i class="fas fa-database fa-2x d-block mb-2"></i>
                                View Poll Results
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Polls -->
    <div class="row">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-fire"></i> Trending Polls</h5>
                    <a href="<?php echo SITE_URL; ?>polls.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        $trending_polls = $conn->query("SELECT * FROM polls WHERE status = 'active' ORDER BY total_responses DESC LIMIT 6");
                        while ($poll = $trending_polls->fetch_assoc()):
                        ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($poll['title']); ?></h6>
                                    <p class="card-text text-muted small">
                                        <?php echo substr(htmlspecialchars($poll['description']), 0, 100); ?>...
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-users"></i> <?php echo $poll['total_responses']; ?> responses
                                        </small>
                                        <a href="<?php echo SITE_URL; ?>view-poll/<?php echo $poll['slug']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            Participate
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Sidebar - Additional Info -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Quick Tips</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-check text-success"></i> Participate in polls daily</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> Share polls with friends</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> Explore new categories</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
