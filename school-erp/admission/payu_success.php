<?php
/**
 * PayU Success Callback
 * PayU POSTs back here on successful payment
 */
require_once '../config/db.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/admission/apply.php');
}

$response = $_POST;

// Extract key fields
$txnid      = sanitize($response['txnid'] ?? '');
$status     = sanitize($response['status'] ?? '');
$admNo      = sanitize($response['udf1'] ?? '');
$admId      = (int)($response['udf2'] ?? 0);
$payuTxnId  = sanitize($response['mihpayid'] ?? '');
$amount     = sanitize($response['amount'] ?? '');

// Verify hash
$payuSalt = getSetting('payu_salt', 'eCwWELxi');
$valid    = verifyPayuHash($response, $payuSalt);

if ($valid && strtolower($status) === 'success') {
    // Update payment log
    $txnEsc    = dbSanitize($txnid);
    $respJson  = dbSanitize(json_encode($response));
    $payuEsc   = dbSanitize($payuTxnId);
    $db->query("UPDATE payment_logs SET status='success', payu_txnid='$payuEsc', payu_status='$status', payu_response='$respJson' WHERE txnid='$txnEsc'");

    // Update admission
    if ($admId > 0) {
        $method = dbSanitize($response['mode'] ?? 'PayU');
        $stmt = $db->prepare("UPDATE admissions SET fee_status='paid', payment_txn_id=?, payment_date=NOW(), payment_method=? WHERE id=?");
        $stmt->bind_param("ssi", $payuTxnId, $method, $admId);
        $stmt->execute();
    }

    redirect(APP_URL . "/admission/success.php?adm=" . urlencode($admNo) . "&txn=" . urlencode($payuTxnId));
} else {
    // Update log as failed
    $txnEsc = dbSanitize($txnid);
    $db->query("UPDATE payment_logs SET status='failure', payu_status='$status' WHERE txnid='$txnEsc'");
    redirect(APP_URL . "/admission/payu_failure.php?reason=hash_mismatch");
}
