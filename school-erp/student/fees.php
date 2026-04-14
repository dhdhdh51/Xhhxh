<?php
require_once '../config/db.php';
requireRole('student');
$pageTitle = 'My Fees';
$db = getDB();
$uid = (int)$_SESSION['user_id'];
$student = $db->query("SELECT s.id FROM students s WHERE s.user_id=$uid")->fetch_assoc();
$sid = (int)($student['id'] ?? 0);

$fees = $db->query("SELECT * FROM fees WHERE student_id=$sid ORDER BY created_at DESC");
$smry = $db->query("SELECT status,SUM(amount) as s FROM fees WHERE student_id=$sid GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$fsum = ['Paid'=>0,'Pending'=>0,'Overdue'=>0]; foreach ($smry as $s) $fsum[$s['status']] = $s['s'];
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent"><div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="page-header"><h2>My Fees</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Fees</li></ol></nav></div>

<div class="row g-3 mb-4">
    <?php foreach ([['Paid','success','cash-coin'],['Pending','warning','clock'],['Overdue','danger','exclamation']] as [$s,$c,$ic]): ?>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between mb-2"><div class="stat-icon bg-<?= $c ?> bg-opacity-10 text-<?= $c ?>"><i class="bi bi-<?= $ic ?>"></i></div><span class="badge bg-<?= $c ?>-subtle text-<?= $c ?>"><?= $s ?></span></div>
            <div class="stat-value text-<?= $c ?>">&#8377;<?= number_format($fsum[$s]??0,2) ?></div>
            <div class="stat-label"><?= $s ?> Amount</div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header"><h6 class="card-title"><i class="bi bi-cash-stack me-2 text-success"></i>Fee Details</h6></div>
    <div class="table-responsive"><table class="table">
        <thead><tr><th>#</th><th>Fee Type</th><th>Amount</th><th>Due Date</th><th>Paid Date</th><th>Method</th><th>Status</th></tr></thead>
        <tbody>
        <?php $i=1; while ($f=$fees->fetch_assoc()): $sc=['Paid'=>'success','Pending'=>'warning','Overdue'=>'danger'][$f['status']]??'secondary'; ?>
        <tr>
            <td class="text-muted"><?= $i++ ?></td>
            <td class="fw-600"><?= htmlspecialchars($f['fee_type']) ?></td>
            <td class="fw-700">&#8377;<?= number_format($f['amount'],2) ?></td>
            <td class="text-muted small"><?= formatDate($f['due_date']) ?></td>
            <td class="text-muted small"><?= $f['paid_date'] ? formatDate($f['paid_date']) : '-' ?></td>
            <td class="small"><?= htmlspecialchars($f['payment_method']??'-') ?></td>
            <td><span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?>"><?= $f['status'] ?></span></td>
        </tr>
        <?php endwhile; ?>
        <?php if ($fees->num_rows===0): ?><tr><td colspan="7" class="text-center py-5 text-muted">No fee records found.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
