<?php
include_once 'connect.php';

echo "<h1>Database Tables Check</h1>";

// Check if following tables exist
$user_follows_exists = false;
$user_category_follows_exists = false;

$result = $conn->query("SHOW TABLES LIKE 'user_follows'");
if ($result && $result->num_rows > 0) {
    $user_follows_exists = true;
}

$result = $conn->query("SHOW TABLES LIKE 'user_category_follows'");
if ($result && $result->num_rows > 0) {
    $user_category_follows_exists = true;
}

echo "<h2>Following System Tables:</h2>";
echo "<p><strong>user_follows table:</strong> " . ($user_follows_exists ? "<span style='color: green;'>EXISTS ✓</span>" : "<span style='color: red;'>MISSING ✗</span>") . "</p>";
echo "<p><strong>user_category_follows table:</strong> " . ($user_category_follows_exists ? "<span style='color: green;'>EXISTS ✓</span>" : "<span style='color: red;'>MISSING ✗</span>") . "</p>";

if (!$user_follows_exists || !$user_category_follows_exists) {
    echo "<h2>Setup Required:</h2>";
    echo "<p>The following tables are missing. Please run one of these options:</p>";

    echo "<h3>Option 1: Run the setup script</h3>";
    echo "<p>Visit: <a href='setup_following_system.php' target='_blank'>setup_following_system.php</a></p>";

    echo "<h3>Option 2: Run the SQL manually</h3>";
    echo "<p>Execute the contents of <code>add_following_system.sql</code> in your MySQL database</p>";

    echo "<h3>Option 3: Quick setup via this page</h3>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='setup_tables' class='btn btn-primary'>Create Tables Now</button>";
    echo "</form>";
}

// Handle table creation if requested
if (isset($_POST['setup_tables'])) {
    echo "<h2>Creating Tables...</h2>";

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
            echo "<p style='color: green;'>✓ Created user_follows table</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create user_follows table: " . $conn->error . "</p>";
        }
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
            echo "<p style='color: green;'>✓ Created user_category_follows table</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create user_category_follows table: " . $conn->error . "</p>";
        }
    }

    echo "<p><a href='check_tables.php'>Refresh to check status</a></p>";
}
?>



