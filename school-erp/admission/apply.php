<?php
require_once '../config/db.php';
$pageTitle = 'Apply for Admission';
$db = getDB();

$schoolName = getSetting('school_name', APP_NAME);
$admFee     = (float)getSetting('admission_fee', 500);

$classes = $db->query("SELECT * FROM classes ORDER BY class_name,section");
$classList = [];
while ($c = $classes->fetch_assoc()) $classList[] = $c;

$errors  = [];
$success = '';
$admNo   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect & validate
    $name        = sanitize($_POST['applicant_name'] ?? '');
    $email       = sanitize($_POST['applicant_email'] ?? '');
    $phone       = sanitize($_POST['applicant_phone'] ?? '');
    $dob         = sanitize($_POST['date_of_birth'] ?? '');
    $gender      = sanitize($_POST['gender'] ?? '');
    $address     = sanitize($_POST['address'] ?? '');
    $parentName  = sanitize($_POST['parent_name'] ?? '');
    $parentPhone = sanitize($_POST['parent_phone'] ?? '');
    $parentEmail = sanitize($_POST['parent_email'] ?? '');
    $classId     = (int)($_POST['applying_class_id'] ?? 0);
    $prevSchool  = sanitize($_POST['previous_school'] ?? '');
    $prevMarks   = (float)($_POST['previous_marks'] ?? 0);
    $blood       = sanitize($_POST['blood_group'] ?? '');

    // Validation
    if (empty($name))        $errors[] = 'Applicant name is required.';
    if (empty($email))       $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if (empty($phone))       $errors[] = 'Phone number is required.';
    if (empty($parentName))  $errors[] = 'Parent/Guardian name is required.';
    if (empty($parentPhone)) $errors[] = 'Parent phone is required.';
    if ($classId <= 0)       $errors[] = 'Please select a class.';

    // Check duplicate email
    if (empty($errors)) {
        $emailCheck = dbSanitize($email);
        $existing = $db->query("SELECT id FROM admissions WHERE applicant_email='$emailCheck' LIMIT 1");
        if ($existing && $existing->num_rows > 0) {
            $errors[] = 'An application with this email already exists. <a href="track.php">Track your application</a>.';
        }
    }

    if (empty($errors)) {
        $admNo = generateAdmissionNo();
        $emailEsc = dbSanitize($email);
        $nameEsc  = dbSanitize($name);
        $phoneEsc = dbSanitize($phone);
        $dobEsc   = dbSanitize($dob);
        $addrEsc  = dbSanitize($address);
        $pnameEsc = dbSanitize($parentName);
        $pphoneEsc= dbSanitize($parentPhone);
        $pemailEsc= dbSanitize($parentEmail);
        $pschoolEsc= dbSanitize($prevSchool);
        $bloodEsc = dbSanitize($blood);

        $stmt = $db->prepare("INSERT INTO admissions
            (admission_no, applicant_name, applicant_email, applicant_phone, date_of_birth, gender,
             address, parent_name, parent_phone, parent_email, applying_class_id, previous_school,
             previous_marks, blood_group, admission_fee)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssssssisssd",
            $admNo, $name, $email, $phone, $dob, $gender,
            $address, $parentName, $parentPhone, $parentEmail, $classId, $prevSchool,
            $prevMarks, $blood, $admFee
        );

        if ($stmt->execute()) {
            $admId = $db->insert_id;
            // Redirect to payment page
            redirect(APP_URL . "/admission/payment.php?id=$admId");
        } else {
            $errors[] = 'Failed to submit application. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Admission - <?= htmlspecialchars($schoolName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body style="background:#0f172a">

<!-- Header -->
<nav class="navbar navbar-dark py-3" style="background:rgba(255,255,255,.07);backdrop-filter:blur(10px)">
    <div class="container">
        <a class="navbar-brand fw-700 d-flex align-items-center gap-2" href="<?= APP_URL ?>">
            <i class="bi bi-mortarboard-fill text-white fs-4"></i>
            <span class="text-white"><?= htmlspecialchars($schoolName) ?></span>
        </a>
        <div class="d-flex gap-2">
            <a href="track.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-search me-1"></i>Track Application
            </a>
            <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-light btn-sm fw-600">
                <i class="bi bi-box-arrow-in-right me-1"></i>Login
            </a>
        </div>
    </div>
</nav>

<!-- Hero -->
<div class="admission-hero text-center py-5">
    <div class="container">
        <span class="badge bg-primary mb-3 px-3 py-2">Admissions Open <?= date('Y') ?></span>
        <h1 class="display-5 fw-700 text-white mb-3">Apply for Admission</h1>
        <p class="text-white opacity-75 fs-5 mb-0">
            Start your journey to excellence. Fill out the form below and pay the admission fee online.
        </p>
    </div>
</div>

<div class="container pb-5">
    <!-- Track CTA -->
    <div class="row justify-content-center mb-4">
        <div class="col-lg-9">
            <div class="card border-0" style="background:rgba(255,255,255,.08);border-radius:14px">
                <div class="card-body py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="text-white">
                        <i class="bi bi-info-circle me-2"></i>
                        Already applied? Track your application status.
                    </div>
                    <a href="track.php" class="btn btn-light btn-sm fw-600">
                        <i class="bi bi-search me-1"></i>Track Application
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="admission-form-card">

                <!-- Step indicator -->
                <div class="step-indicator">
                    <div class="step-item active">
                        <div class="step-num">1</div>
                        <span class="step-label d-none d-sm-inline">Personal Info</span>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step-item">
                        <div class="step-num">2</div>
                        <span class="step-label d-none d-sm-inline">Academic Details</span>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step-item">
                        <div class="step-num">3</div>
                        <span class="step-label d-none d-sm-inline">Parent Info</span>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step-item">
                        <div class="step-num">4</div>
                        <span class="step-label d-none d-sm-inline">Review & Pay</span>
                    </div>
                </div>

                <div class="p-4">
                    <?php if ($errors): ?>
                        <div class="alert alert-danger flash-msg alert-dismissible">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?= implode('<br>', $errors) ?>
                            <button class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="admissionForm" novalidate>

                        <!-- Step 1: Personal Info -->
                        <div class="form-step">
                            <h5 class="fw-700 mb-4"><i class="bi bi-person-circle text-primary me-2"></i>Personal Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="applicant_name" class="form-control" required
                                           placeholder="e.g. Rahul Kumar Sharma"
                                           value="<?= htmlspecialchars($_POST['applicant_name'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="applicant_email" class="form-control" required
                                           placeholder="applicant@email.com"
                                           value="<?= htmlspecialchars($_POST['applicant_email'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" name="applicant_phone" class="form-control" required
                                           placeholder="+91 98765 43210"
                                           value="<?= htmlspecialchars($_POST['applicant_phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control"
                                           value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-select">
                                        <option value="">Select</option>
                                        <?php foreach (['Male','Female','Other'] as $g): ?>
                                            <option <?= ($_POST['gender']??'')===$g?'selected':'' ?>><?= $g ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Blood Group</label>
                                    <select name="blood_group" class="form-select">
                                        <option value="">Select</option>
                                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                                            <option <?= ($_POST['blood_group']??'')===$bg?'selected':'' ?>><?= $bg ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Residential Address</label>
                                    <input type="text" name="address" class="form-control"
                                           placeholder="House no, Street, City, State - PIN"
                                           value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mt-4 d-flex justify-content-end">
                                <button type="button" class="btn btn-primary btn-next-step px-4">
                                    Next <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Academic Details -->
                        <div class="form-step d-none">
                            <h5 class="fw-700 mb-4"><i class="bi bi-book text-primary me-2"></i>Academic Details</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Applying for Class *</label>
                                    <select name="applying_class_id" class="form-select" required>
                                        <option value="">Select Class</option>
                                        <?php foreach ($classList as $c): ?>
                                            <option value="<?= $c['id'] ?>" <?= ($_POST['applying_class_id']??'')==$c['id']?'selected':'' ?>>
                                                <?= htmlspecialchars($c['class_name'].' - '.$c['section']) ?>
                                                (<?= $c['seats'] ?> seats)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Previous School</label>
                                    <input type="text" name="previous_school" class="form-control"
                                           placeholder="Name of last school attended"
                                           value="<?= htmlspecialchars($_POST['previous_school'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Previous Class Marks (%)</label>
                                    <input type="number" name="previous_marks" class="form-control"
                                           min="0" max="100" step="0.01"
                                           placeholder="e.g. 85.5"
                                           value="<?= htmlspecialchars($_POST['previous_marks'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary btn-prev-step px-4">
                                    <i class="bi bi-arrow-left me-1"></i> Back
                                </button>
                                <button type="button" class="btn btn-primary btn-next-step px-4">
                                    Next <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Parent Info -->
                        <div class="form-step d-none">
                            <h5 class="fw-700 mb-4"><i class="bi bi-people text-primary me-2"></i>Parent / Guardian Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Parent/Guardian Name *</label>
                                    <input type="text" name="parent_name" class="form-control" required
                                           placeholder="Full name"
                                           value="<?= htmlspecialchars($_POST['parent_name'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Parent Phone *</label>
                                    <input type="tel" name="parent_phone" class="form-control" required
                                           placeholder="+91 98765 43210"
                                           value="<?= htmlspecialchars($_POST['parent_phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Parent Email</label>
                                    <input type="email" name="parent_email" class="form-control"
                                           placeholder="parent@email.com"
                                           value="<?= htmlspecialchars($_POST['parent_email'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary btn-prev-step px-4">
                                    <i class="bi bi-arrow-left me-1"></i> Back
                                </button>
                                <button type="button" class="btn btn-primary btn-next-step px-4">
                                    Next <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 4: Review & Pay -->
                        <div class="form-step d-none">
                            <h5 class="fw-700 mb-4"><i class="bi bi-clipboard-check text-primary me-2"></i>Review & Payment</h5>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Please review your details before proceeding to payment.
                            </div>
                            <div class="bg-light rounded-xl p-4 mb-4">
                                <div class="row g-2 small">
                                    <div class="col-md-6"><strong>Name:</strong> <span id="rev_name"></span></div>
                                    <div class="col-md-6"><strong>Email:</strong> <span id="rev_email"></span></div>
                                    <div class="col-md-6"><strong>Phone:</strong> <span id="rev_phone"></span></div>
                                    <div class="col-md-6"><strong>Gender:</strong> <span id="rev_gender"></span></div>
                                </div>
                            </div>
                            <div class="card border-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="fw-700 mb-1">Admission Fee</h6>
                                            <p class="text-muted small mb-0">Non-refundable application processing fee</p>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-700 fs-4 text-primary">&#8377;<?= number_format($admFee, 2) ?></div>
                                            <div class="small text-muted">Secure payment via PayU</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 d-flex align-items-center gap-2 text-muted small">
                                <i class="bi bi-shield-lock-fill text-success fs-5"></i>
                                256-bit SSL encrypted &bull; Powered by PayU &bull; Accepts all major cards, UPI, Net Banking
                            </div>
                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary btn-prev-step px-4">
                                    <i class="bi bi-arrow-left me-1"></i> Back
                                </button>
                                <button type="submit" class="btn btn-success px-5 fw-600">
                                    <i class="bi bi-credit-card me-2"></i>Submit & Pay &#8377;<?= number_format($admFee, 2) ?>
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="py-4 text-center text-white opacity-50 small">
    &copy; <?= date('Y') ?> <?= htmlspecialchars($schoolName) ?>. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/script.js"></script>
<script>
// Update review step
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-next-step').forEach(function(btn) {
        btn.addEventListener('click', function() {
            // Update review fields when reaching last step
            document.getElementById('rev_name').textContent   = document.querySelector('[name="applicant_name"]').value;
            document.getElementById('rev_email').textContent  = document.querySelector('[name="applicant_email"]').value;
            document.getElementById('rev_phone').textContent  = document.querySelector('[name="applicant_phone"]').value;
            document.getElementById('rev_gender').textContent = document.querySelector('[name="gender"]').value;
        });
    });
});
</script>
</body>
</html>
