<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole(['client', 'admin']);

$user = getCurrentUser();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Get all polls created by this user
$polls = $conn->query("SELECT p.*, 
                       (SELECT COUNT(*) FROM poll_questions WHERE poll_id = p.id) as question_count,
                       (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as response_count
                       FROM polls p 
                       WHERE p.created_by = {$user['id']} 
                       ORDER BY p.created_at DESC");

$page_title = 'Manage Polls';
include '../header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>My Polls & Surveys</h2>
            <p class="text-muted">Manage all your polls and surveys</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="create-poll.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Create New Poll
            </a>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-poll fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-0">Total Polls</h6>
                            <h3 class="mb-0"><?= $polls->num_rows ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-0">Active</h6>
                            <h3 class="mb-0">
                                <?php
                                $active_count = $conn->query("SELECT COUNT(*) as count FROM polls WHERE created_by = {$user['id']} AND status = 'active'")->fetch_assoc()['count'];
                                echo $active_count;
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-alt fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-0">Drafts</h6>
                            <h3 class="mb-0">
                                <?php
                                $draft_count = $conn->query("SELECT COUNT(*) as count FROM polls WHERE created_by = {$user['id']} AND status = 'draft'")->fetch_assoc()['count'];
                                echo $draft_count;
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users fa-2x text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-0">Total Responses</h6>
                            <h3 class="mb-0">
                                <?php
                                $total_responses = $conn->query("SELECT SUM(total_responses) as total FROM polls WHERE created_by = {$user['id']}")->fetch_assoc()['total'] ?? 0;
                                echo number_format($total_responses);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Tabs -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#all">All Polls</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#active">Active</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#draft">Drafts</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#closed">Closed</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- All Polls Tab -->
                <div class="tab-pane fade show active" id="all">
                    <?php if ($polls->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Poll Title</th>
                                        <th>Type</th>
                                        <th>Questions</th>
                                        <th>Responses</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $polls->data_seek(0); // Reset pointer
                                    while ($poll = $polls->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($poll['title']) ?></strong>
                                                <?php if ($poll['image']): ?>
                                                    <i class="fas fa-image text-muted ms-1"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?= htmlspecialchars($poll['poll_type']) ?></small></td>
                                            <td><span class="badge bg-info"><?= $poll['question_count'] ?></span></td>
                                            <td><span class="badge bg-success"><?= $poll['response_count'] ?></span></td>
                                            <td>
                                                <?php
                                                $status_colors = [
                                                    'draft' => 'warning',
                                                    'active' => 'success',
                                                    'paused' => 'secondary',
                                                    'closed' => 'danger'
                                                ];
                                                $color = $status_colors[$poll['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $color ?>"><?= ucfirst($poll['status']) ?></span>
                                            </td>
                                            <td><small><?= date('M d, Y', strtotime($poll['created_at'])) ?></small></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($poll['status'] === 'draft'): ?>
                                                        <a href="add-questions.php?id=<?= $poll['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="view-poll-results.php?id=<?= $poll['id'] ?>" class="btn btn-outline-info" title="View Results">
                                                            <i class="fas fa-chart-bar"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($poll['status'] === 'active'): ?>
                                                        <a href="../actions.php?action=pause_poll&id=<?= $poll['id'] ?>" class="btn btn-outline-warning" title="Pause">
                                                            <i class="fas fa-pause"></i>
                                                        </a>
                                                    <?php elseif ($poll['status'] === 'paused'): ?>
                                                        <a href="../actions.php?action=resume_poll&id=<?= $poll['id'] ?>" class="btn btn-outline-success" title="Resume">
                                                            <i class="fas fa-play"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="../actions.php?action=delete_poll&id=<?= $poll['id'] ?>" 
                                                       class="btn btn-outline-danger" 
                                                       title="Delete"
                                                       onclick="return confirm('Are you sure you want to delete this poll? This cannot be undone.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-poll fa-4x text-muted mb-3"></i>
                            <h5>No polls created yet</h5>
                            <p class="text-muted">Create your first poll to get started</p>
                            <a href="create-poll.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Create Poll
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Active Tab -->
                <div class="tab-pane fade" id="active">
                    <?php
                    $active_polls = $conn->query("SELECT p.*, 
                                                   (SELECT COUNT(*) FROM poll_questions WHERE poll_id = p.id) as question_count,
                                                   (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as response_count
                                                   FROM polls p 
                                                   WHERE p.created_by = {$user['id']} AND p.status = 'active'
                                                   ORDER BY p.created_at DESC");
                    ?>
                    <?php if ($active_polls->num_rows > 0): ?>
                        <div class="row">
                            <?php while ($poll = $active_polls->fetch_assoc()): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($poll['title']) ?></h5>
                                            <p class="card-text text-muted small"><?= substr($poll['description'], 0, 100) ?>...</p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge bg-info"><?= $poll['question_count'] ?> Questions</span>
                                                    <span class="badge bg-success"><?= $poll['response_count'] ?> Responses</span>
                                                </div>
                                                <a href="view-poll-results.php?id=<?= $poll['id'] ?>" class="btn btn-sm btn-primary">View Results</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No active polls</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Draft Tab -->
                <div class="tab-pane fade" id="draft">
                    <?php
                    $draft_polls = $conn->query("SELECT p.*, 
                                                 (SELECT COUNT(*) FROM poll_questions WHERE poll_id = p.id) as question_count
                                                 FROM polls p 
                                                 WHERE p.created_by = {$user['id']} AND p.status = 'draft'
                                                 ORDER BY p.created_at DESC");
                    ?>
                    <?php if ($draft_polls->num_rows > 0): ?>
                        <div class="list-group">
                            <?php while ($poll = $draft_polls->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($poll['title']) ?></h6>
                                            <small class="text-muted">
                                                <?= $poll['question_count'] ?> questions • 
                                                Created <?= date('M d, Y', strtotime($poll['created_at'])) ?>
                                            </small>
                                        </div>
                                        <div>
                                            <a href="add-questions.php?id=<?= $poll['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Continue Editing
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No draft polls</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Closed Tab -->
                <div class="tab-pane fade" id="closed">
                    <?php
                    $closed_polls = $conn->query("SELECT p.*, 
                                                   (SELECT COUNT(*) FROM poll_questions WHERE poll_id = p.id) as question_count,
                                                   (SELECT COUNT(*) FROM poll_responses WHERE poll_id = p.id) as response_count
                                                   FROM polls p 
                                                   WHERE p.created_by = {$user['id']} AND p.status = 'closed'
                                                   ORDER BY p.created_at DESC");
                    ?>
                    <?php if ($closed_polls->num_rows > 0): ?>
                        <div class="list-group">
                            <?php while ($poll = $closed_polls->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($poll['title']) ?></h6>
                                            <small class="text-muted">
                                                <?= $poll['response_count'] ?> responses • 
                                                Ended <?= date('M d, Y', strtotime($poll['end_date'])) ?>
                                            </small>
                                        </div>
                                        <div>
                                            <a href="view-poll-results.php?id=<?= $poll['id'] ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-chart-bar"></i> View Results
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No closed polls</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
