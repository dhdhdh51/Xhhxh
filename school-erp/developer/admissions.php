<?php
require_once '../config/db.php';
requireRole(['developer','admin']);
$pageTitle = 'Admissions Management';
$db = getDB();

// Handle status update / enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    $admId = (int)($_POST['adm_id'] ?? 0);

    if ($postAction === 'update_status') {
        $status  = sanitize($_POST['status'] ?? '');
        $remarks = dbSanitize($_POST['remarks'] ?? '');
        $allowed = ['pending','approved','rejected','enrolled'];
        if (in_array($status, $allowed) && $admId > 0) {
            $db->query("UPDATE admissions SET status='$status', remarks='$remarks' WHERE id=$admId");
            setFlash('success', "Application status updated to '$status'.");
        }
    } elseif ($postAction === 'enroll') {
        // Enroll: create user account + student record
        $adm = $db->query("SELECT * FROM admissions WHERE id=$admId AND status='approved' AND fee_status='paid'")->fetch_assoc();
        if ($adm) {
            $tempPass = 'Student@' . rand(1000,9999);
            $hash    = password_hash($tempPass, PASSWORD_BCRYPT);
            $email   = dbSanitize($adm['applicant_email']);
            $name    = dbSanitize($adm['applicant_name']);
            $phone   = dbSanitize($adm['applicant_phone']);

            // Create user
            $stmt = $db->prepare("INSERT INTO users (name,email,password,role,phone) VALUES (?,?,?,'student',?)");
            $stmt->bind_param("ssss", $name, $email, $hash, $phone);
            if ($stmt->execute()) {
                $uid = $db->insert_id;
                // Generate roll number
                $count = $db->query("SELECT COUNT(*)+1 as c FROM students WHERE class_id={$adm['applying_class_id']}")->fetch_assoc()['c'];
                $rollNo = 'STU' . str_pad($count, 3,'0',STR_PAD_LEFT);

                $dob   = dbSanitize($adm['date_of_birth'] ?? '');
                $gender= dbSanitize($adm['gender'] ?? '');
                $blood = dbSanitize($adm['blood_group'] ?? '');
                $pname = dbSanitize($adm['parent_name']);
                $pphone= dbSanitize($adm['parent_phone']);
                $pemail= dbSanitize($adm['parent_email'] ?? '');

                $stmt2 = $db->prepare("INSERT INTO students (user_id,admission_id,roll_number,class_id,parent_name,parent_phone,parent_email,date_of_birth,gender,blood_group) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt2->bind_param("iisisssss", $uid, $admId, $rollNo, $adm['applying_class_id'], $pname, $pphone, $pemail, $dob, $gender, $blood);
                $stmt2->execute();

                $db->query("UPDATE admissions SET status='enrolled', user_id=$uid WHERE id=$admId");
                setFlash('success', "Student enrolled! Login: {$adm['applicant_email']} / Password: $tempPass (share with student)");
            } else {
                setFlash('error', 'Email already registered.');
            }
        } else {
            setFlash('error', 'Application must be approved and fee paid before enrollment.');
        }
    }
    redirect(APP_URL . '/' . ($_SESSION['role'] === 'developer' ? 'developer' : 'admin') . '/admissions.php');
}

// Filters
$statusFilter = sanitize($_GET['status'] ?? '');
$feeFilter    = sanitize($_GET['fee_status'] ?? '');
$search       = sanitize($_GET['q'] ?? '');

$where = "WHERE 1=1";
if ($statusFilter) $where .= " AND a.status='$statusFilter'";
if ($feeFilter)    $where .= " AND a.fee_status='$feeFilter'";
if ($search)       $where .= " AND (a.applicant_name LIKE '%$search%' OR a.admission_no LIKE '%$search%' OR a.applicant_email LIKE '%$search%')";

$admissions = $db->query("
    SELECT a.*, c.class_name, c.section
    FROM admissions a
    LEFT JOIN classes c ON a.applying_class_id=c.id
    $where
    ORDER BY a.applied_at DESC
");

// Summary
$summary = $db->query("SELECT status, COUNT(*) as c FROM admissions GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$admStats = ['pending'=>0,'approved'=>0,'rejected'=>0,'enrolled'=>0];
foreach ($summary as $s) $admStats[$s['status']] = $s['c'];

$flash = getFlash();
$baseUrl = ($_SESSION['role'] === 'developer') ? '/developer' : '/admin';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content" id="mainContent">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show flash-msg mb-3">
            <i class="bi bi-<?= $flash['type']==='success'?'check-circle':'exclamation-triangle' ?> me-2"></i>
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2>Admissions Management</h2>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Admissions</li>
            </ol></nav>
        </div>
        <a href="<?= APP_URL ?>/admission/apply.php" target="_blank" class="btn btn-outline-primary">
            <i class="bi bi-box-arrow-up-right me-1"></i>View Admission Form
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <?php $smeta = [
            ['pending','warning','clock','Pending'],
            ['approved','success','check-circle','Approved'],
            ['rejected','danger','x-circle','Rejected'],
            ['enrolled','primary','mortarboard-fill','Enrolled'],
        ]; ?>
        <?php foreach ($smeta as [$s,$c,$ic,$label]): ?>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="stat-icon bg-<?= $c ?> bg-opacity-10 text-<?= $c ?>"><i class="bi bi-<?= $ic ?>"></i></div>
                    <span class="badge bg-<?= $c ?>-subtle text-<?= $c ?>"><?= $label ?></span>
                </div>
                <div class="stat-value text-<?= $c ?>"><?= $admStats[$s] ?></div>
                <div class="stat-label"><?= $label ?> Applications</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4"><input type="text" name="q" class="form-control" placeholder="Search name, email, adm no..." value="<?= htmlspecialchars($search) ?>"></div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <?php foreach (['pending','approved','rejected','enrolled'] as $s): ?>
                            <option <?= $statusFilter===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="fee_status" class="form-select">
                        <option value="">All Fees</option>
                        <?php foreach (['paid','unpaid','refunded'] as $f): ?>
                            <option <?= $feeFilter===$f?'selected':'' ?>><?= $f ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i>Filter</button></div>
                <div class="col-md-2"><a href="admissions.php" class="btn btn-outline-secondary w-100">Clear</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title"><i class="bi bi-person-lines-fill me-2 text-primary"></i>All Applications</h6>
            <span class="badge bg-primary"><?= $admissions->num_rows ?></span>
        </div>
        <div class="table-responsive">
            <table class="table searchable">
                <thead>
                    <tr><th>Adm No</th><th>Applicant</th><th>Class</th><th>Parent</th><th>Fee</th><th>Status</th><th>Applied</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php while ($a=$admissions->fetch_assoc()):
                    $sc = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','enrolled'=>'primary'][$a['status']] ?? 'secondary';
                    $fc = ['paid'=>'success','unpaid'=>'danger','refunded'=>'info'][$a['fee_status']] ?? 'secondary';
                ?>
                <tr>
                    <td><span class="badge bg-primary-subtle text-primary fw-600"><?= htmlspecialchars($a['admission_no']) ?></span></td>
                    <td>
                        <div class="fw-600"><?= htmlspecialchars($a['applicant_name']) ?></div>
                        <div class="text-muted fs-12"><?= htmlspecialchars($a['applicant_email']) ?> &bull; <?= htmlspecialchars($a['applicant_phone']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($a['class_name'].' - '.$a['section']) ?></td>
                    <td>
                        <div class="small"><?= htmlspecialchars($a['parent_name']) ?></div>
                        <div class="text-muted fs-12"><?= htmlspecialchars($a['parent_phone']) ?></div>
                    </td>
                    <td><span class="badge bg-<?= $fc ?>-subtle text-<?= $fc ?>">&#8377;<?= number_format($a['admission_fee'],2) ?> <?= ucfirst($a['fee_status']) ?></span></td>
                    <td><span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td class="text-muted small"><?= formatDate($a['applied_at']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary btn-icon me-1"
                                data-bs-toggle="modal" data-bs-target="#updateModal"
                                onclick="setAdm(<?= $a['id'] ?>,'<?= $a['status'] ?>','<?= htmlspecialchars(addslashes($a['remarks']??'')) ?>')"
                                title="Update Status">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <?php if ($a['status']==='approved' && $a['fee_status']==='paid'): ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Enroll this student? A login account will be created.')">
                            <input type="hidden" name="action" value="enroll">
                            <input type="hidden" name="adm_id" value="<?= $a['id'] ?>">
                            <button class="btn btn-sm btn-success btn-icon" title="Enroll Student" data-bs-toggle="tooltip">
                                <i class="bi bi-mortarboard-fill"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php if ($a['fee_status']==='unpaid'): ?>
                        <a href="<?= APP_URL ?>/admission/payment.php?id=<?= $a['id'] ?>" target="_blank"
                           class="btn btn-sm btn-outline-warning btn-icon" title="Send Payment Link">
                            <i class="bi bi-link-45deg"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($admissions->num_rows===0): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted"><i class="bi bi-inbox d-block fs-1 mb-2"></i>No applications found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="adm_id" id="modalAdmId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Update Application Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="modalStatus" class="form-select" required>
                            <?php foreach (['pending','approved','rejected','enrolled'] as $s): ?>
                                <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks (visible to applicant)</label>
                        <textarea name="remarks" id="modalRemarks" class="form-control" rows="3" placeholder="Optional note..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function setAdm(id, status, remarks) {
    document.getElementById('modalAdmId').value   = id;
    document.getElementById('modalStatus').value  = status;
    document.getElementById('modalRemarks').value = remarks;
}
</script>

<?php include '../includes/footer.php'; ?>
