<?php
$page_title = "Unauthorized Access";
include_once 'header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-6 mx-auto text-center">
            <div class="card">
                <div class="card-body py-5">
                    <i class="fas fa-lock fa-4x text-danger mb-4"></i>
                    <h2 class="mb-3">Access Denied</h2>
                    <p class="text-muted mb-4">
                        You don't have permission to access this page. 
                        Please login with an authorized account or contact the administrator.
                    </p>
                    
                    <div class="d-flex gap-2 justify-content-center">
                        <?php if (isLoggedIn()): ?>
                            <a href="<?= SITE_URL ?>dashboard.php" class="btn btn-primary">
                                <i class="fas fa-home"></i> Go to Dashboard
                            </a>
                        <?php else: ?>
                            <a href="<?= SITE_URL ?>login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?= SITE_URL ?>index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
