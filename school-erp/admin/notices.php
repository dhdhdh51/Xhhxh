<?php
require_once '../config/db.php';
requireRole(['admin','developer']);
$pageTitle = 'Notice Board';
$db = getDB();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $pa = $_POST['action'] ?? '';
    if ($pa === 'add') {
        $title   = dbSanitize($_POST['title']??''); $content = dbSanitize($_POST['content']??'');
        $target  = dbSanitize($_POST['target_role']??'all'); $uid = $_SESSION['user_id'];
        $stmt = $db->prepare("INSERT INTO notices (title,content,target_role,published_by) VALUES (?,?,?,?)");
        $stmt->bind_param("sssi",$title,$content,$target,$uid); $stmt->execute();
        setFlash('success','Notice published!');
    } elseif ($pa === 'delete') {
        $db->query("DELETE FROM notices WHERE id=".(int)$_POST['notice_id']);
        setFlash('success','Notice deleted!');
    }
    redirect(APP_URL.'/admin/notices.php');
}
$notices = $db->query("SELECT n.*,u.name as author FROM notices n JOIN users u ON n.published_by=u.id ORDER BY n.created_at DESC");
$flash = getFlash();
$rColors = ['all'=>'secondary','admin'=>'danger','teacher'=>'primary','student'=>'success','developer'=>'dev'];
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent">
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php if ($flash): ?><div class="alert alert-success alert-dismissible fade show flash-msg mb-3"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flash['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <div><h2>Notice Board</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Notices</li></ol></nav></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-megaphone me-1"></i>Post Notice</button>
</div>
<div class="row g-3">
<?php while ($n=$notices->fetch_assoc()): $col = $rColors[$n['target_role']] ?? 'secondary'; ?>
<div class="col-md-6 col-xl-4">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge bg-<?= $col ?>-subtle text-<?= $col ?>"><?= ucfirst($n['target_role']) ?></span>
                <small class="text-muted"><?= formatDate($n['created_at']) ?></small>
            </div>
            <h6 class="fw-700 mb-2"><?= htmlspecialchars($n['title']) ?></h6>
            <p class="text-muted small mb-3"><?= nl2br(htmlspecialchars(mb_strimwidth($n['content'],0,150,'...'))) ?></p>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($n['author']) ?></small>
                <form method="POST" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="notice_id" value="<?= $n['id'] ?>"><button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button></form>
            </div>
        </div>
    </div>
</div>
<?php endwhile; ?>
</div>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST"><input type="hidden" name="action" value="add">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-megaphone me-2"></i>Post Notice</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Content *</label><textarea name="content" class="form-control" rows="4" required></textarea></div>
        <div class="mb-3"><label class="form-label">Target Audience</label><select name="target_role" class="form-select"><?php foreach (['all','student','teacher','parent','admin'] as $r): ?><option value="<?= $r ?>"><?= ucfirst($r) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Publish</button></div>
    </form>
</div></div></div>
<?php include '../includes/footer.php'; ?>
