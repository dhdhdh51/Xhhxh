<?php
require_once '../config/db.php';
requireRole('teacher');
$pageTitle = 'Teacher Dashboard';
$db = getDB();
$uid = (int)$_SESSION['user_id'];

$teacher = $db->query("SELECT t.* FROM teachers t WHERE t.user_id=$uid")->fetch_assoc();
$tid = $teacher['id'] ?? 0;

$mySubjects  = $db->query("SELECT s.subject_name, c.class_name, c.section FROM subjects s JOIN classes c ON s.class_id=c.id WHERE s.teacher_id=$tid")->num_rows ?? 0;
$myClasses   = $db->query("SELECT DISTINCT c.id FROM subjects s JOIN classes c ON s.class_id=c.id WHERE s.teacher_id=$tid")->num_rows ?? 0;
$todayMarked = $db->query("SELECT COUNT(DISTINCT a.student_id) as c FROM attendance a JOIN students s ON a.student_id=s.id JOIN classes c2 ON s.class_id=c2.id JOIN subjects sub ON sub.class_id=c2.id WHERE sub.teacher_id=$tid AND a.date=CURDATE() AND a.marked_by=$uid")->fetch_assoc()['c'] ?? 0;
$notices = $db->query("SELECT n.*,u.name as author FROM notices n JOIN users u ON n.published_by=u.id WHERE n.target_role IN ('all','teacher') AND n.is_published=1 ORDER BY n.created_at DESC LIMIT 5");

$flash = getFlash();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent">
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php if ($flash): ?><div class="alert alert-success alert-dismissible fade show flash-msg mb-3"><?= htmlspecialchars($flash['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div><h2>Welcome, <?= htmlspecialchars(explode(' ',$_SESSION['name'])[0]) ?>!</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item active">Dashboard</li></ol></nav></div>
    <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= date('D, d M Y') ?></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between mb-3"><div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-book-fill"></i></div><span class="badge bg-primary-subtle text-primary">Subjects</span></div>
            <div class="stat-value text-primary"><?= $mySubjects ?></div><div class="stat-label">My Subjects</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between mb-3"><div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-building-fill"></i></div><span class="badge bg-success-subtle text-success">Classes</span></div>
            <div class="stat-value text-success"><?= $myClasses ?></div><div class="stat-label">Classes I Teach</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between mb-3"><div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-calendar-check-fill"></i></div><span class="badge bg-warning-subtle text-warning">Today</span></div>
            <div class="stat-value text-warning"><?= $todayMarked ?></div><div class="stat-label">Attendance Marked Today</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between"><h6 class="card-title"><i class="bi bi-book me-2 text-primary"></i>My Subjects & Classes</h6></div>
            <div class="table-responsive"><table class="table">
                <thead><tr><th>Subject</th><th>Class</th><th>Action</th></tr></thead>
                <tbody>
                <?php
                $subs = $db->query("SELECT s.id as sid, s.subject_name, c.id as cid, c.class_name, c.section FROM subjects s JOIN classes c ON s.class_id=c.id WHERE s.teacher_id=$tid ORDER BY c.class_name,s.subject_name");
                if ($subs) while ($s=$subs->fetch_assoc()):
                ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($s['subject_name']) ?></td>
                    <td><?= htmlspecialchars($s['class_name'].' - '.$s['section']) ?></td>
                    <td><a href="attendance.php?class_id=<?= $s['cid'] ?>" class="btn btn-sm btn-outline-primary">Mark Attendance</a></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h6 class="card-title"><i class="bi bi-megaphone me-2 text-warning"></i>Notices</h6></div>
            <div class="card-body p-0"><ul class="list-group list-group-flush">
                <?php while ($n=$notices->fetch_assoc()): ?>
                <li class="list-group-item px-4 py-3">
                    <div class="fw-600 small"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="text-muted fs-12"><?= htmlspecialchars($n['author']) ?> &bull; <?= formatDate($n['created_at']) ?></div>
                </li>
                <?php endwhile; ?>
            </ul></div>
        </div>
    </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
