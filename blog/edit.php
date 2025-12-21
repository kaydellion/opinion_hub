<?php
session_start();
require_once '../connect.php';
require_once '../functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($post_id === 0) {
    $_SESSION['error'] = "Invalid post ID.";
    header("Location: my-posts.php");
    exit();
}

// Get post and verify ownership
$stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ? AND user_id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    $_SESSION['error'] = "Post not found or you don't have permission to edit it.";
    header("Location: my-posts.php");
    exit();
}

// Only allow editing of drafts and rejected posts
if (!in_array($post['status'], ['draft', 'rejected'])) {
    $_SESSION['error'] = "You can only edit draft or rejected posts.";
    header("Location: my-posts.php");
    exit();
}

$page_title = "Edit: " . $post['title'];

// Get TinyMCE API key from settings
$tinymce_key = getSetting('tinymce_api_key', 'no-api-key');

include_once '../header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-edit me-2"></i>Edit Blog Post</h2>
                <a href="my-posts.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to My Posts
                </a>
            </div>

            <?php if ($post['status'] === 'rejected' && $post['rejection_reason']): ?>
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Rejection Reason:</h5>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($post['rejection_reason'])); ?></p>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form action="submit-post.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Post Title *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title" 
                                   name="title" 
                                   value="<?php echo htmlspecialchars($post['title']); ?>"
                                   required 
                                   maxlength="255"
                                   placeholder="Enter an engaging title for your post">
                            <div class="form-text">A clear, descriptive title helps readers find your content.</div>
                        </div>

                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Excerpt</label>
                            <textarea class="form-control" 
                                      id="excerpt" 
                                      name="excerpt" 
                                      rows="2"
                                      maxlength="500"
                                      placeholder="Brief summary of your post (optional)"><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
                            <div class="form-text">A short summary that appears in post previews.</div>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Content *</label>
                            <textarea class="form-control" 
                                      id="content" 
                                      name="content" 
                                      rows="15"
                                      required><?php echo htmlspecialchars($post['content']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="featured_image" class="form-label">Featured Image</label>
                            
                            <?php if ($post['featured_image']): ?>
                                <div class="mb-2">
                                    <img src="<?php echo SITE_URL . $post['featured_image']; ?>" 
                                         alt="Current featured image" 
                                         class="img-fluid rounded"
                                         style="max-width: 300px;">
                                    <div class="form-text">Current featured image (upload a new one to replace it)</div>
                                </div>
                            <?php endif; ?>
                            
                            <input type="file" 
                                   class="form-control" 
                                   id="featured_image" 
                                   name="featured_image"
                                   accept="image/*">
                            <div class="form-text">Upload a new featured image to replace the current one (JPG, PNG, or GIF - max 5MB)</div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <div>
                                <button type="submit" name="action" value="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-paper-plane me-2"></i>Submit for Approval
                                </button>
                                <button type="submit" name="action" value="draft" class="btn btn-secondary">
                                    <i class="fas fa-save me-2"></i>Save as Draft
                                </button>
                            </div>
                            <a href="my-posts.php" class="btn btn-outline-danger">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TinyMCE Script -->
<script src="https://cdn.tiny.cloud/1/<?php echo htmlspecialchars($tinymce_key); ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#content',
        height: 500,
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic forecolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | link image | code | help',
        content_style: 'body { font-family: Poppins, sans-serif; font-size: 14px }',
        image_advtab: true,
        image_title: true,
        automatic_uploads: true,
        file_picker_types: 'image',
        images_upload_handler: function (blobInfo, success, failure) {
            // Convert to base64 for now
            var reader = new FileReader();
            reader.onload = function(e) {
                success(e.target.result);
            };
            reader.readAsDataURL(blobInfo.blob());
        }
    });
</script>

<?php include_once '../footer.php'; ?>
