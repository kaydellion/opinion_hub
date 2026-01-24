<?php
require_once 'connect.php';

echo "Running disclaimer migration...\n";

$sql = file_get_contents('migrations/add_poll_disclaimer.sql');

if ($conn->query($sql)) {
    echo "Migration completed successfully!\n";
    echo "✓ Added disclaimer column to polls table\n";
} else {
    echo "Migration failed: " . $conn->error . "\n";
}

$conn->close();
?>



