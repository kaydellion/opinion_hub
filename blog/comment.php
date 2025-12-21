<?php
session_start();
require_once '../connect.php';
require_once '../functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to comment']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
$parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
$comment = trim($_POST['comment'] ?? '');

// Validation
if ($post_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit();
}

if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
    exit();
}

// Verify post exists and is approved
$verify_stmt = $conn->prepare("SELECT id, user_id FROM blog_posts WHERE id = ? AND status = 'approved'");
$verify_stmt->bind_param("i", $post_id);
$verify_stmt->execute();
$post = $verify_stmt->get_result()->fetch_assoc();

if (!$post) {
    echo json_encode(['success' => false, 'message' => 'Post not found or not approved']);
    exit();
}

// Insert comment
if ($parent_id) {
    $stmt = $conn->prepare("INSERT INTO blog_comments (post_id, user_id, parent_id, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $post_id, $user_id, $parent_id, $comment);
} else {
    $stmt = $conn->prepare("INSERT INTO blog_comments (post_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $comment);
}

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

if ($stmt->execute()) {
    // Notify post author (if commenter is not the author)
    if ($user_id != $post['user_id']) {
        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) 
                                      VALUES (?, 'new_comment', 'New Comment on Your Post', ?, ?)");
        $user_name = $_SESSION['full_name'] ?? 'Someone';
        $message = "{$user_name} commented on your blog post.";
        $link = "blog/view.php?id=" . $post_id;
        $notif_stmt->bind_param("iss", $post['user_id'], $message, $link);
        $notif_stmt->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Comment posted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error posting comment']);
}
?>
