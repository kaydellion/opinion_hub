<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$page_title = "Manage User Credits";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : 'all';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$where_clauses = ["1=1"];

if ($role_filter !== 'all') {
    $where_clauses[] = "role = '$role_filter'";
}

if (!empty($search)) {
    $where_clauses[] = "(first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%')";
}

$where_sql = implode(' AND ', $where_clauses);

// Get total count
$count_result = $conn->query("SELECT COUNT(*) as total FROM users WHERE $where_sql");
if (!$count_result) {
    error_log("Count query failed: " . $conn->error);
    $_SESSION['error'] = "Database error: " . $conn->error;
    $total_count = 0;
} else {
    $total_count = $count_result->fetch_assoc()['total'];
}
$total_pages = ceil($total_count / $limit);

// Check which credit columns exist
$sms_check = $conn->query("SHOW COLUMNS FROM users LIKE 'sms_credits'");
$sms_exists = $sms_check && $sms_check->num_rows > 0;

$whatsapp_check = $conn->query("SHOW COLUMNS FROM users LIKE 'whatsapp_credits'");
$whatsapp_exists = $whatsapp_check && $whatsapp_check->num_rows > 0;

$email_check = $conn->query("SHOW COLUMNS FROM users LIKE 'email_credits'");
$email_exists = $email_check && $email_check->num_rows > 0;

// Build SELECT clause based on which columns exist
$credit_columns = '';
if ($sms_exists) {
    $credit_columns .= ', sms_credits';
} else {
    $credit_columns .= ', 0 as sms_credits';
}
if ($whatsapp_exists) {
    $credit_columns .= ', whatsapp_credits';
} else {
    $credit_columns .= ', 0 as whatsapp_credits';
}
if ($email_exists) {
    $credit_columns .= ', email_credits';
} else {
    $credit_columns .= ', 0 as email_credits';
}

// Get users
$query = "SELECT id, first_name, last_name, email, role, created_at{$credit_columns}
          FROM users 
          WHERE $where_sql 
          ORDER BY created_at DESC 
          LIMIT $limit OFFSET $offset";
$users = $conn->query($query);

// Get statistics - build query based on which columns exist
$stats_select = "role, COUNT(*) as count";
if ($sms_exists) {
    $stats_select .= ", SUM(COALESCE(sms_credits, 0)) as total_sms";
} else {
    $stats_select .= ", 0 as total_sms";
}
if ($whatsapp_exists) {
    $stats_select .= ", SUM(COALESCE(whatsapp_credits, 0)) as total_whatsapp";
} else {
    $stats_select .= ", 0 as total_whatsapp";
}
if ($email_exists) {
    $stats_select .= ", SUM(COALESCE(email_credits, 0)) as total_email";
} else {
    $stats_select .= ", 0 as total_email";
}

$stats_query = "SELECT {$stats_select} FROM users GROUP BY role";

$stats_result = $conn->query($stats_query);

// Add error checking
if (!$stats_result) {
    error_log("Stats query failed: " . $conn->error);
    $stats = [];
} else {
    $stats = $stats_result->fetch_all(MYSQLI_ASSOC);
}

$stats_by_role = [];
foreach ($stats as $row) {
    $stats_by_role[$row['role']] = $row;
}

// Add error checking for users query
if (!$users) {
    error_log("Users query failed: " . $conn->error);
    $_SESSION['error'] = "Failed to load users: " . $conn->error;
}

// Show migration notices if columns don't exist
$missing_columns = [];
if (!$sms_exists) $missing_columns[] = 'SMS Credits';
if (!$whatsapp_exists) $missing_columns[] = 'WhatsApp Credits';
if (!$email_exists) $missing_columns[] = 'Email Credits';

if (!empty($missing_columns)) {
    $missing_text = implode(', ', $missing_columns);
    $migrations_needed = [];
    
    if (!$sms_exists) {
        $migrations_needed[] = '<a href="' . SITE_URL . 'migrations/run_poll_payments_migration.php">Poll Payments Migration</a>';
    }
    if (!$whatsapp_exists || !$email_exists) {
        $migrations_needed[] = '<a href="' . SITE_URL . 'migrations/run_whatsapp_email_credits.php">WhatsApp/Email Credits Migration</a>';
    }
    
    $_SESSION['warning'] = "Missing columns: {$missing_text}. Please run: " . implode(' and ', array_unique($migrations_needed));
}

include_once '../header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-credit-card text-primary"></i> Manage User Credits</h2>
            <p class="text-muted">Manage SMS, WhatsApp, and Email credits for all users</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-sms text-primary"></i> Total SMS Credits</h6>
                    <h2 class="mb-0">
                        <?php 
                        $total_sms = 0;
                        foreach ($stats_by_role as $stat) {
                            $total_sms += $stat['total_sms'];
                        }
                        echo number_format($total_sms); 
                        ?>
                    </h2>
                    <small class="text-muted">Across all users</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fab fa-whatsapp text-success"></i> Total WhatsApp Credits</h6>
                    <h2 class="mb-0">
                        <?php 
                        $total_whatsapp = 0;
                        foreach ($stats_by_role as $stat) {
                            $total_whatsapp += $stat['total_whatsapp'];
                        }
                        echo number_format($total_whatsapp); 
                        ?>
                    </h2>
                    <small class="text-muted">Across all users</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1"><i class="fas fa-envelope text-danger"></i> Total Email Credits</h6>
                    <h2 class="mb-0">
                        <?php 
                        $total_email = 0;
                        foreach ($stats_by_role as $stat) {
                            $total_email += $stat['total_email'];
                        }
                        echo number_format($total_email); 
                        ?>
                    </h2>
                    <small class="text-muted">Across all users</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Breakdown -->
    <div class="row mb-4">
        <?php foreach (['admin' => 'shield-alt', 'client' => 'user-tie', 'agent' => 'user-check', 'user' => 'user'] as $role => $icon): ?>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">
                        <i class="fas fa-<?php echo $icon; ?>"></i> <?php echo ucfirst($role); ?>s
                    </h6>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>SMS:</span>
                            <strong><?php echo number_format($stats_by_role[$role]['total_sms'] ?? 0); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>WhatsApp:</span>
                            <strong><?php echo number_format($stats_by_role[$role]['total_whatsapp'] ?? 0); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Email:</span>
                            <strong><?php echo number_format($stats_by_role[$role]['total_email'] ?? 0); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters & Search -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>All Roles</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="client" <?php echo $role_filter === 'client' ? 'selected' : ''; ?>>Client</option>
                        <option value="agent" <?php echo $role_filter === 'agent' ? 'selected' : ''; ?>>Agent</option>
                        <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Users (<?php echo number_format($total_count); ?> total)</h5>
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#bulkCreditModal">
                    <i class="fas fa-plus-circle"></i> Add Bulk Credits
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if ($users && $users->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>SMS Credits</th>
                            <th>WhatsApp Credits</th>
                            <th>Email Credits</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users && $users->num_rows > 0): ?>
                            <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                            </td>
                            <td>
                                <?php
                                $role_badges = [
                                    'admin' => 'danger',
                                    'client' => 'primary',
                                    'agent' => 'success',
                                    'user' => 'secondary'
                                ];
                                $badge_class = $role_badges[$user['role']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $badge_class; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary fs-6">
                                    <?php echo number_format($user['sms_credits'] ?? 0); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-success fs-6">
                                    <?php echo number_format($user['whatsapp_credits'] ?? 0); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-danger fs-6">
                                    <?php echo number_format($user['email_credits'] ?? 0); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="openEditCreditsModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>', <?php echo $user['sms_credits'] ?? 0; ?>, <?php echo $user['whatsapp_credits'] ?? 0; ?>, <?php echo $user['email_credits'] ?? 0; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-users fa-3x mb-3"></i>
                                    <p class="mb-0">No users found matching your filters.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo $role_filter; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <p class="text-muted">No users found</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Credits Modal -->
<div class="modal fade" id="editCreditsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User Credits</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCreditsForm">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <strong id="edit_user_name"></strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">SMS Credits</label>
                        <input type="number" class="form-control" name="sms_credits" id="edit_sms_credits" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">WhatsApp Credits</label>
                        <input type="number" class="form-control" name="whatsapp_credits" id="edit_whatsapp_credits" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Credits</label>
                        <input type="number" class="form-control" name="email_credits" id="edit_email_credits" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Reason for credit adjustment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Credits</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Credit Modal -->
<div class="modal fade" id="bulkCreditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Bulk Credits</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkCreditForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Apply To</label>
                        <select class="form-select" name="target_role" required>
                            <option value="all">All Users</option>
                            <option value="admin">Admins Only</option>
                            <option value="client">Clients Only</option>
                            <option value="agent">Agents Only</option>
                            <option value="user">Users Only</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">SMS Credits to Add</label>
                        <input type="number" class="form-control" name="sms_credits" min="0" value="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">WhatsApp Credits to Add</label>
                        <input type="number" class="form-control" name="whatsapp_credits" min="0" value="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Credits to Add</label>
                        <input type="number" class="form-control" name="email_credits" min="0" value="0">
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <small>Credits will be added to existing balances for selected users.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Credits</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditCreditsModal(userId, userName, smsCredits, whatsappCredits, emailCredits) {
    document.getElementById('edit_user_id').value = userId;
    document.getElementById('edit_user_name').textContent = userName;
    document.getElementById('edit_sms_credits').value = smsCredits;
    document.getElementById('edit_whatsapp_credits').value = whatsappCredits;
    document.getElementById('edit_email_credits').value = emailCredits;
    
    const modal = new bootstrap.Modal(document.getElementById('editCreditsModal'));
    modal.show();
}

document.getElementById('editCreditsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'updateUserCredits');
    
    fetch('<?php echo SITE_URL; ?>actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Credits updated successfully!');
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});

document.getElementById('bulkCreditForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!confirm('Are you sure you want to add credits to multiple users?')) {
        return;
    }
    
    const formData = new FormData(this);
    formData.append('action', 'addBulkCredits');
    
    fetch('<?php echo SITE_URL; ?>actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});
</script>

<?php include_once '../footer.php'; ?>
