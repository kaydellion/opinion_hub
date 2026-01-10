<?php
include_once 'connect.php';
include_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "login.php");
    exit;
}

$page_title = "My Profile";
include_once 'header.php';

global $conn;
$user = getCurrentUser();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <h2 class="mb-4">My Profile</h2>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= SITE_URL ?>actions.php?action=update_profile"
                        enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name"
                                    value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name"
                                    value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>"
                                    disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone"
                                    value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth"
                                    value="<?= $user['date_of_birth'] ?? '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?= ($user['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male
                                    </option>
                                    <option value="Female" <?= ($user['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>
                                        Female</option>

                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address"
                                rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" name="state"
                                    value="<?= htmlspecialchars($user['state'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">LGA</label>
                                <input type="text" class="form-control" name="lga"
                                    value="<?= htmlspecialchars($user['lga'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Profile Image</label>
                            <input type="file" class="form-control" name="profile_image" accept="image/*">
                            <?php if ($user['profile_image']): ?>
                                <div class="mt-2">
                                    <img src="<?= SITE_URL ?>uploads/profiles/<?= $user['profile_image'] ?>" alt="Profile"
                                        class="img-thumbnail" style="max-width: 150px;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= SITE_URL ?>dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= SITE_URL ?>actions.php?action=change_password">
                        <div class="mb-3">
                            <label class="form-label">Current Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password"
                                    name="current_password" required>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye" id="current_password-icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password"
                                    required>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye" id="new_password-icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password" required>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="confirm_password-icon"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
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