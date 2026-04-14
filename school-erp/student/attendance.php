<?php
require_once '../config/db.php';
requireRole('student');
$pageTitle = 'My Attendance';
$db = getDB();
$uid = (int)$_SESSION['user_id'];
$student = $db->query("SELECT s.id FROM students s WHERE s.user_id=$uid")->fetch_assoc();
$sid = (int)($student['id'] ?? 0);
$selMonth = sanitize($_GET['month'] ?? date('Y-m'));

$att = $db->query("SELECT * FROM attendance WHERE student_id=$sid AND DATE_FORMAT(date,'%Y-%m')='".dbSanitize($selMonth)."' ORDER BY date");
$attRows = $att ? $att->fetch_all(MYSQLI_ASSOC) : [];

$summary = $db->query("SELECT status,COUNT(*) as c FROM attendance WHERE student_id=$sid AND DATE_FORMAT(date,'%Y-%m')='".dbSanitize($selMonth)."' GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$stats = ['Present'=>0,'Absent'=>0,'Late'=>0,'Leave'=>0]; foreach ($summary as $s) $stats[$s['status']] = $s['c'];
$total = array_sum($stats) ?: 1;
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent"><div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="page-header d-flex justify-content-between align-items-center">
    <div><h2>My Attendance</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Attendance</li></ol></nav></div>
</div>
<div class="card mb-4"><div class="card-body py-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label">Month</label><input type="month" name="month" class="form-control" value="<?= $selMonth ?>"></div>
        <div class="col-md-2"><label class="form-label">&nbsp;</label><button class="btn btn-primary w-100">Load</button></div>
    </form>
</div></div>

<div class="row g-3 mb-4">
    <?php $scols=['Present'=>'success','Absent'=>'danger','Late'=>'warning','Leave'=>'info'];
    foreach ($stats as $s=>$c): $pct=round(($c/$total)*100); ?>
    <div class="col-md-3 col-6">
        <div class="stat-card text-center">
            <div class="stat-value text-<?= $scols[$s] ?>"><?= $c ?></div>
            <div class="stat-label"><?= $s ?></div>
            <div class="progress mt-2"><div class="progress-bar bg-<?= $scols[$s] ?>" style="width:<?= $pct ?>%"></div></div>
            <div class="text-muted fs-12 mt-1"><?= $pct ?>%</div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header"><h6 class="card-title"><i class="bi bi-calendar3 me-2 text-primary"></i>Attendance Log - <?= date('F Y',strtotime($selMonth.'-01')) ?></h6></div>
    <div class="table-responsive"><table class="table">
        <thead><tr><th>Date</th><th>Day</th><th>Status</th><th>Remarks</th></tr></thead>
        <tbody>
        <?php if ($attRows): foreach ($attRows as $a):
            $sc = ['Present'=>'success','Absent'=>'danger','Late'=>'warning','Leave'=>'info'][$a['status']]??'secondary'; ?>
        <tr>
            <td class="fw-600"><?= formatDate($a['date']) ?></td>
            <td class="text-muted"><?= date('D',strtotime($a['date'])) ?></td>
            <td><span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?>"><?= $a['status'] ?></span></td>
            <td class="text-muted small"><?= htmlspecialchars($a['remarks']??'-') ?></td>
        </tr>
        <?php endforeach; else: ?><tr><td colspan="4" class="text-center py-5 text-muted">No attendance records found.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
