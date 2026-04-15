<?php
require_once '../config/db.php';
requireRole('developer');
$pageTitle = 'Manage Users';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    if ($postAction === 'add') {
        $name  = dbSanitize($_POST['name']  ?? '');
        $email = dbSanitize($_POST['email'] ?? '');
        $role  = dbSanitize($_POST['role']  ?? '');
        $phone = dbSanitize($_POST['phone'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $validRoles = ['developer','admin','teacher','student'];
        if ($name && $email && in_array($role,$validRoles) && $pass) {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (name,email,password,role,phone) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssss", $name, $email, $hash, $role, $phone);
            if ($stmt->execute()) setFlash('success','User created!');
            else setFlash('error','Email already exists.');
        }
    } elseif ($postAction === 'toggle') {
        $uid = (int)$_POST['user_id'];
        $db->query("UPDATE users SET status=IF(status='active','inactive','active') WHERE id=$uid");
        setFlash('success','User status toggled!');
    } elseif ($postAction === 'delete') {
        $uid = (int)$_POST['user_id'];
        // Don't delete current developer
        if ($uid !== (int)$_SESSION['user_id']) {
            $db->query("DELETE FROM users WHERE id=$uid");
            setFlash('success','User deleted!');
        } else {
            setFlash('error','Cannot delete your own account.');
        }
    } elseif ($postAction === 'reset_password') {
        $uid  = (int)$_POST['user_id'];
        $pass = $_POST['new_password'] ?? '';
        if ($pass) {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hash, $uid);
            $stmt->execute();
            setFlash('success','Password reset!');
        }
    }
    redirect(APP_URL.'/developer/users.php');
}

$roleFilter = sanitize($_GET['role'] ?? '');
$search     = sanitize($_GET['q'] ?? '');
$where = "WHERE 1=1";
if ($roleFilter) $where .= " AND role='$roleFilter'";
if ($search)     $where .= " AND (name LIKE '%$search%' OR email LIKE '%$search%')";

$users = $db->query("SELECT * FROM users $where ORDER BY role,name");
$flash = getFlash();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content" id="mainContent">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show flash-msg mb-3">
            <i class="bi bi-<?= $flash['type']==='success'?'check-circle':'exclamation-triangle' ?> me-2"></i>
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2>Manage Users</h2>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Users</li>
            </ol></nav>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus me-1"></i>Add User
        </button>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5"><input type="text" name="q" class="form-control" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>"></div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <?php foreach (['developer','admin','teacher','student'] as $r): ?>
                            <option <?= $roleFilter===$r?'selected':'' ?>><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i>Filter</button></div>
                <div class="col-md-2"><a href="users.php" class="btn btn-outline-secondary w-100">Clear</a></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h6 class="card-title"><i class="bi bi-people me-2 text-dev"></i>All Users</h6>
            <span class="badge bg-dev-subtle text-dev"><?= $users->num_rows ?></span>
        </div>
        <div class="table-responsive">
            <table class="table searchable">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php $i=1; while ($u=$users->fetch_assoc()):
                    $roleColors=['developer'=>'dev','admin'=>'danger','teacher'=>'primary','student'=>'success'];
                    $rc = $roleColors[$u['role']] ?? 'secondary';
                ?>
                <tr>
                    <td class="text-muted"><?= $i++ ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width:32px;height:32px;font-size:.8rem;background:<?= ['developer'=>'#7c3aed','admin'=>'#dc2626','teacher'=>'#2563eb','student'=>'#16a34a'][$u['role']] ?? '#64748b' ?>">
                                <?= strtoupper(substr($u['name'],0,1)) ?>
                            </div>
                            <div class="fw-600"><?= htmlspecialchars($u['name']) ?></div>
                        </div>
                    </td>
                    <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge bg-<?= $rc ?>-subtle text-<?= $rc ?>"><?= ucfirst($u['role']) ?></span></td>
                    <td class="text-muted"><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                    <td>
                        <span class="badge bg-<?= $u['status']==='active'?'success':'danger' ?>-subtle text-<?= $u['status']==='active'?'success':'danger' ?>">
                            <?= ucfirst($u['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button class="btn btn-sm btn-outline-<?= $u['status']==='active'?'warning':'success' ?> btn-icon me-1"
                                    title="<?= $u['status']==='active'?'Deactivate':'Activate' ?>" data-bs-toggle="tooltip">
                                <i class="bi bi-<?= $u['status']==='active'?'pause-circle':'play-circle' ?>"></i>
                            </button>
                        </form>
                        <button class="btn btn-sm btn-outline-secondary btn-icon me-1"
                                data-bs-toggle="modal" data-bs-target="#resetPwdModal"
                                onclick="setResetUid(<?= $u['id'] ?>)" title="Reset Password">
                            <i class="bi bi-key"></i>
                        </button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete user <?= htmlspecialchars(addslashes($u['name'])) ?>?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php else: ?>
                            <span class="text-muted small">(You)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
                        <div class="col-md-6">
                            <label class="form-label">Role *</label>
                            <select name="role" class="form-select" required>
                                <?php foreach (['developer','admin','teacher','student'] as $r): ?>
                                    <option value="<?= $r ?>"><?= ucfirst($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" required></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPwdModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="resetUid">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-key me-2"></i>Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Reset</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>function setResetUid(id){document.getElementById('resetUid').value=id;}</script>

<?php include '../includes/footer.php'; ?>
