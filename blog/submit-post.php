<?php
session_start();
require_once '../connect.php';
require_once '../functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "signin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: create.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
$title = trim($_POST['title'] ?? '');
$excerpt = trim($_POST['excerpt'] ?? '');
$content = $_POST['content'] ?? '';
$action = $_POST['action'] ?? 'draft';

// Validation
if (empty($title)) {
    $_SESSION['error'] = "Post title is required.";
    header("Location: " . ($post_id > 0 ? "edit.php?id=$post_id" : "create.php"));
    exit();
}

if (empty($content)) {
    $_SESSION['error'] = "Post content is required.";
    header("Location: " . ($post_id > 0 ? "edit.php?id=$post_id" : "create.php"));
    exit();
}

// Generate slug from title (using function from functions.php)
$slug = generateSlug($title);

// Handle file upload
$featured_image = null;
$existing_image = null;

// If editing, get existing image
if ($post_id > 0) {
    $stmt = $conn->prepare("SELECT featured_image FROM blog_posts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_post = $result->fetch_assoc();
    if ($existing_post) {
        $existing_image = $existing_post['featured_image'];
    }
}

if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/blog/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_info = pathinfo($_FILES['featured_image']['name']);
    $extension = strtolower($file_info['extension']);
    
    // Validate file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($extension, $allowed_types)) {
        $_SESSION['error'] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        header("Location: " . ($post_id > 0 ? "edit.php?id=$post_id" : "create.php"));
        exit();
    }
    
    // Validate file size (5MB max)
    if ($_FILES['featured_image']['size'] > 5 * 1024 * 1024) {
        $_SESSION['error'] = "File too large. Maximum size is 5MB.";
        header("Location: " . ($post_id > 0 ? "edit.php?id=$post_id" : "create.php"));
        exit();
    }
    
    // Generate unique filename
    $filename = uniqid('blog_') . '_' . time() . '.' . $extension;
    $upload_path = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
        $featured_image = 'uploads/blog/' . $filename;
        
        // Delete old image if it exists and we're updating
        if ($existing_image && file_exists('../' . $existing_image)) {
            unlink('../' . $existing_image);
        }
    }
} else {
    // Keep existing image if editing and no new image uploaded
    $featured_image = $existing_image;
}

// Determine status based on action
$status = ($action === 'draft') ? 'draft' : 'pending';

// Check if editing or creating new post
if ($post_id > 0) {
    // Update existing post
    // Verify ownership
    $verify_stmt = $conn->prepare("SELECT id FROM blog_posts WHERE id = ? AND user_id = ?");
    $verify_stmt->bind_param("ii", $post_id, $user_id);
    $verify_stmt->execute();
    if ($verify_stmt->get_result()->num_rows === 0) {
        $_SESSION['error'] = "You don't have permission to edit this post.";
        header("Location: my-posts.php");
        exit();
    }
    
    // Make sure slug is unique (excluding current post)
    $slug_check = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ? AND id != ?");
    $slug_check->bind_param("si", $slug, $post_id);
    $slug_check->execute();
    if ($slug_check->get_result()->num_rows > 0) {
        $slug = $slug . '-' . time();
    }
    
    if ($featured_image) {
        $update_stmt = $conn->prepare("UPDATE blog_posts 
                                       SET title = ?, slug = ?, excerpt = ?, content = ?, 
                                           featured_image = ?, status = ?, updated_at = NOW()
                                       WHERE id = ? AND user_id = ?");
        $update_stmt->bind_param("ssssssii", $title, $slug, $excerpt, $content, 
                                 $featured_image, $status, $post_id, $user_id);
    } else {
        $update_stmt = $conn->prepare("UPDATE blog_posts 
                                       SET title = ?, slug = ?, excerpt = ?, content = ?, 
                                           status = ?, updated_at = NOW()
                                       WHERE id = ? AND user_id = ?");
        $update_stmt->bind_param("sssssii", $title, $slug, $excerpt, $content, 
                                 $status, $post_id, $user_id);
    }
    
    if ($update_stmt->execute()) {
        if ($status === 'pending') {
            // Send notification to user
            createNotification(
                $user_id,
                'blog_submitted',
                'Blog Post Submitted',
                "Your post '$title' has been submitted for review and will be published once approved.",
                'blog/my-posts.php'
            );
            
            // Notify all admins
            $admin_result = $conn->query("SELECT id, email, first_name, last_name FROM users WHERE role = 'admin'");
            while ($admin = $admin_result->fetch_assoc()) {
                createNotification(
                    $admin['id'],
                    'blog_pending_approval',
                    'New Blog Post Pending',
                    "A blog post '$title' has been submitted and needs approval.",
                    'admin/blog-approval.php'
                );
                
                $user_details = $conn->query("SELECT first_name, last_name, email FROM users WHERE id = $user_id")->fetch_assoc();
                sendTemplatedEmail(
                    $admin['email'],
                    $admin['first_name'] . ' ' . $admin['last_name'],
                    'New Blog Post for Review',
                    "A new blog post '$title' by {$user_details['first_name']} {$user_details['last_name']} requires your review.",
                    'Review Post',
                    SITE_URL . 'admin/blog-approval.php'
                );
            }
            
            $_SESSION['success'] = "Post updated and submitted for approval!";
        } else {
            $_SESSION['success'] = "Draft saved successfully!";
        }
        header("Location: my-posts.php");
    } else {
        $_SESSION['error'] = "Error updating post: " . $conn->error;
        header("Location: edit.php?id=$post_id");
    }
    
} else {
    // Create new post
    // Make sure slug is unique
    $slug_check = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ?");
    $slug_check->bind_param("s", $slug);
    $slug_check->execute();
    if ($slug_check->get_result()->num_rows > 0) {
        $slug = $slug . '-' . time();
    }
    
    $insert_stmt = $conn->prepare("INSERT INTO blog_posts 
                                   (user_id, title, slug, excerpt, content, featured_image, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$insert_stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    $insert_stmt->bind_param("issssss", $user_id, $title, $slug, $excerpt, $content, 
                             $featured_image, $status);
    
    if ($insert_stmt->execute()) {
        $new_post_id = $conn->insert_id;
        
        if ($status === 'pending') {
            // Send notification to user
            createNotification(
                $user_id,
                'blog_submitted',
                'Blog Post Submitted',
                "Your post '$title' has been submitted for review and will be published once approved.",
                'blog/my-posts.php'
            );
            
            // Get user details
            $user_details = $conn->query("SELECT first_name, last_name, email FROM users WHERE id = $user_id")->fetch_assoc();
            
            // Send confirmation email to user
            sendTemplatedEmail(
                $user_details['email'],
                $user_details['first_name'] . ' ' . $user_details['last_name'],
                'Blog Post Submitted for Review',
                "Your blog post '$title' has been successfully submitted and is now pending admin review. You'll be notified once it's published.",
                'View My Posts',
                SITE_URL . 'blog/my-posts.php'
            );
            
            // Notify all admins
            $admin_result = $conn->query("SELECT id, email, first_name, last_name FROM users WHERE role = 'admin'");
            while ($admin = $admin_result->fetch_assoc()) {
                createNotification(
                    $admin['id'],
                    'blog_pending_approval',
                    'New Blog Post Pending',
                    "A blog post '$title' by {$user_details['first_name']} {$user_details['last_name']} has been submitted and needs approval.",
                    'admin/blog-approval.php'
                );
                
                sendTemplatedEmail(
                    $admin['email'],
                    $admin['first_name'] . ' ' . $admin['last_name'],
                    'New Blog Post for Review',
                    "A new blog post '$title' by {$user_details['first_name']} {$user_details['last_name']} requires your review.",
                    'Review Post',
                    SITE_URL . 'admin/blog-approval.php'
                );
            }
            
            $_SESSION['success'] = "Post submitted for approval! You'll be notified once it's reviewed.";
        } else {
            $_SESSION['success'] = "Draft saved successfully! You can edit and submit it later.";
        }
        header("Location: my-posts.php");
    } else {
        $_SESSION['error'] = "Error creating post: " . $conn->error;
        header("Location: create.php");
    }
}

exit();
?>
