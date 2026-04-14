<?php
require_once '../config/db.php';
requireRole('teacher');
$pageTitle = 'Notices';
$db = getDB();
$notices = $db->query("SELECT n.*,u.name as author FROM notices n JOIN users u ON n.published_by=u.id WHERE n.target_role IN ('all','teacher') AND n.is_published=1 ORDER BY n.created_at DESC");
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent"><div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="page-header"><h2>Notice Board</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Notices</li></ol></nav></div>
<div class="row g-3">
<?php while ($n=$notices->fetch_assoc()): ?>
<div class="col-md-6 col-xl-4"><div class="card h-100"><div class="card-body">
    <div class="d-flex justify-content-between mb-2"><span class="badge bg-secondary-subtle text-secondary"><?= ucfirst($n['target_role']) ?></span><small class="text-muted"><?= formatDate($n['created_at']) ?></small></div>
    <h6 class="fw-700"><?= htmlspecialchars($n['title']) ?></h6>
    <p class="text-muted small"><?= nl2br(htmlspecialchars($n['content'])) ?></p>
    <small class="text-muted"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($n['author']) ?></small>
</div></div></div>
<?php endwhile; ?>
</div>
</div>
<?php include '../includes/footer.php'; ?>
