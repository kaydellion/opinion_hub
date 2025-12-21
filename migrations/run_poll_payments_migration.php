<?php
require_once '../connect.php';

$migration_file = 'add_poll_payments_and_earnings.sql';
$migration_name = 'Poll Payments & Agent Earnings System';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $migration_name; ?> - Migration</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-database me-2"></i><?php echo $migration_name; ?></h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            echo '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Running migration...</div>';
                            
                            // Read SQL file
                            $sql_content = file_get_contents($migration_file);
                            
                            // Split into individual statements
                            $statements = array_filter(array_map('trim', explode(';', $sql_content)));
                            
                            $success_count = 0;
                            $error_count = 0;
                            $errors = [];
                            
                            foreach ($statements as $statement) {
                                if (empty($statement) || strpos($statement, '--') === 0) {
                                    continue;
                                }
                                
                                if ($conn->query($statement)) {
                                    $success_count++;
                                    echo '<div class="alert alert-success mb-2"><i class="fas fa-check-circle me-2"></i>Executed: ' . substr($statement, 0, 100) . '...</div>';
                                } else {
                                    $error_count++;
                                    $error_msg = $conn->error;
                                    $errors[] = $error_msg;
                                    
                                    // Check if error is "Duplicate column" or "already exists" - these are OK
                                    if (strpos($error_msg, 'Duplicate column') !== false || 
                                        strpos($error_msg, 'already exists') !== false ||
                                        strpos($error_msg, 'duplicate key') !== false) {
                                        echo '<div class="alert alert-warning mb-2"><i class="fas fa-info-circle me-2"></i>Skipped (already exists): ' . substr($statement, 0, 80) . '...</div>';
                                    } else {
                                        echo '<div class="alert alert-danger mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Error: ' . $error_msg . '<br><small>' . substr($statement, 0, 100) . '...</small></div>';
                                    }
                                }
                            }
                            
                            echo '<hr>';
                            echo '<div class="alert alert-primary">';
                            echo '<h5><i class="fas fa-chart-bar me-2"></i>Migration Summary</h5>';
                            echo '<p class="mb-1"><strong>Successful:</strong> ' . $success_count . ' statements</p>';
                            echo '<p class="mb-1"><strong>Errors:</strong> ' . $error_count . ' statements</p>';
                            echo '</div>';
                            
                            if ($success_count > 0) {
                                echo '<div class="alert alert-success">';
                                echo '<h5><i class="fas fa-check-circle me-2"></i>Migration Completed!</h5>';
                                echo '<p class="mb-0">The following features are now enabled:</p>';
                                echo '<ul class="mb-0">';
                                echo '<li>✅ Client poll payment tracking</li>';
                                echo '<li>✅ Agent earnings system</li>';
                                echo '<li>✅ SMS delivery status tracking</li>';
                                echo '<li>✅ Databank (paid poll results)</li>';
                                echo '<li>✅ Enhanced user earnings tracking</li>';
                                echo '</ul>';
                                echo '</div>';
                            }
                            
                            echo '<a href="index.php" class="btn btn-primary"><i class="fas fa-arrow-left me-2"></i>Back to Migrations</a>';
                            
                        } else {
                        ?>
                        
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>About This Migration</h5>
                            <p>This migration adds comprehensive support for:</p>
                            <ul>
                                <li><strong>Client Poll Payments:</strong> Track poll costs and client payment status</li>
                                <li><strong>Agent Earnings:</strong> Automatic commission tracking for agents</li>
                                <li><strong>SMS Delivery Tracking:</strong> Enhanced message delivery status</li>
                                <li><strong>Databank Feature:</strong> Sell access to poll results</li>
                                <li><strong>Enhanced Credits:</strong> Better SMS credits management</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Note:</strong> This migration is safe to run multiple times. Existing data will not be affected.
                        </div>
                        
                        <h5 class="mb-3">Database Changes</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Table</th>
                                        <th>Changes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>polls</code></td>
                                        <td>
                                            + is_paid_poll, poll_cost, cost_per_response, agent_commission<br>
                                            + payment_status, payment_reference, paid_at, total_paid<br>
                                            + results_for_sale, results_sale_price
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>agent_earnings</code></td>
                                        <td>CREATE TABLE (tracks all agent earnings)</td>
                                    </tr>
                                    <tr>
                                        <td><code>users</code></td>
                                        <td>
                                            + total_earnings, pending_earnings, paid_earnings<br>
                                            + sms_credits
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>message_logs</code></td>
                                        <td>
                                            + delivery_status, delivered_at, failed_reason<br>
                                            + message_id
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>poll_results_access</code></td>
                                        <td>CREATE TABLE (tracks purchased results access)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <form method="POST" class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-play me-2"></i>Run Migration
                            </button>
                            <a href="index.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </form>
                        
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
