<?php
// Debug blog images - check what's in the database
require_once __DIR__ . '/connect.php';

echo "<h2>Debug Blog Images</h2>";

// Get all approved blog posts with their featured images
$posts_query = "SELECT id, title, featured_image, created_at FROM blog_posts WHERE status = 'approved' ORDER BY created_at DESC LIMIT 10";
$posts = $conn->query($posts_query);

if ($posts && $posts->num_rows > 0) {
    echo "<h3>Blog Posts with Featured Images:</h3>";
    echo "<table border='1' cellpadding='5' style='width: 100%;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Featured Image</th><th>Image URL</th><th>Test</th></tr>";
    
    while ($post = $posts->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $post['id'] . "</td>";
        echo "<td>" . htmlspecialchars(substr($post['title'], 0, 50)) . "</td>";
        echo "<td>" . htmlspecialchars($post['featured_image'] ?? 'NULL') . "</td>";
        
        if (!empty($post['featured_image'])) {
            $image_url = SITE_URL . 'uploads/blog/' . $post['featured_image'];
            echo "<td><code>" . htmlspecialchars($image_url) . "</code></td>";
            echo "<td>";
            echo "<a href='$image_url' target='_blank' class='btn btn-sm btn-primary'>Test Image</a> ";
            echo "<img src='$image_url' style='max-width: 50px; max-height: 50px;' onerror='this.style.border=\"2px solid red\"'>";
            echo "</td>";
        } else {
            echo "<td colspan='2'>No image</td>";
        }
        
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p>No approved blog posts found.</p>";
}

// Check if there are any images in the uploads directory
echo "<h3>Uploads Directory Check:</h3>";
$blog_dir = __DIR__ . '/uploads/blog';
if (is_dir($blog_dir)) {
    $files = glob($blog_dir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    if (!empty($files)) {
        echo "<p>Found " . count($files) . " images in uploads/blog/:</p>";
        echo "<ul>";
        foreach ($files as $file) {
            echo "<li>" . basename($file) . " (" . number_format(filesize($file)/1024, 2) . " KB)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No images found in uploads/blog/ directory.</p>";
    }
} else {
    echo "<p>uploads/blog/ directory does not exist.</p>";
}

// Create a test blog post with a placeholder image if needed
echo "<h3>Create Test Blog Post:</h3>";
echo "<p>If you want to test the image display, you can:</p>";
echo "<ol>";
echo "<li>Upload an image to the uploads/blog/ directory</li>";
echo "<li>Update a blog post's featured_image field in the database</li>";
echo "<li>Or create a new blog post with an image</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='polls.php'>← Back to Polls</a></p>";
echo "<p><a href='check_blog_images.php'>← Check Blog Images</a></p>";
?>
