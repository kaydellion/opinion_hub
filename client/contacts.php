<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('client');

$user = getCurrentUser();

$success = '';
$error = '';

// Handle list creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_list'])) {
    $name = sanitize($_POST['list_name']);
    $description = sanitize($_POST['description']);
    
    if (empty($name)) {
        $error = 'List name is required';
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_lists (user_id, name, description) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user['id'], $name, $description);
        
        if ($stmt->execute()) {
            $success = 'Contact list created successfully';
        } else {
            $error = 'Failed to create list';
        }
    }
}

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_csv'])) {
    $list_id = (int)$_POST['list_id'];
    
    if (empty($list_id)) {
        $error = 'Please select a contact list';
    } elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please upload a valid CSV file';
    } else {
        $file = $_FILES['csv_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($ext !== 'csv') {
            $error = 'Only CSV files are allowed';
        } else {
            // Parse CSV
            $contacts = [];
            $handle = fopen($file['tmp_name'], 'r');
            $header = fgetcsv($handle); // Skip header row
            
            while (($row = fgetcsv($handle)) !== false) {
                $contacts[] = [
                    'name' => $row[0] ?? '',
                    'phone' => $row[1] ?? '',
                    'email' => $row[2] ?? '',
                    'whatsapp' => $row[3] ?? ($row[1] ?? '')
                ];
            }
            fclose($handle);
            
            // Import contacts
            $result = importContacts($list_id, $contacts);
            $success = "Imported {$result['imported']} contacts. " . 
                      (count($result['errors']) > 0 ? count($result['errors']) . " skipped." : '');
        }
    }
}

// Handle manual contact add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_contact'])) {
    $list_id = (int)$_POST['list_id'];
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $whatsapp = sanitize($_POST['whatsapp']) ?: $phone;
    
    if (empty($list_id)) {
        $error = 'Please select a contact list';
    } elseif (empty($phone) && empty($email)) {
        $error = 'Phone or email is required';
    } else {
        // Prevent duplicate contact (by phone or email) per list
        $dup_check = $conn->prepare("SELECT id FROM contacts WHERE list_id = ? AND (phone = ? OR (email != '' AND email = ?)) LIMIT 1");
        $dup_check->bind_param("iss", $list_id, $phone, $email);
        $dup_check->execute();
        $dup_check->store_result();
        if ($dup_check->num_rows > 0) {
            $error = 'This contact (phone or email) already exists in this list.';
        } else {
            $stmt = $conn->prepare("INSERT INTO contacts (list_id, name, phone, email, whatsapp) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $list_id, $name, $phone, $email, $whatsapp);
            if ($stmt->execute()) {
                $success = 'Contact added successfully';
            } else {
                $error = 'Failed to add contact';
            }
        }
    }
}

// Handle list deletion
if (isset($_GET['delete_list'])) {
    $list_id = (int)$_GET['delete_list'];
    $conn->query("DELETE FROM contact_lists WHERE id = $list_id AND user_id = {$user['id']}");
    header('Location: contacts.php');
    exit;
}

// Get contact lists with contact count
$lists = $conn->query("SELECT cl.*, 
                       (SELECT COUNT(*) FROM contacts WHERE list_id = cl.id) as total_contacts 
                       FROM contact_lists cl 
                       WHERE cl.user_id = {$user['id']} 
                       ORDER BY cl.created_at DESC");

$page_title = 'Contact Lists';
include '../header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-address-book me-2"></i>Contact Lists</h2>
            <p class="text-muted">Manage your contact lists for bulk messaging</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createListModal">
                <i class="fas fa-plus me-2"></i>Create New List
            </button>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?= $success ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <?php if ($lists && $lists->num_rows > 0): ?>
            <?php while ($list = $lists->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($list['name']) ?></h5>
                            <p class="card-text text-muted small"><?= htmlspecialchars($list['description']) ?></p>
                            <h3 class="text-primary"><?= number_format($list['total_contacts']) ?></h3>
                            <small class="text-muted">Contacts</small>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="btn-group w-100">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="showUploadModal(<?= $list['id'] ?>, '<?= htmlspecialchars($list['name']) ?>')">
                                    <i class="fas fa-upload me-1"></i>Upload CSV
                                </button>
                                <button class="btn btn-sm btn-outline-success" 
                                        onclick="showAddContactModal(<?= $list['id'] ?>, '<?= htmlspecialchars($list['name']) ?>')">
                                    <i class="fas fa-plus me-1"></i>Add
                                </button>
                                <a href="view-contacts.php?list_id=<?= $list['id'] ?>" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-eye me-1"></i>View
                                </a>
                                <a href="?delete_list=<?= $list['id'] ?>" class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Delete this list and all contacts?')">
                                    <i class="fas fa-trash me-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    You don't have any contact lists yet. Create one to start managing your contacts.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create List Modal -->
<div class="modal fade" id="createListModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Create Contact List</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">List Name *</label>
                        <input type="text" name="list_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_list" class="btn btn-primary">Create List</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload CSV Modal -->
<div class="modal fade" id="uploadCsvModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Contacts (CSV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="list_id" id="upload_list_id">
                    <p class="text-muted">Uploading to: <strong id="upload_list_name"></strong></p>
                    
                    <div class="mb-3">
                        <label class="form-label">CSV File *</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <small class="text-muted">CSV format: Name, Phone, Email, WhatsApp</small>
                    </div>
                    
                    <div class="alert alert-info small">
                        <strong>CSV Format Example:</strong><br>
                        <code>
                        Name,Phone,Email,WhatsApp<br>
                        John Doe,08012345678,john@example.com,08012345678<br>
                        Jane Smith,07023456789,jane@example.com,
                        </code>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="upload_csv" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Contact Modal -->
<div class="modal fade" id="addContactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="list_id" id="add_list_id">
                    <p class="text-muted">Adding to: <strong id="add_list_name"></strong></p>
                    
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="08012345678">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="contact@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">WhatsApp (optional)</label>
                        <input type="text" name="whatsapp" class="form-control" placeholder="Defaults to phone if empty">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_contact" class="btn btn-success">Add Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showUploadModal(listId, listName) {
    document.getElementById('upload_list_id').value = listId;
    document.getElementById('upload_list_name').textContent = listName;
    new bootstrap.Modal(document.getElementById('uploadCsvModal')).show();
}

function showAddContactModal(listId, listName) {
    document.getElementById('add_list_id').value = listId;
    document.getElementById('add_list_name').textContent = listName;
    new bootstrap.Modal(document.getElementById('addContactModal')).show();
}
</script>

<?php include '../footer.php'; ?>
