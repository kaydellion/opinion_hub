<?php
// Debug version of signup to isolate the issue
include_once 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Data Received:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Test the registration function directly
    $_REQUEST['action'] = 'register';
    include_once 'actions.php';
    
    echo "<h2>Session Data:</h2>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    if (!empty($_SESSION['errors'])) {
        echo "<h2>Registration Errors:</h2>";
        echo "<ul>";
        foreach ($_SESSION['errors'] as $error) {
            echo "<li style='color: red;'>$error</li>";
        }
        echo "</ul>";
    }
    
    if (!empty($_SESSION['success'])) {
        echo "<h2 style='color: green;'>Registration Success!</h2>";
        echo "<p>" . $_SESSION['success'] . "</p>";
    }
    
    echo "<hr>";
    echo "<a href='debug_signup.php'>← Try Again</a>";
    exit;
}

$page_title = "Debug Registration";
include_once 'header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">
                        <i class="fas fa-bug"></i> Debug Registration Form
                    </h2>
                    
                    <form method="POST" action="debug_signup.php">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required value="Test">
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required value="User">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required value="test<?php echo time(); ?>@example.com">
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="08012345678">
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">I am a *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user" selected>Regular User / Respondent</option>
                                <option value="client">Client / Researcher</option>
                                <option value="agent">Agent / Data Collector</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required value="password123">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="password-icon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required value="password123">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="confirm_password-icon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-bug"></i> Test Registration
                        </button>
                        
                        <div class="text-center">
                            <a href="signup.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Normal Signup
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php include_once 'footer.php'; ?>
