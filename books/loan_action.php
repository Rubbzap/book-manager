<?php
require_once '../includes/layout.php';
require_once '../config/db.php';
require_once '../includes/schema.php';

$pdo = getDB();
ensureAppSchema($pdo);

if (isAdmin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../books/index.php');
    exit;
}

$loanId = (int)($_POST['loan_id'] ?? 0);
$action = $_POST['action'] ?? '';
$allowed = ['return' => 'returned', 'cancel' => 'cancelled'];

if ($loanId <= 0 || !isset($allowed[$action])) {
    $_SESSION['flash'] = 'ข้อมูลรายการยืมไม่ถูกต้อง';
    header('Location: ../auth/account.php');
    exit;
}

$stmt = $pdo->prepare(
    "UPDATE loans
     SET status = ?, returned_at = NOW()
     WHERE id = ? AND user_id = ? AND status = 'active'"
);
$stmt->execute([$allowed[$action], $loanId, $_SESSION['user_id']]);

$_SESSION['flash'] = $action === 'return' ? 'คืนหนังสือเรียบร้อยแล้ว' : 'ยกเลิกการยืมเรียบร้อยแล้ว';
$back = $_POST['back'] ?? '../auth/account.php';
header('Location: ' . ($back === 'index' ? 'index.php' : '../auth/account.php'));
exit;
