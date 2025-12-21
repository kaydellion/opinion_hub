<?php
/**
 * Installation Script for Opinion Hub NG
 * Run this file once to set up the application
 */

// Check if already installed
if (file_exists('installed.lock')) {
    die('<h1>Application Already Installed</h1><p>Delete the <code>installed.lock</code> file to reinstall.</p>');
}

$errors = [];
$success = [];

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    $errors[] = "PHP 7.4 or higher is required. You are running " . PHP_VERSION;
} else {
    $success[] = "PHP version " . PHP_VERSION . " is compatible";
}

// Check required extensions
$required_extensions = ['mysqli', 'curl', 'json', 'mbstring', 'gd'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $errors[] = "Required PHP extension '$ext' is not loaded";
    } else {
        $success[] = "PHP extension '$ext' is loaded";
    }
}

// Check if database.sql exists
if (!file_exists('database.sql')) {
    $errors[] = "database.sql file not found";
} else {
    $success[] = "database.sql file found";
}

// Process installation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? 'opinionhub_ng';
    $site_url = $_POST['site_url'] ?? 'http://localhost/opinion/';
    
    $admin_email = $_POST['admin_email'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    $admin_first_name = $_POST['admin_first_name'] ?? '';
    $admin_last_name = $_POST['admin_last_name'] ?? '';
    
    try {
        // Test database connection
        $conn = new mysqli($db_host, $db_user, $db_pass);
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        // Create database
        $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name`");
        $conn->select_db($db_name);
        
        // Import SQL file
        $sql_content = file_get_contents('database.sql');
        
        // Remove the CREATE DATABASE and USE statements
        $sql_content = preg_replace('/CREATE DATABASE.*?;/i', '', $sql_content);
        $sql_content = preg_replace('/USE.*?;/i', '', $sql_content);
        
        // Split into individual queries
        $queries = array_filter(array_map('trim', explode(';', $sql_content)));
        
        foreach ($queries as $query) {
            if (!empty($query)) {
                if (!$conn->query($query)) {
                    throw new Exception("SQL Error: " . $conn->error);
                }
            }
        }
        
        $success[] = "Database created and tables imported successfully";
        
        // Create admin user
        $password_hash = password_hash($admin_password, PASSWORD_BCRYPT, ['cost' => 12]);
        $username = strtolower($admin_first_name . $admin_last_name);
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role, status) VALUES (?, ?, ?, ?, ?, 'admin', 'active')");
        $stmt->bind_param("sssss", $username, $admin_email, $password_hash, $admin_first_name, $admin_last_name);
        
        if ($stmt->execute()) {
            $success[] = "Admin account created successfully";
        } else {
            throw new Exception("Failed to create admin account");
        }
        
        // Update connect.php
        $connect_content = file_get_contents('connect.php');
        $connect_content = str_replace("define('DB_HOST', 'localhost');", "define('DB_HOST', '$db_host');", $connect_content);
        $connect_content = str_replace("define('DB_USER', 'root');", "define('DB_USER', '$db_user');", $connect_content);
        $connect_content = str_replace("define('DB_PASS', '');", "define('DB_PASS', '$db_pass');", $connect_content);
        $connect_content = str_replace("define('DB_NAME', 'opinionhub_ng');", "define('DB_NAME', '$db_name');", $connect_content);
        $connect_content = str_replace("define('SITE_URL', 'http://localhost/opinion/');", "define('SITE_URL', '$site_url');", $connect_content);
        
        file_put_contents('connect.php', $connect_content);
        $success[] = "Configuration file updated";
        
        // Create uploads directory
        if (!file_exists('uploads')) {
            mkdir('uploads', 0755, true);
            mkdir('uploads/polls', 0755, true);
            mkdir('uploads/profiles', 0755, true);
            mkdir('uploads/ads', 0755, true);
            mkdir('uploads/blog', 0755, true);
            $success[] = "Upload directories created";
        }
        
        // Create lock file
        file_put_contents('installed.lock', date('Y-m-d H:i:s'));
        
        $success[] = "<strong>Installation completed successfully!</strong>";
        $success[] = "Admin Email: $admin_email";
        $success[] = "Please delete install.php for security";
        $success[] = '<a href="' . $site_url . '" class="btn btn-primary mt-3">Go to Website</a>';
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Opinion Hub NG</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 50px 0; }
        .install-card { max-width: 800px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card install-card shadow-lg">
            <div class="card-header bg-primary text-white text-center py-4">
                <h1><i class="fas fa-download"></i> Opinion Hub NG Installation</h1>
                <p class="mb-0">Follow the steps below to install the application</p>
            </div>
            <div class="card-body p-5">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> Errors</h5>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle"></i> Success</h5>
                        <ul class="mb-0">
                            <?php foreach ($success as $msg): ?>
                                <li><?php echo $msg; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (empty($errors) && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
                    <form method="POST">
                        <h4 class="mb-4">Database Configuration</h4>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Database Host</label>
                                <input type="text" class="form-control" name="db_host" value="localhost" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Database Name</label>
                                <input type="text" class="form-control" name="db_name" value="opinionhub_ng" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Database User</label>
                                <input type="text" class="form-control" name="db_user" value="root" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Database Password</label>
                                <input type="password" class="form-control" name="db_pass" placeholder="Leave empty if none">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Site URL</label>
                            <input type="url" class="form-control" name="site_url" value="http://localhost/opinion/" required>
                            <small class="text-muted">Must end with /</small>
                        </div>

                        <hr>

                        <h4 class="mb-4">Admin Account</h4>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="admin_first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="admin_last_name" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Admin Email</label>
                            <input type="email" class="form-control" name="admin_email" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Admin Password</label>
                            <input type="password" class="form-control" name="admin_password" required minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-rocket"></i> Install Opinion Hub NG
                        </button>
                    </form>
                <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)): ?>
                    <div class="text-center">
                        <i class="fas fa-check-circle text-success" style="font-size: 72px;"></i>
                        <h3 class="mt-3">Installation Complete!</h3>
                        <p class="text-muted">You can now start using Opinion Hub NG</p>
                        <a href="login.php" class="btn btn-primary btn-lg mt-3">
                            <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-4">
            <p class="text-white">
                <small>&copy; <?php echo date('Y'); ?> Foraminifera Market Research Limited. All rights reserved.</small>
            </p>
        </div>
    </div>
</body>
</html>
