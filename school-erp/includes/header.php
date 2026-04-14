<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle).' - ' : '' ?><?= getSetting('school_name', APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php
$role        = $_SESSION['role'] ?? '';
$navGradient = [
    'developer' => 'background:linear-gradient(135deg,#4c1d95,#7c3aed)',
    'admin'     => 'background:linear-gradient(135deg,#991b1b,#dc2626)',
    'teacher'   => 'background:linear-gradient(135deg,#1e3a8a,#2563eb)',
    'student'   => 'background:linear-gradient(135deg,#14532d,#16a34a)',
];
$navStyle = $navGradient[$role] ?? $navGradient['admin'];
?>
<nav class="navbar navbar-dark fixed-top shadow-sm" id="topNav" style="<?= $navStyle ?>;height:var(--nav-h)">
    <div class="container-fluid">
        <button class="btn btn-link text-white me-1 p-1" id="sidebarToggle">
            <i class="bi bi-list fs-4"></i>
        </button>
        <a class="navbar-brand fw-700 d-flex align-items-center gap-2" href="<?= APP_URL ?>">
            <i class="bi bi-mortarboard-fill"></i>
            <span><?= htmlspecialchars(getSetting('school_name', APP_NAME)) ?></span>
        </a>

        <div class="ms-auto d-flex align-items-center gap-2">
            <!-- Notifications -->
            <div class="dropdown">
                <button class="btn btn-link text-white position-relative p-1" data-bs-toggle="dropdown">
                    <i class="bi bi-bell-fill fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark" style="font-size:.6rem">3</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="width:290px;border-radius:12px">
                    <li><div class="dropdown-header fw-600">Notifications</div></li>
                    <?php
                    $uid = $_SESSION['user_id'] ?? 0;
                    $notifRole = $role;
                    $db = getDB();
                    $notifQuery = $db->query("SELECT title, created_at FROM notices WHERE (target_role='all' OR target_role='$notifRole') AND is_published=1 ORDER BY created_at DESC LIMIT 3");
                    if ($notifQuery) while ($n=$notifQuery->fetch_assoc()):
                    ?>
                    <li>
                        <a class="dropdown-item fs-13 py-2" href="#">
                            <i class="bi bi-megaphone text-primary me-2"></i>
                            <?= htmlspecialchars(mb_strimwidth($n['title'], 0, 45, '...')) ?>
                        </a>
                    </li>
                    <?php endwhile; ?>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a class="dropdown-item text-center text-primary fs-13" href="#">View all notices</a></li>
                </ul>
            </div>

            <!-- User dropdown -->
            <div class="dropdown">
                <button class="btn btn-link text-white d-flex align-items-center gap-2 p-1" data-bs-toggle="dropdown">
                    <div class="avatar-circle">
                        <i class="bi bi-person-fill small"></i>
                    </div>
                    <div class="d-none d-md-block text-start">
                        <div class="fw-600" style="font-size:.8rem;line-height:1.2"><?= htmlspecialchars(mb_strimwidth($_SESSION['name'] ?? 'User', 0, 18, '...')) ?></div>
                        <div style="font-size:.68rem;opacity:.8"><?= ucfirst($role) ?></div>
                    </div>
                    <i class="bi bi-chevron-down small d-none d-md-inline"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius:12px">
                    <li><div class="px-3 py-2">
                        <div class="fw-600 fs-13"><?= htmlspecialchars($_SESSION['name'] ?? '') ?></div>
                        <div class="text-muted" style="font-size:.72rem"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></div>
                        <span class="badge mt-1 role-<?= $role ?>"><?= ucfirst($role) ?></span>
                    </div></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a class="dropdown-item fs-13" href="#"><i class="bi bi-person me-2 text-muted"></i>Profile</a></li>
                    <li><a class="dropdown-item fs-13" href="#"><i class="bi bi-gear me-2 text-muted"></i>Settings</a></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a class="dropdown-item fs-13 text-danger" href="<?= APP_URL ?>/auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="wrapper d-flex">
