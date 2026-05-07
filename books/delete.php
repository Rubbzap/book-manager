<?php
// books/delete.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php'); exit;
}

require_once '../config/db.php';
require_once '../includes/layout.php';

if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT title FROM books WHERE id = ?");
    $stmt->execute([$id]);
    $book = $stmt->fetch();

    if ($book) {
        $del = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $del->execute([$id]);
        $_SESSION['flash'] = 'ลบหนังสือ "' . $book['title'] . '" เรียบร้อยแล้ว';
    }
}

header('Location: index.php');
exit;
