<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$page_title = "Add WhatsApp & Email Credits Migration";

$migration_run = false;
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $migration_run = true;
    
    // Read SQL file
    $sql_file = __DIR__ . '/add_whatsapp_email_credits.sql';
    $sql_content = file_get_contents($sql_file);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql_content)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        $result = $conn->query($statement);
        $results[] = [
            'statement' => substr($statement, 0, 100) . '...',
            'success' => $result !== false,
            'error' => $result === false ? $conn->error : null
        ];
    }
}

include_once '../header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>migrations/">Migrations</a></li>
                    <li class="breadcrumb-item active">WhatsApp & Email Credits</li>
                </ol>
            </nav>
            <h2><i class="fas fa-database text-primary"></i> Add WhatsApp & Email Credits Migration</h2>
            <p class="text-muted">Add WhatsApp and Email credit fields to users table</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <?php if ($migration_run): ?>
            <!-- Migration Results -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Migration Results</h5>
                </div>
                <div class="card-body">
                    <?php
                    $success_count = 0;
                    $error_count = 0;
                    
                    foreach ($results as $result):
                        if ($result['success']) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                    ?>
                    <div class="alert alert-<?php echo $result['success'] ? 'success' : 'danger'; ?> mb-2">
                        <?php if ($result['success']): ?>
                            <i class="fas fa-check-circle"></i> Success
                        <?php else: ?>
                            <i class="fas fa-times-circle"></i> Error: <?php echo htmlspecialchars($result['error']); ?>
                        <?php endif; ?>
                        <br>
                        <small class="text-muted"><?php echo htmlspecialchars($result['statement']); ?></small>
                    </div>
                    <?php endforeach; ?>

                    <hr>
                    <div class="alert alert-<?php echo $error_count === 0 ? 'success' : 'warning'; ?>">
                        <strong>Summary:</strong> 
                        <?php echo $success_count; ?> successful, 
                        <?php echo $error_count; ?> errors
                    </div>

                    <?php if ($error_count === 0): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <strong>Migration completed successfully!</strong><br>
                        WhatsApp and Email credit fields have been added to the users table.
                    </div>
                    <a href="<?php echo SITE_URL; ?>admin/manage-credits.php" class="btn btn-success">
                        <i class="fas fa-credit-card"></i> Go to Manage Credits
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <!-- Migration Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);">
                    <h5 class="mb-0">About This Migration</h5>
                </div>
                <div class="card-body">
                    <p>This migration will add the following fields to the <code>users</code> table:</p>
                    <ul>
                        <li><code>whatsapp_credits</code> - INT, default 0</li>
                        <li><code>email_credits</code> - INT, default 0</li>
                    </ul>
                    
                    <p class="mb-0">This allows admins to manage WhatsApp and Email credits for all users alongside SMS credits.</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Run Migration</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Important:</strong> This migration will modify the database structure. 
                            Make sure you have a backup before proceeding.
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-play-circle"></i> Run Migration
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-info-circle text-primary"></i> Features Enabled</h6>
                    <ul class="small mb-0">
                        <li class="mb-2">WhatsApp messaging credits</li>
                        <li class="mb-2">Email messaging credits</li>
                        <li class="mb-2">Admin credit management</li>
                        <li class="mb-2">Bulk credit allocation</li>
                        <li class="mb-2">Credit tracking by type</li>
                        <li>Multi-channel messaging support</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../footer.php'; ?>
