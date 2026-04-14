<?php
require_once '../config/db.php';
requireRole(['admin','developer']);
$pageTitle = 'Marks & Grades';
$db = getDB();
$classes   = $db->query("SELECT * FROM classes ORDER BY class_name,section")->fetch_all(MYSQLI_ASSOC);
$selClass  = (int)($_GET['class_id'] ?? ($classes[0]['id'] ?? 0));
$selExam   = (int)($_GET['exam_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $pa = $_POST['action'] ?? '';
    if ($pa === 'add_exam') {
        $en = dbSanitize($_POST['exam_name']??''); $ci = (int)$_POST['class_id']; $si = (int)$_POST['subject_id'];
        $ed = dbSanitize($_POST['exam_date']??''); $tm = (int)$_POST['total_marks']; $pm = (int)$_POST['pass_marks'];
        $stmt = $db->prepare("INSERT INTO exams (exam_name,class_id,subject_id,exam_date,total_marks,pass_marks) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("siisii",$en,$ci,$si,$ed,$tm,$pm); $stmt->execute();
        setFlash('success','Exam created!'); redirect(APP_URL."/admin/marks.php?class_id=$ci");
    } elseif ($pa === 'save_marks') {
        $eid = (int)$_POST['exam_id'];
        $eInfo = $db->query("SELECT * FROM exams WHERE id=$eid")->fetch_assoc();
        foreach ($_POST['marks'] as $sid => $m) {
            $sid = (int)$sid; $m = (float)$m;
            $g = getGrade($m,$eInfo['total_marks']);
            $stmt = $db->prepare("INSERT INTO marks (student_id,exam_id,marks_obtained,grade) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE marks_obtained=VALUES(marks_obtained),grade=VALUES(grade)");
            $stmt->bind_param("iids",$sid,$eid,$m,$g); $stmt->execute();
        }
        setFlash('success','Marks saved!'); redirect(APP_URL."/admin/marks.php?class_id={$_POST['class_id']}&exam_id=$eid");
    }
}

$exams = $selClass ? $db->query("SELECT e.*,s.subject_name FROM exams e JOIN subjects s ON e.subject_id=s.id WHERE e.class_id=$selClass ORDER BY e.exam_date DESC")->fetch_all(MYSQLI_ASSOC) : [];
if (!$selExam && $exams) $selExam = $exams[0]['id'];
$curExam = array_filter($exams, fn($e)=>$e['id']==$selExam);
$curExam = reset($curExam) ?: null;

$marksData = ($selExam && $selClass) ? $db->query("SELECT s.id as sid,s.roll_number,u.name,m.marks_obtained,m.grade FROM students s JOIN users u ON s.user_id=u.id LEFT JOIN marks m ON m.student_id=s.id AND m.exam_id=$selExam WHERE s.class_id=$selClass ORDER BY s.roll_number") : null;
$subjects  = $selClass ? $db->query("SELECT * FROM subjects WHERE class_id=$selClass ORDER BY subject_name")->fetch_all(MYSQLI_ASSOC) : [];
$flash = getFlash();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent">
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php if ($flash): ?><div class="alert alert-success alert-dismissible fade show flash-msg mb-3"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flash['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <div><h2>Marks &amp; Grades</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Marks</li></ol></nav></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExamModal"><i class="bi bi-plus-circle me-1"></i>Create Exam</button>
</div>

<div class="card mb-4"><div class="card-body py-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label">Class</label><select name="class_id" class="form-select" onchange="this.form.submit()"><?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $selClass==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['class_name'].' - '.$c['section']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Exam</label><select name="exam_id" class="form-select" onchange="this.form.submit()"><option value="">Select Exam</option><?php foreach ($exams as $e): ?><option value="<?= $e['id'] ?>" <?= $selExam==$e['id']?'selected':'' ?>><?= htmlspecialchars($e['exam_name'].' ('.$e['subject_name'].')') ?></option><?php endforeach; ?></select></div>
    </form>
</div></div>

<?php if ($curExam && $marksData): ?>
<div class="card">
    <div class="card-header"><h6 class="card-title"><i class="bi bi-award me-2 text-warning"></i><?= htmlspecialchars($curExam['exam_name']) ?> - <?= htmlspecialchars($curExam['subject_name']) ?> <span class="text-muted small">(Total: <?= $curExam['total_marks'] ?>, Pass: <?= $curExam['pass_marks'] ?>)</span></h6></div>
    <form method="POST">
        <input type="hidden" name="action" value="save_marks"><input type="hidden" name="exam_id" value="<?= $selExam ?>"><input type="hidden" name="class_id" value="<?= $selClass ?>">
        <div class="table-responsive"><table class="table">
            <thead><tr><th>#</th><th>Roll No</th><th>Name</th><th>Marks (/<?= $curExam['total_marks'] ?>)</th><th>Grade</th></tr></thead>
            <tbody>
            <?php $i=1; while ($m=$marksData->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($m['roll_number']) ?></span></td>
                <td class="fw-600"><?= htmlspecialchars($m['name']) ?></td>
                <td><input type="number" name="marks[<?= $m['sid'] ?>]" class="form-control form-control-sm" style="width:110px" min="0" max="<?= $curExam['total_marks'] ?>" step="0.5" value="<?= $m['marks_obtained'] ?? '' ?>"></td>
                <td><span class="grade-cell"><?= $m['grade'] ?? '-' ?></span></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table></div>
        <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Marks</button></div>
    </form>
</div>
<?php endif; ?>
</div>

<div class="modal fade" id="addExamModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST"><input type="hidden" name="action" value="add_exam">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-clipboard-plus me-2"></i>Create Exam</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Exam Name *</label><input type="text" name="exam_name" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Class *</label><select name="class_id" class="form-select" required><option value="">Select</option><?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $selClass==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['class_name'].' - '.$c['section']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Subject *</label><select name="subject_id" class="form-select" required><option value="">Select</option><?php foreach ($subjects as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_name']) ?></option><?php endforeach; ?></select></div>
        <div class="row g-3"><div class="col-4"><label class="form-label">Exam Date</label><input type="date" name="exam_date" class="form-control" required></div><div class="col-4"><label class="form-label">Total</label><input type="number" name="total_marks" class="form-control" value="100"></div><div class="col-4"><label class="form-label">Pass</label><input type="number" name="pass_marks" class="form-control" value="40"></div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Create</button></div>
    </form>
</div></div></div>

<?php include '../includes/footer.php'; ?>
