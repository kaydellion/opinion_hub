<?php
// Check if all required tables for polls.php exist
require_once __DIR__ . '/connect.php';

echo "<h2>Check Polls.php Required Tables</h2>";

$required_tables = [
    'polls' => 'Main polls table',
    'categories' => 'Categories for polls',
    'poll_responses' => 'User responses to polls',
    'user_follows' => 'User following system',
    'poll_questions' => 'Questions within polls',
    'blog_posts' => 'Blog posts for sidebar'
];

$missing_tables = [];
$existing_tables = [];

foreach ($required_tables as $table => $description) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        $existing_tables[] = $table;
        echo "<p style='color: green;'>✅ $table - $description</p>";
        
        // Show table structure for key tables
        if ($table === 'polls') {
            $structure = $conn->query("DESCRIBE $table");
            echo "<details><summary>View $table structure</summary>";
            echo "<table border='1' cellpadding='3'><tr><th>Column</th><th>Type</th></tr>";
            while ($row = $structure->fetch_assoc()) {
                echo "<tr><td>" . htmlspecialchars($row['Field']) . "</td><td>" . htmlspecialchars($row['Type']) . "</td></tr>";
            }
            echo "</table></details>";
        }
    } else {
        $missing_tables[] = $table;
        echo "<p style='color: red;'>❌ $table - $description (MISSING)</p>";
    }
}

echo "<hr>";

if (!empty($missing_tables)) {
    echo "<h3 style='color: red;'>Missing Tables Found!</h3>";
    echo "<p>The following tables need to be created:</p>";
    echo "<ul>";
    foreach ($missing_tables as $table) {
        echo "<li><strong>$table</strong> - " . $required_tables[$table] . "</li>";
    }
    echo "</ul>";
    
    // Provide SQL to create missing tables
    echo "<h3>SQL to Create Missing Tables:</h3>";
    echo "<pre>";
    
    if (in_array('user_follows', $missing_tables)) {
        echo "-- Create user_follows table
CREATE TABLE IF NOT EXISTS user_follows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_following (follower_id, following_id),
    INDEX(follower_id),
    INDEX(following_id)
);

";
    }
    
    if (in_array('poll_responses', $missing_tables)) {
        echo "-- Create poll_responses table
CREATE TABLE IF NOT EXISTS poll_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poll_id INT NOT NULL,
    user_id INT,
    response_data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX(poll_id),
    INDEX(user_id)
);

";
    }
    
    echo "</pre>";
    
    echo "<p><a href='create_polls_tables.php'>← Auto-create Missing Tables</a></p>";
} else {
    echo "<h3 style='color: green;'>✅ All Required Tables Exist!</h3>";
    echo "<p>The polls.php page should work correctly.</p>";
}

echo "<hr>";
echo "<p><a href='polls.php'>← Go to Polls Page</a></p>";
?>
