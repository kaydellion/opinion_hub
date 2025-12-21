<?php
require_once '../connect.php';
require_once '../functions.php';

// Check if user is admin
if (!isLoggedIn() || !checkRole('admin')) {
    header("Location: " . SITE_URL . "signin.php");
    exit;
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $updated = 0;
    $errors = [];
    
    foreach ($_POST as $key => $value) {
        if ($key === 'update_settings') continue;
        
        if (updateSetting($key, $value, $_SESSION['user_id'])) {
            $updated++;
        }
    }
    
    if ($updated > 0) {
        $_SESSION['success'] = "Updated $updated setting(s) successfully!";
    } else {
        $_SESSION['errors'] = ["No settings were updated."];
    }
    
    header("Location: " . SITE_URL . "admin/settings.php");
    exit;
}

$success = $_SESSION['success'] ?? '';
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['success'], $_SESSION['errors']);

// Get all settings grouped by category
$categories = [
    'site_config' => 'Site Configuration',
    'agent_earnings' => 'Agent Earnings & Payments',
    'agent_approval' => 'Agent Approval',
    'polls' => 'Poll Settings',
    'payment_api' => 'Payment API (vPay & Paystack)',
    'email_api' => 'Email API (Brevo)',
    'sms_api' => 'SMS API (Termii)',
    'whatsapp_api' => 'WhatsApp API',
    'vtpass_api' => 'VTPass API (Airtime/Data)',
    'editor_api' => 'Editor API (TinyMCE)',
    'email' => 'Email Settings',
    'sms' => 'SMS Settings',
    'company' => 'Company Information',
    'advertising' => 'Advertisement Rates',
    'pricing' => 'Pricing Plans',
    'system' => 'System Settings'
];

$page_title = "Site Settings";
include_once '../header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> Settings Categories</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($categories as $cat_key => $cat_name): ?>
                        <a href="#<?= $cat_key ?>" class="list-group-item list-group-item-action">
                            <?= $cat_name ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="text-muted"><i class="fas fa-info-circle"></i> Info</h6>
                    <p class="small mb-0">These settings control global platform behavior. Changes take effect immediately.</p>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0"><i class="fas fa-sliders-h"></i> Platform Settings</h3>
                </div>
                <div class="card-body">
                    
                    <form method="POST" id="settingsForm">
                        
                        <?php foreach ($categories as $cat_key => $cat_name): ?>
                            <?php $settings = getSettingsByCategory($cat_key); ?>
                            
                            <?php if (!empty($settings)): ?>
                                <div id="<?= $cat_key ?>" class="mb-5">
                                    <h4 class="h6 text-primary mb-3 pb-2 border-bottom">
                                        <?= $cat_name ?>
                                    </h4>
                                    
                                    <div class="row">
                                        <?php foreach ($settings as $setting): ?>
                                            <div class="col-md-6 mb-3">
                                                <label for="<?= $setting['setting_key'] ?>" class="form-label fw-bold">
                                                    <?= ucwords(str_replace('_', ' ', str_replace($cat_key . '_', '', $setting['setting_key']))) ?>
                                                </label>
                                                
                                                <?php if ($setting['setting_type'] === 'boolean'): ?>
                                                    <select class="form-select" id="<?= $setting['setting_key'] ?>" name="<?= $setting['setting_key'] ?>">
                                                        <option value="true" <?= $setting['setting_value'] === 'true' ? 'selected' : '' ?>>Enabled</option>
                                                        <option value="false" <?= $setting['setting_value'] === 'false' ? 'selected' : '' ?>>Disabled</option>
                                                    </select>
                                                
                                                <?php elseif ($setting['setting_type'] === 'number'): ?>
                                                    <input type="number" 
                                                           class="form-control" 
                                                           id="<?= $setting['setting_key'] ?>" 
                                                           name="<?= $setting['setting_key'] ?>" 
                                                           value="<?= htmlspecialchars($setting['setting_value']) ?>"
                                                           step="any">
                                                
                                                <?php elseif (strpos($setting['setting_key'], 'key') !== false || strpos($setting['setting_key'], 'secret') !== false): ?>
                                                    <!-- API Key/Secret fields with reveal toggle -->
                                                    <div class="input-group">
                                                        <input type="password" 
                                                               class="form-control api-key-field" 
                                                               id="<?= $setting['setting_key'] ?>" 
                                                               name="<?= $setting['setting_key'] ?>" 
                                                               value="<?= htmlspecialchars($setting['setting_value']) ?>">
                                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                
                                                <?php elseif ($setting['setting_type'] === 'text' && strlen($setting['setting_value']) > 100): ?>
                                                    <textarea class="form-control" 
                                                              id="<?= $setting['setting_key'] ?>" 
                                                              name="<?= $setting['setting_key'] ?>" 
                                                              rows="3"><?= htmlspecialchars($setting['setting_value']) ?></textarea>
                                                
                                                <?php else: ?>
                                                    <input type="text" 
                                                           class="form-control" 
                                                           id="<?= $setting['setting_key'] ?>" 
                                                           name="<?= $setting['setting_key'] ?>" 
                                                           value="<?= htmlspecialchars($setting['setting_value']) ?>">
                                                <?php endif; ?>
                                                
                                                <?php if ($setting['description']): ?>
                                                    <div class="form-text"><?= htmlspecialchars($setting['description']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <div class="d-flex gap-2 mt-4 pt-3 border-top">
                            <button type="submit" name="update_settings" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save All Changes
                            </button>
                            <a href="<?= SITE_URL ?>dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                        
                    </form>
                    
                </div>
            </div>
            
        </div>
        
    </div>
</div>

<script>
// Smooth scroll to section
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// Password/API Key toggle visibility
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const input = this.previousElementSibling;
        const icon = this.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});
</script>

<?php include_once '../footer.php'; ?>
