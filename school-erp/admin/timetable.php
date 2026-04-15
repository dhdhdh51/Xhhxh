<?php
require_once '../config/db.php';
requireRole(['admin','developer']);
$pageTitle = 'Timetable';
$db = getDB();
$flash = getFlash();
include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="main-content" id="mainContent">
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php if ($flash): ?>
<div class="alert alert-<?php echo $flash['type']==='success'?'success':'danger'; ?> alert-dismissible fade show flash-msg mb-3">
<?php echo htmlspecialchars($flash['message']); ?><button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<div class="page-header"><h2><?php echo ucfirst('timetable'); ?></h2>
<nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active"><?php echo ucfirst('timetable'); ?></li></ol></nav>
</div>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>This page is under construction. Full CRUD for <strong>timetable</strong> module coming soon.</div>
</div>
<?php include '../includes/footer.php'; ?>
