<?php
// auth/login.php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../books/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: ../books/index.php');
            exit;
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบ';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>เข้าสู่ระบบ — Book Manager</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Sarabun:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --ink: #1a1208;
    --paper: #f5f0e8;
    --cream: #ede8dc;
    --accent: #8b4513;
    --accent-light: #c4763a;
    --muted: #7a6e5f;
    --border: #cfc8b8;
  }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    min-height: 100vh;
    background: var(--paper);
    background-image:
      repeating-linear-gradient(0deg, transparent, transparent 27px, var(--border) 27px, var(--border) 28px);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Sarabun', sans-serif;
  }
  .card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 4px;
    padding: 48px 40px;
    width: 100%;
    max-width: 400px;
    box-shadow: 6px 6px 0 var(--cream), 7px 7px 0 var(--border);
    position: relative;
  }
  .card::before {
    content: '';
    position: absolute;
    top: 0; left: 32px;
    width: 3px; height: 100%;
    background: #e8a87c;
    border-radius: 0 0 0 0;
    opacity: .5;
  }
  h1 {
    font-family: 'Playfair Display', serif;
    color: var(--ink);
    font-size: 2rem;
    margin-bottom: 4px;
  }
  .subtitle { color: var(--muted); font-size: .9rem; margin-bottom: 32px; }
  label { display: block; font-size: .85rem; font-weight: 500; color: var(--ink); margin-bottom: 6px; }
  input[type=text], input[type=password] {
    width: 100%; padding: 10px 14px;
    border: 1.5px solid var(--border);
    border-radius: 3px;
    background: var(--paper);
    font-family: 'Sarabun', sans-serif;
    font-size: 1rem;
    color: var(--ink);
    transition: border-color .2s;
    margin-bottom: 20px;
  }
  input:focus { outline: none; border-color: var(--accent); background: #fff; }
  .btn {
    width: 100%; padding: 12px;
    background: var(--accent);
    color: #fff;
    border: none; border-radius: 3px;
    font-family: 'Sarabun', sans-serif;
    font-size: 1rem; font-weight: 500;
    cursor: pointer;
    transition: background .2s;
    letter-spacing: .5px;
  }
  .btn:hover { background: var(--accent-light); }
  .error {
    background: #fdf0f0; border: 1px solid #f5c6c6;
    color: #c0392b; border-radius: 3px;
    padding: 10px 14px; margin-bottom: 20px;
    font-size: .9rem;
  }
  .hint { margin-top: 20px; text-align: center; color: var(--muted); font-size: .8rem; }
</style>
</head>
<body>
<div class="card">
  <h1>📚 Book Manager</h1>
  <p class="subtitle">ระบบจัดการหนังสือ — กรุณาเข้าสู่ระบบ</p>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <label for="username">ชื่อผู้ใช้</label>
    <input type="text" id="username" name="username"
           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
           placeholder="กรอกชื่อผู้ใช้" autocomplete="username">

    <label for="password">รหัสผ่าน</label>
    <input type="password" id="password" name="password"
           placeholder="กรอกรหัสผ่าน" autocomplete="current-password">

    <button type="submit" class="btn">เข้าสู่ระบบ</button>
  </form>
  <p class="hint">ยังไม่มีบัญชี? <a href="register.php" style="color:var(--accent)">สมัครบัญชี</a></p>
</div>
</body>
</html>
