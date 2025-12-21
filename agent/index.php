<?php
$page_title = "Become an Agent";
include_once '../header.php';
?>

<div class="container my-5">
    <div class="row mb-5">
        <div class="col-md-12 text-center">
            <h1 class="display-4 mb-3">Become an Agent</h1>
            <p class="lead text-muted">
                Earn income by facilitating surveys and polls across Nigeria
            </p>
        </div>
    </div>

    <!-- Why Become an Agent -->
    <div class="row mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-4">
                    <i class="fas fa-money-bill-wave fa-3x text-success mb-3"></i>
                    <h4>Earn Income</h4>
                    <p>Earn up to ₦1,000 per completed poll response. The more respondents you engage, the more you earn.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-4">
                    <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                    <h4>Flexible Work</h4>
                    <p>Work on your own terms. Perfect for students, full-time employees, or anyone with a smartphone.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-4">
                    <i class="fas fa-network-wired fa-3x text-info mb-3"></i>
                    <h4>Expand Network</h4>
                    <p>Build connections across diverse groups beneficial for personal and professional growth.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- How It Works -->
    <div class="row mb-5">
        <div class="col-md-12">
            <h2 class="text-center mb-4">How It Works</h2>
        </div>
        <div class="col-md-4 text-center">
            <div class="mb-4">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <h2 class="mb-0">1</h2>
                </div>
            </div>
            <h5>Sign Up</h5>
            <p>Apply to join our community by completing your profile and agreeing to our terms.</p>
        </div>
        <div class="col-md-4 text-center">
            <div class="mb-4">
                <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <h2 class="mb-0">2</h2>
                </div>
            </div>
            <h5>Take Tasks</h5>
            <p>Receive tasks via email and engage respondents to participate in polls.</p>
        </div>
        <div class="col-md-4 text-center">
            <div class="mb-4">
                <div class="rounded-circle bg-warning text-white d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <h2 class="mb-0">3</h2>
                </div>
            </div>
            <h5>Get Paid</h5>
            <p>Earn money for every task completed. Credited within 5 working days.</p>
        </div>
    </div>

    <!-- Requirements -->
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-clipboard-check"></i> Requirements</h4>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Own a Smartphone with at least Android OS 6.0</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Be at least eighteen (18) years old</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Have at least a Secondary School Leaving Certificate</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Have a functional email address</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="row">
        <div class="col-md-12 text-center">
            <?php if (!isLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>register.php?type=agent" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-user-plus"></i> Sign Up as Agent
                </a>
            <?php elseif (getCurrentUser()['role'] === 'agent'): ?>
                <a href="<?php echo SITE_URL; ?>dashboard.php" class="btn btn-success btn-lg px-5">
                    <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                </a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>agent/complete-profile.php" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-user-edit"></i> Complete Agent Profile
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../footer.php'; ?>
