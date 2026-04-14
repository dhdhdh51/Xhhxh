<?php
require_once '../config/db.php';
requireRole('developer');
$pageTitle = 'Payment Logs';
$db = getDB();

$statusFilter = sanitize($_GET['status'] ?? '');
$search       = sanitize($_GET['q'] ?? '');
$where = "WHERE 1=1";
if ($statusFilter) $where .= " AND p.status='$statusFilter'";
if ($search)       $where .= " AND (p.txnid LIKE '%$search%' OR p.email LIKE '%$search%' OR a.admission_no LIKE '%$search%')";

$logs = $db->query("
    SELECT p.*, a.admission_no, a.applicant_name
    FROM payment_logs p
    LEFT JOIN admissions a ON p.admission_id=a.id
    $where
    ORDER BY p.created_at DESC
");

// Summary
$total      = $db->query("SELECT COUNT(*) as c FROM payment_logs")->fetch_assoc()['c'];
$successAmt = $db->query("SELECT COALESCE(SUM(amount),0) as s FROM payment_logs WHERE status='success'")->fetch_assoc()['s'];
$failCount  = $db->query("SELECT COUNT(*) as c FROM payment_logs WHERE status='failure'")->fetch_assoc()['c'];

$flash = getFlash();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content" id="mainContent">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2>Payment Logs</h2>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Payment Logs</li>
            </ol></nav>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary mb-2"><i class="bi bi-receipt"></i></div>
                <div class="stat-value text-primary"><?= $total ?></div>
                <div class="stat-label">Total Transactions</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-success bg-opacity-10 text-success mb-2"><i class="bi bi-cash-coin"></i></div>
                <div class="stat-value text-success">&#8377;<?= number_format($successAmt,2) ?></div>
                <div class="stat-label">Total Collected</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger mb-2"><i class="bi bi-x-circle"></i></div>
                <div class="stat-value text-danger"><?= $failCount ?></div>
                <div class="stat-label">Failed Transactions</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5"><input type="text" name="q" class="form-control" placeholder="Search txn ID, email, admission no..." value="<?= htmlspecialchars($search) ?>"></div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <?php foreach (['initiated','success','failure','pending'] as $s): ?>
                            <option <?= $statusFilter===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
                <div class="col-md-2"><a href="logs.php" class="btn btn-outline-secondary w-100">Clear</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h6 class="card-title"><i class="bi bi-receipt me-2 text-success"></i>All Payment Logs</h6></div>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Txn ID</th><th>Adm No</th><th>Applicant</th><th>Amount</th><th>PayU Txn</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php while ($l=$logs->fetch_assoc()):
                    $sc=['success'=>'success','failure'=>'danger','initiated'=>'secondary','pending'=>'warning'][$l['status']]??'secondary';
                ?>
                <tr>
                    <td><code class="small"><?= htmlspecialchars($l['txnid']) ?></code></td>
                    <td><?= htmlspecialchars($l['admission_no'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($l['applicant_name'] ?? '-') ?></td>
                    <td class="fw-600">&#8377;<?= number_format((float)$l['amount'],2) ?></td>
                    <td><code class="small text-success"><?= htmlspecialchars($l['payu_txnid'] ?? '-') ?></code></td>
                    <td><span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?>"><?= ucfirst($l['status']) ?></span></td>
                    <td class="text-muted small"><?= formatDate($l['created_at']) ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if ($logs->num_rows===0): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No payment logs found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
