<?php
// Check response tables structure and sample data
require_once __DIR__ . '/connect.php';

echo "<h2>Check Response Tables Structure</h2>";

// Check poll_responses table
echo "<h3>Poll Responses Table:</h3>";
$poll_responses_check = $conn->query("DESCRIBE poll_responses");
if ($poll_responses_check) {
    echo "<table border='1' cellpadding='3'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $poll_responses_check->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($row['Field']) . "</td><td>" . htmlspecialchars($row['Type']) . "</td><td>" . htmlspecialchars($row['Null']) . "</td><td>" . htmlspecialchars($row['Key']) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>poll_responses table not found</p>";
}

// Check question_responses table
echo "<h3>Question Responses Table:</h3>";
$question_responses_check = $conn->query("DESCRIBE question_responses");
if ($question_responses_check) {
    echo "<table border='1' cellpadding='3'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $question_responses_check->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($row['Field']) . "</td><td>" . htmlspecialchars($row['Type']) . "</td><td>" . htmlspecialchars($row['Null']) . "</td><td>" . htmlspecialchars($row['Key']) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>question_responses table not found</p>";
}

// Show sample data
echo "<h3>Sample Question Responses Data:</h3>";
$sample_data = $conn->query("SELECT * FROM question_responses LIMIT 5");
if ($sample_data && $sample_data->num_rows > 0) {
    echo "<table border='1' cellpadding='3'>";
    echo "<tr><th>ID</th><th>Response ID</th><th>Question ID</th><th>Option ID</th><th>Text Response</th></tr>";
    while ($row = $sample_data->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['response_id'] . "</td>";
        echo "<td>" . $row['question_id'] . "</td>";
        echo "<td>" . $row['option_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['text_response'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No sample data found</p>";
}

// Show sample poll responses data
echo "<h3>Sample Poll Responses Data:</h3>";
$sample_poll_data = $conn->query("SELECT * FROM poll_responses LIMIT 3");
if ($sample_poll_data && $sample_poll_data->num_rows > 0) {
    echo "<table border='1' cellpadding='3'>";
    echo "<tr><th>ID</th><th>Poll ID</th><th>Respondent ID</th><th>Response Data</th></tr>";
    while ($row = $sample_poll_data->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['poll_id'] . "</td>";
        echo "<td>" . $row['respondent_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['response_data'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No sample poll data found</p>";
}

echo "<hr>";
echo "<p><a href='view-poll.php'>← Back to Poll</a></p>";
?>
