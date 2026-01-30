<?php
require_once '../connect.php';
require_once '../functions.php';

requireRole('admin');

$user = getCurrentUser();
$page_title = "System Settings";

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = [];

    // Collect all form data
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $setting_key = str_replace('setting_', '', $key);
            $updates[$setting_key] = sanitize($value);
        }
    }

    // Check if site_settings table exists, create if not
    $table_check = $conn->query("SHOW TABLES LIKE 'site_settings'");
    if (!$table_check || $table_check->num_rows === 0) {
        $create_sql = "CREATE TABLE IF NOT EXISTS `site_settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `setting_key` varchar(100) NOT NULL UNIQUE,
            `setting_value` text,
            `setting_type` enum('text','number','boolean','email','url','json') DEFAULT 'text',
            `category` varchar(50) DEFAULT 'general',
            `description` varchar(255) DEFAULT NULL,
            `updated_by` int(11) DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `category` (`category`),
            KEY `updated_by` (`updated_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!$conn->query($create_sql)) {
            $error = "Failed to create site_settings table: " . $conn->error;
        }
    }

    // Update settings in database (site_settings)
    if (empty($error)) {
        foreach ($updates as $key => $value) {
            $stmt = $conn->prepare(
                "INSERT INTO site_settings (setting_key, setting_value, updated_by)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)"
            );
            if ($stmt) {
                $user_id = $user['id'] ?? null;
                $stmt->bind_param("ssi", $key, $value, $user_id);
                if (!$stmt->execute()) {
                    $error = "Failed to update setting: $key";
                    break;
                }
            } else {
                $error = "Failed to prepare statement for setting: $key";
                break;
            }
        }
    }

    if (empty($error)) {
        $success = "Settings updated successfully!";
    }
}

include_once '../header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                </ol>
            </nav>
            <h2><i class="fas fa-cogs text-primary"></i> System Settings</h2>
            <p class="text-muted">Configure system-wide settings including pricing and messaging costs</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" class="needs-validation" novalidate>
        <!-- Messaging Prices -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-comments"></i> Messaging Prices (₦ per unit)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <h6 class="text-center mb-3">Free Plan</h6>
                        <div class="mb-3">
                            <label class="form-label">SMS Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="setting_sms_price_free" value="<?php echo getSetting('sms_price_free', '20'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="setting_email_price_free" value="<?php echo getSetting('email_price_free', '10'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">WhatsApp Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="setting_whatsapp_price_free" value="<?php echo getSetting('whatsapp_price_free', '24'); ?>" required>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <h6 class="text-center mb-3">Basic Plan</h6>
                        <div class="mb-3">
                            <label class="form-label">SMS Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="setting_sms_price_basic" value="<?php echo getSetting('sms_price_basic', '18'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="setting_email_price_basic" value="<?php echo getSetting('email_price_basic', '8'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">WhatsApp Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="setting_whatsapp_price_basic" value="<?php echo getSetting('whatsapp_price_basic', '22'); ?>" required>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <h6 class="text-center mb-3">Classic Plan</h6>
                        <div class="mb-3">
                            <label class="form-label">SMS Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="setting_sms_price_classic" value="<?php echo getSetting('sms_price_classic', '17'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="setting_email_price_classic" value="<?php echo getSetting('email_price_classic', '9'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">WhatsApp Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="setting_whatsapp_price_classic" value="<?php echo getSetting('whatsapp_price_classic', '21'); ?>" required>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <h6 class="text-center mb-3">Enterprise Plan</h6>
                        <div class="mb-3">
                            <label class="form-label">SMS Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="setting_sms_price_enterprise" value="<?php echo getSetting('sms_price_enterprise', '16'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="setting_email_price_enterprise" value="<?php echo getSetting('email_price_enterprise', '8'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">WhatsApp Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="setting_whatsapp_price_enterprise" value="<?php echo getSetting('whatsapp_price_enterprise', '20'); ?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscription Pricing -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-credit-card"></i> Subscription Pricing (₦)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6 class="text-center mb-3">Basic Plan</h6>
                        <div class="mb-3">
                            <label class="form-label">Monthly Price</label>
                            <input type="number" min="0" class="form-control" name="setting_subscription_price_basic_monthly" value="<?php echo getSetting('subscription_price_basic_monthly', '35000'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Annual Price</label>
                            <input type="number" min="0" class="form-control" name="setting_subscription_price_basic_annual" value="<?php echo getSetting('subscription_price_basic_annual', '392000'); ?>" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <h6 class="text-center mb-3">Classic Plan</h6>
                        <div class="mb-3">
                            <label class="form-label">Monthly Price</label>
                            <input type="number" min="0" class="form-control" name="setting_subscription_price_classic_monthly" value="<?php echo getSetting('subscription_price_classic_monthly', '65000'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Annual Price</label>
                            <input type="number" min="0" class="form-control" name="setting_subscription_price_classic_annual" value="<?php echo getSetting('subscription_price_classic_annual', '735000'); ?>" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <h6 class="text-center mb-3">Enterprise Plan</h6>
                        <div class="mb-3">
                            <label class="form-label">Monthly Price</label>
                            <input type="number" min="0" class="form-control" name="setting_subscription_price_enterprise_monthly" value="<?php echo getSetting('subscription_price_enterprise_monthly', '100000'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Annual Price</label>
                            <input type="number" min="0" class="form-control" name="setting_subscription_price_enterprise_annual" value="<?php echo getSetting('subscription_price_enterprise_annual', '1050000'); ?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- General Settings -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-globe"></i> General Settings</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Site Name</label>
                            <input type="text" class="form-control" name="setting_site_name" value="<?php echo getSetting('site_name', 'Opinion Hub NG'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Site Description</label>
                            <input type="text" class="form-control" name="setting_site_description" value="<?php echo getSetting('site_description', 'Nigeria\'s Leading Poll Platform'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Email</label>
                            <input type="email" class="form-control" name="setting_contact_email" value="<?php echo getSetting('contact_email', 'support@opinionhub.ng'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Max Upload Size (bytes)</label>
                            <input type="number" min="1024" class="form-control" name="setting_max_upload_size" value="<?php echo getSetting('max_upload_size', '5242880'); ?>" required>
                            <small class="text-muted">Current: <?php echo number_format(getSetting('max_upload_size', '5242880')); ?> bytes (<?php echo number_format(getSetting('max_upload_size', '5242880') / 1024 / 1024, 1); ?> MB)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Maintenance Mode</label>
                            <select class="form-select" name="setting_maintenance_mode">
                                <option value="0" <?php echo getSetting('maintenance_mode', '0') == '0' ? 'selected' : ''; ?>>Off</option>
                                <option value="1" <?php echo getSetting('maintenance_mode', '1') == '1' ? 'selected' : ''; ?>>On</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </div>
    </form>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php include_once '../footer.php'; ?>