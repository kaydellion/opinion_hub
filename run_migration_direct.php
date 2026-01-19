<?php
// Direct migration runner - no external dependencies

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

echo "<h2>Running Disclaimer Migration</h2>";

// Check if disclaimer column already exists
$result = $conn->query("SHOW COLUMNS FROM polls LIKE 'disclaimer'");
$column_exists = $result->num_rows > 0;

if ($column_exists) {
    echo "<p style='color: green;'>✅ Disclaimer column already exists!</p>";
} else {
    // Run the migration
    $sql = "ALTER TABLE polls ADD COLUMN disclaimer TEXT NULL AFTER description";

    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>✅ Migration completed successfully!</p>";
        echo "<p>Disclaimer column has been added to the polls table.</p>";
    } else {
        echo "<p style='color: red;'>❌ Migration failed: " . $conn->error . "</p>";
    }
}

// Verify the column exists now
$result = $conn->query("SHOW COLUMNS FROM polls LIKE 'disclaimer'");
$column_exists_now = $result->num_rows > 0;

echo "<h3>Verification:</h3>";
if ($column_exists_now) {
    echo "<p style='color: green;'>✅ Disclaimer column is now present in polls table</p>";
} else {
    echo "<p style='color: red;'>❌ Disclaimer column is still missing</p>";
}

$conn->close();

echo "<br><br>";
echo "<a href='../admin/polls.php'>← Back to Admin Polls</a>";
?>


