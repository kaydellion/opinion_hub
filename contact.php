<?php
$page_title = "Contact Us";
include_once 'header.php';

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
?>

<div class="container my-5">
    <div class="row mb-5">
        <div class="col-md-12 text-center">
            <h1 class="display-4 mb-3">Contact Us</h1>
            <p class="lead text-muted">We'd love to hear from you</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h4 class="mb-4"><i class="fas fa-envelope"></i> Send us a Message</h4>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div>• <?php echo $error; ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo SITE_URL; ?>actions.php?action=contact">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Mobile Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject *</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h4 class="mb-4"><i class="fas fa-map-marker-alt"></i> Our Office</h4>
                    
                    <div class="mb-3">
                        <h6 class="text-muted mb-2">Address</h6>
                        <p>
                            61-65 Egbe-Isolo Road,<br>
                            Iyana Ejigbo Shopping Arcade,<br>
                            Block A, Suite 19,<br>
                            Iyana Ejigbo Bus Stop, Ejigbo,<br>
                            Lagos State, Nigeria
                        </p>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-2">Phone</h6>
                        <p>
                            <i class="fas fa-phone text-primary me-2"></i> +234 (0) 803 3782 777<br>
                            <i class="fas fa-phone text-primary me-2"></i> +234 (01) 29 52 413
                        </p>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-2">Email</h6>
                        <p>
                            <i class="fas fa-envelope text-primary me-2"></i> hello@opinionhub.ng
                        </p>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-2">Business Hours</h6>
                        <p>
                            Monday - Friday: 8:00 AM - 5:00 PM<br>
                            Saturday: 9:00 AM - 2:00 PM<br>
                            Sunday: Closed
                        </p>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h4 class="mb-3"><i class="fas fa-share-alt"></i> Connect With Us</h4>
                    <div class="d-flex gap-3">
                        <a href="#" class="btn btn-outline-primary btn-lg">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="btn btn-outline-info btn-lg">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="btn btn-outline-danger btn-lg">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="btn btn-outline-primary btn-lg">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
