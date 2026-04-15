<?php
require_once '../config/db.php';
requireRole('teacher');
$pageTitle = 'Timetable';
$db = getDB();
$uid = (int)$_SESSION['user_id'];
$teacher = $db->query("SELECT t.id FROM teachers t WHERE t.user_id=$uid")->fetch_assoc();
$tid = (int)($teacher['id'] ?? 0);
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$tt   = [];
$res  = $db->query("SELECT t.*,s.subject_name,c.class_name,c.section FROM timetable t JOIN subjects s ON t.subject_id=s.id JOIN classes c ON t.class_id=c.id WHERE t.teacher_id=$tid ORDER BY t.day_of_week,t.start_time");
while ($r=$res->fetch_assoc()) $tt[$r['day_of_week']][] = $r;
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent"><div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="page-header"><h2>My Timetable</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Timetable</li></ol></nav></div>
<div class="row g-3">
<?php foreach ($days as $day): ?>
<div class="col-md-4 col-xl-2">
    <div class="card h-100">
        <div class="card-header" style="background:<?= date('l')===$day?'#dbeafe':'' ?>"><h6 class="card-title mb-0 small fw-700"><?= $day ?><?= date('l')===$day?' <span class="badge bg-primary ms-1">Today</span>':'' ?></h6></div>
        <div class="card-body p-2">
        <?php if (!empty($tt[$day])): foreach ($tt[$day] as $slot): ?>
        <div class="bg-primary bg-opacity-10 rounded p-2 mb-2 small">
            <div class="fw-600 text-primary"><?= htmlspecialchars($slot['subject_name']) ?></div>
            <div class="text-muted fs-12"><?= htmlspecialchars($slot['class_name'].' - '.$slot['section']) ?></div>
            <div class="text-muted fs-12"><i class="bi bi-clock me-1"></i><?= date('h:i A',strtotime($slot['start_time'])) ?> - <?= date('h:i A',strtotime($slot['end_time'])) ?></div>
        </div>
        <?php endforeach; else: ?><p class="text-muted small text-center py-2 mb-0">Free</p><?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
</div>
<?php include '../includes/footer.php'; ?>
