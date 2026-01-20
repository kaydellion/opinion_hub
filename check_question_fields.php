<?php
// Direct database check for question fields

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "opinionhub_ng";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Checking Poll Questions Table Structure</h2>";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'poll_questions'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ poll_questions table does not exist!</p>";
    echo "<p>You need to create the poll_questions table first.</p>";
    $conn->close();
    exit;
}

echo "<p style='color: green;'>✅ poll_questions table exists</p>";

// Check columns
$result = $conn->query("DESCRIBE poll_questions");

$columns = [];
echo "<h3>Current columns:</h3><ul>";
while ($row = $result->fetch_assoc()) {
    $columns[$row['Field']] = $row;
    echo "<li>{$row['Field']}: {$row['Type']} ({$row['Null']})</li>";
}
echo "</ul>";

// Check for required columns
$required_columns = ['question_description', 'question_image'];
$missing_columns = [];

foreach ($required_columns as $col) {
    if (!isset($columns[$col])) {
        $missing_columns[] = $col;
    }
}

if (empty($missing_columns)) {
    echo "<p style='color: green;'>✅ All required columns exist!</p>";
} else {
    echo "<p style='color: red;'>❌ Missing columns: " . implode(', ', $missing_columns) . "</p>";

    // Run the migration
    echo "<h3>Running migration...</h3>";

    $migration_sql = "
        ALTER TABLE poll_questions ADD COLUMN question_description TEXT NULL AFTER question_text;
        ALTER TABLE poll_questions ADD COLUMN question_image VARCHAR(255) NULL AFTER question_description;
        ALTER TABLE poll_questions ADD INDEX idx_question_image (question_image);
    ";

    if ($conn->multi_query($migration_sql)) {
        echo "<p style='color: green;'>✅ Migration completed successfully!</p>";
        echo "<p>The question_description and question_image columns have been added.</p>";
    } else {
        echo "<p style='color: red;'>❌ Migration failed: " . $conn->error . "</p>";
    }
}

// Test a simple insert
echo "<h3>Testing question insertion...</h3>";
$test_poll_id = 1; // Assuming there's at least one poll
$test_query = "INSERT INTO poll_questions (poll_id, question_text, question_description, question_type, is_required, question_order)
               VALUES ($test_poll_id, 'Test Question', 'Test Description', 'multiple_choice', 1, 1)";

if ($conn->query($test_query)) {
    echo "<p style='color: green;'>✅ Test question insertion successful!</p>";
    // Clean up
    $conn->query("DELETE FROM poll_questions WHERE question_text = 'Test Question'");
} else {
    echo "<p style='color: red;'>❌ Test question insertion failed: " . $conn->error . "</p>";
}

$conn->close();

echo "<br><br>";
echo "<a href='client/add-questions.php?id=1'>← Try Adding a Question</a>";
?>



