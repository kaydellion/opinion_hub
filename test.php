<?php
$page_title = "Browse Polls";
include_once 'header.php';
global $conn;

$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM polls WHERE status = 'active'";

if (!empty($category)) {
    $category = sanitize($category);
    $query .= " AND category_id IN (SELECT id FROM categories WHERE slug = '$category')";
}

if (!empty($search)) {
    $search = sanitize($search);
    $query .= " AND (title LIKE '%$search%' OR description LIKE '%$search%')";
}

$query .= " ORDER BY created_at DESC LIMIT 50";
$polls = $conn->query($query);
?>

<div class="container my-5">
    <h1 class="mb-4">Browse Polls</h1>
    
    <div class="row mb-4">
        <div class="col-md-8">
            <form method="GET" class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search polls...">
                <button class="btn btn-primary" type="submit">Search</button>
            </form>
        </div>
    </div>
    
    <div class="row">
        <?php while ($poll = $polls->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $poll['title']; ?></h5>
                        <p class="card-text text-muted"><?php echo substr($poll['description'], 0, 80); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary"><?php echo $poll['total_responses']; ?> votes</span>
                            <a href="view-poll.php?id=<?php echo $poll['id']; ?>" class="btn btn-sm btn-primary">Vote</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include_once 'footer.php'; ?>
```

#### view-poll.php (View poll and submit response)
```php
<?php
$page_title = "Poll";
include_once 'header.php';

$poll_id = (int)($_GET['id'] ?? 0);
if ($poll_id === 0) {
    header("Location: polls.php");
    exit;
}

$poll = getPoll($poll_id);
if (!$poll) {
    header("Location: polls.php");
    exit;
}

$questions = getPollQuestions($poll_id);
$stats = getPollStats($poll_id);
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <h1><?php echo $poll['title']; ?></h1>
            <p class="text-muted mb-4"><?php echo $poll['description']; ?></p>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <?php echo $poll['total_responses']; ?> people have responded
            </div>
            
            <?php if ($poll['status'] === 'active'): ?>
                <form method="POST" action="actions.php?action=submit_response" class="bg-light p-4 rounded">
                    <input type="hidden" name="poll_id" value="<?php echo $poll_id; ?>">
                    
                    <?php foreach ($questions as $question): ?>
                        <div class="mb-4">
                            <h5><?php echo $question['question_text']; ?></h5>
                            
                            <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                <?php foreach ($question['options'] as $option): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="responses[<?php echo $question['id']; ?>]" 
                                               value="<?php echo $option['id']; ?>" id="option_<?php echo $option['id']; ?>">
                                        <label class="form-check-label" for="option_<?php echo $option['id']; ?>">
                                            <?php echo $option['option_text']; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php elseif ($question['question_type'] === 'open_ended'): ?>
                                <textarea class="form-control" name="responses[<?php echo $question['id']; ?>]" rows="3"></textarea>
                            <?php elseif ($question['question_type'] === 'rating'): ?>
                                <div class="btn-group" role="group">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <input type="radio" class="btn-check" name="responses[<?php echo $question['id']; ?>]" 
                                               value="<?php echo $i; ?>" id="rating_<?php echo $i; ?>">
                                        <label class="btn btn-outline-primary" for="rating_<?php echo $i; ?>">
                                            <?php echo $i; ?> <i class="fas fa-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100">Submit Response</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">This poll is currently closed</div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Results</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($stats['questions'] as $question): ?>
                        <h6><?php echo $question['question_text']; ?></h6>
                        <?php foreach ($question['options'] as $option): ?>
                            <div class="mb-2">
                                <small><?php echo $option['option_text']; ?></small>
                                <div class="progress">
                                    <div class="progress-bar" style="width: <?php echo $option['percentage']; ?>%">
                                        <?php echo round($option['percentage'], 1); ?>%
                                    </div>
                                </div>
                                <small class="text-muted"><?php echo $option['count']; ?> votes</small>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
```

#### Client Dashboard (client/dashboard.php)
```php
<?php
$page_title = "Client Dashboard";
include_once '../header.php';
requireRole(['client']);

$user = getCurrentUser();
global $conn;

// Get client's polls
$polls = $conn->query("SELECT * FROM polls WHERE created_by = " . $user['id'] . " ORDER BY created_at DESC");

// Get subscription info
$subscription = getUserSubscription($user['id']);
$plan = $subscription ? $subscription['type'] : 'free';

// Get messaging credits
$credits = getMessagingCredits($user['id']);
?>

<div class="container my-5">
    <h1 class="mb-4">Client Dashboard</h1>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Plan</h5>
                    <h2><?php echo ucfirst($plan); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Polls</h5>
                    <h2><?php echo $polls->num_rows; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">SMS Credits</h5>
                    <h2><?php echo $credits ? $credits['sms_balance'] : 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Email Credits</h5>
                    <h2><?php echo $credits ? $credits['email_balance'] : 0; ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Your Polls</h5>
            <a href="create-poll.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Create New Poll
            </a>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Responses</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($poll = $polls->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $poll['title']; ?></td>
                            <td><span class="badge bg-<?php echo $poll['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($poll['status']); ?>
                            </span></td>
                            <td><?php echo $poll['total_responses']; ?></td>
                            <td><?php echo formatDate($poll['created_at']); ?></td>
                            <td>
                                <a href="edit-poll.php?id=<?php echo $poll['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../view-poll.php?id=<?php echo $poll['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once '../footer.php'; ?>
```

#### Admin Dashboard (admin/dashboard.php)
```php
<?php
$page_title = "Admin Dashboard";
include_once '../header.php';
requireRole(['admin']);

global $conn;

// Get system stats
$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$polls_count = $conn->query("SELECT COUNT(*) as count FROM polls")->fetch_assoc()['count'];
$responses_count = $conn->query("SELECT COUNT(*) as count FROM poll_responses")->fetch_assoc()['count'];
$agents_count = $conn->query("SELECT COUNT(*) as count FROM agents WHERE approval_status = 'approved'")->fetch_assoc()['count'];

// Get pending agents
$pending_agents = $conn->query("SELECT * FROM agents a JOIN users u ON a.user_id = u.id WHERE a.approval_status = 'pending' LIMIT 5");
?>

<div class="container my-5">
    <h1 class="mb-4">Admin Dashboard</h1>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <h2 class="text-primary"><?php echo $users_count; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Polls</h5>
                    <h2 class="text-success"><?php echo $polls_count; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Responses</h5>
                    <h2 class="text-warning"><?php echo $responses_count; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Active Agents</h5>
                    <h2 class="text-info"><?php echo $agents_count; ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="manage-users.php" class="btn btn-primary me-2">Manage Users</a>
                    <a href="manage-agents.php" class="btn btn-secondary me-2">Manage Agents</a>
                    <a href="manage-polls.php" class="btn btn-info me-2">Manage Polls</a>
                    <a href="reports.php" class="btn btn-warning">View Reports</a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Pending Agent Approvals</h5>
                </div>
                <div class="card-body">
                    <?php if ($pending_agents->num_rows > 0): ?>
                        <?php while ($agent = $pending_agents->fetch_assoc()): ?>
                            <div class="mb-2 pb-2 border-bottom">
                                <p class="mb-1">
                                    <strong><?php echo $agent['first_name'] . ' ' . $agent['last_name']; ?></strong><br>
                                    <small class="text-muted"><?php echo $agent['email']; ?></small>
                                </p>
                            </div>
                        <?php endwhile; ?>
                        <a href="manage-agents.php" class="btn btn-sm btn-primary w-100">Review Agents</a>
                    <?php else: ?>
                        <p class="text-muted">No pending approvals</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../footer.php'; ?>
