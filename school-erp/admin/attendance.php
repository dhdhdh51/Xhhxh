<?php
require_once '../config/db.php';
requireRole(['admin','developer']);
$pageTitle = 'Attendance';
$db = getDB();

$classes     = $db->query("SELECT * FROM classes ORDER BY class_name,section")->fetch_all(MYSQLI_ASSOC);
$selClass    = (int)($_GET['class_id'] ?? ($classes[0]['id'] ?? 0));
$selDate     = $_GET['date'] ?? date('Y-m-d');

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['attendance'])) {
    $cid = (int)$_POST['class_id']; $date = dbSanitize($_POST['date']); $by = $_SESSION['user_id'];
    foreach ($_POST['attendance'] as $sid => $status) {
        $sid = (int)$sid; $status = in_array($status,['Present','Absent','Late','Leave'])?$status:'Present';
        $rem = dbSanitize($_POST['remarks'][$sid] ?? '');
        $stmt = $db->prepare("INSERT INTO attendance (student_id,class_id,date,status,marked_by,remarks) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),marked_by=VALUES(marked_by),remarks=VALUES(remarks)");
        $stmt->bind_param("iissss",$sid,$cid,$date,$status,$by,$rem); $stmt->execute();
    }
    setFlash('success','Attendance saved for '.formatDate($selDate).'!');
    redirect(APP_URL."/admin/attendance.php?class_id=$cid&date=$date");
}

$students = [];
if ($selClass) {
    $res = $db->query("SELECT s.id as sid,u.name,s.roll_number,a.status as att,a.remarks as rem FROM students s JOIN users u ON s.user_id=u.id LEFT JOIN attendance a ON a.student_id=s.id AND a.date='$selDate' WHERE s.class_id=$selClass ORDER BY s.roll_number");
    while ($r=$res->fetch_assoc()) $students[]=$r;
}

$monthly = $selClass ? $db->query("SELECT s.roll_number,u.name,SUM(a.status='Present') as p,SUM(a.status='Absent') as ab,COUNT(a.id) as tot FROM students s JOIN users u ON s.user_id=u.id LEFT JOIN attendance a ON a.student_id=s.id AND MONTH(a.date)=MONTH('$selDate') AND YEAR(a.date)=YEAR('$selDate') WHERE s.class_id=$selClass GROUP BY s.id ORDER BY s.roll_number") : null;

$flash = getFlash();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent">
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php if ($flash): ?><div class="alert alert-success alert-dismissible fade show flash-msg mb-3"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flash['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="page-header"><h2>Attendance</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Attendance</li></ol></nav></div>

<div class="card mb-4"><div class="card-body py-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label">Class</label><select name="class_id" class="form-select"><?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $selClass==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['class_name'].' - '.$c['section']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label">Date</label><input type="date" name="date" class="form-control" value="<?= $selDate ?>" max="<?= date('Y-m-d') ?>"></div>
        <div class="col-md-2"><label class="form-label">&nbsp;</label><button type="submit" class="btn btn-primary w-100">Load</button></div>
    </form>
</div></div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title"><i class="bi bi-calendar-check me-2 text-primary"></i>Mark Attendance - <?= formatDate($selDate) ?></h6>
                <div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-outline-success" id="markAllPresent">All Present</button><button type="button" class="btn btn-sm btn-outline-danger" id="markAllAbsent">All Absent</button></div>
            </div>
            <?php if ($students): ?>
            <form method="POST">
                <input type="hidden" name="class_id" value="<?= $selClass ?>">
                <input type="hidden" name="date" value="<?= $selDate ?>">
                <div class="table-responsive"><table class="table">
                    <thead><tr><th>Roll No</th><th>Name</th><th>Status</th><th>Remarks</th></tr></thead>
                    <tbody>
                    <?php foreach ($students as $s): ?>
                    <tr>
                        <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($s['roll_number']) ?></span></td>
                        <td class="fw-600"><?= htmlspecialchars($s['name']) ?></td>
                        <td><select name="attendance[<?= $s['sid'] ?>]" class="form-select form-select-sm" style="width:130px"><?php foreach (['Present','Absent','Late','Leave'] as $st): ?><option <?= $s['att']===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?></select></td>
                        <td><input type="text" name="remarks[<?= $s['sid'] ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars($s['rem']??'') ?>" placeholder="Optional"></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
                <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Attendance</button></div>
            </form>
            <?php else: ?><div class="card-body text-center py-5 text-muted"><i class="bi bi-people fs-1 d-block mb-2"></i>No students found.</div><?php endif; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="card-title"><i class="bi bi-bar-chart me-2 text-success"></i>Monthly Summary</h6></div>
            <div class="table-responsive"><table class="table table-sm">
                <thead><tr><th>Student</th><th class="text-success">P</th><th class="text-danger">A</th><th>%</th></tr></thead>
                <tbody>
                <?php if ($monthly) while ($r=$monthly->fetch_assoc()):
                    $pct = $r['tot']>0 ? round(($r['p']/$r['tot'])*100) : 0; $c = $pct>=75?'success':($pct>=50?'warning':'danger');
                ?>
                <tr>
                    <td class="small fw-600"><?= htmlspecialchars($r['name']) ?></td>
                    <td class="text-success fw-600"><?= $r['p'] ?></td>
                    <td class="text-danger fw-600"><?= $r['ab'] ?></td>
                    <td><span class="badge bg-<?= $c ?>-subtle text-<?= $c ?>"><?= $pct ?>%</span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
