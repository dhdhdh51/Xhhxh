<?php
require_once '../config/db.php';
requireRole(['admin','developer']);
$pageTitle = 'Teachers';
$db = getDB();
$action = $_GET['action'] ?? '';
$editId = (int)($_GET['id'] ?? 0);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pa = $_POST['action'] ?? '';
    if ($pa === 'add' || $pa === 'edit') {
        $name  = dbSanitize($_POST['name'] ?? '');
        $email = dbSanitize($_POST['email'] ?? '');
        $phone = dbSanitize($_POST['phone'] ?? '');
        $empId = dbSanitize($_POST['employee_id'] ?? '');
        $qual  = dbSanitize($_POST['qualification'] ?? '');
        $exp   = (int)($_POST['experience_years'] ?? 0);
        $join  = dbSanitize($_POST['join_date'] ?? '');
        $sal   = (float)($_POST['salary'] ?? 0);
        $pass  = $_POST['password'] ?? '';

        if (!$name)  $errors[] = 'Name required.';
        if (!$email) $errors[] = 'Email required.';
        if (!$empId) $errors[] = 'Employee ID required.';

        if (!$errors) {
            if ($pa === 'add') {
                if (!$pass) { $errors[] = 'Password required.'; }
                else {
                    $hash = password_hash($pass, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("INSERT INTO users (name,email,password,role,phone) VALUES (?,?,?,'teacher',?)");
                    $stmt->bind_param("ssss", $name, $email, $hash, $phone);
                    if ($stmt->execute()) {
                        $uid = $db->insert_id;
                        $stmt2 = $db->prepare("INSERT INTO teachers (user_id,employee_id,qualification,experience_years,join_date,salary) VALUES (?,?,?,?,?,?)");
                        $stmt2->bind_param("issids", $uid, $empId, $qual, $exp, $join, $sal);
                        $stmt2->execute();
                        setFlash('success','Teacher added!');
                        redirect(APP_URL.'/admin/teachers.php');
                    } else { $errors[] = 'Email already exists.'; }
                }
            } else {
                $tid = (int)$_POST['teacher_id'];
                $uid = (int)$_POST['user_id'];
                $stmt = $db->prepare("UPDATE users SET name=?,email=?,phone=? WHERE id=?");
                $stmt->bind_param("sssi",$name,$email,$phone,$uid); $stmt->execute();
                $stmt2 = $db->prepare("UPDATE teachers SET employee_id=?,qualification=?,experience_years=?,join_date=?,salary=? WHERE id=?");
                $stmt2->bind_param("ssidsi",$empId,$qual,$exp,$join,$sal,$tid); $stmt2->execute();
                setFlash('success','Teacher updated!');
                redirect(APP_URL.'/admin/teachers.php');
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $uid = (int)$_POST['user_id'];
        $db->query("DELETE FROM users WHERE id=$uid");
        setFlash('success','Teacher deleted!');
        redirect(APP_URL.'/admin/teachers.php');
    }
}

$editTeacher = null;
if ($action === 'edit' && $editId > 0) {
    $res = $db->query("SELECT t.*,u.name,u.email,u.phone FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.id=$editId");
    $editTeacher = $res ? $res->fetch_assoc() : null;
}

$teachers = $db->query("SELECT t.*,u.name,u.email,u.phone,u.status FROM teachers t JOIN users u ON t.user_id=u.id ORDER BY u.name");
$flash = getFlash();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent">
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php if ($flash): ?><div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show flash-msg mb-3"><?= htmlspecialchars($flash['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-danger flash-msg alert-dismissible mb-3"><?= implode('<br>',$errors) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div><h2>Teachers</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Teachers</li></ol></nav></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-circle me-1"></i>Add Teacher</button>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between"><h6 class="card-title"><i class="bi bi-person-badge me-2 text-primary"></i>All Teachers</h6><span class="badge bg-primary"><?= $teachers->num_rows ?></span></div>
    <div class="table-responsive"><table class="table">
        <thead><tr><th>#</th><th>Emp ID</th><th>Name</th><th>Email</th><th>Qualification</th><th>Experience</th><th>Salary</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php $i=1; while ($t=$teachers->fetch_assoc()): ?>
        <tr>
            <td class="text-muted"><?= $i++ ?></td>
            <td><span class="badge bg-success-subtle text-success"><?= htmlspecialchars($t['employee_id']) ?></span></td>
            <td class="fw-600"><?= htmlspecialchars($t['name']) ?></td>
            <td class="text-muted small"><?= htmlspecialchars($t['email']) ?></td>
            <td class="small"><?= htmlspecialchars($t['qualification']??'-') ?></td>
            <td><?= $t['experience_years'] ?> yrs</td>
            <td>&#8377;<?= number_format($t['salary'],2) ?></td>
            <td><span class="badge bg-<?= $t['status']==='active'?'success':'danger' ?>-subtle text-<?= $t['status']==='active'?'success':'danger' ?>"><?= ucfirst($t['status']) ?></span></td>
            <td>
                <a href="?action=edit&id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon me-1"><i class="bi bi-pencil"></i></a>
                <form method="POST" class="d-inline" onsubmit="return confirm('Delete teacher?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?= $t['user_id'] ?>"><button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button></form>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($teachers->num_rows===0): ?><tr><td colspan="9" class="text-center py-5 text-muted">No teachers found.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
</div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST"><input type="hidden" name="action" value="add">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Add Teacher</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Employee ID *</label><input type="text" name="employee_id" class="form-control" required placeholder="EMP001"></div>
        <div class="col-md-4"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Experience (yrs)</label><input type="number" name="experience_years" class="form-control" value="0" min="0"></div>
        <div class="col-md-3"><label class="form-label">Salary (&#8377;)</label><input type="number" name="salary" class="form-control" step="0.01" value="0"></div>
        <div class="col-md-4"><label class="form-label">Join Date</label><input type="date" name="join_date" class="form-control"></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add Teacher</button></div>
    </form>
</div></div></div>

<?php if ($editTeacher): ?>
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="teacher_id" value="<?= $editTeacher['id'] ?>"><input type="hidden" name="user_id" value="<?= $editTeacher['user_id'] ?>">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Teacher</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editTeacher['name']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editTeacher['email']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Employee ID</label><input type="text" name="employee_id" class="form-control" value="<?= htmlspecialchars($editTeacher['employee_id']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($editTeacher['phone']??'') ?>"></div>
        <div class="col-md-4"><label class="form-label">Experience</label><input type="number" name="experience_years" class="form-control" value="<?= $editTeacher['experience_years'] ?>"></div>
        <div class="col-md-6"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($editTeacher['qualification']??'') ?>"></div>
        <div class="col-md-3"><label class="form-label">Salary</label><input type="number" name="salary" class="form-control" value="<?= $editTeacher['salary'] ?>" step="0.01"></div>
        <div class="col-md-3"><label class="form-label">Join Date</label><input type="date" name="join_date" class="form-control" value="<?= $editTeacher['join_date']??'' ?>"></div>
    </div></div>
    <div class="modal-footer"><a href="teachers.php" class="btn btn-secondary">Cancel</a><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
</div></div></div>
<script>document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('editModal')).show());</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
