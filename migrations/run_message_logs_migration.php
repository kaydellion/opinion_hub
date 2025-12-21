<?php
/**
 * Migration: Add message_logs and messaging_credits tables
 * Run this file once to create the tables needed for messaging functionality
 */

// Change to parent directory to load files properly
chdir(dirname(__DIR__));
require_once 'connect.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Message Logs Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Message Logs & Credits Migration</h1>";

// Read the migration file
$sql_file = __DIR__ . '/add_message_logs.sql';
$sql_content = file_get_contents($sql_file);

if (!$sql_content) {
    echo "<div class='error'>Failed to read migration file!</div>";
    exit;
}

// Split by semicolons and filter empty statements
$statements = array_filter(
    array_map('trim', explode(';', $sql_content)),
    function($stmt) {
        return !empty($stmt) && !preg_match('/^--/', $stmt);
    }
);

$success_count = 0;
$error_count = 0;

echo "<div class='info'>Found " . count($statements) . " SQL statements to execute...</div>";

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    // Show what we're executing
    $preview = substr($statement, 0, 100) . (strlen($statement) > 100 ? '...' : '');
    echo "<div class='info'><strong>Executing:</strong> " . htmlspecialchars($preview) . "</div>";
    
    if ($conn->query($statement)) {
        echo "<div class='success'>✓ Success</div>";
        $success_count++;
    } else {
        echo "<div class='error'>✗ Error: " . htmlspecialchars($conn->error) . "</div>";
        $error_count++;
    }
}

echo "<hr>";
echo "<h2>Migration Summary</h2>";
echo "<div class='info'>";
echo "<p><strong>Successful:</strong> $success_count statements</p>";
echo "<p><strong>Failed:</strong> $error_count statements</p>";
echo "</div>";

if ($error_count === 0) {
    echo "<div class='success'>";
    echo "<h3>✓ Migration completed successfully!</h3>";
    echo "<p>The following tables are now available:</p>";
    echo "<ul>";
    echo "<li><strong>message_logs</strong> - Tracks all sent messages (SMS, Email, WhatsApp)</li>";
    echo "<li><strong>messaging_credits</strong> - Manages user messaging credits</li>";
    echo "</ul>";
    echo "<p><a href='../client/send-invites.php'>Go back to Send Invites</a></p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>Migration completed with errors</h3>";
    echo "<p>Please check the errors above and try again.</p>";
    echo "</div>";
}

echo "<p><a href='index.php'>← Back to Migrations Hub</a></p>";
echo "</body></html>";

$conn->close();
?>
