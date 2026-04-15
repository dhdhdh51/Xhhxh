<?php
require_once '../config/db.php';
$db = getDB();

$admId = (int)($_GET['id'] ?? 0);
if (!$admId) {
    redirect(APP_URL . '/admission/apply.php');
}

$adm = $db->query("SELECT * FROM admissions WHERE id=$admId LIMIT 1")->fetch_assoc();
if (!$adm) {
    redirect(APP_URL . '/admission/apply.php');
}

// If already paid, redirect to success
if ($adm['fee_status'] === 'paid') {
    redirect(APP_URL . "/admission/success.php?adm={$adm['admission_no']}");
}

// PayU credentials from settings
$payuKey  = getSetting('payu_key', 'gtKFFx');
$payuSalt = getSetting('payu_salt', 'eCwWELxi');
$payuMode = getSetting('payu_mode', 'test');

// PayU URLs
$payuUrl = $payuMode === 'live'
    ? 'https://secure.payu.in/_payment'
    : 'https://test.payu.in/_payment';

// Build transaction
$txnid      = 'ADM' . time() . rand(100, 999);
$amount     = number_format((float)$adm['admission_fee'], 2, '.', '');
$productinfo= 'Admission Fee - ' . $adm['admission_no'];
$firstname  = explode(' ', $adm['applicant_name'])[0];
$email      = $adm['applicant_email'];
$phone      = $adm['applicant_phone'];
$surl       = APP_URL . '/admission/payu_success.php';
$furl       = APP_URL . '/admission/payu_failure.php';
$udf1       = $adm['admission_no'];
$udf2       = $admId;

// Generate hash
$hashString = "$payuKey|$txnid|$amount|$productinfo|$firstname|$email|$udf1|$udf2||||||||$payuSalt";
$hash       = strtolower(hash('sha512', $hashString));

// Log the payment initiation
$txnEsc = dbSanitize($txnid);
$stmt = $db->prepare("INSERT INTO payment_logs (txnid, admission_id, amount, productinfo, firstname, email, phone, status) VALUES (?,?,?,?,?,?,?,'initiated')");
$stmt->bind_param("sidsssss", $txnid, $admId, $adm['admission_fee'], $productinfo, $firstname, $email, $phone);
$stmt->execute();

$schoolName = getSetting('school_name', APP_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Admission Fee - <?= htmlspecialchars($schoolName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body style="background:linear-gradient(135deg,#0f172a,#1e3a8a);min-height:100vh;display:flex;align-items:center;justify-content:center">

    <div class="payment-card">
        <!-- Logo -->
        <div class="login-logo mx-auto mb-4">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h4 class="fw-700 mb-1">Secure Payment</h4>
        <p class="text-muted small mb-4">Powered by PayU Payment Gateway</p>

        <!-- Application Summary -->
        <div class="bg-light rounded-xl p-3 mb-4 text-start">
            <div class="d-flex justify-content-between small mb-2">
                <span class="text-muted">Applicant</span>
                <span class="fw-600"><?= htmlspecialchars($adm['applicant_name']) ?></span>
            </div>
            <div class="d-flex justify-content-between small mb-2">
                <span class="text-muted">Admission No</span>
                <span class="fw-600 text-primary"><?= htmlspecialchars($adm['admission_no']) ?></span>
            </div>
            <div class="d-flex justify-content-between small mb-2">
                <span class="text-muted">Description</span>
                <span class="fw-600">Admission Fee</span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between">
                <span class="fw-600">Total Amount</span>
                <span class="fw-700 fs-5 text-primary">&#8377;<?= number_format((float)$adm['admission_fee'], 2) ?></span>
            </div>
        </div>

        <!-- Payment methods badge -->
        <div class="d-flex flex-wrap gap-2 justify-content-center mb-4">
            <?php foreach (['UPI', 'Cards', 'Net Banking', 'Wallets'] as $pm): ?>
                <span class="badge bg-light text-dark border fs-12 px-3 py-2"><?= $pm ?></span>
            <?php endforeach; ?>
        </div>

        <!-- PayU Form (auto-submit) -->
        <form id="payuAutoForm" method="POST" action="<?= htmlspecialchars($payuUrl) ?>">
            <input type="hidden" name="key"         value="<?= htmlspecialchars($payuKey) ?>">
            <input type="hidden" name="txnid"       value="<?= htmlspecialchars($txnid) ?>">
            <input type="hidden" name="productinfo" value="<?= htmlspecialchars($productinfo) ?>">
            <input type="hidden" name="amount"      value="<?= htmlspecialchars($amount) ?>">
            <input type="hidden" name="email"       value="<?= htmlspecialchars($email) ?>">
            <input type="hidden" name="firstname"   value="<?= htmlspecialchars($firstname) ?>">
            <input type="hidden" name="phone"       value="<?= htmlspecialchars($phone) ?>">
            <input type="hidden" name="surl"        value="<?= htmlspecialchars($surl) ?>">
            <input type="hidden" name="furl"        value="<?= htmlspecialchars($furl) ?>">
            <input type="hidden" name="hash"        value="<?= htmlspecialchars($hash) ?>">
            <input type="hidden" name="udf1"        value="<?= htmlspecialchars($udf1) ?>">
            <input type="hidden" name="udf2"        value="<?= htmlspecialchars($admId) ?>">
            <input type="hidden" name="service_provider" value="payu_paisa">

            <div class="alert alert-info fs-13 mb-3">
                <i class="bi bi-arrow-clockwise me-2"></i>
                Redirecting to PayU payment gateway...
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-600">
                <i class="bi bi-credit-card me-2"></i>
                Proceed to Pay &#8377;<?= number_format((float)$adm['admission_fee'], 2) ?>
            </button>
        </form>

        <div class="mt-3 d-flex align-items-center justify-content-center gap-2 text-muted" style="font-size:.72rem">
            <i class="bi bi-lock-fill text-success"></i>
            256-bit SSL Encrypted &bull; PCI DSS Compliant
        </div>
        <div class="mt-2 text-muted" style="font-size:.72rem">
            Reference: <code><?= htmlspecialchars($txnid) ?></code>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/script.js"></script>
</body>
</html>
