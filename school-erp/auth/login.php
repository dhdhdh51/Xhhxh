<?php
require_once '../config/db.php';

if (isset($_SESSION['user_id'])) {
    redirect(APP_URL . '/' . $_SESSION['role'] . '/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) {
        $error = 'Please enter both email and password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email=? AND status='active' LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            setFlash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect(APP_URL . '/' . $user['role'] . '/dashboard.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$schoolName = getSetting('school_name', APP_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($schoolName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-bg">
    <div class="login-card">
        <!-- Logo -->
        <div class="text-center mb-4">
            <div class="login-logo"><i class="bi bi-mortarboard-fill"></i></div>
            <h2 class="fw-700 mb-1"><?= htmlspecialchars($schoolName) ?></h2>
            <p class="text-muted small">Sign in to continue</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger flash-msg alert-dismissible mb-3">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="you@school.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" name="password" id="pwInput" class="form-control" placeholder="••••••••" required>
                    <button class="btn btn-light border" type="button" onclick="togglePw()"><i class="bi bi-eye" id="pwEye"></i></button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-600">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <!-- Demo Credentials -->
        <div class="mt-4">
            <hr>
            <p class="text-center text-muted small fw-600 mb-3">Demo Accounts (Password: <code>password</code>)</p>
            <div class="row g-2">
                <?php
                $demos = [
                    ['developer@school.com','#7c3aed','code-slash','Developer','developer123'],
                    ['admin@school.com','#dc2626','shield-fill','Admin','developer123'],
                    ['rajesh@school.com','#2563eb','person-badge','Teacher','password'],
                    ['arjun@student.com','#16a34a','backpack','Student','password'],
                ];
                foreach ($demos as [$em,$col,$icon,$label,$pw]):
                ?>
                <div class="col-6">
                    <div class="rounded-xl border p-2 text-center" style="cursor:pointer;border-color:<?= $col ?>20!important;background:<?= $col ?>08"
                         onclick="fillLogin('<?= $em ?>','<?= $pw ?>')">
                        <i class="bi bi-<?= $icon ?> mb-1 d-block" style="color:<?= $col ?>"></i>
                        <div class="fw-600 fs-13"><?= $label ?></div>
                        <div class="text-muted fs-12"><?= $em ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mt-3 text-center">
            <a href="<?= APP_URL ?>/admission/apply.php" class="text-decoration-none small text-primary">
                <i class="bi bi-person-plus me-1"></i>New Student? Apply for Admission
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePw() {
    const input = document.getElementById('pwInput');
    const eye   = document.getElementById('pwEye');
    if (input.type === 'password') { input.type = 'text'; eye.className = 'bi bi-eye-slash'; }
    else { input.type = 'password'; eye.className = 'bi bi-eye'; }
}
function fillLogin(email, pw) {
    document.querySelector('input[name="email"]').value = email;
    document.getElementById('pwInput').value = pw;
}
</script>
</body>
</html>
