<?php
session_start();
require_once '../connect.php';
require_once '../functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to like posts']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

if ($post_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit();
}

// Check if user already liked this post
$check_stmt = $conn->prepare("SELECT id FROM blog_likes WHERE post_id = ? AND user_id = ?");
if (!$check_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$check_stmt->bind_param("ii", $post_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Unlike - remove the like
    $delete_stmt = $conn->prepare("DELETE FROM blog_likes WHERE post_id = ? AND user_id = ?");
    $delete_stmt->bind_param("ii", $post_id, $user_id);
    $delete_stmt->execute();
    $liked = false;
} else {
    // Like - add the like
    $insert_stmt = $conn->prepare("INSERT INTO blog_likes (post_id, user_id) VALUES (?, ?)");
    $insert_stmt->bind_param("ii", $post_id, $user_id);
    $insert_stmt->execute();
    $liked = true;
}

// Get updated like count
$count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM blog_likes WHERE post_id = ?");
$count_stmt->bind_param("i", $post_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'liked' => $liked,
    'like_count' => $count_result['count']
]);
?>
