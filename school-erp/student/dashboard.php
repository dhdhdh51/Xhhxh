<?php
require_once '../config/db.php';
requireRole('student');
$pageTitle = 'My Dashboard';
$db = getDB();
$uid = (int)$_SESSION['user_id'];

$student = $db->query("SELECT s.*,c.class_name,c.section FROM students s JOIN classes c ON s.class_id=c.id WHERE s.user_id=$uid")->fetch_assoc();
$sid = (int)($student['id'] ?? 0);

// Attendance this month
$att = $db->query("SELECT status,COUNT(*) as c FROM attendance WHERE student_id=$sid AND MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE()) GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$attStats = ['Present'=>0,'Absent'=>0,'Late'=>0,'Leave'=>0]; foreach ($att as $a) $attStats[$a['status']] = $a['c'];
$attTotal = array_sum($attStats) ?: 1;
$attPct   = round(($attStats['Present']/$attTotal)*100);

// Pending fees
$feesPending = $db->query("SELECT COUNT(*) as c, COALESCE(SUM(amount),0) as s FROM fees WHERE student_id=$sid AND status!='Paid'")->fetch_assoc();

// Recent marks
$recentMarks = $db->query("SELECT m.*,e.exam_name,e.total_marks,s.subject_name FROM marks m JOIN exams e ON m.exam_id=e.id JOIN subjects s ON e.subject_id=s.id WHERE m.student_id=$sid ORDER BY m.created_at DESC LIMIT 5");

// Notices
$notices = $db->query("SELECT n.*,u.name as author FROM notices n JOIN users u ON n.published_by=u.id WHERE n.target_role IN ('all','student') AND n.is_published=1 ORDER BY n.created_at DESC LIMIT 4");
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent">
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="page-header d-flex justify-content-between align-items-center">
    <div><h2>Welcome, <?= htmlspecialchars(explode(' ',$_SESSION['name'])[0]) ?>!</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item active">Dashboard</li></ol></nav></div>
    <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= date('D, d M Y') ?></div>
</div>

<?php if ($student): ?>
<!-- Profile Banner -->
<div class="card mb-4" style="background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff">
    <div class="card-body d-flex align-items-center gap-4 p-4">
        <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:70px;height:70px;font-size:1.8rem">
            <i class="bi bi-person-fill text-white"></i>
        </div>
        <div>
            <h4 class="fw-700 mb-1 text-white"><?= htmlspecialchars($_SESSION['name']) ?></h4>
            <div class="text-white opacity-75">
                <span class="badge bg-white text-primary me-2"><?= htmlspecialchars($student['roll_number']) ?></span>
                <?= htmlspecialchars($student['class_name'].' - '.$student['section']) ?>
            </div>
        </div>
        <div class="ms-auto text-end d-none d-md-block">
            <div class="text-white opacity-75 small">Attendance This Month</div>
            <div class="fw-700 text-white" style="font-size:1.8rem"><?= $attPct ?>%</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between mb-3"><div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-calendar-check-fill"></i></div><span class="badge bg-<?= $attPct>=75?'success':($attPct>=50?'warning':'danger') ?>-subtle text-<?= $attPct>=75?'success':($attPct>=50?'warning':'danger') ?>"><?= $attPct ?>%</span></div>
            <div class="stat-value text-success"><?= $attStats['Present'] ?></div><div class="stat-label">Days Present This Month</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between mb-3"><div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-cash-stack"></i></div><span class="badge bg-<?= $feesPending['c']>0?'danger':'success' ?>-subtle text-<?= $feesPending['c']>0?'danger':'success' ?>"><?= $feesPending['c']>0?'Due':'Clear' ?></span></div>
            <div class="stat-value text-danger">&#8377;<?= number_format($feesPending['s'],2) ?></div><div class="stat-label">Fees Pending</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between mb-3"><div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-x-circle-fill"></i></div><span class="badge bg-danger-subtle text-danger">Missed</span></div>
            <div class="stat-value text-warning"><?= $attStats['Absent'] ?></div><div class="stat-label">Absences This Month</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><h6 class="card-title"><i class="bi bi-award me-2 text-warning"></i>Recent Marks</h6><a href="marks.php" class="btn btn-sm btn-outline-warning">View All</a></div>
            <div class="table-responsive"><table class="table">
                <thead><tr><th>Subject</th><th>Exam</th><th>Marks</th><th>Grade</th></tr></thead>
                <tbody>
                <?php while ($m=$recentMarks->fetch_assoc()): ?>
                <tr>
                    <td class="fw-600 small"><?= htmlspecialchars($m['subject_name']) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($m['exam_name']) ?></td>
                    <td class="fw-600"><?= $m['marks_obtained'] ?>/<?= $m['total_marks'] ?></td>
                    <td><span class="grade-cell"><?= $m['grade'] ?></span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h6 class="card-title"><i class="bi bi-megaphone me-2 text-warning"></i>Notices</h6></div>
            <div class="card-body p-0"><ul class="list-group list-group-flush">
                <?php while ($n=$notices->fetch_assoc()): ?>
                <li class="list-group-item px-4 py-3">
                    <div class="fw-600 small"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="text-muted fs-12"><?= formatDate($n['created_at']) ?></div>
                </li>
                <?php endwhile; ?>
            </ul></div>
        </div>
    </div>
</div>
<?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
