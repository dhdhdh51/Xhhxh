<?php
require_once '../config/db.php';
requireRole('developer');
$pageTitle = 'Developer Dashboard';
$db = getDB();

// System stats
$stats = [
    'users'      => $db->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'],
    'students'   => $db->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'],
    'teachers'   => $db->query("SELECT COUNT(*) as c FROM teachers")->fetch_assoc()['c'],
    'admissions' => $db->query("SELECT COUNT(*) as c FROM admissions")->fetch_assoc()['c'],
    'paid_adm'   => $db->query("SELECT COUNT(*) as c FROM admissions WHERE fee_status='paid'")->fetch_assoc()['c'],
    'revenue'    => $db->query("SELECT COALESCE(SUM(amount),0) as c FROM payment_logs WHERE status='success'")->fetch_assoc()['c'],
    'classes'    => $db->query("SELECT COUNT(*) as c FROM classes")->fetch_assoc()['c'],
    'notices'    => $db->query("SELECT COUNT(*) as c FROM notices")->fetch_assoc()['c'],
];

// Recent admissions
$recentAdm = $db->query("
    SELECT a.*, c.class_name, c.section
    FROM admissions a
    LEFT JOIN classes c ON a.applying_class_id=c.id
    ORDER BY a.applied_at DESC LIMIT 6
");

// Recent payment logs
$recentPayments = $db->query("
    SELECT p.*, a.admission_no, a.applicant_name
    FROM payment_logs p
    LEFT JOIN admissions a ON p.admission_id=a.id
    ORDER BY p.created_at DESC LIMIT 5
");

// Users by role
$roleCount = $db->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role")->fetch_all(MYSQLI_ASSOC);
$roleCounts = [];
foreach ($roleCount as $r) $roleCounts[$r['role']] = $r['cnt'];

$flash = getFlash();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content" id="mainContent">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show flash-msg mb-3">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2>Developer Dashboard</h2>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">System Overview</li>
            </ol></nav>
        </div>
        <div class="text-muted small">
            <i class="bi bi-calendar3 me-1"></i><?= date('D, d M Y') ?> &bull;
            <span class="badge bg-dev-subtle text-dev">Developer Access</span>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="bi bi-people-fill"></i></div>
                    <span class="badge bg-dev-subtle text-dev">All Roles</span>
                </div>
                <div class="stat-value text-dev"><?= $stats['users'] ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-person-plus-fill"></i></div>
                    <span class="badge bg-primary-subtle text-primary"><?= $stats['paid_adm'] ?> paid</span>
                </div>
                <div class="stat-value text-primary"><?= $stats['admissions'] ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-cash-coin"></i></div>
                    <span class="badge bg-success-subtle text-success">PayU</span>
                </div>
                <div class="stat-value text-success">&#8377;<?= number_format($stats['revenue']) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-mortarboard-fill"></i></div>
                    <span class="badge bg-warning-subtle text-warning"><?= $stats['teachers'] ?> teachers</span>
                </div>
                <div class="stat-value text-warning"><?= $stats['students'] ?></div>
                <div class="stat-label">Enrolled Students</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Users by role -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title"><i class="bi bi-pie-chart me-2 text-dev"></i>Users by Role</h6>
                </div>
                <div class="card-body">
                    <?php
                    $roleIcons = ['developer'=>'code-slash','admin'=>'shield-fill','teacher'=>'person-badge-fill','student'=>'backpack-fill'];
                    $roleColors= ['developer'=>'#7c3aed','admin'=>'#dc2626','teacher'=>'#2563eb','student'=>'#16a34a'];
                    foreach (['developer','admin','teacher','student'] as $r):
                        $cnt = $roleCounts[$r] ?? 0;
                        $pct = $stats['users'] > 0 ? round(($cnt/$stats['users'])*100) : 0;
                    ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;background:<?= $roleColors[$r] ?>20;color:<?= $roleColors[$r] ?>">
                            <i class="bi bi-<?= $roleIcons[$r] ?> small"></i>
                        </div>
                        <div class="flex-grow-1 me-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-600"><?= ucfirst($r) ?></span>
                                <span class="text-muted"><?= $cnt ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $roleColors[$r] ?>"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="users.php" class="btn btn-sm btn-outline-secondary">Manage All Users</a>
                </div>
            </div>
        </div>

        <!-- Recent Payment Logs -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title"><i class="bi bi-receipt me-2 text-success"></i>Recent Payments</h6>
                    <a href="logs.php" class="btn btn-sm btn-outline-success">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Txn ID</th><th>Applicant</th><th>Adm No</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php while ($p = $recentPayments->fetch_assoc()):
                            $scols = ['success'=>'success','failure'=>'danger','initiated'=>'secondary','pending'=>'warning'];
                            $sc = $scols[$p['status']] ?? 'secondary';
                        ?>
                        <tr>
                            <td><code class="small"><?= htmlspecialchars(substr($p['txnid'],0,14)) ?>...</code></td>
                            <td><?= htmlspecialchars($p['applicant_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['admission_no'] ?? '-') ?></td>
                            <td class="fw-600">&#8377;<?= number_format((float)$p['amount'],2) ?></td>
                            <td><span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?>"><?= ucfirst($p['status']) ?></span></td>
                            <td class="text-muted small"><?= formatDate($p['created_at']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Admissions -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Recent Applications</h6>
            <a href="admissions.php" class="btn btn-sm btn-primary">View All</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Adm No</th><th>Applicant</th><th>Class</th><th>Fee</th><th>Status</th><th>Applied</th><th>Actions</th></tr></thead>
                <tbody>
                <?php while ($a=$recentAdm->fetch_assoc()):
                    $statusColors = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','enrolled'=>'primary'];
                    $sc = $statusColors[$a['status']] ?? 'secondary';
                    $feeColors = ['paid'=>'success','unpaid'=>'danger','refunded'=>'info'];
                    $fc = $feeColors[$a['fee_status']] ?? 'secondary';
                ?>
                <tr>
                    <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($a['admission_no']) ?></span></td>
                    <td>
                        <div class="fw-600"><?= htmlspecialchars($a['applicant_name']) ?></div>
                        <div class="text-muted fs-12"><?= htmlspecialchars($a['applicant_email']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($a['class_name'].' - '.$a['section']) ?></td>
                    <td>
                        <span class="badge bg-<?= $fc ?>-subtle text-<?= $fc ?>"><?= ucfirst($a['fee_status']) ?></span>
                    </td>
                    <td><span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td class="text-muted small"><?= formatDate($a['applied_at']) ?></td>
                    <td><a href="admissions.php" class="btn btn-sm btn-outline-primary btn-icon"><i class="bi bi-eye"></i></a></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
