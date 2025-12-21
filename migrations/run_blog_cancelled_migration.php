<?php
/**
 * Migration: Add 'cancelled' status to blog_posts
 * Allows admins to cancel/unpublish live blog posts
 */

chdir(dirname(__DIR__));
require_once 'connect.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Blog Cancelled Status Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Blog Cancelled Status Migration</h1>";

$sql = "ALTER TABLE blog_posts MODIFY COLUMN status ENUM('draft', 'pending', 'approved', 'rejected', 'cancelled') DEFAULT 'draft'";

if ($conn->query($sql)) {
    echo "<div class='success'>";
    echo "<h3>✓ Migration completed successfully!</h3>";
    echo "<p>The blog_posts status column now supports 'cancelled' status.</p>";
    echo "<p>Admins can now cancel/unpublish live blog posts.</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>✗ Migration failed</h3>";
    echo "<p>Error: " . htmlspecialchars($conn->error) . "</p>";
    echo "</div>";
}

echo "<p><a href='index.php'>← Back to Migrations Hub</a> | <a href='../admin/blog-approval.php'>Go to Blog Management</a></p>";
echo "</body></html>";

$conn->close();
?>
