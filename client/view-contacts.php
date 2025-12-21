<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('client');

$user = getCurrentUser();
$list_id = (int)($_GET['list_id'] ?? 0);

// Verify ownership
$list_query = $conn->query("SELECT * FROM contact_lists WHERE id = $list_id AND user_id = {$user['id']}");
if (!$list_query || $list_query->num_rows === 0) {
    header('Location: contacts.php');
    exit;
}
$list = $list_query->fetch_assoc();

// Get contacts
$contacts = $conn->query("SELECT * FROM contacts WHERE list_id = $list_id ORDER BY created_at DESC");

// Handle delete
if (isset($_GET['delete'])) {
    $contact_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM contacts WHERE id = $contact_id AND list_id = $list_id");
    $conn->query("UPDATE contact_lists SET total_contacts = (SELECT COUNT(*) FROM contacts WHERE list_id = $list_id) WHERE id = $list_id");
    header("Location: view-contacts.php?list_id=$list_id");
    exit;
}

$page_title = 'View Contacts';
include '../header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><?= htmlspecialchars($list['name']) ?></h2>
            <p class="text-muted"><?= htmlspecialchars($list['description']) ?></p>
        </div>
        <div class="col-md-4 text-end">
            <a href="contacts.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Lists
            </a>
        </div>
    </div>
    
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>WhatsApp</th>
                            <th>Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($contacts && $contacts->num_rows > 0): ?>
                            <?php while ($contact = $contacts->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($contact['name']) ?></td>
                                    <td><?= htmlspecialchars($contact['phone']) ?></td>
                                    <td><?= htmlspecialchars($contact['email']) ?></td>
                                    <td><?= htmlspecialchars($contact['whatsapp']) ?></td>
                                    <td><?= date('M j, Y', strtotime($contact['created_at'])) ?></td>
                                    <td>
                                        <a href="?list_id=<?= $list_id ?>&delete=<?= $contact['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Delete this contact?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No contacts in this list yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
