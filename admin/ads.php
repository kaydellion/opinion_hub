<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$user = getCurrentUser();

$success = '';
$error = '';

// Handle ad creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ad'])) {
    $ad_id = isset($_POST['ad_id']) ? (int)$_POST['ad_id'] : 0;
    $advertiser_id = isset($_POST['advertiser_id']) ? (int)$_POST['advertiser_id'] : $user['id'];
    $title = sanitize($_POST['title']);
    $placement = sanitize($_POST['placement']);
    $ad_size = sanitize($_POST['ad_size']);
    $ad_url = sanitize($_POST['ad_url']);
    $status = sanitize($_POST['status']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $amount_paid = isset($_POST['amount_paid']) ? floatval($_POST['amount_paid']) : 0;
    
    // Debug: Log received values
    error_log("AD UPDATE DEBUG: start_date='$start_date', end_date='$end_date', ad_id=$ad_id");
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
        $error = 'Invalid start date format. Please use YYYY-MM-DD.';
        exit;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
        $error = 'Invalid end date format. Please use YYYY-MM-DD.';
        exit;
    }
    
    // Handle image upload
    $image_url = '';
    if (isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['ad_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $filename = 'ad_' . time() . '.' . $ext;
            $dest = UPLOAD_DIR . '/ads/' . $filename;
            if (move_uploaded_file($_FILES['ad_image']['tmp_name'], $dest)) {
                $image_url = '/uploads/ads/' . $filename;
            }
        }
    }
    
    if ($ad_id > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE advertisements SET 
                               title = ?, placement = ?, ad_size = ?, ad_url = ?, status = ?, 
                               start_date = ?, end_date = ?, amount_paid = ?" . 
                               ($image_url ? ", image_url = ?" : "") . " WHERE id = ?");
        if (!$stmt) {
            error_log('Ads update prepare failed: ' . $conn->error);
            $error = 'Database error while updating advertisement.';
        } else {
            if ($image_url) {
                // types: title(s), placement(s), ad_size(s), ad_url(s), status(s), start_date(s), end_date(s), amount_paid(d), image_url(s), ad_id(i)
                $stmt->bind_param("sssssssdsi", $title, $placement, $ad_size, $ad_url, $status, 
                                 $start_date, $end_date, $amount_paid, $image_url, $ad_id);
            } else {
                // types: title(s), placement(s), ad_size(s), ad_url(s), status(s), start_date(s), end_date(s), amount_paid(d), ad_id(i)
                $stmt->bind_param("sssssssdi", $title, $placement, $ad_size, $ad_url, $status, 
                                 $start_date, $end_date, $amount_paid, $ad_id);
            }
            $result = $stmt->execute();
            if (!$result) {
                $error = 'Failed to update advertisement: ' . $stmt->error;
            } else {
                $success = 'Advertisement updated';
                // Send notification and email to advertiser if status changed
                $ad = $conn->query("SELECT * FROM advertisements WHERE id = $ad_id")->fetch_assoc();
                $advertiser_id = $ad['advertiser_id'];
                $advertiser = $conn->query("SELECT * FROM users WHERE id = $advertiser_id")->fetch_assoc();
                if ($advertiser && $ad['status'] !== 'pending') {
                    createNotification($advertiser_id, 'ad_status_changed', 'Ad Status Updated', 'Your advertisement "' . $ad['title'] . '" status is now: ' . ucfirst($ad['status']), SITE_URL . 'client/view-ad.php?ad_id=' . $ad_id);
                    sendTemplatedEmail($advertiser['email'], $advertiser['first_name'] . ' ' . $advertiser['last_name'], 'Your Ad Status Changed', 'Your advertisement "' . $ad['title'] . '" status is now: ' . ucfirst($ad['status']) . '.', 'View Ad', SITE_URL . 'client/view-ad.php?ad_id=' . $ad_id);
                }
            }
        }
    } else {
        // Create
        $stmt = $conn->prepare("INSERT INTO advertisements 
                               (advertiser_id, title, placement, ad_size, ad_url, image_url, status, start_date, end_date, amount_paid) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log('Ads insert prepare failed: ' . $conn->error);
            $error = 'Database error while creating advertisement.';
        } else {
            $stmt->bind_param("issssssssd", $advertiser_id, $title, $placement, $ad_size, $ad_url, 
                             $image_url, $status, $start_date, $end_date, $amount_paid);
            $stmt->execute();
            $success = 'Advertisement created';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $ad_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM advertisements WHERE id = $ad_id");
    header('Location: ads.php');
    exit;
}

// Pagination
$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Search functionality
$search_term = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = $conn->real_escape_string(trim($_GET['search']));
}

// Status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Count total ads for pagination
$count_query = "SELECT COUNT(*) as total
                FROM advertisements a
                JOIN users u ON a.advertiser_id = u.id
                WHERE 1=1";
if (!empty($status_filter)) {
    $count_query .= " AND a.status = '$status_filter'";
}
if (!empty($search_term)) {
    $count_query .= " AND (a.title LIKE '%$search_term%' 
                           OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search_term%' 
                           OR a.placement LIKE '%$search_term%')";
}

$total_ads = $conn->query($count_query)->fetch_assoc()['total'];
$total_pages = ceil($total_ads / $per_page);

// Get all ads with pagination
$ads_query = "SELECT a.*, 
              CONCAT(u.first_name, ' ', u.last_name) as advertiser_name 
              FROM advertisements a 
              JOIN users u ON a.advertiser_id = u.id 
              WHERE 1=1";
if (!empty($status_filter)) {
    $ads_query .= " AND a.status = '$status_filter'";
}
if (!empty($search_term)) {
    $ads_query .= " AND (a.title LIKE '%$search_term%' 
                         OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search_term%' 
                         OR a.placement LIKE '%$search_term%')";
}
$ads_query .= " ORDER BY a.created_at DESC LIMIT $per_page OFFSET $offset";

$ads = $conn->query($ads_query);

if (!$ads) {
    die("Query failed: " . $conn->error);
}

// Get count of pending paid ads
$pending_paid = $conn->query("SELECT COUNT(*) as count FROM advertisements WHERE status = 'pending' AND amount_paid > 0")->fetch_assoc()['count'];

$page_title = 'Advertisement Management';
include '../header.php';
?>

<style>
/* Fix modal rendering issues - Force Bootstrap modal to work */
#adModal {
    display: none !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    z-index: 9999 !important;
    width: 100% !important;
    height: 100% !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    outline: 0;
}

#adModal.show {
    display: block !important;
    opacity: 1 !important;
}

#adModal .modal-dialog {
    position: relative !important;
    width: auto !important;
    margin: 1.75rem auto !important;
    max-width: 800px !important;
    pointer-events: none;
    display: block !important;
}

#adModal.show .modal-dialog {
    transform: none !important;
}

#adModal .modal-dialog-scrollable {
    max-height: calc(100vh - 3.5rem) !important;
    display: flex !important;
}

#adModal .modal-dialog-scrollable .modal-content {
    max-height: 100% !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
}

#adModal .modal-dialog-scrollable .modal-body {
    overflow-y: auto !important;
    max-height: calc(100vh - 200px) !important;
}

#adModal .modal-content {
    position: relative !important;
    display: flex !important;
    flex-direction: column !important;
    width: 100% !important;
    min-width: 300px !important;
    pointer-events: auto !important;
    background-color: #fff !important;
    background-clip: padding-box;
    border: 1px solid rgba(0,0,0,.2) !important;
    border-radius: 0.5rem !important;
    outline: 0;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.5) !important;
}

.modal-backdrop {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    z-index: 9998 !important;
    width: 100vw !important;
    height: 100vh !important;
    background-color: #000 !important;
}

.modal-backdrop.show {
    opacity: 0.5 !important;
}

#adModal .modal-header {
    display: flex !important;
    flex-shrink: 0;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1rem !important;
    border-bottom: 1px solid #dee2e6;
    border-top-left-radius: calc(0.5rem - 1px);
    border-top-right-radius: calc(0.5rem - 1px);
}

#adModal .modal-body {
    position: relative !important;
    flex: 1 1 auto !important;
    padding: 1rem !important;
}

#adModal .modal-footer {
    display: flex !important;
    flex-wrap: wrap;
    flex-shrink: 0;
    align-items: center;
    justify-content: flex-end;
    padding: 0.75rem !important;
    border-top: 1px solid #dee2e6;
    border-bottom-right-radius: calc(0.5rem - 1px);
    border-bottom-left-radius: calc(0.5rem - 1px);
}

@media (min-width: 576px) {
    #adModal .modal-dialog {
        max-width: 500px !important;
        margin: 1.75rem auto !important;
    }
}

@media (min-width: 992px) {
    #adModal.modal-lg .modal-dialog,
    #adModal .modal-dialog.modal-lg {
        max-width: 800px !important;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-ad me-2"></i>Advertisement Management</h2>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adModal" onclick="resetForm()">
                <i class="fas fa-plus me-2"></i>Create Advertisement
            </button>
        </div>
    </div>
    
    <?php if ($pending_paid > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i>
            <strong><?= $pending_paid ?></strong> paid advertisement<?= $pending_paid > 1 ? 's' : '' ?> awaiting your approval!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <!-- Search and Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="ads.php" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by title, advertiser, or placement..." 
                           value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paused" <?php echo $status_filter === 'paused' ? 'selected' : ''; ?>>Paused</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                </div>
                <?php if (!empty($search_term) || !empty($status_filter)): ?>
                <div class="col-md-3">
                    <a href="ads.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Placement</th>
                            <th>Duration</th>
                            <th>Amount Paid</th>
                            <th>Views/Clicks</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($ad = $ads->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if ($ad['image_url']): ?>
                                        <img src="<?= SITE_URL . $ad['image_url'] ?>" style="max-width:80px;max-height:40px;object-fit:cover;">
                                    <?php else: ?>
                                        <span class="text-muted">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($ad['title'] ?? 'Untitled') ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($ad['advertiser_name']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= htmlspecialchars($ad['placement']) ?></span><br>
                                    <small><?= htmlspecialchars($ad['ad_size']) ?></small>
                                </td>
                                <td>
                                    <?php if ($ad['start_date'] && $ad['end_date']): ?>
                                        <small>
                                            <?= date('M d', strtotime($ad['start_date'])) ?> - 
                                            <?= date('M d, Y', strtotime($ad['end_date'])) ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong>₦<?= number_format($ad['amount_paid'], 2) ?></strong></td>
                                <td>
                                    <small>
                                        <i class="fas fa-eye"></i> <?= number_format($ad['total_views']) ?><br>
                                        <i class="fas fa-mouse-pointer"></i> <?= number_format($ad['click_throughs']) ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $ad['status'] === 'active' ? 'success' : ($ad['status'] === 'paused' ? 'warning' : 'secondary') ?>">
                                        <?= ucfirst($ad['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick='editAd(<?= json_encode($ad) ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?= $ad['id'] ?>" class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Delete this ad?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div>
                    <p class="text-muted mb-0">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_ads); ?> of <?php echo $total_ads; ?> advertisements
                    </p>
                </div>
                <nav>
                    <ul class="pagination mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Ad Modal -->
<div class="modal" id="adModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Create Advertisement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="ad_id" id="ad_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Ad Title *</label>
                        <input type="text" name="title" id="title" class="form-control" required
                               placeholder="e.g., Summer Sale Campaign">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Advertiser</label>
                        <select name="advertiser_id" id="advertiser_id" class="form-select">
                            <option value="">Select advertiser (optional)</option>
                            <?php
                            $advertisers = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, email FROM users WHERE role IN ('user', 'client', 'agent') ORDER BY first_name");
                            if ($advertisers):
                                while ($advertiser = $advertisers->fetch_assoc()):
                            ?>
                                <option value="<?= $advertiser['id'] ?>">
                                    <?= htmlspecialchars($advertiser['name']) ?> (<?= htmlspecialchars($advertiser['email']) ?>)
                                </option>
                            <?php 
                                endwhile;
                            endif;
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Placement *</label>
                        <select name="placement" id="placement" class="form-select" required>
                            <option value="homepage_top">Homepage - Top Banner</option>
                            <option value="homepage_sidebar">Homepage - Sidebar</option>
                            <option value="poll_page_top">Poll Page - Top</option>
                            <option value="poll_page_sidebar">Poll Page - Sidebar</option>
                            <option value="dashboard">Dashboard</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ad Size *</label>
                        <select name="ad_size" id="ad_size" class="form-select" required>
                            <option value="728x90">728x90 (Leaderboard)</option>
                            <option value="300x250">300x250 (Medium Rectangle)</option>
                            <option value="160x600">160x600 (Wide Skyscraper)</option>
                            <option value="320x50">320x50 (Mobile Banner)</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date *</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date *</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount Paid (₦) *</label>
                        <input type="number" name="amount_paid" id="amount_paid" class="form-control" 
                               step="0.01" min="0" required placeholder="0.00">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ad Image *</label>
                        <input type="file" name="ad_image" id="ad_image" class="form-control" accept="image/*">
                        <small class="text-muted">JPG, PNG, or GIF. Match the selected ad size.</small>
                        <div id="current_image" class="mt-2"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Target URL *</label>
                        <input type="url" name="ad_url" id="ad_url" class="form-control" required
                               placeholder="https://example.com">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select name="status" id="ad_status" class="form-select" required>
                            <option value="pending" <?= isset($ad) && $ad['status'] === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                            <option value="active" <?= isset($ad) && $ad['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="paused" <?= isset($ad) && $ad['status'] === 'paused' ? 'selected' : '' ?>>Paused</option>
                            <option value="rejected" <?= isset($ad) && $ad['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_ad" class="btn btn-primary">Save Advertisement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('ad_id').value = '';
    document.getElementById('title').value = '';
    document.getElementById('advertiser_id').value = '';
    document.getElementById('placement').selectedIndex = 0;
    document.getElementById('ad_size').selectedIndex = 0;
    document.getElementById('start_date').value = '';
    document.getElementById('end_date').value = '';
    document.getElementById('amount_paid').value = '0.00';
    document.getElementById('ad_url').value = '';
    document.getElementById('ad_status').selectedIndex = 0;
    document.getElementById('ad_image').value = '';
    document.getElementById('modalTitle').textContent = 'Create Advertisement';
    
    const currentImageDiv = document.getElementById('current_image');
    if (currentImageDiv) {
        currentImageDiv.innerHTML = '';
    }
    
    // Make image required for new ads
    document.getElementById('ad_image').setAttribute('required', 'required');
}

function editAd(ad) {
    document.getElementById('ad_id').value = ad.id;
    document.getElementById('title').value = ad.title || '';
    document.getElementById('advertiser_id').value = ad.advertiser_id || '';
    document.getElementById('placement').value = ad.placement;
    document.getElementById('ad_size').value = ad.ad_size;
    document.getElementById('start_date').value = ad.start_date || '';
    document.getElementById('end_date').value = ad.end_date || '';
    document.getElementById('amount_paid').value = ad.amount_paid || '0.00';
    document.getElementById('ad_url').value = ad.ad_url;
    document.getElementById('ad_status').value = ad.status;
    
    // Show current image if exists
    const currentImageDiv = document.getElementById('current_image');
    if (currentImageDiv) {
        if (ad.image_url) {
            currentImageDiv.innerHTML = 
                '<small class="text-muted">Current image:</small><br>' +
                '<img src="<?= SITE_URL ?>' + ad.image_url + '" style="max-width: 200px; margin-top: 5px;">';
            document.getElementById('ad_image').removeAttribute('required');
        } else {
            currentImageDiv.innerHTML = '';
            document.getElementById('ad_image').setAttribute('required', 'required');
        }
    }
    
    // Fix date format for MySQL
    if (ad.start_date) {
        document.getElementById('start_date').value = ad.start_date.substring(0, 10);
    }
    if (ad.end_date) {
        document.getElementById('end_date').value = ad.end_date.substring(0, 10);
    }
    
    document.getElementById('modalTitle').textContent = 'Edit Advertisement';
    
    // Open modal
    const modal = new bootstrap.Modal(document.getElementById('adModal'));
    modal.show();
}

// Add date validation
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin Ads page loaded');
    console.log('Bootstrap version:', typeof bootstrap !== 'undefined' ? 'Loaded' : 'NOT LOADED');
    
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    if (startDate && endDate) {
        startDate.addEventListener('change', function() {
            endDate.min = this.value;
        });
        
        endDate.addEventListener('change', function() {
            if (this.value && startDate.value) {
                const start = new Date(startDate.value);
                const end = new Date(this.value);
                if (end <= start) {
                    alert('End date must be after start date');
                    this.value = '';
                }
            }
        });
    }
    
    // Debug modal
    const modalEl = document.getElementById('adModal');
    if (modalEl) {
        console.log('Modal element found');
        modalEl.addEventListener('show.bs.modal', function () {
            console.log('Modal is showing');
            console.log('Modal computed style display:', window.getComputedStyle(modalEl).display);
            console.log('Modal computed style opacity:', window.getComputedStyle(modalEl).opacity);
            console.log('Modal position:', modalEl.getBoundingClientRect());
        });
        modalEl.addEventListener('shown.bs.modal', function () {
            console.log('Modal is shown');
            console.log('Modal classes:', modalEl.className);
            console.log('Backdrop exists:', document.querySelector('.modal-backdrop') !== null);
            
            // Check if modal is visible
            const rect = modalEl.getBoundingClientRect();
            console.log('Modal position after shown:', rect);
            if (rect.top < 0 || rect.left < 0) {
                console.error('Modal is positioned off-screen!');
            }
        });
        modalEl.addEventListener('hide.bs.modal', function () {
            console.log('Modal is hiding');
        });
    } else {
        console.error('Modal element NOT found');
    }
});
</script>

<?php include '../footer.php'; ?>
