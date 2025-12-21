<?php
/**
 * Migration Script: Add Slug Column to Polls Table
 * Run this file once to add slug support for pretty URLs
 * Access: http://localhost/opinion/migrations/run_poll_slug_migration.php
 */

require_once '../connect.php';

set_time_limit(300);

echo "<h2>Running Poll Slug Migration</h2>";
echo "<pre>";

$errors = [];
$success = [];

// 1. Add slug column
echo "Adding slug column to polls table...\n";
$sql = "ALTER TABLE polls ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title";
if ($conn->query($sql) === TRUE) {
    $success[] = "✓ Added slug column";
    echo "✓ Added slug column\n";
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "- slug column already exists\n";
    } else {
        $errors[] = "Error adding slug column: " . $conn->error;
        echo "✗ Error adding slug column: " . $conn->error . "\n";
    }
}

// 2. Add index on slug
echo "Adding index on slug...\n";
$sql = "ALTER TABLE polls ADD INDEX idx_slug (slug)";
if ($conn->query($sql) === TRUE) {
    $success[] = "✓ Added slug index";
    echo "✓ Added slug index\n";
} else {
    if (strpos($conn->error, 'Duplicate key') !== false) {
        echo "- slug index already exists\n";
    } else {
        echo "✗ Error adding slug index: " . $conn->error . "\n";
    }
}

// 3. Generate slugs for existing polls
echo "\nGenerating slugs for existing polls...\n";

// Get all polls without slugs
$polls_result = $conn->query("SELECT id, title FROM polls WHERE slug IS NULL OR slug = ''");
if ($polls_result) {
    $updated_count = 0;
    while ($poll = $polls_result->fetch_assoc()) {
        // Create slug from title
        $slug = strtolower(trim($poll['title']));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 50); // Limit length
        $slug = $slug . '-' . $poll['id']; // Add ID to ensure uniqueness
        
        // Update poll with slug
        $update_stmt = $conn->prepare("UPDATE polls SET slug = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("si", $slug, $poll['id']);
            if ($update_stmt->execute()) {
                $updated_count++;
                echo "  ✓ Generated slug for poll #{$poll['id']}: {$slug}\n";
            } else {
                echo "  ✗ Failed to update poll #{$poll['id']}: " . $update_stmt->error . "\n";
            }
        }
    }
    
    if ($updated_count > 0) {
        $success[] = "✓ Generated slugs for $updated_count polls";
        echo "\n✓ Generated slugs for $updated_count polls\n";
    } else {
        echo "\n- No polls needed slug generation\n";
    }
} else {
    echo "✗ Error fetching polls: " . $conn->error . "\n";
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "MIGRATION SUMMARY\n";
echo str_repeat("=", 50) . "\n";

if (!empty($success)) {
    echo "\nSuccessful operations:\n";
    foreach ($success as $msg) {
        echo "  $msg\n";
    }
}

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $msg) {
        echo "  ✗ $msg\n";
    }
    echo "\nMigration completed with errors.\n";
} else {
    echo "\n✓ Migration completed successfully!\n";
    echo "\nYou can now use pretty URLs like:\n";
    echo "  http://localhost/opinion/view-poll/your-poll-title-123\n";
}

echo "\n</pre>";
echo "<p><a href='../polls.php'>Go to Polls Page</a></p>";
echo "<p><a href='../'>Back to Homepage</a></p>";

$conn->close();
?>
