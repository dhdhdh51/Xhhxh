<?php
require_once '../config/db.php';
requireRole('teacher');
$pageTitle = 'Marks';
$db = getDB();
$uid = (int)$_SESSION['user_id'];
$teacher = $db->query("SELECT t.id FROM teachers t WHERE t.user_id=$uid")->fetch_assoc();
$tid = (int)($teacher['id'] ?? 0);

$exams = $db->query("SELECT e.*,s.subject_name,c.class_name,c.section FROM exams e JOIN subjects s ON e.subject_id=s.id JOIN classes c ON e.class_id=c.id WHERE s.teacher_id=$tid ORDER BY e.exam_date DESC")->fetch_all(MYSQLI_ASSOC);
$selExam = (int)($_GET['exam_id'] ?? ($exams[0]['id'] ?? 0));
$curExam = array_filter($exams,fn($e)=>$e['id']==$selExam); $curExam = reset($curExam)?:null;

if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']==='save') {
    $eid = (int)$_POST['exam_id'];
    $eI  = $db->query("SELECT * FROM exams WHERE id=$eid")->fetch_assoc();
    foreach ($_POST['marks'] as $sid => $m) {
        $sid=(int)$sid; $m=(float)$m; $g=getGrade($m,$eI['total_marks']);
        $stmt=$db->prepare("INSERT INTO marks (student_id,exam_id,marks_obtained,grade) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE marks_obtained=VALUES(marks_obtained),grade=VALUES(grade)");
        $stmt->bind_param("iids",$sid,$eid,$m,$g); $stmt->execute();
    }
    setFlash('success','Marks saved!'); redirect(APP_URL."/teacher/marks.php?exam_id=$eid");
}

$marksData = ($curExam) ? $db->query("SELECT s.id as sid,s.roll_number,u.name,m.marks_obtained,m.grade FROM students s JOIN users u ON s.user_id=u.id LEFT JOIN marks m ON m.student_id=s.id AND m.exam_id=$selExam WHERE s.class_id={$curExam['class_id']} ORDER BY s.roll_number") : null;
$flash = getFlash();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent">
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php if ($flash): ?><div class="alert alert-success alert-dismissible fade show flash-msg mb-3"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flash['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="page-header"><h2>Marks &amp; Grades</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Marks</li></ol></nav></div>

<div class="card mb-4"><div class="card-body py-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-5"><label class="form-label">Select Exam</label><select name="exam_id" class="form-select" onchange="this.form.submit()"><option value="">Select</option><?php foreach ($exams as $e): ?><option value="<?= $e['id'] ?>" <?= $selExam==$e['id']?'selected':'' ?>><?= htmlspecialchars($e['exam_name'].' - '.$e['subject_name'].' ('.$e['class_name'].')') ?></option><?php endforeach; ?></select></div>
    </form>
</div></div>

<?php if ($curExam && $marksData): ?>
<div class="card">
    <div class="card-header"><h6 class="card-title"><i class="bi bi-award me-2 text-warning"></i><?= htmlspecialchars($curExam['exam_name']) ?> - <?= htmlspecialchars($curExam['subject_name']) ?> (<?= htmlspecialchars($curExam['class_name'].' - '.$curExam['section']) ?>)</h6></div>
    <form method="POST">
        <input type="hidden" name="action" value="save"><input type="hidden" name="exam_id" value="<?= $selExam ?>">
        <div class="table-responsive"><table class="table">
            <thead><tr><th>Roll No</th><th>Name</th><th>Marks (/<?= $curExam['total_marks'] ?>)</th><th>Grade</th></tr></thead>
            <tbody>
            <?php while ($m=$marksData->fetch_assoc()): ?>
            <tr>
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
<?php else: ?><div class="text-center py-5 text-muted"><i class="bi bi-clipboard-x fs-1 d-block mb-2"></i>Select an exam to enter marks.</div><?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
