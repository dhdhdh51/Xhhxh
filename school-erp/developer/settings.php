<?php
require_once '../config/db.php';
requireRole('developer');
$pageTitle = 'System Settings';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['school_name','school_email','school_phone','school_address','academic_year',
             'admission_fee','payu_key','payu_salt','payu_mode','currency'];
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $val = dbSanitize($_POST[$key]);
            $db->query("INSERT INTO settings (setting_key,setting_value) VALUES ('$key','$val')
                        ON DUPLICATE KEY UPDATE setting_value='$val'");
        }
    }
    setFlash('success', 'Settings saved successfully!');
    redirect(APP_URL.'/developer/settings.php');
}

// Load all settings
$res = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($r = $res->fetch_assoc()) $settings[$r['setting_key']] = $r['setting_value'];

$flash = getFlash();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content" id="mainContent">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php if ($flash): ?>
        <div class="alert alert-success alert-dismissible fade show flash-msg mb-3">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <h2>System Settings</h2>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Settings</li>
        </ol></nav>
    </div>

    <form method="POST">
    <div class="row g-4">
        <!-- School Information -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title"><i class="bi bi-building me-2 text-primary"></i>School Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">School Name</label>
                        <input type="text" name="school_name" class="form-control" value="<?= htmlspecialchars($settings['school_name']??'') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">School Email</label>
                        <input type="email" name="school_email" class="form-control" value="<?= htmlspecialchars($settings['school_email']??'') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="school_phone" class="form-control" value="<?= htmlspecialchars($settings['school_phone']??'') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="school_address" class="form-control" rows="3"><?= htmlspecialchars($settings['school_address']??'') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Academic Year</label>
                        <input type="text" name="academic_year" class="form-control" value="<?= htmlspecialchars($settings['academic_year']??'') ?>" placeholder="e.g. 2025-2026">
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Settings -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title"><i class="bi bi-credit-card me-2 text-success"></i>PayU Payment Settings</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning small mb-3">
                        <i class="bi bi-shield-lock me-2"></i>
                        PayU credentials are sensitive. Keep them confidential.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PayU Merchant Key</label>
                        <input type="text" name="payu_key" class="form-control font-monospace" value="<?= htmlspecialchars($settings['payu_key']??'') ?>" placeholder="e.g. gtKFFx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PayU Salt</label>
                        <input type="text" name="payu_salt" class="form-control font-monospace" value="<?= htmlspecialchars($settings['payu_salt']??'') ?>" placeholder="e.g. eCwWELxi">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Mode</label>
                        <select name="payu_mode" class="form-select">
                            <option value="test" <?= ($settings['payu_mode']??'test')==='test'?'selected':'' ?>>Test Mode (Sandbox)</option>
                            <option value="live" <?= ($settings['payu_mode']??'test')==='live'?'selected':'' ?>>Live Mode (Production)</option>
                        </select>
                        <?php if (($settings['payu_mode']??'test') === 'live'): ?>
                            <div class="text-danger small mt-1"><i class="bi bi-exclamation-triangle me-1"></i>Live mode is active!</div>
                        <?php else: ?>
                            <div class="text-success small mt-1"><i class="bi bi-info-circle me-1"></i>Test mode - no real transactions.</div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admission Fee (&#8377;)</label>
                        <div class="input-group">
                            <span class="input-group-text">&#8377;</span>
                            <input type="number" name="admission_fee" class="form-control" step="0.01" min="0"
                                   value="<?= htmlspecialchars($settings['admission_fee']??500) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Currency</label>
                        <select name="currency" class="form-select">
                            <option value="INR" <?= ($settings['currency']??'INR')==='INR'?'selected':'' ?>>INR - Indian Rupee</option>
                            <option value="USD" <?= ($settings['currency']??'INR')==='USD'?'selected':'' ?>>USD - US Dollar</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- PayU Test Info -->
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header" style="background:#e0f2fe">
                    <h6 class="card-title text-info"><i class="bi bi-info-circle me-2"></i>PayU Test Credentials & Info</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 small">
                        <div class="col-md-4">
                            <strong>Test Merchant Key:</strong> <code>gtKFFx</code><br>
                            <strong>Test Salt:</strong> <code>eCwWELxi</code>
                        </div>
                        <div class="col-md-4">
                            <strong>Test Card:</strong> <code>5123456789012346</code><br>
                            <strong>Expiry:</strong> 05/2026 &bull; <strong>CVV:</strong> 123
                        </div>
                        <div class="col-md-4">
                            <strong>Test UPI:</strong> <code>success@payu</code><br>
                            <strong>Test Net Banking:</strong> Any bank, OTP: 123456
                        </div>
                    </div>
                    <p class="text-muted mt-2 mb-0 small">
                        For live payments, get credentials from
                        <a href="https://onboarding.payu.in" target="_blank" rel="noopener">PayU Merchant Dashboard</a>.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-primary px-5 fw-600">
                <i class="bi bi-save me-2"></i>Save Settings
            </button>
        </div>
    </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
