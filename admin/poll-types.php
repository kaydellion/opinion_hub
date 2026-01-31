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
                $status = sanitize($_POST['status']);
                $category = sanitize($_POST['category'] ?? 'General');
                
                if (!empty($name)) {
                    $check = $conn->query("SELECT id FROM poll_types WHERE name = '$name'");
                    if ($check && $check->num_rows === 0) {
                        $conn->query("INSERT INTO poll_types (name, description, category, status, created_at) 
                                   VALUES ('$name', '$description', '$category', '$status', NOW())");
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
                $category = sanitize($_POST['category'] ?? 'General');
                
                if (!empty($name) && $id > 0) {
                    $check = $conn->query("SELECT id FROM poll_types WHERE name = '$name' AND id != $id");
                    if ($check && $check->num_rows === 0) {
                        $conn->query("UPDATE poll_types 
                                   SET name = '$name', description = '$description', category = '$category', status = '$status', updated_at = NOW() 
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
                    // Check if any polls are using this type (poll_type is a string field)
                    $type_name = $conn->query("SELECT name FROM poll_types WHERE id = $id")->fetch_assoc()['name'] ?? '';
                    $check_polls = $conn->query("SELECT COUNT(*) as count FROM polls WHERE poll_type = '$type_name'");
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

$page_title = "Manage Poll Types";
include_once '../header.php';

// Check if poll_types table exists, create if not
$table_check = $conn->query("SHOW TABLES LIKE 'poll_types'");
if (!$table_check || $table_check->num_rows === 0) {
    // Create the table
    $create_table = "CREATE TABLE poll_types (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        category VARCHAR(100) DEFAULT 'General',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table)) {
        // Insert comprehensive poll types with categories (from create-poll.php)
        $default_types = [
            // Political Polling
            ['Approval Poll', 'Measure approval ratings for political figures', 'Political Polling', 'active'],
            ['Favourability Poll', 'Assess public perception and favorability', 'Political Polling', 'active'],
            ['Head-to-Head Poll', 'Compare support between candidates or parties', 'Political Polling', 'active'],
            ['Issue Poll', 'Gauge public opinion on specific policy issues', 'Political Polling', 'active'],
            ['Benchmark Poll', 'Establish baseline measurements for tracking', 'Political Polling', 'active'],
            ['Tracking Poll', 'Monitor changes in opinion over time', 'Political Polling', 'active'],
            ['Exit Poll', 'Survey voters as they leave polling locations', 'Political Polling', 'active'],
            ['Push Poll', 'Polls designed to influence voter opinion', 'Political Polling', 'active'],
            ['Deliberative Poll', 'In-depth polls with informed deliberation', 'Political Polling', 'active'],
            ['Flash Poll', 'Quick polls capturing immediate reactions', 'Political Polling', 'active'],
            ['Opinion Poll', 'General opinion polls on various topics', 'Political Polling', 'active'],
            ['Straw Poll', 'Informal preliminary polls', 'Political Polling', 'active'],
            ['Referendum Poll', 'Polls on referendum or ballot measures', 'Political Polling', 'active'],
            ['Omnibus Poll', 'Multiple questions from different sponsors', 'Political Polling', 'active'],
            ['Sentiment Poll', 'Measure public sentiment and emotions', 'Political Polling', 'active'],
            ['Ballot Test Poll', 'Test ballot designs and wording', 'Political Polling', 'active'],
            ['Engagement Poll', 'Measure public engagement levels', 'Political Polling', 'active'],
            ['Satisfaction Poll', 'Measure satisfaction with policies', 'Political Polling', 'active'],
            ['Electability Poll', 'Assess candidate viability', 'Political Polling', 'active'],
            ['Priority Poll', 'Identify voter priorities and concerns', 'Political Polling', 'active'],
            ['Awareness Poll', 'Measure awareness of issues or campaigns', 'Political Polling', 'active'],
            // Business & Market Research
            ['Customer Satisfaction Poll', 'Measure customer satisfaction levels', 'Business & Market Research', 'active'],
            ['Brand Awareness Poll', 'Assess brand recognition and recall', 'Business & Market Research', 'active'],
            ['Market Segmentation Poll', 'Identify and profile market segments', 'Business & Market Research', 'active'],
            ['Product Development Poll', 'Gather input for product development', 'Business & Market Research', 'active'],
            ['Pricing Poll', 'Test pricing strategies and acceptance', 'Business & Market Research', 'active'],
            ['Advertising Effectiveness Poll', 'Measure advertising impact', 'Business & Market Research', 'active'],
            ['Employee Satisfaction Poll', 'Assess employee engagement and satisfaction', 'Business & Market Research', 'active'],
            ['Competitor Analysis Poll', 'Analyze competitive landscape perception', 'Business & Market Research', 'active'],
            ['Purchase Intent Poll', 'Measure likelihood to purchase', 'Business & Market Research', 'active'],
            ['Market Trend Poll', 'Identify emerging market trends', 'Business & Market Research', 'active'],
            ['Customer Experience Poll', 'Evaluate customer experience quality', 'Business & Market Research', 'active'],
            ['Product Usage Poll', 'Understand product usage patterns', 'Business & Market Research', 'active'],
            ['Demand Forecasting Poll', 'Project future demand', 'Business & Market Research', 'active'],
            ['Concept Testing Poll', 'Test new product or marketing concepts', 'Business & Market Research', 'active'],
            ['Brand Loyalty Poll', 'Measure brand loyalty and retention', 'Business & Market Research', 'active'],
            ['Economic Outlook Poll', 'Assess economic sentiment', 'Business & Market Research', 'active'],
            ['Crisis Management Poll', 'Measure response to crises', 'Business & Market Research', 'active'],
            // Social Research
            ['Community Feedback Poll', 'Gather community input and feedback', 'Social Research', 'active'],
            ['Cross-Sectional Poll', 'Snapshot of population at one point in time', 'Social Research', 'active'],
            ['Longitudinal Poll', 'Track same subjects over time', 'Social Research', 'active'],
            ['Attitudinal Poll', 'Measure attitudes and beliefs', 'Social Research', 'active'],
            ['Behavioural Poll', 'Assess behaviors and habits', 'Social Research', 'active'],
            ['Demographic Poll', 'Analyze demographic characteristics', 'Social Research', 'active'],
            ['Social Network Poll', 'Study social network patterns', 'Social Research', 'active'],
            ['Experimental Poll', 'Poll with experimental design', 'Social Research', 'active'],
            ['Qualitative Poll', 'In-depth qualitative research', 'Social Research', 'active'],
            ['Cultural Poll', 'Explore cultural attitudes and norms', 'Social Research', 'active'],
            ['Social Mobility Poll', 'Assess social mobility perceptions', 'Social Research', 'active'],
            ['Policy Impact Poll', 'Measure impact of policies', 'Social Research', 'active'],
            ['Social Norms Poll', 'Understand social norms and expectations', 'Social Research', 'active'],
            ['Life Satisfaction Poll', 'Measure life satisfaction and well-being', 'Social Research', 'active'],
            // Environment
            ['Climate Change Poll', 'Assess views on climate change', 'Environment', 'active'],
            ['Environmental Awareness Poll', 'Measure environmental awareness', 'Environment', 'active'],
            ['Sustainability Poll', 'Gauge support for sustainability', 'Environment', 'active'],
            ['Conservation Poll', 'Measure conservation attitudes', 'Environment', 'active'],
            // Survey
            ['Survey', 'General surveys with multiple questions', 'General', 'active']
        ];
        
        foreach ($default_types as $type) {
            $conn->query("INSERT INTO poll_types (name, description, category, status) 
                       VALUES ('{$type[0]}', '{$type[1]}', '{$type[2]}', '{$type[3]}')");
        }
    }
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
