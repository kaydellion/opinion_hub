<?php
// Create missing tables for polls.php
require_once __DIR__ . '/connect.php';

echo "<h2>Create Missing Polls Tables</h2>";

$tables_created = [];

// Create user_follows table
$user_follows_sql = "CREATE TABLE IF NOT EXISTS user_follows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_following (follower_id, following_id),
    INDEX(follower_id),
    INDEX(following_id)
)";

if ($conn->query($user_follows_sql)) {
    $tables_created[] = 'user_follows';
    echo "<p style='color: green;'>✅ user_follows table created successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to create user_follows table: " . $conn->error . "</p>";
}

// Create poll_responses table
$poll_responses_sql = "CREATE TABLE IF NOT EXISTS poll_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poll_id INT NOT NULL,
    user_id INT,
    response_data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX(poll_id),
    INDEX(user_id)
)";

if ($conn->query($poll_responses_sql)) {
    $tables_created[] = 'poll_responses';
    echo "<p style='color: green;'>✅ poll_responses table created successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to create poll_responses table: " . $conn->error . "</p>";
}

// Verify tables were created
echo "<hr>";
echo "<h3>Verification:</h3>";

foreach ($tables_created as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        echo "<p style='color: green;'>✅ $table exists and is ready</p>";
    } else {
        echo "<p style='color: red;'>❌ $table still missing</p>";
    }
}

if (!empty($tables_created)) {
    echo "<hr>";
    echo "<h3 style='color: green;'>✅ Tables Created Successfully!</h3>";
    echo "<p>The polls.php page should now work correctly.</p>";
}

echo "<hr>";
echo "<p><a href='polls.php'>← Go to Polls Page</a></p>";
echo "<p><a href='check_polls_tables.php'>← Check Tables Again</a></p>";
?>
