<?php
/**
 * view-ad.php - View single advertisement details
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
$ad_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$ad_id) {
    $_SESSION['error'] = "Invalid advertisement ID.";
    header('Location: ' . SITE_URL . 'client/my-ads.php');
    exit;
}

// Get advertisement details
$stmt = $conn->prepare("SELECT * FROM advertisements WHERE id = ? AND advertiser_id = ?");
$stmt->bind_param('ii', $ad_id, $user['id']);
$stmt->execute();
$ad = $stmt->get_result()->fetch_assoc();

if (!$ad) {
    $_SESSION['error'] = "Advertisement not found or you don't have permission to access it.";
    header('Location: ' . SITE_URL . 'client/my-ads.php');
    exit;
}

// Calculate CTR
$ctr = ($ad['total_views'] > 0) ? ($ad['click_throughs'] / $ad['total_views']) * 100 : 0;

// Calculate days remaining
$days_remaining = 0;
if ($ad['end_date']) {
    $end_timestamp = strtotime($ad['end_date']);
    $today_timestamp = time();
    $days_remaining = ceil(($end_timestamp - $today_timestamp) / 86400);
}

$page_title = "Advertisement Details";
include_once '../header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col">
            <a href="<?= SITE_URL ?>client/my-ads.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to My Ads
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Main Details -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h4 class="mb-0"><?= htmlspecialchars($ad['title']) ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($ad['image_url']): ?>
                        <div class="text-center mb-4">
                            <img src="<?= SITE_URL . $ad['image_url'] ?>" 
                                 class="img-fluid" 
                                 style="max-width: 100%; border: 1px solid #ddd; border-radius: 8px;"
                                 alt="<?= htmlspecialchars($ad['title']) ?>">
                        </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Placement</h6>
                            <span class="badge bg-info"><?= htmlspecialchars($ad['placement']) ?></span>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Ad Size</h6>
                            <span class="badge bg-secondary"><?= htmlspecialchars($ad['ad_size']) ?></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-2">Target URL</h6>
                        <a href="<?= htmlspecialchars($ad['ad_url']) ?>" target="_blank" class="text-break">
                            <?= htmlspecialchars($ad['ad_url']) ?> 
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Start Date</h6>
                            <p class="mb-0">
                                <i class="fas fa-calendar"></i> 
                                <?= $ad['start_date'] ? date('F d, Y', strtotime($ad['start_date'])) : 'Not set' ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">End Date</h6>
                            <p class="mb-0">
                                <i class="fas fa-calendar"></i> 
                                <?= $ad['end_date'] ? date('F d, Y', strtotime($ad['end_date'])) : 'Not set' ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($days_remaining > 0): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-clock"></i> 
                            <strong><?= $days_remaining ?> days</strong> remaining in campaign
                        </div>
                    <?php elseif ($days_remaining < 0): ?>
                        <div class="alert alert-secondary">
                            <i class="fas fa-ban"></i> Campaign has ended
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Performance Stats -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Performance</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <div class="p-3 bg-light rounded">
                                <h3 class="text-primary mb-0"><?= number_format($ad['total_views']) ?></h3>
                                <small class="text-muted">Total Views</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-3 bg-light rounded">
                                <h3 class="text-success mb-0"><?= number_format($ad['click_throughs']) ?></h3>
                                <small class="text-muted">Click-Throughs</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-3 bg-light rounded">
                                <h3 class="text-warning mb-0"><?= number_format($ctr, 2) ?>%</h3>
                                <small class="text-muted">Click-Through Rate</small>
                            </div>
                        </div>
                    </div>

                    <?php if ($ad['total_views'] > 0): ?>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Cost per View:</strong></p>
                                <p class="text-muted">₦<?= number_format($ad['amount_paid'] / $ad['total_views'], 2) ?></p>
                            </div>
                            <?php if ($ad['click_throughs'] > 0): ?>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Cost per Click:</strong></p>
                                    <p class="text-muted">₦<?= number_format($ad['amount_paid'] / $ad['click_throughs'], 2) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Status -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Status</h6>
                </div>
                <div class="card-body">
                    <?php
                    $status_info = [
                        'pending' => ['color' => 'warning', 'icon' => 'clock', 'text' => 'Awaiting admin approval'],
                        'active' => ['color' => 'success', 'icon' => 'check-circle', 'text' => 'Currently active'],
                        'paused' => ['color' => 'secondary', 'icon' => 'pause-circle', 'text' => 'Campaign paused'],
                        'rejected' => ['color' => 'danger', 'icon' => 'times-circle', 'text' => 'Not approved']
                    ];
                    
                    $info = $status_info[$ad['status']] ?? $status_info['pending'];
                    ?>
                    
                    <div class="text-center">
                        <i class="fas fa-<?= $info['icon'] ?> fa-3x text-<?= $info['color'] ?> mb-3"></i>
                        <h5>
                            <span class="badge bg-<?= $info['color'] ?>"><?= ucfirst($ad['status']) ?></span>
                        </h5>
                        <p class="text-muted"><?= $info['text'] ?></p>
                    </div>

                    <?php if ($ad['status'] === 'pending' && $ad['amount_paid'] == 0): ?>
                        <hr>
                        <a href="<?= SITE_URL ?>client/pay-for-ad.php?ad_id=<?= $ad['id'] ?>" 
                           class="btn btn-success w-100">
                            <i class="fas fa-credit-card"></i> Complete Payment
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Payment Information</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Amount Paid:</strong></p>
                    <h4 class="text-success mb-3">₦<?= number_format($ad['amount_paid'], 2) ?></h4>
                    
                    <?php if ($ad['amount_paid'] > 0): ?>
                        <p class="mb-1 text-muted">
                            <i class="fas fa-check-circle text-success"></i> Payment confirmed
                        </p>
                    <?php else: ?>
                        <p class="mb-1 text-danger">
                            <i class="fas fa-exclamation-circle"></i> Payment pending
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Dates -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Important Dates</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <i class="fas fa-calendar-plus text-primary"></i> 
                        <strong>Created:</strong><br>
                        <small class="text-muted"><?= date('F d, Y g:i A', strtotime($ad['created_at'])) ?></small>
                    </p>
                    
                    <?php if (isset($ad['updated_at']) && $ad['updated_at'] !== $ad['created_at']): ?>
                        <p class="mb-2">
                            <i class="fas fa-calendar-check text-info"></i> 
                            <strong>Updated:</strong><br>
                            <small class="text-muted"><?= date('F d, Y g:i A', strtotime($ad['updated_at'])) ?></small>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../footer.php'; ?>
