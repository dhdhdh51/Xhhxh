<?php
require_once '../config/db.php';
requireRole(['admin','developer']);
$pageTitle = 'Fee Management';
$db = getDB();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $pa = $_POST['action'] ?? '';
    if ($pa === 'add') {
        $sid = (int)$_POST['student_id']; $ft = dbSanitize($_POST['fee_type']??'');
        $amt = (float)$_POST['amount']; $due = dbSanitize($_POST['due_date']??'');
        $stmt = $db->prepare("INSERT INTO fees (student_id,fee_type,amount,due_date) VALUES (?,?,?,?)");
        $stmt->bind_param("isds",$sid,$ft,$amt,$due); $stmt->execute();
        setFlash('success','Fee record added!'); redirect(APP_URL.'/admin/fees.php');
    } elseif ($pa === 'mark_paid') {
        $fid = (int)$_POST['fee_id']; $method = dbSanitize($_POST['payment_method']??'Cash'); $txn = dbSanitize($_POST['transaction_id']??'');
        $stmt = $db->prepare("UPDATE fees SET status='Paid',paid_date=CURDATE(),payment_method=?,transaction_id=? WHERE id=?");
        $stmt->bind_param("ssi",$method,$txn,$fid); $stmt->execute();
        setFlash('success','Payment recorded!'); redirect(APP_URL.'/admin/fees.php');
    } elseif ($pa === 'delete') {
        $db->query("DELETE FROM fees WHERE id=".(int)$_POST['fee_id']);
        setFlash('success','Fee deleted!'); redirect(APP_URL.'/admin/fees.php');
    }
}

$search = sanitize($_GET['q'] ?? ''); $sfilt = sanitize($_GET['status'] ?? '');
$where = "WHERE 1=1";
if ($search) $where .= " AND (u.name LIKE '%".dbSanitize($search)."%' OR s.roll_number LIKE '%".dbSanitize($search)."%')";
if ($sfilt)  $where .= " AND f.status='$sfilt'";
$fees = $db->query("SELECT f.*,s.roll_number,u.name as sname,c.class_name,c.section FROM fees f JOIN students s ON f.student_id=s.id JOIN users u ON s.user_id=u.id JOIN classes c ON s.class_id=c.id $where ORDER BY f.created_at DESC");

$sm = $db->query("SELECT status,SUM(amount) as tot FROM fees GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$fsum = ['Paid'=>0,'Pending'=>0,'Overdue'=>0]; foreach ($sm as $s) $fsum[$s['status']] = $s['tot'];
$students = $db->query("SELECT s.id,s.roll_number,u.name FROM students s JOIN users u ON s.user_id=u.id ORDER BY u.name")->fetch_all(MYSQLI_ASSOC);
$flash = getFlash();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content" id="mainContent">
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php if ($flash): ?><div class="alert alert-success alert-dismissible fade show flash-msg mb-3"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flash['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div><h2>Fee Management</h2><nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Fees</li></ol></nav></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-circle me-1"></i>Add Fee</button>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([['Paid','success','cash-coin'],['Pending','warning','clock'],['Overdue','danger','exclamation-circle']] as [$s,$c,$ic]): ?>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2"><div class="stat-icon bg-<?= $c ?> bg-opacity-10 text-<?= $c ?>"><i class="bi bi-<?= $ic ?>"></i></div><span class="badge bg-<?= $c ?>-subtle text-<?= $c ?>"><?= $s ?></span></div>
            <div class="stat-value text-<?= $c ?>">&#8377;<?= number_format($fsum[$s]??0,2) ?></div>
            <div class="stat-label"><?= $s ?> Fees</div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card mb-3"><div class="card-body py-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-5"><input type="text" name="q" class="form-control" placeholder="Search student..." value="<?= htmlspecialchars($search) ?>"></div>
        <div class="col-md-2"><select name="status" class="form-select"><option value="">All</option><?php foreach (['Paid','Pending','Overdue'] as $s): ?><option <?= $sfilt===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i>Filter</button></div>
        <div class="col-md-2"><a href="fees.php" class="btn btn-outline-secondary w-100">Clear</a></div>
    </form>
</div></div>

<div class="card">
    <div class="card-header"><h6 class="card-title"><i class="bi bi-cash-stack me-2 text-success"></i>Fee Records</h6></div>
    <div class="table-responsive"><table class="table">
        <thead><tr><th>#</th><th>Student</th><th>Class</th><th>Fee Type</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php $i=1; while ($f=$fees->fetch_assoc()): $sc=['Paid'=>'success','Pending'=>'warning','Overdue'=>'danger'][$f['status']]??'secondary'; ?>
        <tr>
            <td class="text-muted"><?= $i++ ?></td>
            <td><div class="fw-600"><?= htmlspecialchars($f['sname']) ?></div><div class="text-muted fs-12"><?= htmlspecialchars($f['roll_number']) ?></div></td>
            <td><?= htmlspecialchars($f['class_name'].' - '.$f['section']) ?></td>
            <td><?= htmlspecialchars($f['fee_type']) ?></td>
            <td class="fw-600">&#8377;<?= number_format($f['amount'],2) ?></td>
            <td class="text-muted small"><?= formatDate($f['due_date']) ?></td>
            <td><span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?>"><?= $f['status'] ?></span></td>
            <td>
                <?php if ($f['status']!=='Paid'): ?>
                <button class="btn btn-sm btn-outline-success btn-icon me-1" data-bs-toggle="modal" data-bs-target="#payModal" onclick="document.getElementById('payFeeId').value=<?= $f['id'] ?>"><i class="bi bi-check-circle"></i></button>
                <?php endif; ?>
                <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="fee_id" value="<?= $f['id'] ?>"><button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button></form>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($fees->num_rows===0): ?><tr><td colspan="8" class="text-center py-5 text-muted">No records found.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
</div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST"><input type="hidden" name="action" value="add">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-cash me-2"></i>Add Fee Record</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Student *</label><select name="student_id" class="form-select" required><option value="">Select</option><?php foreach ($students as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name'].' ('.$s['roll_number'].')') ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Fee Type *</label><select name="fee_type" class="form-select" required><?php foreach (['Tuition Fee','Library Fee','Sports Fee','Examination Fee','Transport Fee','Miscellaneous'] as $ft): ?><option><?= $ft ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Amount (&#8377;) *</label><input type="number" name="amount" class="form-control" step="0.01" min="1" required></div>
        <div class="mb-3"><label class="form-label">Due Date *</label><input type="date" name="due_date" class="form-control" required></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add</button></div>
    </form>
</div></div></div>

<div class="modal fade" id="payModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST"><input type="hidden" name="action" value="mark_paid"><input type="hidden" name="fee_id" id="payFeeId">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-check-circle me-2 text-success"></i>Record Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Payment Method</label><select name="payment_method" class="form-select"><?php foreach (['Cash','UPI','Online Transfer','Cheque','Card'] as $pm): ?><option><?= $pm ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Transaction ID (optional)</label><input type="text" name="transaction_id" class="form-control"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Mark Paid</button></div>
    </form>
</div></div></div>

<?php include '../includes/footer.php'; ?>
