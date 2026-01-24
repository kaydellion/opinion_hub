<?php
require_once 'connect.php';

echo "Checking polls in database...\n\n";

// Check total polls
$result = $conn->query('SELECT COUNT(*) as total FROM polls');
if ($result) {
    $count = $result->fetch_assoc()['total'];
    echo "Total polls in database: $count\n\n";

    if ($count > 0) {
        $polls = $conn->query('SELECT id, title, status, created_at FROM polls ORDER BY created_at DESC LIMIT 10');
        echo "Recent polls:\n";
        while ($poll = $polls->fetch_assoc()) {
            echo "- ID: {$poll['id']}, Title: {$poll['title']}, Status: {$poll['status']}, Created: {$poll['created_at']}\n";
        }
    } else {
        echo "No polls found in database.\n";
    }
} else {
    echo "Query failed: " . $conn->error . "\n";
}

echo "\nChecking users table...\n";
$users_result = $conn->query('SELECT COUNT(*) as total FROM users');
if ($users_result) {
    $users_count = $users_result->fetch_assoc()['total'];
    echo "Total users: $users_count\n";
}

echo "\nChecking categories table...\n";
$cat_result = $conn->query('SELECT COUNT(*) as total FROM categories');
if ($cat_result) {
    $cat_count = $cat_result->fetch_assoc()['total'];
    echo "Total categories: $cat_count\n";

    if ($cat_count > 0) {
        $cats = $conn->query('SELECT id, name FROM categories LIMIT 5');
        echo "Sample categories:\n";
        while ($cat = $cats->fetch_assoc()) {
            echo "- ID: {$cat['id']}, Name: {$cat['name']}\n";
        }
    }
}

$conn->close();
?>



