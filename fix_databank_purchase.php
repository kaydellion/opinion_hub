<?php
require_once 'connect.php';
require_once 'functions.php';

// Fix the failed databank purchases
$user_id = 1;
$poll_id = 2;
$amount_paid = 50.00; // ₦5000 / 100

// Check if access already exists
$check = $conn->query("SELECT id FROM poll_results_access WHERE user_id = $user_id AND poll_id = $poll_id");
if ($check->num_rows > 0) {
    echo "Access already exists!\n";
} else {
    // Grant access
    $stmt = $conn->prepare("INSERT INTO poll_results_access (user_id, poll_id, amount_paid) VALUES (?, ?, ?)");
    if (!$stmt) {
        echo "Error: " . $conn->error . "\n";
        exit;
    }
    $stmt->bind_param("iid", $user_id, $poll_id, $amount_paid);
    
    if ($stmt->execute()) {
        echo "Access granted successfully!\n";
        
        // Create notification
        createNotification(
            $user_id,
            'success',
            "Poll Results Purchased",
            "You now have lifetime access to the poll results (manually granted)"
        );
        echo "Notification created!\n";
    } else {
        echo "Error granting access: " . $stmt->error . "\n";
    }
}
