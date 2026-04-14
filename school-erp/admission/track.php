<?php
require_once '../config/db.php';
$db = getDB();

$admNo  = sanitize($_GET['adm'] ?? '');
$adm    = null;
$notFound = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $admNo) {
    $admNoEsc = dbSanitize($admNo);
    $res = $db->query("
        SELECT a.*, c.class_name, c.section
        FROM admissions a
        LEFT JOIN classes c ON a.applying_class_id=c.id
        WHERE a.admission_no='$admNoEsc' LIMIT 1
    ");
    if ($res && $res->num_rows > 0) {
        $adm = $res->fetch_assoc();
    } else {
        $notFound = true;
    }
}

$schoolName = getSetting('school_name', APP_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Admission - <?= htmlspecialchars($schoolName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body style="background:linear-gradient(135deg,#0f172a,#1e3a8a);min-height:100vh">

<nav class="navbar navbar-dark py-3" style="background:rgba(255,255,255,.07)">
    <div class="container">
        <a class="navbar-brand fw-700 text-white" href="<?= APP_URL ?>">
            <i class="bi bi-mortarboard-fill me-2"></i><?= htmlspecialchars($schoolName) ?>
        </a>
        <div class="d-flex gap-2">
            <a href="apply.php" class="btn btn-outline-light btn-sm">Apply Now</a>
            <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-light btn-sm fw-600">Login</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="track-card">
                <!-- Search Form -->
                <div class="text-center mb-4">
                    <div style="width:60px;height:60px;background:linear-gradient(135deg,#2563eb,#7c3aed);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.6rem;color:#fff">
                        <i class="bi bi-search"></i>
                    </div>
                    <h4 class="fw-700 mb-1">Track Your Application</h4>
                    <p class="text-muted small">Enter your admission number to check status</p>
                </div>

                <form method="GET" class="mb-4">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-hash"></i></span>
                        <input type="text" name="adm" class="form-control"
                               placeholder="e.g. ADM2026-001"
                               value="<?= htmlspecialchars($admNo) ?>">
                        <button type="submit" class="btn btn-primary fw-600">Track</button>
                    </div>
                </form>

                <?php if ($notFound): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        No application found with number <strong><?= htmlspecialchars($admNo) ?></strong>.
                        Please check and try again.
                    </div>
                <?php endif; ?>

                <?php if ($adm):
                    $statusInfo = [
                        'pending'  => ['icon'=>'clock',          'color'=>'warning', 'label'=>'Under Review'],
                        'approved' => ['icon'=>'check-circle',   'color'=>'success', 'label'=>'Approved'],
                        'rejected' => ['icon'=>'x-circle',       'color'=>'danger',  'label'=>'Rejected'],
                        'enrolled' => ['icon'=>'mortarboard-fill','color'=>'primary', 'label'=>'Enrolled'],
                    ];
                    $si = $statusInfo[$adm['status']] ?? $statusInfo['pending'];
                ?>

                <hr>
                <!-- Status Banner -->
                <div class="alert alert-<?= $si['color'] ?> d-flex align-items-center gap-3 mb-4">
                    <i class="bi bi-<?= $si['icon'] ?> fs-3"></i>
                    <div>
                        <div class="fw-700">Status: <?= $si['label'] ?></div>
                        <div class="small">Application <?= htmlspecialchars($adm['admission_no']) ?></div>
                    </div>
                </div>

                <!-- Applicant Details -->
                <h6 class="fw-700 mb-3">Application Details</h6>
                <div class="row g-2 small mb-4">
                    <div class="col-6"><span class="text-muted d-block">Applicant Name</span><strong><?= htmlspecialchars($adm['applicant_name']) ?></strong></div>
                    <div class="col-6"><span class="text-muted d-block">Email</span><strong><?= htmlspecialchars($adm['applicant_email']) ?></strong></div>
                    <div class="col-6"><span class="text-muted d-block">Applied Class</span><strong><?= htmlspecialchars($adm['class_name'].' - '.$adm['section']) ?></strong></div>
                    <div class="col-6"><span class="text-muted d-block">Applied On</span><strong><?= formatDate($adm['applied_at']) ?></strong></div>
                    <div class="col-6">
                        <span class="text-muted d-block">Admission Fee</span>
                        <strong>&#8377;<?= number_format($adm['admission_fee'],2) ?></strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Payment Status</span>
                        <span class="badge bg-<?= $adm['fee_status']==='paid'?'success':'warning' ?>-subtle text-<?= $adm['fee_status']==='paid'?'success':'warning' ?>">
                            <i class="bi bi-<?= $adm['fee_status']==='paid'?'check-circle':'clock' ?> me-1"></i>
                            <?= ucfirst($adm['fee_status']) ?>
                        </span>
                    </div>
                    <?php if ($adm['payment_txn_id']): ?>
                    <div class="col-12">
                        <span class="text-muted d-block">PayU Transaction ID</span>
                        <code class="text-success"><?= htmlspecialchars($adm['payment_txn_id']) ?></code>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Timeline -->
                <h6 class="fw-700 mb-3">Application Timeline</h6>
                <ul class="status-timeline">
                    <li>
                        <div class="timeline-dot bg-success text-white"><i class="bi bi-check"></i></div>
                        <div>
                            <div class="fw-600 small">Application Submitted</div>
                            <div class="text-muted" style="font-size:.75rem"><?= formatDate($adm['applied_at']) ?></div>
                        </div>
                    </li>
                    <li>
                        <div class="timeline-dot bg-<?= $adm['fee_status']==='paid'?'success':'secondary' ?> text-white">
                            <i class="bi bi-<?= $adm['fee_status']==='paid'?'check':'clock' ?>"></i>
                        </div>
                        <div>
                            <div class="fw-600 small">Admission Fee Payment</div>
                            <div class="text-muted" style="font-size:.75rem">
                                <?= $adm['fee_status']==='paid' ? '&#8377;'.number_format($adm['admission_fee'],2).' paid' : 'Pending payment' ?>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="timeline-dot bg-<?= in_array($adm['status'],['approved','enrolled'])?'success':'secondary' ?> text-white">
                            <i class="bi bi-<?= in_array($adm['status'],['approved','enrolled'])?'check':'clock' ?>"></i>
                        </div>
                        <div>
                            <div class="fw-600 small">Application Review</div>
                            <div class="text-muted" style="font-size:.75rem">
                                <?= $adm['status']==='rejected' ? 'Application rejected' : ($adm['status']==='pending'?'Under review':'Approved') ?>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="timeline-dot bg-<?= $adm['status']==='enrolled'?'primary':'secondary' ?> text-white">
                            <i class="bi bi-<?= $adm['status']==='enrolled'?'mortarboard-fill':'clock' ?>"></i>
                        </div>
                        <div>
                            <div class="fw-600 small">Enrollment Complete</div>
                            <div class="text-muted" style="font-size:.75rem">
                                <?= $adm['status']==='enrolled' ? 'Student enrolled successfully' : 'Pending' ?>
                            </div>
                        </div>
                    </li>
                </ul>

                <?php if ($adm['fee_status']==='unpaid'): ?>
                <div class="alert alert-warning mt-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Your admission fee is unpaid. Please pay to continue.
                    <a href="payment.php?id=<?= $adm['id'] ?>" class="btn btn-warning btn-sm ms-2 fw-600">Pay Now</a>
                </div>
                <?php endif; ?>

                <?php if ($adm['remarks']): ?>
                <div class="alert alert-info mt-2 small">
                    <strong>Note from Admin:</strong> <?= htmlspecialchars($adm['remarks']) ?>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
