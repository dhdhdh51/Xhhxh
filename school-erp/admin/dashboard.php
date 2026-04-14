<?php
require_once '../config/db.php';
requireRole(['admin','developer']);
$pageTitle = 'Dashboard';
$db = getDB();

$totalStudents = $db->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
$totalTeachers = $db->query("SELECT COUNT(*) as c FROM teachers")->fetch_assoc()['c'];
$totalClasses  = $db->query("SELECT COUNT(*) as c FROM classes")->fetch_assoc()['c'];
$pendingAdm    = $db->query("SELECT COUNT(*) as c FROM admissions WHERE status='pending'")->fetch_assoc()['c'];
$feesPending   = $db->query("SELECT COALESCE(SUM(amount),0) as c FROM fees WHERE status='Pending'")->fetch_assoc()['c'];
$todayPresent  = $db->query("SELECT COUNT(*) as c FROM attendance WHERE date=CURDATE() AND status='Present'")->fetch_assoc()['c'];
$todayAbsent   = $db->query("SELECT COUNT(*) as c FROM attendance WHERE date=CURDATE() AND status='Absent'")->fetch_assoc()['c'];

$recentStudents = $db->query("
    SELECT s.roll_number, u.name, c.class_name, c.section, s.created_at
    FROM students s JOIN users u ON s.user_id=u.id JOIN classes c ON s.class_id=c.id
    ORDER BY s.created_at DESC LIMIT 5
");
$notices = $db->query("SELECT n.*, u.name as author FROM notices n JOIN users u ON n.published_by=u.id ORDER BY n.created_at DESC LIMIT 4");

$attData = $db->query("SELECT status, COUNT(*) as c FROM attendance WHERE MONTH(date)=MONTH(CURDATE()) GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$att = ['Present'=>0,'Absent'=>0,'Late'=>0,'Leave'=>0];
foreach ($attData as $a) $att[$a['status']] = $a['c'];
$attTotal = array_sum($att) ?: 1;

$flash = getFlash();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show flash-msg mb-3">
            <?= htmlspecialchars($flash['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="page-header d-flex justify-content-between align-items-center">
        <div><h2>Dashboard</h2><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item active">Overview</li></ol></nav></div>
        <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= date('D, d M Y') ?></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                    <span class="badge bg-success-subtle text-success">Active</span>
                </div>
                <div class="stat-value text-primary"><?= $totalStudents ?></div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-person-badge-fill"></i></div>
                    <span class="badge bg-success-subtle text-success">Active</span>
                </div>
                <div class="stat-value text-success"><?= $totalTeachers ?></div>
                <div class="stat-label">Total Teachers</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-person-plus-fill"></i></div>
                    <span class="badge bg-warning-subtle text-warning">Pending</span>
                </div>
                <div class="stat-value text-warning"><?= $pendingAdm ?></div>
                <div class="stat-label">Pending Admissions</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-cash-stack"></i></div>
                    <span class="badge bg-danger-subtle text-danger">Due</span>
                </div>
                <div class="stat-value text-danger">&#8377;<?= number_format($feesPending) ?></div>
                <div class="stat-label">Fees Pending</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h6 class="card-title"><i class="bi bi-calendar-check me-2 text-primary"></i>Attendance Today</h6>
                    <a href="attendance.php" class="btn btn-sm btn-outline-primary">Mark</a>
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center mb-4">
                        <div class="col-6">
                            <div class="bg-success bg-opacity-10 rounded-xl p-3">
                                <div class="fw-700 text-success" style="font-size:2rem"><?= $todayPresent ?></div>
                                <div class="text-muted small">Present</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-danger bg-opacity-10 rounded-xl p-3">
                                <div class="fw-700 text-danger" style="font-size:2rem"><?= $todayAbsent ?></div>
                                <div class="text-muted small">Absent</div>
                            </div>
                        </div>
                    </div>
                    <p class="fw-600 small text-muted mb-2">This Month</p>
                    <?php foreach (['Present'=>'success','Absent'=>'danger','Late'=>'warning','Leave'=>'info'] as $s=>$c): ?>
                        <?php $p=round(($att[$s]/$attTotal)*100); ?>
                        <div class="d-flex align-items-center mb-2 small">
                            <span class="text-muted me-2" style="width:55px"><?= $s ?></span>
                            <div class="progress flex-grow-1 me-2"><div class="progress-bar bg-<?= $c ?>" style="width:<?= $p ?>%"></div></div>
                            <span class="fw-600" style="width:32px;text-align:right"><?= $p ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h6 class="card-title"><i class="bi bi-megaphone me-2 text-warning"></i>Recent Notices</h6>
                    <a href="notices.php" class="btn btn-sm btn-outline-warning">Post Notice</a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php while ($n=$notices->fetch_assoc()): ?>
                        <li class="list-group-item px-4 py-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="fw-600 small"><?= htmlspecialchars($n['title']) ?></div>
                                    <div class="text-muted" style="font-size:.72rem"><?= htmlspecialchars($n['author']) ?> &bull; <?= formatDate($n['created_at']) ?></div>
                                </div>
                                <span class="badge bg-secondary-subtle text-secondary ms-2"><?= ucfirst($n['target_role']) ?></span>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h6 class="card-title"><i class="bi bi-people me-2 text-primary"></i>Recent Students</h6>
            <a href="students.php" class="btn btn-sm btn-primary"><i class="bi bi-plus me-1"></i>Add Student</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Roll No</th><th>Name</th><th>Class</th><th>Admitted</th></tr></thead>
                <tbody>
                <?php while ($s=$recentStudents->fetch_assoc()): ?>
                <tr>
                    <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($s['roll_number']) ?></span></td>
                    <td class="fw-600"><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['class_name'].' - '.$s['section']) ?></td>
                    <td class="text-muted small"><?= formatDate($s['created_at']) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
