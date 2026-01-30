<?php
/**
 * Migration script to create the dataset_downloads table
 * Run this script once on the live server to set up the missing table
 */

// Database configuration - ensure $conn is defined
if (!isset($conn)) {
    // Include database configuration
    require_once 'connect.php';
}

// Check if we have database connection
if (!isset($conn) || !$conn) {
    // Fallback: manually establish connection  
    die('ERROR: Database connection not established. Please ensure connect.php is properly configured.');
}

// Create the dataset_downloads table
$create_table_sql = "CREATE TABLE IF NOT EXISTS dataset_downloads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    poll_id INT NOT NULL,
    dataset_format VARCHAR(50),
    time_period VARCHAR(50),
    download_count INT DEFAULT 1,
    download_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    INDEX(user_id),
    INDEX(poll_id),
    UNIQUE KEY unique_download (user_id, poll_id, dataset_format, time_period)
)";

if ($conn->query($create_table_sql)) {
    echo "<div style='color: green; padding: 20px; background-color: #f0fff0; margin: 10px 0; border: 1px solid #90EE90;'>";
    echo "<h3>✓ SUCCESS: dataset_downloads table created successfully!</h3>";
    echo "<p>The missing table has been created with the following structure:</p>";
    echo "<ul>";
    echo "<li><strong>id</strong> - PRIMARY KEY, AUTO_INCREMENT</li>";
    echo "<li><strong>user_id</strong> - Foreign key to users table</li>";
    echo "<li><strong>poll_id</strong> - Foreign key to polls table</li>";
    echo "<li><strong>dataset_format</strong> - Format of downloaded dataset (CSV, JSON, XLSX, etc.)</li>";
    echo "<li><strong>time_period</strong> - Time period (monthly, yearly, custom, etc.)</li>";
    echo "<li><strong>download_count</strong> - Number of times downloaded (DEFAULT 1)</li>";
    echo "<li><strong>download_date</strong> - Timestamp of last download</li>";
    echo "<li><strong>created_at</strong> - Timestamp when record was created</li>";
    echo "</ul>";
    echo "<p><strong>Indexes:</strong> user_id, poll_id, and unique constraint on (user_id, poll_id, dataset_format, time_period)</p>";
    echo "</div>";
    
    // Log the migration
    error_log("[MIGRATION] dataset_downloads table created successfully at " . date('Y-m-d H:i:s'));
} else {
    echo "<div style='color: red; padding: 20px; background-color: #fff0f0; margin: 10px 0; border: 1px solid #FF6B6B;'>";
    echo "<h3>✗ ERROR: Failed to create dataset_downloads table</h3>";
    echo "<p><strong>Database Error:</strong> " . $conn->error . "</p>";
    echo "</div>";
    
    error_log("[MIGRATION ERROR] Failed to create dataset_downloads table: " . $conn->error);
}

// Verify the table was created
echo "<hr>";
echo "<h4>Verification:</h4>";
$verify_result = $conn->query("SHOW TABLES LIKE 'dataset_downloads'");
if ($verify_result && $verify_result->num_rows > 0) {
    echo "<p style='color: green;'><strong>✓ Table exists in database</strong></p>";
    
    // Show table structure
    $structure_result = $conn->query("DESCRIBE dataset_downloads");
    if ($structure_result) {
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; margin-top: 10px;'>";
        echo "<tr style='background-color: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $structure_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'><strong>✗ Table does not exist - migration may have failed</strong></p>";
}

// Check if there are any references to this table that need updating
echo "<hr>";
echo "<h4>Files Using dataset_downloads Table:</h4>";
echo "<ul>";
echo "<li>vpay-callback.php (lines 308, 427) - Records dataset downloads after payment</li>";
echo "<li>view-purchased-result.php (lines 26, 65, 73, 77) - Tracks dataset download history</li>";
echo "</ul>";

echo "<p style='margin-top: 20px; padding: 10px; background-color: #f9f9f9; border-left: 4px solid #007bff;'>";
echo "<strong>Note:</strong> This table is used to track when users download datasets they've purchased. ";
echo "The download_count field helps identify which datasets are most popular, and download_date helps monitor usage patterns.";
echo "</p>";
?>
