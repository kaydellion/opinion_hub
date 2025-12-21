<?php
session_start();
require_once '../connect.php';
require_once '../functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
$platform = isset($_POST['platform']) ? $_POST['platform'] : '';

if ($post_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit();
}

$allowed_platforms = ['facebook', 'twitter', 'whatsapp', 'email'];
if (!in_array($platform, $allowed_platforms)) {
    echo json_encode(['success' => false, 'message' => 'Invalid platform']);
    exit();
}

// Verify post exists
$verify_stmt = $conn->prepare("SELECT id FROM blog_posts WHERE id = ? AND status = 'approved'");
$verify_stmt->bind_param("i", $post_id);
$verify_stmt->execute();
if ($verify_stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Post not found']);
    exit();
}

// Track share (only if user is logged in, otherwise anonymous)
$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;

if ($user_id) {
    $stmt = $conn->prepare("INSERT INTO blog_shares (post_id, user_id, platform) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $platform);
} else {
    $stmt = $conn->prepare("INSERT INTO blog_shares (post_id, platform) VALUES (?, ?)");
    $stmt->bind_param("is", $post_id, $platform);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Share tracked']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error tracking share']);
}
?>
