<?php
require_once __DIR__ . '/../connect.php';

echo "Adding total_contacts column to contact_lists table...\n";

try {
    // Add the column if it doesn't exist
    $sql1 = "ALTER TABLE contact_lists ADD COLUMN IF NOT EXISTS total_contacts INT DEFAULT 0";
    if ($conn->query($sql1) === TRUE) {
        echo "✓ Added total_contacts column to contact_lists table successfully.\n";
    } else {
        echo "✗ Error adding total_contacts column: " . $conn->error . "\n";
    }

    // Update existing records to have correct count
    $sql2 = "UPDATE contact_lists SET total_contacts = (
        SELECT COUNT(*) FROM contacts WHERE contacts.list_id = contact_lists.id
    )";
    if ($conn->query($sql2) === TRUE) {
        echo "✓ Updated existing contact list totals.\n";
    } else {
        echo "✗ Error updating contact totals: " . $conn->error . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Migration completed.\n";
?>
