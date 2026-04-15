<?php
require_once '../config/db.php';
requireRole('student');
$pageTitle = 'Timetable';
$db = getDB();
$uid = (int)$_SESSION['user_id'];
$student = $db->query("SELECT s.class_id FROM students s WHERE s.user_id=$uid")->fetch_assoc();
$cid = (int)($student['class_id'] ?? 0);
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$tt = [];
$res = $db->query("SELECT t.*,s.subject_name,u.name as teacher_name FROM timetable t JOIN subjects s ON t.subject_id=s.id LEFT JOIN teachers tr ON t.teacher_id=tr.id LEFT JOIN users u ON tr.user_id=u.id WHERE t.class_id=$cid ORDER BY t.day_of_week,t.start_time");
while ($r=$res->fetch_assoc()) $tt[$r['day_of_week']][] = $r;
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent"><div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="page-header"><h2>My Timetable</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Timetable</li></ol></nav></div>
<?php if (empty(array_filter($tt))): ?>
<div class="card"><div class="card-body text-center py-5 text-muted"><i class="bi bi-calendar3 fs-1 d-block mb-2"></i>No timetable available yet. Contact your teacher or admin.</div></div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($days as $day): ?>
<div class="col-md-4 col-xl-2">
    <div class="card h-100">
        <div class="card-header" style="<?= date('l')===$day?'background:#dbeafe':'' ?>"><h6 class="card-title mb-0 small fw-700"><?= $day ?><?= date('l')===$day?' <span class="badge bg-primary ms-1 fs-12">Today</span>':'' ?></h6></div>
        <div class="card-body p-2">
        <?php if (!empty($tt[$day])): foreach ($tt[$day] as $slot): ?>
        <div class="bg-primary bg-opacity-10 rounded p-2 mb-2 small">
            <div class="fw-600 text-primary"><?= htmlspecialchars($slot['subject_name']) ?></div>
            <?php if ($slot['teacher_name']): ?><div class="text-muted fs-12"><i class="bi bi-person me-1"></i><?= htmlspecialchars($slot['teacher_name']) ?></div><?php endif; ?>
            <div class="text-muted fs-12"><i class="bi bi-clock me-1"></i><?= date('h:i A',strtotime($slot['start_time'])) ?> - <?= date('h:i A',strtotime($slot['end_time'])) ?></div>
        </div>
        <?php endforeach; else: ?><p class="text-muted small text-center py-2 mb-0">Free Period</p><?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
