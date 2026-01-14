<?php
/**
 * Following System Setup Script
 * Run this once to create the necessary database tables for the following system
 */

include_once 'connect.php';

echo "<h1>Following System Setup</h1>";

// Check if tables already exist
$user_follows_exists = $conn->query("SHOW TABLES LIKE 'user_follows'")->num_rows > 0;
$user_category_follows_exists = $conn->query("SHOW TABLES LIKE 'user_category_follows'")->num_rows > 0;

if ($user_follows_exists && $user_category_follows_exists) {
    echo "<div style='color: green;'><strong>Following system is already set up!</strong></div>";
    echo "<p>All required tables exist. The following system is ready to use.</p>";
    exit;
}

echo "<h2>Setting up following system tables...</h2>";

// Create user_follows table
if (!$user_follows_exists) {
    $sql1 = "CREATE TABLE user_follows (
        id INT PRIMARY KEY AUTO_INCREMENT,
        follower_id INT NOT NULL,
        following_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_follow (follower_id, following_id),
        INDEX(follower_id),
        INDEX(following_id),
        INDEX(created_at)
    )";

    if ($conn->query($sql1)) {
        echo "<div style='color: green;'>✓ Created user_follows table</div>";
    } else {
        echo "<div style='color: red;'>✗ Failed to create user_follows table: " . $conn->error . "</div>";
    }
} else {
    echo "<div style='color: blue;'>- user_follows table already exists</div>";
}

// Create user_category_follows table
if (!$user_category_follows_exists) {
    $sql2 = "CREATE TABLE user_category_follows (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        category_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        UNIQUE KEY unique_category_follow (user_id, category_id),
        INDEX(user_id),
        INDEX(category_id),
        INDEX(created_at)
    )";

    if ($conn->query($sql2)) {
        echo "<div style='color: green;'>✓ Created user_category_follows table</div>";
    } else {
        echo "<div style='color: red;'>✗ Failed to create user_category_follows table: " . $conn->error . "</div>";
    }
} else {
    echo "<div style='color: blue;'>- user_category_follows table already exists</div>";
}

// Create indexes for better performance
if (!$user_follows_exists) {
    $index_sql1 = "CREATE INDEX idx_user_follows_follower ON user_follows(follower_id)";
    $conn->query($index_sql1);

    $index_sql2 = "CREATE INDEX idx_user_follows_following ON user_follows(following_id)";
    $conn->query($index_sql2);
}

if (!$user_category_follows_exists) {
    $index_sql3 = "CREATE INDEX idx_user_category_follows_user ON user_category_follows(user_id)";
    $conn->query($index_sql3);

    $index_sql4 = "CREATE INDEX idx_user_category_follows_category ON user_category_follows(category_id)";
    $conn->query($index_sql4);
}

echo "<h2>Setup Complete!</h2>";
echo "<div style='color: green; font-weight: bold;'>Following system is now ready to use.</div>";
echo "<p>Users can now follow poll creators and categories.</p>";
echo "<a href='polls.php'>Go to Polls</a> | <a href='index.php'>Go Home</a>";
?>



