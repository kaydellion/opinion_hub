<?php
$page_title = "Browse Polls";
include_once 'header.php';

global $conn;

// Get filters
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$where = ["status = 'active'"];
if ($category > 0) {
    $where[] = "category_id = $category";
}
if (!empty($search)) {
    $where[] = "(title LIKE '%$search%' OR description LIKE '%$search%')";
}

$where_clause = implode(' AND ', $where);

// Get polls - make sure to select slug column
$polls_query = "SELECT p.*, c.name as category_name, 
                (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as total_responses
                FROM polls p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE $where_clause 
                ORDER BY p.created_at DESC";
$polls = $conn->query($polls_query);

// Get categories for filter
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-3"><i class="fas fa-poll-h"></i> Browse Polls & Surveys</h1>
            <p class="text-muted">Participate in polls and share your opinion</p>
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
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Search polls..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
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
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Polls Grid -->
    <div class="row">
        <!-- Left Column: Polls -->
        <div class="col-lg-9">
            <div class="row">
            <?php if ($polls->num_rows === 0): ?>
                <div class="col-md-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i> No polls found. Try adjusting your filters.
                    </div>
                </div>
            <?php else: ?>
                <?php while ($poll = $polls->fetch_assoc()): ?>
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
                                </div>
                                
                                <h5 class="card-title"><?php echo htmlspecialchars($poll['title']); ?></h5>
                                
                                <p class="card-text text-muted">
                                    <?php echo substr(htmlspecialchars($poll['description']), 0, 120); ?>...
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-users"></i> <?php echo $poll['total_responses']; ?> responses
                                    </small>
                                    <?php if ($poll['end_date']): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> Ends <?php echo date('M d', strtotime($poll['end_date'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <a href="<?php echo SITE_URL; ?>view-poll/<?php echo $poll['slug']; ?>"
                                   class="btn btn-primary w-100">
                                    <i class="fas fa-vote-yea"></i> Participate
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
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
