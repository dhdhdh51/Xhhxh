<?php
require_once '../config/db.php';
requireRole('teacher');
$pageTitle = 'My Students';
$db = getDB();
$uid = (int)$_SESSION['user_id'];
$teacher = $db->query("SELECT t.id FROM teachers t WHERE t.user_id=$uid")->fetch_assoc();
$tid = (int)($teacher['id'] ?? 0);
$students = $db->query("SELECT DISTINCT s.*,u.name,u.email,c.class_name,c.section FROM students s JOIN users u ON s.user_id=u.id JOIN classes c ON s.class_id=c.id JOIN subjects sub ON sub.class_id=c.id WHERE sub.teacher_id=$tid ORDER BY c.class_name,s.roll_number");
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent"><div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="page-header"><h2>My Students</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Students</li></ol></nav></div>
<div class="card">
    <div class="card-header d-flex justify-content-between"><h6 class="card-title"><i class="bi bi-people me-2 text-primary"></i>Students I Teach</h6><span class="badge bg-primary"><?= $students->num_rows ?></span></div>
    <div class="table-responsive"><table class="table">
        <thead><tr><th>Roll No</th><th>Name</th><th>Email</th><th>Class</th></tr></thead>
        <tbody>
        <?php while ($s=$students->fetch_assoc()): ?>
        <tr><td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($s['roll_number']) ?></span></td><td class="fw-600"><?= htmlspecialchars($s['name']) ?></td><td class="text-muted small"><?= htmlspecialchars($s['email']) ?></td><td><?= htmlspecialchars($s['class_name'].' - '.$s['section']) ?></td></tr>
        <?php endwhile; ?>
        </tbody>
    </table></div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
