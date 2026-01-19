<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$user = getCurrentUser();

// Create a sample poll
$title = "Sample Poll - " . date('Y-m-d H:i:s');
$description = "This is a sample poll created for testing the admin poll management system. It demonstrates all the features of the poll system.";
$slug = generateUniquePollSlug($title);

// Get a category if available
$category_result = $conn->query("SELECT id FROM categories WHERE is_active = 1 LIMIT 1");
$category_id = $category_result && $category_result->num_rows > 0 ? $category_result->fetch_assoc()['id'] : null;

$query = "INSERT INTO polls
          (created_by, title, slug, description, category_id, poll_type, status, allow_comments, allow_multiple_votes, one_vote_per_ip, one_vote_per_account, results_public_after_vote, results_private, target_responders)
          VALUES (?, ?, ?, ?, ?, 'Opinion Poll', 'draft', 1, 0, 0, 1, 0, 1, 100)";

$stmt = $conn->prepare($query);
$stmt->bind_param('issssi', $user['id'], $title, $slug, $description, $category_id);

if ($stmt->execute()) {
    $poll_id = $conn->insert_id;

    // Add a sample question
    $question_query = "INSERT INTO poll_questions (poll_id, question_text, question_type, question_order, is_required)
                      VALUES (?, 'What is your favorite color?', 'multiple_choice', 1, 1)";
    $question_stmt = $conn->prepare($question_query);
    $question_stmt->bind_param('i', $poll_id);

    if ($question_stmt->execute()) {
        $question_id = $conn->insert_id;

        // Add question options
        $options = ['Red', 'Blue', 'Green', 'Yellow', 'Purple'];
        $options_json = json_encode($options);

        $conn->query("UPDATE poll_questions SET question_options = '$options_json' WHERE id = $question_id");
    }

    echo json_encode(['success' => true, 'message' => 'Sample poll created successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create poll: ' . $conn->error]);
}

$conn->close();
?>


