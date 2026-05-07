<?php
require_once '../includes/layout.php';
require_once '../config/db.php';
require_once '../includes/schema.php';
require_once '../includes/mailer.php';

$pdo = getDB();
ensureAppSchema($pdo);

if (isAdmin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$bookId = (int)($_POST['book_id'] ?? 0);
$durationDays = (int)($_POST['duration_days'] ?? 0);
$selectedVolumes = $_POST['volume_labels'] ?? [];
if (!is_array($selectedVolumes)) {
    $selectedVolumes = [];
}
$selectedVolumes = array_values(array_unique(array_filter(array_map('trim', $selectedVolumes))));
$allowedDurations = [1, 3, 5, 7];

if ($bookId <= 0 || !in_array($durationDays, $allowedDurations, true) || !$selectedVolumes) {
    $_SESSION['flash'] = 'ข้อมูลการยืมไม่ถูกต้อง';
    header('Location: index.php');
    exit;
}

$userStmt = $pdo->prepare("SELECT username, gmail FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch();

if (!$user || empty($user['gmail']) || strtolower(substr($user['gmail'], -10)) !== '@gmail.com') {
    $_SESSION['flash'] = 'กรุณาเพิ่ม Gmail ก่อนยืมหนังสือ';
    header('Location: ../auth/account.php');
    exit;
}

$bookStmt = $pdo->prepare("SELECT id, title, volume_options FROM books WHERE id = ?");
$bookStmt->execute([$bookId]);
$book = $bookStmt->fetch();

if (!$book) {
    $_SESSION['flash'] = 'ไม่พบหนังสือที่ต้องการยืม';
    header('Location: index.php');
    exit;
}

$volumes = json_decode($book['volume_options'] ?? '[]', true);
if (!is_array($volumes) || !$volumes) {
    $volumes = ['เล่มเดียว'];
}

foreach ($selectedVolumes as $volumeLabel) {
    if (!in_array($volumeLabel, $volumes, true)) {
        $_SESSION['flash'] = 'ไม่พบเล่มที่ต้องการยืม';
        header('Location: index.php');
        exit;
    }
}

$activeStmt = $pdo->prepare(
    "SELECT id FROM loans
     WHERE user_id = ? AND book_id = ? AND volume_label = ? AND status = 'active' AND due_at > NOW()
     LIMIT 1"
);

$insert = $pdo->prepare(
    "INSERT INTO loans (user_id, book_id, volume_label, duration_days, borrowed_at, due_at)
     VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY))"
);
$loanStmt = $pdo->prepare("SELECT due_at FROM loans WHERE id = ?");
$emailUpdate = $pdo->prepare("UPDATE loans SET email_sent_at = NOW() WHERE id = ?");

$borrowedVolumes = [];
$skippedVolumes = [];
$mailSentAny = false;

foreach ($selectedVolumes as $volumeLabel) {
    $activeStmt->execute([$_SESSION['user_id'], $bookId, $volumeLabel]);
    if ($activeStmt->fetch()) {
        $skippedVolumes[] = $volumeLabel;
        continue;
    }

    $insert->execute([$_SESSION['user_id'], $bookId, $volumeLabel, $durationDays, $durationDays]);
    $loanId = (int)$pdo->lastInsertId();

    $loanStmt->execute([$loanId]);
    $dueAt = (string)$loanStmt->fetchColumn();

    $mailSent = sendBorrowEmail($user['gmail'], $user['username'], $book['title'], $volumeLabel, $durationDays, $dueAt);
    if ($mailSent) {
        $emailUpdate->execute([$loanId]);
        $mailSentAny = true;
    }
    $borrowedVolumes[] = $volumeLabel;
}

if (!$borrowedVolumes) {
    $_SESSION['flash'] = 'เล่มที่เลือกกำลังถูกยืมอยู่แล้ว';
    header('Location: index.php');
    exit;
}

$_SESSION['flash'] = 'ยืม "' . $book['title'] . '" สำเร็จ: ' . implode(', ', $borrowedVolumes) . ' ระยะเวลา ' . $durationDays . ' วัน';
if ($skippedVolumes) {
    $_SESSION['flash'] .= ' (ข้ามเล่มที่ยืมอยู่แล้ว: ' . implode(', ', $skippedVolumes) . ')';
}
if (!$mailSentAny) {
    $_SESSION['flash'] .= ' (ระบบพยายามส่งอีเมลแล้ว แต่เซิร์ฟเวอร์นี้ยังไม่ได้ตั้งค่า mail transport)';
}

header('Location: index.php');
exit;
