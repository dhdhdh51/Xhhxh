<?php
require_once '../config/db.php';
$pageTitle = 'Unauthorized';
$schoolName = getSetting('school_name', APP_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized - <?= htmlspecialchars($schoolName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body { background: var(--bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .error-card { background: #fff; border-radius: 20px; padding: 48px 40px; max-width: 460px; width: 100%; text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .error-icon { width: 88px; height: 88px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 2.5rem; color: #dc2626; }
    </style>
</head>
<body>
<div class="error-card">
    <div class="error-icon"><i class="bi bi-shield-x"></i></div>
    <h2 class="fw-700 mb-2">Access Denied</h2>
    <p class="text-muted mb-4">You don't have permission to access this page. Please contact your administrator if you believe this is a mistake.</p>

    <?php if (isset($_SESSION['user_id'])): ?>
    <a href="<?= APP_URL ?>/<?= $_SESSION['role'] ?>/dashboard.php" class="btn btn-primary px-4 me-2">
        <i class="bi bi-house me-2"></i>Go to Dashboard
    </a>
    <?php else: ?>
    <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-primary px-4 me-2">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
    </a>
    <?php endif; ?>
    <a href="javascript:history.back()" class="btn btn-outline-secondary px-4">
        <i class="bi bi-arrow-left me-2"></i>Go Back
    </a>

    <hr class="my-4">
    <small class="text-muted">Error 403 &mdash; <?= htmlspecialchars($schoolName) ?></small>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
