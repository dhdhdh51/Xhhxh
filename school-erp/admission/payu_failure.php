<?php
require_once '../config/db.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response  = $_POST;
    $txnid     = dbSanitize($response['txnid'] ?? '');
    $status    = dbSanitize($response['status'] ?? 'failure');
    $respJson  = dbSanitize(json_encode($response));
    if ($txnid) {
        $db->query("UPDATE payment_logs SET status='failure', payu_status='$status', payu_response='$respJson' WHERE txnid='$txnid'");
    }
}

$reason = sanitize($_GET['reason'] ?? 'Payment was cancelled or failed.');
$schoolName = getSetting('school_name', APP_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - <?= htmlspecialchars($schoolName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body style="background:linear-gradient(135deg,#7f1d1d,#dc2626);min-height:100vh;display:flex;align-items:center;justify-content:center">
    <div class="payment-card">
        <div class="mx-auto mb-4" style="width:70px;height:70px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center">
            <i class="bi bi-x-circle-fill text-danger fs-1"></i>
        </div>
        <h4 class="fw-700 text-danger mb-2">Payment Failed</h4>
        <p class="text-muted mb-4">Your payment could not be processed. No amount has been deducted.</p>
        <?php if ($reason): ?>
            <div class="alert alert-danger fs-13 mb-4"><?= htmlspecialchars($reason) ?></div>
        <?php endif; ?>
        <div class="d-grid gap-2">
            <a href="apply.php" class="btn btn-danger fw-600">
                <i class="bi bi-arrow-counterclockwise me-2"></i>Try Again
            </a>
            <a href="track.php" class="btn btn-outline-secondary">
                <i class="bi bi-search me-2"></i>Track Application
            </a>
        </div>
    </div>
</body>
</html>
