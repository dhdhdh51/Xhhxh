<?php
require_once '../config/db.php';
requireRole(['admin','developer']);
$pageTitle = 'Students';
$db = getDB();
$action = $_GET['action'] ?? '';
$editId = (int)($_GET['id'] ?? 0);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pa = $_POST['action'] ?? '';
    if ($pa === 'add' || $pa === 'edit') {
        $name    = dbSanitize($_POST['name'] ?? '');
        $email   = dbSanitize($_POST['email'] ?? '');
        $phone   = dbSanitize($_POST['phone'] ?? '');
        $rollNo  = dbSanitize($_POST['roll_number'] ?? '');
        $classId = (int)($_POST['class_id'] ?? 0);
        $pname   = dbSanitize($_POST['parent_name'] ?? '');
        $pphone  = dbSanitize($_POST['parent_phone'] ?? '');
        $pemail  = dbSanitize($_POST['parent_email'] ?? '');
        $dob     = dbSanitize($_POST['date_of_birth'] ?? '');
        $gender  = dbSanitize($_POST['gender'] ?? '');
        $blood   = dbSanitize($_POST['blood_group'] ?? '');
        $pass    = $_POST['password'] ?? '';

        if (!$name)   $errors[] = 'Name required.';
        if (!$email)  $errors[] = 'Email required.';
        if (!$rollNo) $errors[] = 'Roll number required.';
        if (!$classId) $errors[] = 'Select a class.';

        if (!$errors) {
            if ($pa === 'add') {
                if (!$pass) { $errors[] = 'Password required.'; }
                else {
                    $hash = password_hash($pass, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("INSERT INTO users (name,email,password,role,phone) VALUES (?,?,?,'student',?)");
                    $stmt->bind_param("ssss", $name, $email, $hash, $phone);
                    if ($stmt->execute()) {
                        $uid = $db->insert_id;
                        $stmt2 = $db->prepare("INSERT INTO students (user_id,roll_number,class_id,parent_name,parent_phone,parent_email,date_of_birth,gender,blood_group) VALUES (?,?,?,?,?,?,?,?,?)");
                        $stmt2->bind_param("isisssss", $uid, $rollNo, $classId, $pname, $pphone, $pemail, $dob, $gender, $blood);
                        $stmt2->execute();
                        setFlash('success', 'Student added!');
                        redirect(APP_URL.'/admin/students.php');
                    } else { $errors[] = 'Email already exists.'; }
                }
            } else {
                $sid = (int)$_POST['student_id'];
                $uid = (int)$_POST['user_id'];
                $db->prepare("UPDATE users SET name=?,email=?,phone=? WHERE id=?")->bind_param("sssi",$name,$email,$phone,$uid) && $db->prepare("UPDATE users SET name=?,email=?,phone=? WHERE id=?")->execute();
                $stmt = $db->prepare("UPDATE users SET name=?,email=?,phone=? WHERE id=?");
                $stmt->bind_param("sssi",$name,$email,$phone,$uid); $stmt->execute();
                $stmt2 = $db->prepare("UPDATE students SET roll_number=?,class_id=?,parent_name=?,parent_phone=?,parent_email=?,date_of_birth=?,gender=?,blood_group=? WHERE id=?");
                $stmt2->bind_param("sissssssi",$rollNo,$classId,$pname,$pphone,$pemail,$dob,$gender,$blood,$sid); $stmt2->execute();
                setFlash('success','Student updated!');
                redirect(APP_URL.'/admin/students.php');
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $uid = (int)$_POST['user_id'];
        $db->query("DELETE FROM users WHERE id=$uid");
        setFlash('success','Student deleted!');
        redirect(APP_URL.'/admin/students.php');
    }
}

$editStudent = null;
if ($action === 'edit' && $editId > 0) {
    $res = $db->query("SELECT s.*,u.name,u.email,u.phone FROM students s JOIN users u ON s.user_id=u.id WHERE s.id=$editId");
    $editStudent = $res ? $res->fetch_assoc() : null;
}

$search  = sanitize($_GET['q'] ?? '');
$cFilter = (int)($_GET['class_id'] ?? 0);
$where   = "WHERE 1=1";
if ($search)  $where .= " AND (u.name LIKE '%".dbSanitize($search)."%' OR s.roll_number LIKE '%".dbSanitize($search)."%')";
if ($cFilter) $where .= " AND s.class_id=$cFilter";

$students = $db->query("SELECT s.*,u.name,u.email,u.phone,u.status,c.class_name,c.section FROM students s JOIN users u ON s.user_id=u.id JOIN classes c ON s.class_id=c.id $where ORDER BY s.roll_number");
$classes  = $db->query("SELECT * FROM classes ORDER BY class_name,section")->fetch_all(MYSQLI_ASSOC);
$flash    = getFlash();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent">
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php if ($flash): ?><div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show flash-msg mb-3"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flash['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-danger flash-msg alert-dismissible mb-3"><?= implode('<br>',$errors) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div><h2>Students</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Students</li></ol></nav></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-circle me-1"></i>Add Student</button>
</div>

<div class="card mb-3"><div class="card-body py-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-5"><input type="text" name="q" class="form-control" placeholder="Search by name or roll no..." value="<?= htmlspecialchars($search) ?>"></div>
        <div class="col-md-3"><select name="class_id" class="form-select"><option value="">All Classes</option><?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $cFilter==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['class_name'].' - '.$c['section']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i>Filter</button></div>
        <div class="col-md-2"><a href="students.php" class="btn btn-outline-secondary w-100">Clear</a></div>
    </form>
</div></div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title"><i class="bi bi-people me-2 text-primary"></i>All Students</h6>
        <span class="badge bg-primary"><?= $students->num_rows ?></span>
    </div>
    <div class="table-responsive">
        <table class="table searchable">
            <thead><tr><th>#</th><th>Roll No</th><th>Name</th><th>Email</th><th>Class</th><th>Parent</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php $i=1; while ($s=$students->fetch_assoc()): ?>
            <tr>
                <td class="text-muted"><?= $i++ ?></td>
                <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($s['roll_number']) ?></span></td>
                <td class="fw-600"><?= htmlspecialchars($s['name']) ?></td>
                <td class="text-muted small"><?= htmlspecialchars($s['email']) ?></td>
                <td><?= htmlspecialchars($s['class_name'].' - '.$s['section']) ?></td>
                <td><div class="small"><?= htmlspecialchars($s['parent_name']??'-') ?></div><div class="text-muted fs-12"><?= htmlspecialchars($s['parent_phone']??'') ?></div></td>
                <td><span class="badge bg-<?= $s['status']==='active'?'success':'danger' ?>-subtle text-<?= $s['status']==='active'?'success':'danger' ?>"><?= ucfirst($s['status']) ?></span></td>
                <td>
                    <a href="?action=edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon me-1"><i class="bi bi-pencil"></i></a>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this student?')">
                        <input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?= $s['user_id'] ?>">
                        <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if ($students->num_rows===0): ?><tr><td colspan="8" class="text-center py-5 text-muted">No students found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST"><input type="hidden" name="action" value="add">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Roll Number *</label><input type="text" name="roll_number" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Class *</label>
            <select name="class_id" class="form-select" required><option value="">Select</option><?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name'].' - '.$c['section']) ?></option><?php endforeach; ?></select>
        </div>
        <div class="col-md-4"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Gender</label><select name="gender" class="form-select"><option value="">Select</option><?php foreach (['Male','Female','Other'] as $g): ?><option><?= $g ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Blood Group</label><select name="blood_group" class="form-select"><option value="">Select</option><?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?><option><?= $bg ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Parent Name</label><input type="text" name="parent_name" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Parent Phone</label><input type="text" name="parent_phone" class="form-control"></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Add</button></div>
    </form>
</div></div></div>

<?php if ($editStudent): ?>
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="student_id" value="<?= $editStudent['id'] ?>"><input type="hidden" name="user_id" value="<?= $editStudent['user_id'] ?>">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editStudent['name']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editStudent['email']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Roll No</label><input type="text" name="roll_number" class="form-control" value="<?= htmlspecialchars($editStudent['roll_number']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Class</label><select name="class_id" class="form-select" required><?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $editStudent['class_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['class_name'].' - '.$c['section']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($editStudent['phone']??'') ?>"></div>
        <div class="col-md-4"><label class="form-label">DOB</label><input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($editStudent['date_of_birth']??'') ?>"></div>
        <div class="col-md-4"><label class="form-label">Gender</label><select name="gender" class="form-select"><?php foreach (['Male','Female','Other'] as $g): ?><option <?= $editStudent['gender']===$g?'selected':'' ?>><?= $g ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Blood</label><select name="blood_group" class="form-select"><?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?><option <?= $editStudent['blood_group']===$bg?'selected':'' ?>><?= $bg ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Parent Name</label><input type="text" name="parent_name" class="form-control" value="<?= htmlspecialchars($editStudent['parent_name']??'') ?>"></div>
        <div class="col-md-4"><label class="form-label">Parent Phone</label><input type="text" name="parent_phone" class="form-control" value="<?= htmlspecialchars($editStudent['parent_phone']??'') ?>"></div>
        <div class="col-md-4"><label class="form-label">Parent Email</label><input type="email" name="parent_email" class="form-control" value="<?= htmlspecialchars($editStudent['parent_email']??'') ?>"></div>
    </div></div>
    <div class="modal-footer"><a href="students.php" class="btn btn-secondary">Cancel</a><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
</div></div></div>
<script>document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('editModal')).show());</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
