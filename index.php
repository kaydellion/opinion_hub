
<?php
$page_title = "Home - What Gets Measured, Gets Done!";
include_once 'header.php';
global $conn;

// Get statistics
$stats_query = $conn->query("SELECT 
    (SELECT COUNT(*) FROM users WHERE status = 'active') as total_users,
    (SELECT COUNT(*) FROM polls WHERE status = 'active') as total_polls,
    (SELECT SUM(total_responses) FROM (SELECT COUNT(*) as total_responses FROM poll_responses GROUP BY poll_id) as responses) as total_responses");
$stats = $stats_query ? $stats_query->fetch_assoc() : ['total_users' => 0, 'total_polls' => 0, 'total_responses' => 0];

// Get categories with poll count
$categories = $conn->query("SELECT c.*, COUNT(p.id) as poll_count 
                            FROM categories c 
                            LEFT JOIN polls p ON c.id = p.category_id AND p.status = 'active'
                            GROUP BY c.id 
                            HAVING poll_count > 0
                            ORDER BY poll_count DESC 
                            LIMIT 8");

// Get latest polls for slider
$latest_polls = $conn->query("SELECT p.*, c.name as category_name,
                               (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as total_responses 
                               FROM polls p 
                               LEFT JOIN categories c ON p.category_id = c.id
                               WHERE p.status = 'active' 
                               ORDER BY p.created_at DESC 
                               LIMIT 8");

// Get trending polls (most responses)
$trending_polls = $conn->query("SELECT p.*, c.name as category_name,
                                (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as total_responses 
                                FROM polls p 
                                LEFT JOIN categories c ON p.category_id = c.id
                                WHERE p.status = 'active' 
                                ORDER BY total_responses DESC 
                                LIMIT 6");
?>

<!-- Hero Section -->
<div class="hero-section" style="background: linear-gradient(135deg, #FF6B35 0%, #004E89 100%); color: white; padding: 100px 0; position: relative; overflow: hidden;">
    <div class="container text-center" style="position: relative; z-index: 2;">
        <h1 class="display-3 mb-4 fw-bold">
            <i class="fas fa-chart-pie"></i> What Gets Measured, Gets Done!
        </h1>
        <p class="lead mb-5" style="font-size: 1.4rem; max-width: 800px; margin: 0 auto;">
            Nigeria's Premier Polling Platform - Discover insights, share opinions, and drive data-driven decisions
        </p>
        <?php if (!isLoggedIn()): ?>
            <div class="d-flex gap-3 justify-content-center">
                <a href="<?php echo SITE_URL; ?>signup.php" class="btn btn-light btn-lg px-5 py-3">
                    <i class="fas fa-user-plus"></i> Join Free
                </a>
                <a href="<?php echo SITE_URL; ?>polls.php" class="btn btn-outline-light btn-lg px-5 py-3">
                    <i class="fas fa-poll"></i> Browse Polls
                </a>
            </div>
        <?php else: ?>
            <a href="<?php echo SITE_URL; ?>polls.php" class="btn btn-light btn-lg px-5 py-3">
                <i class="fas fa-poll"></i> Browse Polls
            </a>
        <?php endif; ?>
    </div>
    <!-- Animated background shapes -->
    <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 100px; background: rgba(255,255,255,0.1); transform: skewY(-2deg);"></div>
</div>

<!-- Stats Section -->
<div class="container my-5">
    <div class="row text-center">
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <i class="fas fa-users fa-3x mb-3" style="color: #FF6B35;"></i>
                    <h2 class="display-4 fw-bold text-primary"><?php echo number_format($stats['total_users'] ?? 0); ?></h2>
                    <p class="text-muted mb-0">Active Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <i class="fas fa-poll-h fa-3x mb-3" style="color: #FF6B35;"></i>
                    <h2 class="display-4 fw-bold text-primary"><?php echo number_format($stats['total_polls'] ?? 0); ?></h2>
                    <p class="text-muted mb-0">Live Polls</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <i class="fas fa-check-circle fa-3x mb-3" style="color: #FF6B35;"></i>
                    <h2 class="display-4 fw-bold text-primary"><?php echo number_format($stats['total_responses'] ?? 0); ?></h2>
                    <p class="text-muted mb-0">Total Responses</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="bg-light py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold">Why Choose Opinion Hub NG?</h2>
            <p class="text-muted">Everything you need to create, share, and analyze polls</p>
        </div>
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-bolt fa-4x" style="color: #FF6B35;"></i>
                    </div>
                    <h5 class="fw-bold">Real-Time Results</h5>
                    <p class="text-muted">Get instant feedback and live analytics</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-chart-line fa-4x" style="color: #FF6B35;"></i>
                    </div>
                    <h5 class="fw-bold">Advanced Analytics</h5>
                    <p class="text-muted">Comprehensive data visualization and insights</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-mobile-alt fa-4x" style="color: #FF6B35;"></i>
                    </div>
                    <h5 class="fw-bold">Mobile Friendly</h5>
                    <p class="text-muted">Access polls anytime, anywhere</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-shield-alt fa-4x" style="color: #FF6B35;"></i>
                    </div>
                    <h5 class="fw-bold">Secure & Private</h5>
                    <p class="text-muted">Your data is protected and confidential</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Advertisement: Homepage Top -->
<div class="container my-4">
    <?php displayAd('homepage_top'); ?>
</div>

<!-- Categories Section -->
<?php if ($categories && $categories->num_rows > 0): ?>
<div class="container my-5">
    <div class="text-center mb-4">
        <h2 class="display-6 fw-bold">Explore by Category</h2>
        <p class="text-muted">Find polls that match your interests</p>
    </div>
    <div class="row">
        <?php while ($cat = $categories->fetch_assoc()): ?>
        <div class="col-md-3 col-sm-6 mb-4">
            <a href="<?php echo SITE_URL; ?>polls.php?category=<?php echo $cat['id']; ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 hover-scale">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-folder fa-3x mb-3" style="color: #FF6B35;"></i>
                        <h5 class="fw-bold text-dark"><?php echo htmlspecialchars($cat['name']); ?></h5>
                        <p class="text-muted mb-0"><?php echo $cat['poll_count']; ?> <?php echo $cat['poll_count'] == 1 ? 'poll' : 'polls'; ?></p>
                    </div>
                </div>
            </a>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<!-- Latest Polls Slider -->
<?php if ($latest_polls && $latest_polls->num_rows > 0): ?>
<div class="bg-light py-5">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="display-6 fw-bold">Latest Polls</h2>
            <p class="text-muted">Join the conversation on today's trending topics</p>
        </div>
        
        <div class="row">
            <div class="col-lg-9">
                <div id="pollsCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php 
                        $polls_array = [];
                        while ($poll = $latest_polls->fetch_assoc()) {
                            $polls_array[] = $poll;
                        }
                        $chunks = array_chunk($polls_array, 2);
                        foreach ($chunks as $index => $chunk): 
                        ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <div class="row">
                                <?php foreach ($chunk as $poll): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 border-0 shadow-sm hover-scale">
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
                                            </div>
                                            <h5 class="card-title"><?php echo htmlspecialchars($poll['title']); ?></h5>
                                            <p class="card-text text-muted">
                                                <?php echo substr(htmlspecialchars($poll['description']), 0, 100); ?>...
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-users"></i> <?php echo $poll['total_responses']; ?> responses
                                                </small>
                                                <a href="<?php echo SITE_URL; ?>view-poll/<?php echo $poll['slug']; ?>" class="btn btn-primary btn-sm">
                                                    Take Poll <i class="fas fa-arrow-right"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#pollsCarousel" data-bs-slide="prev" style="width: 5%;">
                        <span class="carousel-control-prev-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#pollsCarousel" data-bs-slide="next" style="width: 5%;">
                        <span class="carousel-control-next-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
            
            <!-- Sidebar Ads -->
            <div class="col-lg-3">
                <div class="mb-4">
                    <h6 class="text-muted small">Advertisement</h6>
                    <?php displayAd('homepage_sidebar'); ?>
                </div>
                <div class="mb-4">
                    <?php displayAd('homepage_sidebar_2'); ?>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="<?php echo SITE_URL; ?>polls.php" class="btn btn-primary btn-lg px-5">
                View All Polls <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Trending Polls -->
<?php if ($trending_polls && $trending_polls->num_rows > 0): ?>
<div class="container my-5">
    <div class="text-center mb-4">
        <h2 class="display-6 fw-bold">Trending Polls</h2>
        <p class="text-muted">Most popular polls right now</p>
    </div>
    <div class="row">
        <?php while ($poll = $trending_polls->fetch_assoc()): ?>
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100 hover-scale">
                <div class="card-body">
                    <div class="mb-2">
                        <span class="badge bg-danger"><i class="fas fa-fire"></i> Trending</span>
                        <span class="badge bg-primary"><?php echo htmlspecialchars($poll['category_name'] ?? 'General'); ?></span>
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($poll['title']); ?></h5>
                    <p class="card-text text-muted">
                        <?php echo substr(htmlspecialchars($poll['description']), 0, 80); ?>...
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-success fw-bold">
                            <i class="fas fa-users"></i> <?php echo number_format($poll['total_responses']); ?> votes
                        </small>
                        <a href="<?php echo SITE_URL; ?>view-poll/<?php echo $poll['slug']; ?>" class="btn btn-outline-primary btn-sm">
                            Vote Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<!-- How It Works -->
<div class="bg-primary text-white py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-6 fw-bold">How It Works</h2>
            <p class="lead">Getting started is easy</p>
        </div>
        <div class="row">
            <div class="col-md-3 text-center mb-4">
                <div class="mb-3">
                    <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <h2 class="mb-0">1</h2>
                    </div>
                </div>
                <h5>Sign Up Free</h5>
                <p class="text-white-50">Create your account in seconds</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="mb-3">
                    <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <h2 class="mb-0">2</h2>
                    </div>
                </div>
                <h5>Browse Polls</h5>
                <p class="text-white-50">Explore topics that interest you</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="mb-3">
                    <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <h2 class="mb-0">3</h2>
                    </div>
                </div>
                <h5>Share Opinion</h5>
                <p class="text-white-50">Vote and make your voice heard</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="mb-3">
                    <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <h2 class="mb-0">4</h2>
                    </div>
                </div>
                <h5>See Results</h5>
                <p class="text-white-50">View real-time insights</p>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="container my-5 py-5">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h2 class="display-5 fw-bold mb-4">Ready to Get Started?</h2>
            <p class="lead text-muted mb-4">Join thousands of Nigerians sharing their opinions and driving change</p>
            <?php if (!isLoggedIn()): ?>
            <a href="<?php echo SITE_URL; ?>signup.php" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-user-plus"></i> Create Free Account
            </a>
            <?php endif; ?>
        </div>
        <div class="col-md-6 text-center">
            <i class="fas fa-chart-pie" style="font-size: 200px; color: #FF6B35; opacity: 0.2;"></i>
        </div>
    </div>
</div>

<style>
.hover-scale {
    transition: transform 0.3s;
}
.hover-scale:hover {
    transform: scale(1.05);
}
</style>

<?php include_once 'footer.php'; ?>