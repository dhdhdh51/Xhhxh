<?php
require_once '../config/db.php';
requireRole('student');
$pageTitle = 'My Marks';
$db = getDB();
$uid = (int)$_SESSION['user_id'];
$student = $db->query("SELECT s.id,s.class_id FROM students s WHERE s.user_id=$uid")->fetch_assoc();
$sid = (int)($student['id'] ?? 0);

$marks = $db->query("SELECT m.*,e.exam_name,e.total_marks,e.pass_marks,e.exam_date,s.subject_name FROM marks m JOIN exams e ON m.exam_id=e.id JOIN subjects s ON e.subject_id=s.id WHERE m.student_id=$sid ORDER BY e.exam_date DESC");
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent"><div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="page-header"><h2>My Marks &amp; Grades</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Marks</li></ol></nav></div>
<div class="card">
    <div class="card-header"><h6 class="card-title"><i class="bi bi-award me-2 text-warning"></i>Exam Results</h6></div>
    <div class="table-responsive"><table class="table">
        <thead><tr><th>#</th><th>Subject</th><th>Exam</th><th>Date</th><th>Marks Obtained</th><th>Total</th><th>Grade</th><th>Result</th></tr></thead>
        <tbody>
        <?php $i=1; while ($m=$marks->fetch_assoc()):
            $pct = $m['total_marks'] > 0 ? round(($m['marks_obtained']/$m['total_marks'])*100) : 0;
            $pass = $m['marks_obtained'] >= $m['pass_marks'];
        ?>
        <tr>
            <td class="text-muted"><?= $i++ ?></td>
            <td class="fw-600"><?= htmlspecialchars($m['subject_name']) ?></td>
            <td><?= htmlspecialchars($m['exam_name']) ?></td>
            <td class="text-muted small"><?= formatDate($m['exam_date']) ?></td>
            <td class="fw-700"><?= $m['marks_obtained'] ?></td>
            <td class="text-muted"><?= $m['total_marks'] ?></td>
            <td><span class="grade-cell"><?= $m['grade'] ?></span></td>
            <td><span class="badge bg-<?= $pass?'success':'danger' ?>-subtle text-<?= $pass?'success':'danger' ?>"><?= $pass?'Pass':'Fail' ?></span></td>
        </tr>
        <?php endwhile; ?>
        <?php if ($marks->num_rows===0): ?><tr><td colspan="8" class="text-center py-5 text-muted">No marks available yet.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
