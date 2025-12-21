<?php
session_start();
require_once '../connect.php';
require_once '../functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($post_id === 0) {
    $_SESSION['error'] = "Invalid post ID.";
    header("Location: my-posts.php");
    exit();
}

// Get post and verify ownership or admin
$stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    $_SESSION['error'] = "Post not found.";
    header("Location: my-posts.php");
    exit();
}

$is_admin = ($_SESSION['user_role'] ?? '') === 'admin';
$is_owner = $post['user_id'] == $user_id;

if (!$is_admin && !$is_owner) {
    $_SESSION['error'] = "You don't have permission to delete this post.";
    header("Location: my-posts.php");
    exit();
}

// Only allow deleting drafts and rejected posts (or admin can delete any)
if (!$is_admin && !in_array($post['status'], ['draft', 'rejected'])) {
    $_SESSION['error'] = "You can only delete draft or rejected posts.";
    header("Location: my-posts.php");
    exit();
}

// Delete associated data first (foreign key constraints)
// Delete comments
$conn->query("DELETE FROM blog_comments WHERE post_id = $post_id");

// Delete likes
$conn->query("DELETE FROM blog_likes WHERE post_id = $post_id");

// Delete shares
$conn->query("DELETE FROM blog_shares WHERE post_id = $post_id");

// Delete featured image if exists
if ($post['featured_image'] && file_exists('../' . $post['featured_image'])) {
    unlink('../' . $post['featured_image']);
}

// Delete the post
$delete_stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
$delete_stmt->bind_param("i", $post_id);

if ($delete_stmt->execute()) {
    $_SESSION['success'] = "Post deleted successfully.";
} else {
    $_SESSION['error'] = "Error deleting post: " . $conn->error;
}

header("Location: my-posts.php");
exit();
?>
