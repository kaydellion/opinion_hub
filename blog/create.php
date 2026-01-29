<?php
require_once '../connect.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "signin.php");
    exit;
}

$page_title = "Create Blog Post";
include_once '../header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <h2 class="mb-4"><i class="fas fa-pen"></i> Create New Blog Post</h2>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Your post will be submitted for admin approval before it goes live.
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form action="submit-post.php" method="POST" enctype="multipart/form-data">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold">Post Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required 
                                   placeholder="Enter an engaging title...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="excerpt" class="form-label fw-bold">Short Excerpt</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="2" 
                                      placeholder="Brief summary of your post (optional)"></textarea>
                            <div class="form-text">This appears on the blog listing page</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label fw-bold">Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="15" 
                                      placeholder="Write your post content here..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="featured_image" class="form-label fw-bold">Featured Image</label>
                            <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
                            <div class="form-text">Recommended size: 1200x630px (JPG, PNG, max 5MB)</div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Submit for Approval
                            </button>
                            <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">
                                <i class="fas fa-save"></i> Save as Draft
                            </button>
                            <a href="<?= SITE_URL ?>blog/my-posts.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                        
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include_once '../footer.php'; ?>
