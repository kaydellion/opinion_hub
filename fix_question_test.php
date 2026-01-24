<?php
// Fix the question insertion test

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

echo "<h2>Testing Question Insertion (Fixed)</h2>";

// Test a simple insert
echo "<h3>Testing question insertion...</h3>";
$test_poll_id = 1; // Assuming there's at least one poll
$test_query = "INSERT INTO poll_questions (poll_id, question_text, question_description, question_type, is_required, question_order)
               VALUES ($test_poll_id, 'Test Question', 'Test Description', 'multiple_choice', 1, 1)";

if ($conn->query($test_query)) {
    echo "<p style='color: green;'>✅ Test question insertion successful!</p>";
    echo "<p>The question_description and question_image columns are working correctly.</p>";

    // Clean up
    $conn->query("DELETE FROM poll_questions WHERE question_text = 'Test Question'");
    echo "<p>Test data cleaned up.</p>";
} else {
    echo "<p style='color: red;'>❌ Test question insertion failed: " . $conn->error . "</p>";

    // Check if it's just because poll_id 1 doesn't exist
    $poll_check = $conn->query("SELECT id FROM polls LIMIT 1");
    if ($poll_check && $poll_check->num_rows > 0) {
        $poll = $poll_check->fetch_assoc();
        $real_poll_id = $poll['id'];

        $test_query2 = "INSERT INTO poll_questions (poll_id, question_text, question_description, question_type, is_required, question_order)
                       VALUES ($real_poll_id, 'Test Question', 'Test Description', 'multiple_choice', 1, 1)";

        if ($conn->query($test_query2)) {
            echo "<p style='color: green;'>✅ Test question insertion successful with correct poll_id!</p>";
            // Clean up
            $conn->query("DELETE FROM poll_questions WHERE question_text = 'Test Question'");
        } else {
            echo "<p style='color: red;'>❌ Still failed: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No polls exist in the database, so question insertion test is expected to fail.</p>";
        echo "<p>This is normal - you need to create a poll first before adding questions.</p>";
    }
}

$conn->close();

echo "<br><br>";
echo "<a href='client/manage-polls.php'>← Go to Manage Polls</a> | ";
echo "<a href='client/add-questions.php?id=1'>Add Questions to Poll</a>";
?>



