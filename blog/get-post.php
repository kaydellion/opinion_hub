<?php
session_start();
require_once '../connect.php';

header('Content-Type: application/json');

$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($post_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit();
}

$stmt = $conn->prepare("SELECT content FROM blog_posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if ($post) {
    echo json_encode(['success' => true, 'content' => $post['content']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Post not found']);
}
?>
