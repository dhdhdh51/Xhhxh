<?php
require_once '../config/db.php';
requireRole(['admin','developer']);
$pageTitle = 'Subjects';
$db = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pa = $_POST['action'] ?? '';
    if ($pa === 'add') {
        $sn = dbSanitize($_POST['subject_name']??''); $sc = dbSanitize($_POST['subject_code']??'');
        $ci = (int)$_POST['class_id']; $ti = (int)($_POST['teacher_id']??0)?:null;
        $stmt = $db->prepare("INSERT INTO subjects (subject_name,subject_code,class_id,teacher_id) VALUES (?,?,?,?)");
        $stmt->bind_param("ssii",$sn,$sc,$ci,$ti);
        setFlash($stmt->execute()?'success':'error',$stmt->execute()?'Subject added!':'Code exists.');
        redirect(APP_URL.'/admin/subjects.php');
    } elseif ($pa === 'delete') {
        $db->query("DELETE FROM subjects WHERE id=".(int)$_POST['subject_id']);
        setFlash('success','Subject deleted!'); redirect(APP_URL.'/admin/subjects.php');
    }
}
$subjects = $db->query("SELECT s.*,c.class_name,c.section,u.name as tname FROM subjects s JOIN classes c ON s.class_id=c.id LEFT JOIN teachers t ON s.teacher_id=t.id LEFT JOIN users u ON t.user_id=u.id ORDER BY c.class_name,c.section,s.subject_name");
$classes  = $db->query("SELECT * FROM classes ORDER BY class_name,section")->fetch_all(MYSQLI_ASSOC);
$teachers = $db->query("SELECT t.id,u.name FROM teachers t JOIN users u ON t.user_id=u.id ORDER BY u.name")->fetch_all(MYSQLI_ASSOC);
$flash = getFlash();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent">
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php if ($flash): ?><div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show flash-msg mb-3"><?= htmlspecialchars($flash['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <div><h2>Subjects</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Subjects</li></ol></nav></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-circle me-1"></i>Add Subject</button>
</div>
<div class="card">
    <div class="card-header"><h6 class="card-title"><i class="bi bi-book me-2 text-primary"></i>All Subjects</h6></div>
    <div class="table-responsive"><table class="table">
        <thead><tr><th>#</th><th>Code</th><th>Subject</th><th>Class</th><th>Teacher</th><th>Action</th></tr></thead>
        <tbody>
        <?php $i=1; while ($s=$subjects->fetch_assoc()): ?>
        <tr>
            <td class="text-muted"><?= $i++ ?></td>
            <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($s['subject_code']) ?></span></td>
            <td class="fw-600"><?= htmlspecialchars($s['subject_name']) ?></td>
            <td><?= htmlspecialchars($s['class_name'].' - '.$s['section']) ?></td>
            <td><?= $s['tname'] ? htmlspecialchars($s['tname']) : '<span class="text-muted small">Not assigned</span>' ?></td>
            <td><form method="POST" class="d-inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="subject_id" value="<?= $s['id'] ?>"><button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button></form></td>
        </tr>
        <?php endwhile; ?>
        <?php if ($subjects->num_rows===0): ?><tr><td colspan="6" class="text-center py-5 text-muted">No subjects found.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST"><input type="hidden" name="action" value="add">
    <div class="modal-header"><h5 class="modal-title">Add Subject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Subject Name *</label><input type="text" name="subject_name" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Subject Code *</label><input type="text" name="subject_code" class="form-control" required placeholder="e.g. MATH01"></div>
        <div class="mb-3"><label class="form-label">Class *</label><select name="class_id" class="form-select" required><option value="">Select</option><?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name'].' - '.$c['section']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Assign Teacher</label><select name="teacher_id" class="form-select"><option value="">Optional</option><?php foreach ($teachers as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add</button></div>
    </form>
</div></div></div>
<?php include '../includes/footer.php'; ?>
