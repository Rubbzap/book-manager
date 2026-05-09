<?php
require_once '../includes/layout.php';
require_once '../config/db.php';
require_once '../includes/schema.php';

$pdo = getDB();
ensureAppSchema($pdo);

if (isAdmin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$bookId = (int)($_POST['book_id'] ?? 0);
$durationDays = (int)($_POST['duration_days'] ?? 0);
$durationHours = $durationDays * 24;
$selectedVolumes = $_POST['volume_labels'] ?? [];
if (!is_array($selectedVolumes)) {
    $selectedVolumes = [];
}
$selectedVolumes = array_values(array_unique(array_filter(array_map('trim', $selectedVolumes))));
$allowedDurations = [1, 3, 5, 7];

if ($bookId <= 0 || !in_array($durationDays, $allowedDurations, true) || !$selectedVolumes) {
    $_SESSION['flash'] = tt('ข้อมูลการยืมไม่ถูกต้อง', 'Invalid borrowing request.');
    header('Location: index.php');
    exit;
}

$userStmt = $pdo->prepare("SELECT username, gmail FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch();

if (!$user || empty($user['gmail']) || strtolower(substr($user['gmail'], -10)) !== '@gmail.com') {
    $_SESSION['flash'] = tt('กรุณาเพิ่ม Gmail ก่อนยืมหนังสือ', 'Please add Gmail before borrowing books.');
    header('Location: ../auth/account.php');
    exit;
}

$bookStmt = $pdo->prepare("SELECT id, title, volume_options FROM books WHERE id = ?");
$bookStmt->execute([$bookId]);
$book = $bookStmt->fetch();

if (!$book) {
    $_SESSION['flash'] = tt('ไม่พบหนังสือที่ต้องการยืม', 'The requested book was not found.');
    header('Location: index.php');
    exit;
}

$volumes = json_decode($book['volume_options'] ?? '[]', true);
if (!is_array($volumes) || !$volumes) {
    $volumes = ['เล่มเดียว'];
}

foreach ($selectedVolumes as $volumeLabel) {
    if (!in_array($volumeLabel, $volumes, true)) {
        $_SESSION['flash'] = tt('ไม่พบเล่มที่ต้องการยืม', 'The selected volume was not found.');
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
     VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? HOUR))"
);

$borrowedVolumes = [];
$skippedVolumes = [];

foreach ($selectedVolumes as $volumeLabel) {
    $activeStmt->execute([$_SESSION['user_id'], $bookId, $volumeLabel]);
    if ($activeStmt->fetch()) {
        $skippedVolumes[] = $volumeLabel;
        continue;
    }

    $insert->execute([$_SESSION['user_id'], $bookId, $volumeLabel, $durationDays, $durationHours]);
    $borrowedVolumes[] = $volumeLabel;
}

if (!$borrowedVolumes) {
    $_SESSION['flash'] = tt('เล่มที่เลือกกำลังถูกยืมอยู่แล้ว', 'The selected volumes are already borrowed.');
    header('Location: index.php');
    exit;
}

$borrowedText = implode(', ', array_map('localizeValue', $borrowedVolumes));
$_SESSION['flash'] = tt('ยืม', 'Borrowed') . ' "' . $book['title'] . '" ' . tt('สำเร็จ', 'successfully') . ': ' . $borrowedText . ' ' . tt('ระยะเวลา', 'for') . ' ' . $durationDays . ' ' . t($durationDays === 1 ? 'day' : 'days');
if ($skippedVolumes) {
    $_SESSION['flash'] .= ' (' . tt('ข้ามเล่มที่ยืมอยู่แล้ว:', 'Skipped already borrowed volumes:') . ' ' . implode(', ', array_map('localizeValue', $skippedVolumes)) . ')';
}

header('Location: index.php');
exit;
