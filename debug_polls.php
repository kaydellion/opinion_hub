<?php
require_once 'connect.php';
require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    die("Please login first");
}

$user = getCurrentUser();
echo "Current User: " . $user['first_name'] . " " . $user['last_name'] . " (Role: " . $user['role'] . ", ID: " . $user['id'] . ")\n\n";

// Check total polls in database
$total_result = $conn->query("SELECT COUNT(*) as total FROM polls");
$total_polls = $total_result->fetch_assoc()['total'];
echo "Total polls in database: $total_polls\n\n";

// Check polls created by current user
$user_polls_result = $conn->query("SELECT COUNT(*) as total FROM polls WHERE created_by = " . $user['id']);
$user_polls = $user_polls_result->fetch_assoc()['total'];
echo "Polls created by current user: $user_polls\n\n";

// Show sample polls
if ($total_polls > 0) {
    echo "Sample polls:\n";
    $sample_result = $conn->query("SELECT p.id, p.title, p.status, p.created_by, u.first_name, u.last_name FROM polls p LEFT JOIN users u ON p.created_by = u.id LIMIT 5");
    while ($poll = $sample_result->fetch_assoc()) {
        echo "- ID: {$poll['id']}, Title: {$poll['title']}, Status: {$poll['status']}, Creator: {$poll['first_name']} {$poll['last_name']} (ID: {$poll['created_by']})\n";
    }
    echo "\n";
}

// Check if admin polls page query works
echo "Testing admin polls query:\n";
$query = "
    SELECT p.id, p.title, p.description, p.status, p.poll_type, p.created_at, p.category_id,
           CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as creator_name,
           COALESCE(c.name, 'No Category') as category_name,
           COALESCE((SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id), 0) as response_count
    FROM polls p
    LEFT JOIN users u ON p.created_by = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
";

$admin_result = $conn->query($query);
if ($admin_result) {
    echo "Admin query successful, found " . $admin_result->num_rows . " polls\n";
    if ($admin_result->num_rows > 0) {
        $poll = $admin_result->fetch_assoc();
        echo "First poll: ID {$poll['id']}, Title: {$poll['title']}, Creator: {$poll['creator_name']}\n";
    }
} else {
    echo "Admin query failed: " . $conn->error . "\n";
}

$conn->close();
?>



