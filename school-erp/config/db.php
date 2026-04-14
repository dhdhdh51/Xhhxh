<?php
// ============================================================
// School ERP - Database & App Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_erp');

define('APP_NAME', 'School ERP');
define('APP_URL',  'http://localhost/school-erp');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database singleton
function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('<div style="font-family:sans-serif;padding:30px;background:#fee;border-left:4px solid red;margin:30px;border-radius:5px">
                <h3 style="margin:0 0 10px">Database Connection Error</h3>
                <p>' . htmlspecialchars($conn->connect_error) . '</p>
                <p>Please check your config in <code>config/db.php</code> and ensure MySQL is running.</p>
            </div>');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// Get setting from DB
function getSetting($key, $default = '') {
    $db  = getDB();
    $key = $db->real_escape_string($key);
    $res = $db->query("SELECT setting_value FROM settings WHERE setting_key='$key' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc()['setting_value'];
    }
    return $default;
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// DB-escaped sanitize
function dbSanitize($data) {
    $db = getDB();
    return $db->real_escape_string(strip_tags(trim($data)));
}

// Redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Require login
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirect(APP_URL . '/auth/login.php');
    }
}

// Require specific role(s)
function requireRole($roles) {
    requireLogin();
    $roles = (array)$roles;
    // Developer can access everything
    if ($_SESSION['role'] === 'developer') return;
    if (!in_array($_SESSION['role'], $roles)) {
        redirect(APP_URL . '/auth/unauthorized.php');
    }
}

// Current user
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) return null;
    $id  = (int)$_SESSION['user_id'];
    $res = getDB()->query("SELECT * FROM users WHERE id=$id LIMIT 1");
    return $res ? $res->fetch_assoc() : null;
}

// Flash messages
function setFlash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
}
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// Grade calculator
function getGrade($obtained, $total) {
    if ($total <= 0) return 'N/A';
    $pct = ($obtained / $total) * 100;
    if ($pct >= 90) return 'A+';
    if ($pct >= 80) return 'A';
    if ($pct >= 70) return 'B+';
    if ($pct >= 60) return 'B';
    if ($pct >= 50) return 'C';
    if ($pct >= 40) return 'D';
    return 'F';
}

// Date formatter
function formatDate($d) {
    return $d ? date('d M Y', strtotime($d)) : '-';
}

// Generate unique admission number
function generateAdmissionNo() {
    $db   = getDB();
    $year = date('Y');
    $res  = $db->query("SELECT COUNT(*) as c FROM admissions WHERE YEAR(applied_at)=$year");
    $seq  = ($res->fetch_assoc()['c'] ?? 0) + 1;
    return 'ADM' . $year . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
}

// PayU Hash generation
function generatePayuHash($key, $txnid, $amount, $productinfo, $firstname, $email, $salt) {
    $hashString = "$key|$txnid|$amount|$productinfo|$firstname|$email|||||||||||$salt";
    return strtolower(hash('sha512', $hashString));
}

// PayU response hash verification
function verifyPayuHash($response, $salt) {
    $status    = $response['status'];
    $key       = $response['key'];
    $txnid     = $response['txnid'];
    $amount    = $response['amount'];
    $productinfo = $response['productinfo'];
    $firstname = $response['firstname'];
    $email     = $response['email'];
    $mihpayid  = $response['mihpayid'];
    $hashFromPayu = strtolower($response['hash'] ?? '');
    $hashString = "$salt|$status|||||||||||$email|$firstname|$productinfo|$amount|$txnid|$key";
    $calculatedHash = strtolower(hash('sha512', $hashString));
    return $hashFromPayu === $calculatedHash;
}
