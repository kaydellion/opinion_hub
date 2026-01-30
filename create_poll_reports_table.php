<?php
// Quick script to create poll_reports table
require_once __DIR__ . '/connect.php';

echo "<h2>Create Poll Reports Table</h2>";

// Create the poll_reports table
$sql = "CREATE TABLE IF NOT EXISTS poll_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poll_id INT NOT NULL,
    reporter_id INT NOT NULL,
    reason ENUM('inappropriate_content', 'spam', 'misleading', 'duplicate', 'other') NOT NULL,
    description TEXT,
    status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY(reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX(poll_id),
    INDEX(reporter_id),
    INDEX(status),
    INDEX(created_at)
)";

if ($conn->query($sql)) {
    echo "<p style='color: green;'>✅ Poll reports table created successfully!</p>";
    
    // Verify table exists
    $check = $conn->query("SHOW TABLES LIKE 'poll_reports'");
    if ($check && $check->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table verification successful!</p>";
    } else {
        echo "<p style='color: red;'>❌ Table verification failed!</p>";
    }
    
    // Test a simple query
    $test = $conn->query("SELECT COUNT(*) as count FROM poll_reports");
    if ($test) {
        $count = $test->fetch_assoc()['count'];
        echo "<p style='color: green;'>✅ Test query successful! Current reports: $count</p>";
    } else {
        echo "<p style='color: red;'>❌ Test query failed: " . $conn->error . "</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Failed to create table: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<p><a href='admin/dashboard.php'>← Go to Admin Dashboard</a></p>";
?>
