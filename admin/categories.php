<?php
require_once '../connect.php';
require_once '../functions.php';

// Check if user is admin
requireRole(['admin']);

// Handle form submissions BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $status = sanitize($_POST['status'] ?? 'active');
                
                if (!empty($name)) {
                    $check = $conn->query("SELECT id FROM categories WHERE name = '$name'");
                    if ($check && $check->num_rows === 0) {
                        // Check if status column exists
                        $col_check = $conn->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'categories' AND COLUMN_NAME = 'status'");
                        if ($col_check && $col_check->num_rows > 0) {
                            $conn->query("INSERT INTO categories (name, description, status, created_at) 
                                       VALUES ('$name', '$description', '$status', NOW())");
                        } else {
                            $conn->query("INSERT INTO categories (name, description, created_at) 
                                       VALUES ('$name', '$description', NOW())");
                        }
                        $_SESSION['success'] = "Category added successfully!";
                    } else {
                        $_SESSION['errors'] = ["Category with this name already exists!"];
                    }
                } else {
                    $_SESSION['errors'] = ["Category name is required!"];
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $status = sanitize($_POST['status'] ?? 'active');
                
                if (!empty($name) && $id > 0) {
                    $check = $conn->query("SELECT id FROM categories WHERE name = '$name' AND id != $id");
                    if ($check && $check->num_rows === 0) {
                        // Check which columns exist
                        $has_status = $conn->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'categories' AND COLUMN_NAME = 'status'");
                        $has_updated_at = $conn->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'categories' AND COLUMN_NAME = 'updated_at'");
                        
                        $has_status_col = $has_status && $has_status->num_rows > 0;
                        $has_updated_col = $has_updated_at && $has_updated_at->num_rows > 0;
                        
                        $update_fields = "name = '$name', description = '$description'";
                        if ($has_status_col) $update_fields .= ", status = '$status'";
                        if ($has_updated_col) $update_fields .= ", updated_at = NOW()";
                        
                        $conn->query("UPDATE categories SET $update_fields WHERE id = $id");
                        $_SESSION['success'] = "Category updated successfully!";
                    } else {
                        $_SESSION['errors'] = ["Category with this name already exists!"];
                    }
                } else {
                    $_SESSION['errors'] = ["Invalid data provided!"];
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                if ($id > 0) {
                    // Check if any polls are using this category
                    $check_polls = $conn->query("SELECT COUNT(*) as count FROM polls WHERE category_id = $id");
                    $poll_count = $check_polls ? $check_polls->fetch_assoc()['count'] : 0;
                    
                    if ($poll_count === 0) {
                        $conn->query("DELETE FROM categories WHERE id = $id");
                        $_SESSION['success'] = "Category deleted successfully!";
                    } else {
                        $_SESSION['errors'] = ["Cannot delete category - $poll_count polls are using it!"];
                    }
                } else {
                    $_SESSION['errors'] = ["Invalid category ID!"];
                }
                break;
                
            case 'toggle_status':
                $id = (int)$_POST['id'];
                if ($id > 0) {
                    $current = $conn->query("SELECT status FROM categories WHERE id = $id")->fetch_assoc();
                    if ($current) {
                        $current_status = $current['status'] ?? 'active';
                        $new_status = ($current_status === 'active') ? 'inactive' : 'active';
                        $conn->query("UPDATE categories SET status = '$new_status', updated_at = NOW() WHERE id = $id");
                        $_SESSION['success'] = "Category status updated!";
                    }
                }
                break;
        }
        
        header("Location: categories.php");
        exit;
    }
}

$page_title = "Manage Categories";
include_once '../header.php';

// Check if categories table exists, create if not
$table_check = $conn->query("SHOW TABLES LIKE 'categories'");
if (!$table_check || $table_check->num_rows === 0) {
    // Create the table
    $create_table = "CREATE TABLE categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table)) {
        // Insert default categories
        $default_categories = [
            ['Politics', 'Political opinions, governance, and elections', 'active'],
            ['Technology', 'Tech trends, gadgets, software, and innovation', 'active'],
            ['Business', 'Business trends, entrepreneurship, and economy', 'active'],
            ['Entertainment', 'Movies, music, celebrities, and entertainment news', 'active'],
            ['Sports', 'Sports news, events, and athletic competitions', 'active'],
            ['Health', 'Health, wellness, and medical topics', 'active'],
            ['Education', 'Education, learning, and academic topics', 'active'],
            ['Lifestyle', 'Lifestyle, fashion, and personal interests', 'active'],
            ['Science', 'Scientific discoveries and research', 'active'],
            ['Social Issues', 'Social topics and community issues', 'active']
        ];
        
        foreach ($default_categories as $category) {
            $conn->query("INSERT INTO categories (name, description, status) 
                       VALUES ('{$category[0]}', '{$category[1]}', '{$category[2]}')");
        }
        
        $_SESSION['success'] = "Categories table created with default categories!";
        header("Location: categories.php");
        exit;
    }
} else {
    // Update existing categories that don't have a status (if the column exists)
    $check_col = $conn->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'categories' AND COLUMN_NAME = 'status'");
    if ($check_col && $check_col->num_rows > 0) {
        $conn->query("UPDATE categories SET status = 'active' WHERE status IS NULL OR status = ''");
    }
}

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors']);
unset($_SESSION['success']);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-tags me-2"></i>Manage Categories</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus me-2"></i>Add New Category
                </button>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php foreach ($errors as $error): ?>
                        <?= $error ?><br>
                    <?php endforeach; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <?php if ($categories && $categories->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Polls Count</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($category = $categories->fetch_assoc()): ?>
                                        <?php 
                                        // Get polls count for this category
                                        $polls_count = 0;
                                        $polls_query = $conn->query("SELECT COUNT(*) as count FROM polls WHERE category_id = " . $category['id']);
                                        if ($polls_query) {
                                            $polls_count = $polls_query->fetch_assoc()['count'];
                                        }
                                        ?>
                                        <tr>
                                            <td><?= $category['id'] ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($category['name']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars(substr($category['description'], 0, 100)) ?>...</td>
                                            <td>
                                                <span class="badge bg-<?= ($category['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($category['status'] ?? 'active') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $polls_count ?></span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($category['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="editCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>', '<?= htmlspecialchars($category['description']) ?>', '<?= $category['status'] ?? 'active' ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-<?= ($category['status'] ?? 'active') === 'active' ? 'warning' : 'success' ?>">
                                                            <i class="fas fa-<?= ($category['status'] ?? 'active') === 'active' ? 'pause' : 'play' ?>"></i>
                                                        </button>
                                                    </form>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="deleteCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>', <?= $polls_count ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Categories Found</h5>
                            <p class="text-muted">Get started by adding your first category.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus me-2"></i>Add First Category
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="editName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="editDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="editStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the category "<strong id="deleteCategoryName"></strong>"?</p>
                <p id="deleteWarning" class="text-warning"><i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone.</p>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCategory(id, name, description, status) {
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editDescription').value = description;
    document.getElementById('editStatus').value = status;
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}

function deleteCategory(id, name, pollsCount) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteCategoryName').textContent = name;
    
    const warning = document.getElementById('deleteWarning');
    if (pollsCount > 0) {
        warning.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i><strong>Warning:</strong> ' + pollsCount + ' polls are using this category. You cannot delete it until all polls are moved to another category.';
        warning.className = 'text-danger';
        document.querySelector('#deleteModal .btn-danger').disabled = true;
    } else {
        warning.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone.';
        warning.className = 'text-warning';
        document.querySelector('#deleteModal .btn-danger').disabled = false;
    }
    
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php include_once '../footer.php'; ?>
