<?php
include_once 'connect.php';

echo "<h1>Database Data Check</h1>";

// Check users table
echo "<h2>Users Table:</h2>";
$result = $conn->query("SELECT id, first_name, last_name, email FROM users LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['first_name']} {$row['last_name']}</td><td>{$row['email']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No users found or query failed</p>";
}

// Check categories table
echo "<h2>Categories Table:</h2>";
$result = $conn->query("SELECT id, name FROM categories LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No categories found or query failed</p>";
}

// Check current user session
echo "<h2>Current Session:</h2>";
session_start();
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "<p>Logged in as user ID: $user_id</p>";

    // Get user details
    $result = $conn->query("SELECT id, first_name, last_name, email FROM users WHERE id = $user_id");
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p>User: {$user['first_name']} {$user['last_name']} ({$user['email']})</p>";
    } else {
        echo "<p style='color: red;'>User not found in database!</p>";
    }
} else {
    echo "<p style='color: red;'>No user logged in</p>";
}

echo "<p><a href='index.php'>Back to Home</a></p>";
?>


