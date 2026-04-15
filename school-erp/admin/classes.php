<?php
require_once '../config/db.php';
requireRole(['admin','developer']);
$pageTitle = 'Classes';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pa = $_POST['action'] ?? '';
    if ($pa === 'add') {
        $cn  = dbSanitize($_POST['class_name'] ?? '');
        $sec = dbSanitize($_POST['section'] ?? '');
        $tid = (int)($_POST['class_teacher_id'] ?? 0) ?: null;
        $seats = (int)($_POST['seats'] ?? 40);
        $stmt = $db->prepare("INSERT INTO classes (class_name,section,class_teacher_id,seats) VALUES (?,?,?,?)");
        $stmt->bind_param("ssii",$cn,$sec,$tid,$seats);
        setFlash($stmt->execute() ? 'success' : 'error', $stmt->execute() ? 'Class added!' : 'Class exists.');
        redirect(APP_URL.'/admin/classes.php');
    } elseif ($pa === 'delete') {
        $id = (int)$_POST['class_id'];
        $db->query("DELETE FROM classes WHERE id=$id");
        setFlash('success','Class deleted!');
        redirect(APP_URL.'/admin/classes.php');
    }
}

$classes  = $db->query("SELECT c.*,u.name as tname,(SELECT COUNT(*) FROM students s WHERE s.class_id=c.id) as scount FROM classes c LEFT JOIN teachers t ON c.class_teacher_id=t.id LEFT JOIN users u ON t.user_id=u.id ORDER BY c.class_name,c.section");
$teachers = $db->query("SELECT t.id,u.name FROM teachers t JOIN users u ON t.user_id=u.id ORDER BY u.name")->fetch_all(MYSQLI_ASSOC);
$flash = getFlash();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent">
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php if ($flash): ?><div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show flash-msg mb-3"><?= htmlspecialchars($flash['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div><h2>Classes &amp; Sections</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Classes</li></ol></nav></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-circle me-1"></i>Add Class</button>
</div>

<div class="row g-3">
<?php while ($c=$classes->fetch_assoc()): ?>
<div class="col-xl-3 col-md-4 col-sm-6">
    <div class="card h-100">
        <div class="card-body text-center p-4">
            <div class="bg-primary bg-opacity-10 rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:56px;height:56px">
                <i class="bi bi-building text-primary fs-4"></i>
            </div>
            <h5 class="fw-700"><?= htmlspecialchars($c['class_name']) ?></h5>
            <p class="text-muted mb-2">Section: <strong><?= htmlspecialchars($c['section']) ?></strong></p>
            <div class="d-flex justify-content-center gap-4 mb-3">
                <div><div class="fw-700 text-primary"><?= $c['scount'] ?></div><div class="small text-muted">Students</div></div>
                <div><div class="fw-700 text-success"><?= $c['seats'] ?></div><div class="small text-muted">Seats</div></div>
            </div>
            <?= $c['tname'] ? '<span class="badge bg-success-subtle text-success small"><i class="bi bi-person me-1"></i>'.htmlspecialchars($c['tname']).'</span>' : '<span class="badge bg-secondary-subtle text-secondary small">No class teacher</span>' ?>
        </div>
        <div class="card-footer bg-transparent d-flex justify-content-end">
            <form method="POST" onsubmit="return confirm('Delete class?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="class_id" value="<?= $c['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button></form>
        </div>
    </div>
</div>
<?php endwhile; ?>
</div>
</div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST"><input type="hidden" name="action" value="add">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-building me-2"></i>Add Class</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Class Name *</label><input type="text" name="class_name" class="form-control" required placeholder="e.g. Class 1, Grade 5"></div>
        <div class="mb-3"><label class="form-label">Section *</label><input type="text" name="section" class="form-control" required placeholder="e.g. A, B, Science"></div>
        <div class="mb-3"><label class="form-label">Total Seats</label><input type="number" name="seats" class="form-control" value="40" min="1"></div>
        <div class="mb-3"><label class="form-label">Class Teacher</label>
            <select name="class_teacher_id" class="form-select"><option value="">Optional</option><?php foreach ($teachers as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add Class</button></div>
    </form>
</div></div></div>

<?php include '../includes/footer.php'; ?>
