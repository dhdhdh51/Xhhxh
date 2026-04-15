<?php
$role    = $_SESSION['role'] ?? 'student';
$cur     = basename($_SERVER['PHP_SELF']);
$sideClass = $role === 'developer' ? 'dev-sidebar' : '';

$avatarColors = [
    'developer' => '#7c3aed', 'admin' => '#dc2626',
    'teacher' => '#2563eb',   'student' => '#16a34a'
];
$avatarColor = $avatarColors[$role] ?? '#64748b';

function navLink($url, $icon, $label, $cur, $page) {
    $active = basename($cur) === $page ? 'active' : '';
    return "<a class='nav-link $active' href='$url'>
        <i class='bi bi-$icon nav-icon'></i>
        <span class='nav-text'>$label</span>
    </a>";
}
?>
<nav class="sidebar <?= $sideClass ?>" id="sidebar">

    <!-- User info -->
    <div class="sidebar-user">
        <div class="sidebar-user-avatar" style="background:<?= $avatarColor ?>">
            <i class="bi bi-person-fill"></i>
        </div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?= htmlspecialchars(mb_strimwidth($_SESSION['name'] ?? '', 0, 22, '...')) ?></div>
            <span class="badge role-<?= $role ?> sidebar-user-role"><?= ucfirst($role) ?></span>
        </div>
    </div>

    <!-- Nav items -->
    <div class="py-2">

    <?php if ($role === 'developer'): ?>
        <div class="nav-group-label">Main</div>
        <?= navLink(APP_URL.'/developer/dashboard.php', 'speedometer2', 'Dashboard', $cur, 'dashboard.php') ?>

        <div class="nav-group-label">System</div>
        <?= navLink(APP_URL.'/developer/users.php', 'people', 'Manage Users', $cur, 'users.php') ?>
        <?= navLink(APP_URL.'/developer/settings.php', 'gear', 'System Settings', $cur, 'settings.php') ?>
        <?= navLink(APP_URL.'/developer/logs.php', 'journal-text', 'Payment Logs', $cur, 'logs.php') ?>

        <div class="nav-group-label">Academic</div>
        <?= navLink(APP_URL.'/admin/students.php', 'people-fill', 'Students', $cur, 'students.php') ?>
        <?= navLink(APP_URL.'/admin/teachers.php', 'person-badge', 'Teachers', $cur, 'teachers.php') ?>
        <?= navLink(APP_URL.'/admin/classes.php', 'building', 'Classes', $cur, 'classes.php') ?>

        <div class="nav-group-label">Admissions</div>
        <?= navLink(APP_URL.'/developer/admissions.php', 'person-plus', 'Admissions', $cur, 'admissions.php') ?>

    <?php elseif ($role === 'admin'): ?>
        <div class="nav-group-label">Main</div>
        <?= navLink(APP_URL.'/admin/dashboard.php', 'speedometer2', 'Dashboard', $cur, 'dashboard.php') ?>

        <div class="nav-group-label">Academics</div>
        <?= navLink(APP_URL.'/admin/students.php', 'people', 'Students', $cur, 'students.php') ?>
        <?= navLink(APP_URL.'/admin/teachers.php', 'person-badge', 'Teachers', $cur, 'teachers.php') ?>
        <?= navLink(APP_URL.'/admin/classes.php', 'building', 'Classes', $cur, 'classes.php') ?>
        <?= navLink(APP_URL.'/admin/subjects.php', 'book', 'Subjects', $cur, 'subjects.php') ?>
        <?= navLink(APP_URL.'/admin/timetable.php', 'calendar3', 'Timetable', $cur, 'timetable.php') ?>

        <div class="nav-group-label">Reports</div>
        <?= navLink(APP_URL.'/admin/attendance.php', 'calendar-check', 'Attendance', $cur, 'attendance.php') ?>
        <?= navLink(APP_URL.'/admin/marks.php', 'award', 'Marks & Grades', $cur, 'marks.php') ?>
        <?= navLink(APP_URL.'/admin/fees.php', 'cash-stack', 'Fee Management', $cur, 'fees.php') ?>

        <div class="nav-group-label">Admissions</div>
        <?= navLink(APP_URL.'/admin/admissions.php', 'person-plus', 'Admissions', $cur, 'admissions.php') ?>

        <div class="nav-group-label">Other</div>
        <?= navLink(APP_URL.'/admin/notices.php', 'megaphone', 'Notice Board', $cur, 'notices.php') ?>

    <?php elseif ($role === 'teacher'): ?>
        <div class="nav-group-label">Main</div>
        <?= navLink(APP_URL.'/teacher/dashboard.php', 'speedometer2', 'Dashboard', $cur, 'dashboard.php') ?>

        <div class="nav-group-label">Academics</div>
        <?= navLink(APP_URL.'/teacher/attendance.php', 'calendar-check', 'Attendance', $cur, 'attendance.php') ?>
        <?= navLink(APP_URL.'/teacher/marks.php', 'award', 'Marks & Grades', $cur, 'marks.php') ?>
        <?= navLink(APP_URL.'/teacher/timetable.php', 'calendar3', 'Timetable', $cur, 'timetable.php') ?>
        <?= navLink(APP_URL.'/teacher/students.php', 'people', 'My Students', $cur, 'students.php') ?>

        <div class="nav-group-label">Other</div>
        <?= navLink(APP_URL.'/teacher/notices.php', 'megaphone', 'Notices', $cur, 'notices.php') ?>

    <?php elseif ($role === 'student'): ?>
        <div class="nav-group-label">Main</div>
        <?= navLink(APP_URL.'/student/dashboard.php', 'speedometer2', 'Dashboard', $cur, 'dashboard.php') ?>

        <div class="nav-group-label">Academics</div>
        <?= navLink(APP_URL.'/student/attendance.php', 'calendar-check', 'My Attendance', $cur, 'attendance.php') ?>
        <?= navLink(APP_URL.'/student/marks.php', 'award', 'My Marks', $cur, 'marks.php') ?>
        <?= navLink(APP_URL.'/student/timetable.php', 'calendar3', 'Timetable', $cur, 'timetable.php') ?>

        <div class="nav-group-label">Finance</div>
        <?= navLink(APP_URL.'/student/fees.php', 'cash-stack', 'My Fees', $cur, 'fees.php') ?>

        <div class="nav-group-label">Other</div>
        <?= navLink(APP_URL.'/student/notices.php', 'megaphone', 'Notices', $cur, 'notices.php') ?>
    <?php endif; ?>

    </div>
</nav>
