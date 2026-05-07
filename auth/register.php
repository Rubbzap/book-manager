<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ../books/index.php'); exit;
}
require_once '../config/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$username)              $errors[] = 'กรุณากรอกชื่อผู้ใช้';
    elseif (strlen($username) < 3) $errors[] = 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร';

    if (!$password)              $errors[] = 'กรุณากรอกรหัสผ่าน';
    elseif (strlen($password) < 6) $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    elseif ($password !== $confirm) $errors[] = 'รหัสผ่านไม่ตรงกัน';

    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $ins->execute([$username, $hash]);
            $success = 'สมัครบัญชีสำเร็จ! กรุณาเข้าสู่ระบบ';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>สมัครบัญชี — Book Manager</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Sarabun:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root { --ink: #1a1208; --paper: #f5f0e8; --cream: #ede8dc; --accent: #8b4513; --accent-light: #c4763a; --muted: #7a6e5f; --border: #cfc8b8; --danger: #c0392b; --success: #27ae60; }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { min-height: 100vh; background: var(--paper); background-image: repeating-linear-gradient(0deg, transparent, transparent 27px, var(--border) 27px, var(--border) 28px); display: flex; align-items: center; justify-content: center; font-family: "Sarabun", sans-serif; }
  .card { background: #fff; border: 1px solid var(--border); border-radius: 4px; padding: 48px 40px; width: 100%; max-width: 400px; box-shadow: 6px 6px 0 var(--cream), 7px 7px 0 var(--border); position: relative; }
  .card::before { content: ""; position: absolute; top: 0; left: 32px; width: 3px; height: 100%; background: #e8a87c; opacity: .5; }
  h1 { font-family: "Playfair Display", serif; color: var(--ink); font-size: 2rem; margin-bottom: 4px; }
  .subtitle { color: var(--muted); font-size: .9rem; margin-bottom: 32px; }
  label { display: block; font-size: .85rem; font-weight: 500; color: var(--ink); margin-bottom: 6px; }
  input { width: 100%; padding: 10px 14px; border: 1.5px solid var(--border); border-radius: 3px; background: var(--paper); font-family: "Sarabun", sans-serif; font-size: 1rem; color: var(--ink); transition: border-color .2s; margin-bottom: 20px; }
  input:focus { outline: none; border-color: var(--accent); background: #fff; }
  .btn { width: 100%; padding: 12px; background: var(--accent); color: #fff; border: none; border-radius: 3px; font-family: "Sarabun", sans-serif; font-size: 1rem; font-weight: 500; cursor: pointer; transition: background .2s; }
  .btn:hover { background: var(--accent-light); }
  .error { background: #fdf0f0; border: 1px solid #f5c6c6; color: var(--danger); border-radius: 3px; padding: 10px 14px; margin-bottom: 20px; font-size: .9rem; }
  .success { background: #eafaf1; border: 1px solid #a9dfbf; color: var(--success); border-radius: 3px; padding: 10px 14px; margin-bottom: 20px; font-size: .9rem; }
  .hint { margin-top: 20px; text-align: center; color: var(--muted); font-size: .85rem; }
  .hint a { color: var(--accent); text-decoration: none; }
</style>
</head>
<body>
<div class="card">
  <h1>📚 Book Manager</h1>
  <p class="subtitle">สมัครบัญชีใหม่</p>

  <?php if ($errors): ?>
    <div class="error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST">
    <label>ชื่อผู้ใช้</label>
    <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="อย่างน้อย 3 ตัวอักษร">
    <label>รหัสผ่าน</label>
    <input type="password" name="password" placeholder="อย่างน้อย 6 ตัวอักษร">
    <label>ยืนยันรหัสผ่าน</label>
    <input type="password" name="confirm" placeholder="กรอกรหัสผ่านอีกครั้ง">
    <button type="submit" class="btn">สมัครบัญชี</button>
  </form>
  <p class="hint">มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
</div>
</body>
</html>
