<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$user = getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'delete_poll':
            handleDeletePoll();
            break;
        case 'change_status':
            handleChangePollStatus();
            break;
        case 'update_poll':
            handleUpdatePoll();
            break;
    }
}

// Get filters
$status_filter = $_POST['status_filter'] ?? $_GET['status'] ?? 'all';
$category_filter = $_POST['category_filter'] ?? $_GET['category'] ?? 'all';
$search = $_POST['search'] ?? $_GET['search'] ?? '';

// Build query - even simpler to debug
$query = "
    SELECT p.id, p.title, p.description, p.disclaimer, p.status, p.poll_type, p.created_at, p.category_id,
           p.created_by, u.first_name, u.last_name, c.name as category_name
    FROM polls p
    LEFT JOIN users u ON p.created_by = u.id
    LEFT JOIN categories c ON p.category_id = c.id
";

// Add WHERE conditions
$where_conditions = [];
$params = [];
$types = '';

if ($status_filter !== 'all') {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($category_filter !== 'all') {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR p.description LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY p.created_at DESC";

// Execute main query
$polls_result = null;

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $polls_result = $stmt->get_result();
    } else {
        // Fallback to direct query
        $fallback_query = $query;
        foreach ($params as $index => $param) {
            $replacement = is_numeric($param) ? $param : "'" . $conn->real_escape_string($param) . "'";
            $fallback_query = preg_replace('/\?/', $replacement, $fallback_query, 1);
        }
        $polls_result = $conn->query($fallback_query);
    }
} else {
    $polls_result = $conn->query($query);
}

// Get categories for filter dropdown
$categories_query = $conn->query("SELECT id, name FROM categories ORDER BY name");
$categories = $categories_query && $categories_query->num_rows > 0 ? $categories_query : null;

// Get statistics with error checking
$stats = [
    'total' => 0,
    'active' => 0,
    'draft' => 0,
    'paused' => 0,
    'completed' => 0
];

// Safe query execution for stats
$stat_queries = [
    'total' => "SELECT COUNT(*) as count FROM polls",
    'active' => "SELECT COUNT(*) as count FROM polls WHERE status = 'active'",
    'draft' => "SELECT COUNT(*) as count FROM polls WHERE status = 'draft'",
    'paused' => "SELECT COUNT(*) as count FROM polls WHERE status = 'paused'",
    'completed' => "SELECT COUNT(*) as count FROM polls WHERE status = 'completed'"
];

foreach ($stat_queries as $key => $query) {
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $stats[$key] = $result->fetch_assoc()['count'];
    }
}

$total_polls = $stats['total'];

$page_title = "Manage Polls";
include_once '../header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-poll me-2"></i>Manage All Polls (Admin)</h2>
                <div>
                    <button class="btn btn-info me-2" onclick="location.reload()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                    <button class="btn btn-success me-2" onclick="createSamplePoll()">
                        <i class="fas fa-magic"></i> Create Sample Poll
                    </button>
                    <a href="create-poll.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Poll
                    </a>
                </div>
            </div>
            <p class="text-muted">Manage all polls in the system across all users</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="mb-1"><?= number_format($stats['total']) ?></h3>
                    <small class="text-muted">Total Polls</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-success"><?= number_format($stats['active']) ?></h3>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-warning"><?= number_format($stats['draft']) ?></h3>
                    <small class="text-muted">Draft</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-danger"><?= number_format($stats['paused']) ?></h3>
                    <small class="text-muted">Paused</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-info"><?= number_format($stats['completed']) ?></h3>
                    <small class="text-muted">Completed</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status_filter" class="form-select">
                                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                                <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="paused" <?= $status_filter === 'paused' ? 'selected' : '' ?>>Paused</option>
                                <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="category_filter" class="form-select">
                                <option value="all" <?= $category_filter === 'all' ? 'selected' : '' ?>>All Categories</option>
                                <?php if ($categories): ?>
                                    <?php
                                    $categories->data_seek(0);
                                    while ($cat = $categories->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $cat['id']; ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option disabled>No categories available</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search polls..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="polls.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Polls Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Polls</h5>
                    <small class="text-muted">
                        Total polls: <?php echo isset($total_polls) ? $total_polls : 0; ?> |
                        Showing: <?php echo $polls_result ? $polls_result->num_rows : 0; ?>
                    </small>
                </div>
                <div class="card-body">
                    <?php if ($polls_result && $polls_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Creator</th>
                                        <th>Category</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Responses</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($poll = $polls_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars(substr($poll['title'], 0, 50)); ?>
                                                    <?php echo strlen($poll['title']) > 50 ? '...' : ''; ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars(substr($poll['description'], 0, 60)); ?>
                                                    <?php echo strlen($poll['description']) > 60 ? '...' : ''; ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($poll['first_name'] . ' ' . $poll['last_name']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($poll['category_name'] ?? 'No Category'); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($poll['poll_type']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $poll['status'] === 'active' ? 'success' :
                                                         ($poll['status'] === 'draft' ? 'warning' :
                                                         ($poll['status'] === 'paused' ? 'danger' :
                                                         ($poll['status'] === 'completed' ? 'info' : 'secondary')));
                                                ?>">
                                                    <?php echo ucfirst($poll['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php
                                                // Get response count separately if needed
                                                $response_count = 0;
                                                if (isset($poll['response_count'])) {
                                                    $response_count = $poll['response_count'];
                                                } else {
                                                    // Fallback: count responses for this poll
                                                    $count_query = $conn->query("SELECT COUNT(*) as count FROM poll_responses WHERE poll_id = " . $poll['id']);
                                                    if ($count_query) {
                                                        $response_count = $count_query->fetch_assoc()['count'];
                                                    }
                                                }
                                                echo number_format($response_count);
                                            ?></td>
                                            <td><?php echo date('M d, Y', strtotime($poll['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit-poll.php?id=<?php echo $poll['id']; ?>" class="btn btn-outline-primary btn-sm" title="Edit Poll">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-outline-info btn-sm view-details" title="View Details" data-poll-id="<?php echo $poll['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <div class="dropdown">
                                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                            <i class="fas fa-cog"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <?php if ($poll['status'] === 'draft'): ?>
                                                                <li><button class="dropdown-item status-change" data-poll-id="<?php echo $poll['id']; ?>" data-status="active">Activate</button></li>
                                                            <?php elseif ($poll['status'] === 'active'): ?>
                                                                <li><button class="dropdown-item status-change" data-poll-id="<?php echo $poll['id']; ?>" data-status="paused">Pause</button></li>
                                                            <?php elseif ($poll['status'] === 'paused'): ?>
                                                                <li><button class="dropdown-item status-change" data-poll-id="<?php echo $poll['id']; ?>" data-status="active">Resume</button></li>
                                                            <?php endif; ?>
                                                            <li><button class="dropdown-item status-change" data-poll-id="<?php echo $poll['id']; ?>" data-status="completed">Mark Complete</button></li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li><button class="dropdown-item text-danger delete-poll" data-poll-id="<?php echo $poll['id']; ?>" data-poll-title="<?php echo htmlspecialchars($poll['title']); ?>">Delete</button></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-poll fa-3x text-muted mb-3"></i>
                            <h5>No Polls Found</h5>
                            <p class="text-muted">
                                <?php if ($total_polls > 0): ?>
                                    No polls match your current filters. Try adjusting your search criteria or <a href="polls.php">clear all filters</a>.
                                <?php else: ?>
                                    No polls exist in the database yet.
                                <?php endif; ?>
                            </p>
                            <div class="mt-3">
                                <?php if ($total_polls == 0): ?>
                                    <button class="btn btn-success me-2" onclick="createSamplePoll()">
                                        <i class="fas fa-magic"></i> Create Sample Poll
                                    </button>
                                <?php endif; ?>
                                <a href="create-poll.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create New Poll
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Poll Details Modal -->
<div class="modal fade" id="pollDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Poll Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="pollDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
// View poll details
document.querySelectorAll('.view-details').forEach(btn => {
    btn.addEventListener('click', function() {
        const pollId = this.dataset.pollId;

        fetch(`view-poll-details.php?id=${pollId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('pollDetailsContent').innerHTML = data;
                new bootstrap.Modal(document.getElementById('pollDetailsModal')).show();
            })
            .catch(error => {
                alert('Error loading poll details: ' + error.message);
            });
    });
});

// Status change functionality
document.querySelectorAll('.status-change').forEach(btn => {
    btn.addEventListener('click', function() {
        const pollId = this.dataset.pollId;
        const newStatus = this.dataset.status;
        const statusText = this.textContent.trim();

        if (confirm(`Are you sure you want to ${statusText.toLowerCase()} this poll?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="change_status">
                <input type="hidden" name="poll_id" value="${pollId}">
                <input type="hidden" name="new_status" value="${newStatus}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
});

// Delete poll functionality
document.querySelectorAll('.delete-poll').forEach(btn => {
    btn.addEventListener('click', function() {
        const pollId = this.dataset.pollId;
        const pollTitle = this.dataset.pollTitle;

        if (confirm(`Are you sure you want to delete the poll "${pollTitle}"? This action cannot be undone.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_poll">
                <input type="hidden" name="poll_id" value="${pollId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
});

// Create sample poll
function createSamplePoll() {
    if (confirm('This will create a sample poll for testing purposes. Continue?')) {
        fetch('create-sample-poll.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Sample poll created successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error creating sample poll: ' + error.message);
        });
    }
}
</script>

<?php include_once '../footer.php'; ?>

<?php
// Backend functions
function handleDeletePoll() {
    global $conn;

    $poll_id = (int)$_POST['poll_id'];

    // Check if poll has responses
    $result = $conn->query("SELECT COUNT(*) as count FROM poll_responses WHERE poll_id = $poll_id");
    if ($result && $result->fetch_assoc()['count'] > 0) {
        $_SESSION['error_message'] = "Cannot delete poll with existing responses";
        header("Location: polls.php");
        exit;
    }

    // Delete poll questions first (cascading delete)
    $conn->query("DELETE FROM poll_questions WHERE poll_id = $poll_id");
    
    // Then delete the poll
    if ($conn->query("DELETE FROM polls WHERE id = $poll_id")) {
        $_SESSION['success_message'] = "Poll deleted successfully";
    } else {
        $_SESSION['error_message'] = "Failed to delete poll: " . $conn->error;
    }

    header("Location: polls.php");
    exit;
}

function handleChangePollStatus() {
    global $conn;

    $poll_id = (int)$_POST['poll_id'];
    $new_status = sanitize($_POST['new_status']);

    $valid_statuses = ['draft', 'active', 'paused', 'completed'];
    if (!in_array($new_status, $valid_statuses)) {
        $_SESSION['error_message'] = "Invalid status";
        header("Location: polls.php");
        exit;
    }

    if ($conn->query("UPDATE polls SET status = '$new_status', updated_at = NOW() WHERE id = $poll_id")) {
        $_SESSION['success_message'] = "Poll status updated successfully";
    } else {
        $_SESSION['error_message'] = "Failed to update poll status: " . $conn->error;
    }

    header("Location: polls.php");
    exit;
}

function handleUpdatePoll() {
    global $conn;

    // This would be implemented in the edit-poll.php page
    // For now, just redirect back
    header("Location: polls.php");
    exit;
}
?>
