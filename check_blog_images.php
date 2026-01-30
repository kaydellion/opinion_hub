<?php
// Check blog posts structure and image paths
require_once __DIR__ . '/connect.php';

echo "<h2>Check Blog Images</h2>";

// Check blog_posts table structure
echo "<h3>Blog Posts Table Structure:</h3>";
$structure = $conn->query("DESCRIBE blog_posts");
if ($structure) {
    echo "<table border='1' cellpadding='3'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check sample blog posts data
echo "<h3>Sample Blog Posts Data:</h3>";
$posts = $conn->query("SELECT id, title, featured_image FROM blog_posts WHERE status = 'approved' LIMIT 5");
if ($posts && $posts->num_rows > 0) {
    echo "<table border='1' cellpadding='3'>";
    echo "<tr><th>ID</th><th>Title</th><th>Featured Image Path</th><th>File Exists?</th></tr>";
    while ($post = $posts->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $post['id'] . "</td>";
        echo "<td>" . htmlspecialchars(substr($post['title'], 0, 50)) . "</td>";
        echo "<td>" . htmlspecialchars($post['featured_image'] ?? 'NULL') . "</td>";
        
        // Check if file exists with different possible paths
        $image_path = $post['featured_image'];
        $exists = 'No';
        
        if (!empty($image_path)) {
            // Try different paths
            $paths_to_check = [
                __DIR__ . '/uploads/blog/' . $image_path,
                __DIR__ . '/uploads/' . $image_path,
                __DIR__ . '/uploads/blog_images/' . $image_path,
                __DIR__ . '/' . $image_path
            ];
            
            foreach ($paths_to_check as $path) {
                if (file_exists($path)) {
                    $exists = 'Yes (' . basename(dirname($path)) . '/' . basename($path) . ')';
                    break;
                }
            }
        }
        
        echo "<td>" . $exists . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No approved blog posts found.</p>";
}

// Check uploads directory structure
echo "<h3>Uploads Directory Structure:</h3>";
$uploads_dir = __DIR__ . '/uploads';
if (is_dir($uploads_dir)) {
    echo "<p>Uploads directory exists: " . $uploads_dir . "</p>";
    
    // List subdirectories
    $dirs = glob($uploads_dir . '/*', GLOB_ONLYDIR);
    if (!empty($dirs)) {
        echo "<p>Subdirectories found:</p>";
        echo "<ul>";
        foreach ($dirs as $dir) {
            $dir_name = basename($dir);
            echo "<li>$dir_name</li>";
            
            // List some files in each directory
            $files = glob($dir . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
            if (!empty($files)) {
                echo "<ul>";
                foreach (array_slice($files, 0, 3) as $file) {
                    echo "<li>" . basename($file) . "</li>";
                }
                if (count($files) > 3) {
                    echo "<li>... and " . (count($files) - 3) . " more</li>";
                }
                echo "</ul>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>No subdirectories found in uploads.</p>";
    }
} else {
    echo "<p>Uploads directory does not exist.</p>";
}

// Test current URL path
echo "<h3>Test Image URL:</h3>";
$test_post = $conn->query("SELECT featured_image FROM blog_posts WHERE featured_image IS NOT NULL AND featured_image != '' LIMIT 1");
if ($test_post && $test_post->num_rows > 0) {
    $test_image = $test_post->fetch_assoc()['featured_image'];
    echo "<p>Test image path: " . htmlspecialchars($test_image) . "</p>";
    
    $test_urls = [
        SITE_URL . 'uploads/blog/' . $test_image,
        SITE_URL . 'uploads/' . $test_image,
        SITE_URL . 'uploads/blog_images/' . $test_image,
        SITE_URL . $test_image
    ];
    
    echo "<p>Testing URLs:</p>";
    echo "<ul>";
    foreach ($test_urls as $url) {
        echo "<li><a href='$url' target='_blank'>" . htmlspecialchars($url) . "</a></li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='polls.php'>← Back to Polls</a></p>";
?>
