<?php
/**
 * my-ads.php - User's advertisement management dashboard
 * Users can create, view, and manage their own advertisements
 */

require_once '../connect.php';
require_once '../functions.php';

// Require login
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$user_id = $user['id'];

// Handle ad submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ad'])) {
    $title = sanitize($_POST['title']);
    $placement = sanitize($_POST['placement']);
    $ad_size = sanitize($_POST['ad_size']);
    $ad_url = sanitize($_POST['ad_url']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);

    // Validate dates
    if (empty($start_date) || empty($end_date)) {
        $_SESSION['error'] = "Both start date and end date are required";
        header("Location: my-ads.php");
        exit;
    }

    $start_timestamp = strtotime($start_date);
    $end_timestamp = strtotime($end_date);
    $today_timestamp = strtotime(date('Y-m-d'));

    if ($start_timestamp < $today_timestamp) {
        $_SESSION['error'] = "Start date cannot be in the past";
        header("Location: my-ads.php");
        exit;
    }

    if ($end_timestamp <= $start_timestamp) {
        $_SESSION['error'] = "End date must be at least one day after start date";
        header("Location: my-ads.php");
        exit;
    }

    $duration = ($end_timestamp - $start_timestamp) / (60 * 60 * 24);
    
    // Calculate amount based on pricing
    $pricing = [
        'homepage_top' => ['7' => 25000, '14' => 45000, '30' => 80000, 'daily' => 4000],
        'homepage_sidebar' => ['7' => 20000, '14' => 35000, '30' => 60000, 'daily' => 3000],
        'poll_page_top' => ['7' => 30000, '14' => 55000, '30' => 100000, 'daily' => 5000],
        'poll_page_sidebar' => ['7' => 22000, '14' => 40000, '30' => 70000, 'daily' => 3500],
        'dashboard' => ['7' => 18000, '14' => 30000, '30' => 50000, 'daily' => 2500]
    ];
    
    // Determine package price
    $amount = 0;
    if ($duration <= 7) {
        $amount = $pricing[$placement]['7'] ?? 0;
    } elseif ($duration <= 14) {
        $amount = $pricing[$placement]['14'] ?? 0;
    } elseif ($duration <= 30) {
        $amount = $pricing[$placement]['30'] ?? 0;
    } else {
        $amount = ceil($duration) * ($pricing[$placement]['daily'] ?? 0);
    }
    
    // Handle image upload
    $image_url = null;
    if (isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/ads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['ad_image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $new_filename = 'ad_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['ad_image']['tmp_name'], $upload_path)) {
                $image_url = '/uploads/ads/' . $new_filename;
            }
        }
    }
    
    if ($image_url) {
        // Insert advertisement with pending status
        $stmt = $conn->prepare("
            INSERT INTO advertisements 
            (advertiser_id, title, placement, ad_size, image_url, ad_url, start_date, end_date, amount_paid, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->bind_param('isssssssd', $user_id, $title, $placement, $ad_size, $image_url, $ad_url, $start_date, $end_date, $amount);
        
        if ($stmt->execute()) {
            $ad_id = $conn->insert_id;
            
            // Send notification to admin
            $admin_email_query = $conn->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
            if ($admin_row = $admin_email_query->fetch_assoc()) {
                $message = "A new advertisement has been submitted by " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . ".<br><br>";
                $message .= "<strong>Advertisement Details:</strong><br>";
                $message .= "• Title: " . htmlspecialchars($title) . "<br>";
                $message .= "• Placement: " . htmlspecialchars($placement) . "<br>";
                $message .= "• Duration: " . date('M d', strtotime($start_date)) . " - " . date('M d, Y', strtotime($end_date)) . "<br>";
                $message .= "• Calculated Amount: ₦" . number_format($amount, 2) . "<br><br>";
                $message .= "Please review and approve/reject this advertisement.";
                
                sendTemplatedEmail(
                    $admin_row['email'],
                    'Admin',
                    'New Advertisement Pending Approval',
                    $message,
                    'Review Advertisement',
                    SITE_URL . 'admin/ads.php'
                );
            }
            
            $_SESSION['success'] = "Your advertisement has been submitted! Amount to pay: ₦" . number_format($amount, 2) . ". Please proceed to payment.";
            header('Location: ' . SITE_URL . 'client/pay-for-ad.php?ad_id=' . $ad_id);
            exit;
        } else {
            $error = "Failed to create advertisement. Please try again.";
        }
    } else {
        $error = "Failed to upload image. Please ensure it's a valid JPG, PNG, or GIF file.";
    }
}

// Get user's advertisements
$stmt = $conn->prepare("
    SELECT * FROM advertisements 
    WHERE advertiser_id = ? 
    ORDER BY created_at DESC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$my_ads = $stmt->get_result();

$page_title = "My Advertisements";
include_once '../header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-ad text-primary"></i> My Advertisements</h1>
            <p class="text-muted">Create and manage your advertising campaigns</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdModal">
                <i class="fas fa-plus"></i> Create New Ad
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <?php
    $stats = [
        'total' => 0,
        'active' => 0,
        'pending' => 0,
        'paused' => 0,
        'total_views' => 0,
        'total_clicks' => 0,
        'total_revenue' => 0
    ];

    $my_ads_copy = $my_ads;
    mysqli_data_seek($my_ads, 0);
    while ($ad = $my_ads->fetch_assoc()) {
        $stats['total']++;
        $stats[$ad['status']]++;
        $stats['total_views'] += $ad['total_views'];
        $stats['total_clicks'] += $ad['click_throughs'];
        // Only count revenue from paid/active advertisements
        if ($ad['status'] === 'active' && $ad['amount_paid'] > 0) {
            $stats['total_revenue'] += $ad['amount_paid'];
        }
    }
    mysqli_data_seek($my_ads, 0);
    ?>

    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-primary mb-0"><?= $stats['total'] ?></h3>
                    <small class="text-muted">Total Ads</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-success mb-0"><?= $stats['active'] ?></h3>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-info mb-0"><?= number_format($stats['total_views']) ?></h3>
                    <small class="text-muted">Total Views</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-warning mb-0"><?= number_format($stats['total_clicks']) ?></h3>
                    <small class="text-muted">Total Clicks</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0">₦<?= number_format($stats['total_revenue'], 2) ?></h3>
                    <small>Total Revenue</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Advertisements List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Your Advertisements</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($my_ads->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Preview</th>
                                <th>Title</th>
                                <th>Placement</th>
                                <th>Duration</th>
                                <th>Amount</th>
                                <th>Performance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ad = $my_ads->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if ($ad['image_url']): ?>
                                            <img src="<?= SITE_URL . $ad['image_url'] ?>" 
                                                 style="max-width:80px;max-height:40px;object-fit:cover;"
                                                 alt="<?= htmlspecialchars($ad['title']) ?>">
                                        <?php else: ?>
                                            <span class="text-muted">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($ad['title']) ?></strong><br>
                                        <small class="text-muted"><?= $ad['ad_size'] ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= htmlspecialchars($ad['placement']) ?></span>
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
                                            <?php if ($ad['total_views'] > 0): ?>
                                                <br><span class="text-success"><?= number_format(($ad['click_throughs'] / $ad['total_views']) * 100, 2) ?>% CTR</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php
                                        $status_colors = [
                                            'active' => 'success',
                                            'pending' => 'warning',
                                            'paused' => 'secondary',
                                            'rejected' => 'danger'
                                        ];
                                        $color = $status_colors[$ad['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?>"><?= ucfirst($ad['status']) ?></span>
                                        <?php if ($ad['status'] === 'pending'): ?>
                                            <br><small class="text-muted">Awaiting approval</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ad['status'] === 'pending' && $ad['amount_paid'] == 0): ?>
                                            <a href="<?= SITE_URL ?>client/pay-for-ad.php?ad_id=<?= $ad['id'] ?>" 
                                               class="btn btn-sm btn-success">
                                                <i class="fas fa-credit-card"></i> Pay
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= SITE_URL ?>client/view-ad.php?id=<?= $ad['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-ad fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Advertisements Yet</h5>
                    <p class="text-muted">Create your first advertisement to get started!</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdModal">
                        <i class="fas fa-plus"></i> Create Advertisement
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Ad Modal -->
<div class="modal fade" id="createAdModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data" id="createAdForm">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Advertisement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Your ad will be reviewed by our team before going live. 
                        Payment is required before approval.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Advertisement Title *</label>
                        <input type="text" name="title" class="form-control" required
                               placeholder="e.g., Summer Sale Campaign">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Placement *</label>
                            <select name="placement" id="placement" class="form-select" required>
                                <option value="homepage_top">Homepage - Top Banner (₦25K-80K)</option>
                                <option value="homepage_sidebar">Homepage - Sidebar (₦20K-60K)</option>
                                <option value="poll_page_top">Poll Page - Top (₦30K-100K)</option>
                                <option value="poll_page_sidebar">Poll Page - Sidebar (₦22K-70K)</option>
                                <option value="dashboard">Dashboard Banner (₦18K-50K)</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ad Size *</label>
                            <select name="ad_size" class="form-select" required>
                                <option value="728x90">728x90 (Leaderboard)</option>
                                <option value="300x250">300x250 (Medium Rectangle)</option>
                                <option value="160x600">160x600 (Wide Skyscraper)</option>
                                <option value="320x50">320x50 (Mobile Banner)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date *</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" 
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date *</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" disabled required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Advertisement Image *</label>
                        <input type="file" name="ad_image" class="form-control" accept="image/*" required>
                        <small class="text-muted">JPG, PNG, or GIF. Recommended size matches ad size selected above.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Target URL *</label>
                        <input type="url" name="ad_url" class="form-control" required
                               placeholder="https://example.com">
                        <small class="text-muted">Where should users go when they click your ad?</small>
                    </div>

                    <div class="alert alert-warning">
                        <strong>Pricing:</strong> Cost is calculated based on placement and duration (7/14/30 days packages). 
                        You'll see the exact amount after submission.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_ad" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Advertisement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Date handling - disable end date until start date is selected
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');

    // Initially disable end date
    endDate.disabled = true;
    endDate.placeholder = "Select start date first";

    // Set minimum date for start date (today)
    const today = new Date().toISOString().split('T')[0];
    startDate.min = today;

    startDate.addEventListener('change', function() {
        if (this.value) {
            // Enable end date and set minimum to day after start date
            const startDateObj = new Date(this.value);
            const minEndDate = new Date(startDateObj);
            minEndDate.setDate(startDateObj.getDate() + 1);

            endDate.disabled = false;
            endDate.min = minEndDate.toISOString().split('T')[0];
            endDate.placeholder = "Select end date";

            // Clear any existing end date
            endDate.value = '';
        } else {
            // Disable end date if start date is cleared
            endDate.disabled = true;
            endDate.placeholder = "Select start date first";
            endDate.value = '';
        }
    });
});
</script>

<?php include_once '../footer.php'; ?>
