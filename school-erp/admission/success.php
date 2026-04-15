<?php
require_once '../config/db.php';
$db = getDB();

$admNo  = sanitize($_GET['adm'] ?? '');
$txnId  = sanitize($_GET['txn'] ?? '');

$adm = null;
if ($admNo) {
    $admNoEsc = dbSanitize($admNo);
    $res = $db->query("
        SELECT a.*, c.class_name, c.section
        FROM admissions a
        LEFT JOIN classes c ON a.applying_class_id=c.id
        WHERE a.admission_no='$admNoEsc' LIMIT 1
    ");
    $adm = $res ? $res->fetch_assoc() : null;
}

$schoolName = getSetting('school_name', APP_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted - <?= htmlspecialchars($schoolName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #14532d, #16a34a); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .success-card { background: #fff; border-radius: 20px; padding: 40px; max-width: 560px; width: 100%; box-shadow: 0 30px 60px rgba(0,0,0,.2); text-align: center; }
        .confetti-icon { font-size: 4rem; animation: bounce .8s infinite alternate; }
        @keyframes bounce { from { transform: translateY(0); } to { transform: translateY(-12px); } }
        .admission-no-box { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #fff; border-radius: 14px; padding: 20px 24px; }
        @media print { body { background: #fff; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="confetti-icon mb-3">&#127881;</div>
        <h3 class="fw-700 text-success mb-1">Application Submitted!</h3>
        <p class="text-muted mb-4">Your admission application has been received and fee payment was successful.</p>

        <?php if ($adm): ?>
        <!-- Admission Number Box -->
        <div class="admission-no-box mb-4">
            <div class="text-white opacity-75 small mb-1">Your Admission Tracking Number</div>
            <div class="fw-700 text-white" style="font-size:1.8rem;letter-spacing:2px"><?= htmlspecialchars($adm['admission_no']) ?></div>
            <div class="text-white opacity-60 small mt-1">Save this number to track your application</div>
        </div>

        <!-- Application Details -->
        <div class="bg-light rounded-xl p-3 text-start mb-4">
            <div class="row g-2 small">
                <div class="col-6"><span class="text-muted">Applicant:</span><br><strong><?= htmlspecialchars($adm['applicant_name']) ?></strong></div>
                <div class="col-6"><span class="text-muted">Email:</span><br><strong><?= htmlspecialchars($adm['applicant_email']) ?></strong></div>
                <div class="col-6"><span class="text-muted">Applied Class:</span><br><strong><?= htmlspecialchars($adm['class_name'].' - '.$adm['section']) ?></strong></div>
                <div class="col-6"><span class="text-muted">Status:</span><br>
                    <span class="badge bg-warning-subtle text-warning">
                        <i class="bi bi-clock me-1"></i>Pending Review
                    </span>
                </div>
                <?php if ($txnId): ?>
                <div class="col-12 mt-2">
                    <span class="text-muted">PayU Transaction ID:</span><br>
                    <code class="text-success"><?= htmlspecialchars($txnId) ?></code>
                </div>
                <?php endif; ?>
                <div class="col-6"><span class="text-muted">Fee Paid:</span><br><strong class="text-success">&#8377;<?= number_format($adm['admission_fee'],2) ?></strong></div>
                <div class="col-6"><span class="text-muted">Applied On:</span><br><strong><?= formatDate($adm['applied_at']) ?></strong></div>
            </div>
        </div>
        <?php else: ?>
            <div class="alert alert-success">
                Your application has been submitted successfully. Please save your admission number to track status.
            </div>
        <?php endif; ?>

        <!-- What's Next -->
        <div class="text-start mb-4">
            <h6 class="fw-700 mb-3">What happens next?</h6>
            <ul class="list-unstyled small text-muted">
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Application is under review by the admission team.</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>You'll receive an email update within 3-5 working days.</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Track your status anytime using the admission number.</li>
            </ul>
        </div>

        <div class="d-grid gap-2 no-print">
            <a href="track.php?adm=<?= urlencode($admNo) ?>" class="btn btn-success fw-600">
                <i class="bi bi-search me-2"></i>Track Application
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-2"></i>Print Receipt
            </button>
            <a href="apply.php" class="btn btn-outline-secondary btn-sm">
                Apply for Another Student
            </a>
        </div>
    </div>
</body>
</html>
