<?php
require_once 'connect.php';

echo "Checking polls table structure...\n\n";

// Check if disclaimer column exists
$result = $conn->query("DESCRIBE polls");

$has_disclaimer = false;
echo "Polls table columns:\n";
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']}: {$row['Type']} ({$row['Null']})\n";
    if ($row['Field'] === 'disclaimer') {
        $has_disclaimer = true;
    }
}

echo "\n";
if ($has_disclaimer) {
    echo "✅ Disclaimer column found!\n";
} else {
    echo "❌ Disclaimer column NOT found!\n";
    echo "Run this SQL in phpMyAdmin:\n";
    echo "ALTER TABLE polls ADD COLUMN disclaimer TEXT NULL AFTER description;\n";
}

// Test poll creation query
echo "\nTesting poll creation query...\n";
$test_query = "INSERT INTO polls (created_by, title, slug, description, disclaimer, category_id, poll_type, status)
               VALUES (1, 'Test Poll', 'test-poll', 'Test Description', 'Test Disclaimer', 1, 'Opinion Poll', 'draft')";

if ($conn->query($test_query)) {
    echo "✅ Test query successful!\n";
    // Clean up test data
    $conn->query("DELETE FROM polls WHERE title = 'Test Poll'");
} else {
    echo "❌ Test query failed: " . $conn->error . "\n";
}

$conn->close();
?>


