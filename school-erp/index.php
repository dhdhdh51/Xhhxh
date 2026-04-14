<?php
require_once 'config/db.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    redirect(APP_URL . '/' . $_SESSION['role'] . '/dashboard.php');
} else {
    redirect(APP_URL . '/auth/login.php');
}
