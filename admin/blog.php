<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$user = getCurrentUser();
$success = '';
$error = '';

// Handle article save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_article'])) {
    $article_id = isset($_POST['article_id']) ? (int)$_POST['article_id'] : 0;
    $title = sanitize($_POST['title']);
    $content = $_POST['content']; // Allow HTML
    $category_id = (int)$_POST['category_id'];
    $status = sanitize($_POST['status']);
    $tags = isset($_POST['tags']) ? json_encode(array_map('trim', explode(',', $_POST['tags']))) : '[]';
    
    $slug = generateSlug($title);
    
    // Handle featured image
    $featured_image = '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $filename = 'blog_' . time() . '.' . $ext;
            $dest = UPLOAD_DIR . '/blog/' . $filename;
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $dest)) {
                $featured_image = '/uploads/blog/' . $filename;
            }
        }
    }
    
    if ($article_id > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE blog_articles SET title = ?, slug = ?, content = ?, category_id = ?, status = ?, tags = ?" .
                               ($featured_image ? ", featured_image = ?" : "") .
                               ", updated_at = NOW()" .
                               ($status === 'published' ? ", published_at = NOW()" : "") .
                               " WHERE id = ?");
        if ($featured_image) {
            $stmt->bind_param("sssisssi", $title, $slug, $content, $category_id, $status, $tags, $featured_image, $article_id);
        } else {
            $stmt->bind_param("ssisssi", $title, $slug, $content, $category_id, $status, $tags, $article_id);
        }
        $stmt->execute();
        $success = 'Article updated';
    } else {
        // Create
        $stmt = $conn->prepare("INSERT INTO blog_articles (title, slug, content, category_id, author_id, status, tags, featured_image, published_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, " . ($status === 'published' ? 'NOW()' : 'NULL') . ")");
        $stmt->bind_param("ssiissss", $title, $slug, $content, $category_id, $user['id'], $status, $tags, $featured_image);
        $stmt->execute();
        $success = 'Article created';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $article_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM blog_articles WHERE id = $article_id");
    header('Location: blog.php');
    exit;
}

// Get articles
$articles = $conn->query("SELECT a.*, 
                          CONCAT(u.first_name, ' ', u.last_name) as author_name, 
                          c.name as category_name 
                          FROM blog_articles a 
                          JOIN users u ON a.author_id = u.id 
                          LEFT JOIN categories c ON a.category_id = c.id 
                          ORDER BY a.created_at DESC");

if (!$articles) {
    die("Query failed: " . $conn->error);
}

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Get TinyMCE API key from settings
$tinymce_key = getSetting('tinymce_api_key', 'no-api-key');

$page_title = 'Blog Management';
include '../header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-blog me-2"></i>Blog Management</h2>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#articleModal" onclick="resetForm()">
                <i class="fas fa-plus me-2"></i>New Article
            </button>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Views</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($article = $articles->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($article['title']) ?></td>
                                <td><?= htmlspecialchars($article['category_name'] ?? 'Uncategorized') ?></td>
                                <td><?= htmlspecialchars($article['author_name']) ?></td>
                                <td><?= number_format($article['views']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $article['status'] === 'published' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($article['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($article['created_at'])) ?></td>
                                <td>
                                    <a href="../blog/view.php?slug=<?= $article['slug'] ?>" class="btn btn-sm btn-outline-info" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-primary" onclick='editArticle(<?= json_encode($article) ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?= $article['id'] ?>" class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Delete this article?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Article Modal -->
<div class="modal fade" id="articleModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">New Article</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="article_id" id="article_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="category_id" class="form-select">
                                <option value="">None</option>
                                <?php $categories->data_seek(0); while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Featured Image</label>
                        <input type="file" name="featured_image" class="form-control" accept="image/*">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Content *</label>
                        <textarea name="content" id="content" class="form-control" rows="10" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tags (comma-separated)</label>
                        <input type="text" name="tags" id="tags" class="form-control" placeholder="tag1, tag2, tag3">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_article" class="btn btn-primary">Save Article</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TinyMCE Script -->
<script src="https://cdn.tiny.cloud/1/<?php echo htmlspecialchars($tinymce_key); ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
let editorInitialized = false;

// Initialize TinyMCE
function initTinyMCE() {
    if (editorInitialized) {
        return;
    }
    
    tinymce.init({
        selector: '#content',
        height: 400,
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
        setup: function(editor) {
            editorInitialized = true;
        }
    });
}

// Initialize when modal is shown
document.getElementById('articleModal').addEventListener('shown.bs.modal', function() {
    initTinyMCE();
});

function resetForm() {
    document.getElementById('article_id').value = '';
    document.getElementById('modalTitle').textContent = 'New Article';
    document.querySelector('form').reset();
    
    // Reset TinyMCE content
    if (tinymce.get('content')) {
        tinymce.get('content').setContent('');
    }
}

function editArticle(article) {
    document.getElementById('article_id').value = article.id;
    document.getElementById('title').value = article.title;
    document.getElementById('category_id').value = article.category_id || '';
    document.getElementById('status').value = article.status;
    document.getElementById('tags').value = article.tags ? JSON.parse(article.tags).join(', ') : '';
    document.getElementById('modalTitle').textContent = 'Edit Article';
    
    // Set content in TinyMCE after editor is ready
    const setContent = () => {
        if (tinymce.get('content')) {
            tinymce.get('content').setContent(article.content || '');
        } else {
            // If editor not ready, try again after short delay
            setTimeout(setContent, 100);
        }
    };
    
    // Show modal first
    const modal = new bootstrap.Modal(document.getElementById('articleModal'));
    modal.show();
    
    // Set content after modal is shown and editor initialized
    setTimeout(setContent, 200);
}
</script>

<?php include '../footer.php'; ?>
