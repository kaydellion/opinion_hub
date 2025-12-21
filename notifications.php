<?php
require_once 'connect.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "signin.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Mark notification as read if requested
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $notif_id = intval($_GET['id']);
    $mark_sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($mark_sql);
    $stmt->bind_param("ii", $notif_id, $user_id);
    $stmt->execute();
    
    // Redirect to link if exists
    $link_sql = "SELECT link FROM notifications WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($link_sql);
    $stmt->bind_param("ii", $notif_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['link'])) {
            header("Location: " . $row['link']);
            exit;
        }
    }
    header("Location: " . SITE_URL . "notifications.php");
    exit;
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $mark_all_sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($mark_all_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $_SESSION['success'] = "All notifications marked as read.";
    header("Location: " . SITE_URL . "notifications.php");
    exit;
}

// Get all notifications
$notifications_sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50";
$stmt = $conn->prepare($notifications_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

$page_title = "Notifications";
include_once 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-bell"></i> Notifications</h2>
                <a href="?mark_all_read=1" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-check-double"></i> Mark All Read
                </a>
            </div>
            
            <?php if ($notifications->num_rows > 0): ?>
                <div class="list-group">
                    <?php while ($notif = $notifications->fetch_assoc()): ?>
                        <a href="?mark_read=1&id=<?= $notif['id'] ?>" 
                           class="list-group-item list-group-item-action <?= $notif['is_read'] ? '' : 'list-group-item-primary' ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">
                                    <?php if (!$notif['is_read']): ?>
                                        <i class="fas fa-circle text-primary" style="font-size: 0.5rem;"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($notif['title']) ?>
                                </h6>
                                <small class="text-muted">
                                    <?php
                                    $time_diff = time() - strtotime($notif['created_at']);
                                    if ($time_diff < 60) echo "Just now";
                                    elseif ($time_diff < 3600) echo floor($time_diff / 60) . " min ago";
                                    elseif ($time_diff < 86400) echo floor($time_diff / 3600) . " hours ago";
                                    else echo date('M d, Y', strtotime($notif['created_at']));
                                    ?>
                                </small>
                            </div>
                            <p class="mb-1"><?= htmlspecialchars($notif['message']) ?></p>
                            <?php if ($notif['type']): ?>
                                <small class="badge bg-secondary"><?= ucfirst($notif['type']) ?></small>
                            <?php endif; ?>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No notifications yet</h5>
                    <p class="text-muted">We'll notify you when something important happens</p>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
