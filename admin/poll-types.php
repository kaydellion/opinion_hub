<?php
require_once '../connect.php';
require_once '../functions.php';

// Check if user is admin
requireRole(['admin']);

$page_title = "Manage Poll Types";
include_once '../header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $status = sanitize($_POST['status']);
                
                if (!empty($name)) {
                    $check = $conn->query("SELECT id FROM poll_types WHERE name = '$name'");
                    if ($check && $check->num_rows === 0) {
                        $conn->query("INSERT INTO poll_types (name, description, status, created_at) 
                                   VALUES ('$name', '$description', '$status', NOW())");
                        $_SESSION['success'] = "Poll type added successfully!";
                    } else {
                        $_SESSION['errors'] = ["Poll type with this name already exists!"];
                    }
                } else {
                    $_SESSION['errors'] = ["Poll type name is required!"];
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $status = sanitize($_POST['status']);
                
                if (!empty($name) && $id > 0) {
                    $check = $conn->query("SELECT id FROM poll_types WHERE name = '$name' AND id != $id");
                    if ($check && $check->num_rows === 0) {
                        $conn->query("UPDATE poll_types 
                                   SET name = '$name', description = '$description', status = '$status', updated_at = NOW() 
                                   WHERE id = $id");
                        $_SESSION['success'] = "Poll type updated successfully!";
                    } else {
                        $_SESSION['errors'] = ["Poll type with this name already exists!"];
                    }
                } else {
                    $_SESSION['errors'] = ["Invalid data provided!"];
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                if ($id > 0) {
                    // Check if any polls are using this type
                    $check_polls = $conn->query("SELECT COUNT(*) as count FROM polls WHERE poll_type_id = $id");
                    $poll_count = $check_polls ? $check_polls->fetch_assoc()['count'] : 0;
                    
                    if ($poll_count === 0) {
                        $conn->query("DELETE FROM poll_types WHERE id = $id");
                        $_SESSION['success'] = "Poll type deleted successfully!";
                    } else {
                        $_SESSION['errors'] = ["Cannot delete poll type - $poll_count polls are using it!"];
                    }
                } else {
                    $_SESSION['errors'] = ["Invalid poll type ID!"];
                }
                break;
                
            case 'toggle_status':
                $id = (int)$_POST['id'];
                if ($id > 0) {
                    $current = $conn->query("SELECT status FROM poll_types WHERE id = $id")->fetch_assoc();
                    if ($current) {
                        $new_status = ($current['status'] === 'active') ? 'inactive' : 'active';
                        $conn->query("UPDATE poll_types SET status = '$new_status', updated_at = NOW() WHERE id = $id");
                        $_SESSION['success'] = "Poll type status updated!";
                    }
                }
                break;
        }
        
        header("Location: poll-types.php");
        exit;
    }
}

// Check if poll_types table exists, create if not
$table_check = $conn->query("SHOW TABLES LIKE 'poll_types'");
if (!$table_check || $table_check->num_rows === 0) {
    // Create the table
    $create_table = "CREATE TABLE poll_types (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table)) {
        // Insert default poll types
        $default_types = [
            ['Opinion Poll', 'General opinion polls on various topics', 'active'],
            ['Survey', 'Detailed surveys with multiple questions', 'active'],
            ['Market Research', 'Commercial market research polls', 'active'],
            ['Political Poll', 'Political opinion and voting polls', 'active'],
            ['Social Research', 'Academic and social research polls', 'active']
        ];
        
        foreach ($default_types as $type) {
            $conn->query("INSERT INTO poll_types (name, description, status) 
                       VALUES ('{$type[0]}', '{$type[1]}', '{$type[2]}')");
        }
        
        $_SESSION['success'] = "Poll types table created with default types!";
        header("Location: poll-types.php");
        exit;
    }
} else {
    // Update existing table if needed (add columns if missing)
    $conn->query("ALTER TABLE poll_types ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active'");
    $conn->query("ALTER TABLE poll_types ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP");
}

// Get poll types
$poll_types = $conn->query("SELECT * FROM poll_types ORDER BY name ASC");

$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors']);
unset($_SESSION['success']);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-list me-2"></i>Manage Poll Types</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPollTypeModal">
                    <i class="fas fa-plus me-2"></i>Add New Type
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
                    <?php if ($poll_types && $poll_types->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($type = $poll_types->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $type['id'] ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($type['name']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars(substr($type['description'], 0, 100)) ?>...</td>
                                            <td>
                                                <span class="badge bg-<?= $type['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($type['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($type['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="editPollType(<?= $type['id'] ?>, '<?= htmlspecialchars($type['name']) ?>', '<?= htmlspecialchars($type['description']) ?>', '<?= $type['status'] ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="id" value="<?= $type['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-<?= $type['status'] === 'active' ? 'warning' : 'success' ?>">
                                                            <i class="fas fa-<?= $type['status'] === 'active' ? 'pause' : 'play' ?>"></i>
                                                        </button>
                                                    </form>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="deletePollType(<?= $type['id'] ?>, '<?= htmlspecialchars($type['name']) ?>')">
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
                            <i class="fas fa-list fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Poll Types Found</h5>
                            <p class="text-muted">Get started by adding your first poll type.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPollTypeModal">
                                <i class="fas fa-plus me-2"></i>Add First Poll Type
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Poll Type Modal -->
<div class="modal fade" id="addPollTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Poll Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
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
                    <button type="submit" class="btn btn-primary">Add Poll Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Poll Type Modal -->
<div class="modal fade" id="editPollTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Poll Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
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
                    <button type="submit" class="btn btn-primary">Update Poll Type</button>
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
                <h5 class="modal-title">Delete Poll Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the poll type "<strong id="deleteTypeName"></strong>"?</p>
                <p class="text-warning"><i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone.</p>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Poll Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editPollType(id, name, description, status) {
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editDescription').value = description;
    document.getElementById('editStatus').value = status;
    new bootstrap.Modal(document.getElementById('editPollTypeModal')).show();
}

function deletePollType(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteTypeName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php include_once '../footer.php'; ?>
