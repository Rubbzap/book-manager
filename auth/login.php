<?php
// auth/login.php
session_start();
require_once '../config/db.php';
require_once '../includes/i18n.php';

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
            $error = tt('ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง', 'Invalid username or password.');
        }
    } else {
        $error = tt('กรุณากรอกข้อมูลให้ครบ', 'Please fill in all required fields.');
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(appLang()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(t('login')) ?> - Book Manager</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Sarabun:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --ink: #18201c;
    --paper: #f6f3ee;
    --cream: #ebe4d8;
    --accent: #8b4b2f;
    --accent-light: #b96d45;
    --muted: #716a60;
    --border: #d8d0c3;
  }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes softPop { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: scale(1); } }
  body {
    min-height: 100vh;
    background:
      radial-gradient(circle at 15% 10%, rgba(185,109,69,.20), transparent 30vw),
      linear-gradient(135deg, #fbfaf7 0%, var(--paper) 44%, #e9dfd0 100%);
    display: grid;
    place-items: center;
    font-family: 'Sarabun', sans-serif;
    color: var(--ink);
    padding: 32px;
  }
  .login-shell {
    width: min(1080px, 100%);
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(360px, .75fr);
    background: rgba(255,255,255,.72);
    border: 1px solid rgba(216,208,195,.85);
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 28px 80px rgba(24,32,28,.16);
    animation: fadeUp .5s ease both;
  }
  .brand-panel {
    min-height: 620px;
    padding: 52px;
    background: linear-gradient(145deg, rgba(24,32,28,.96), rgba(53,68,57,.94));
    color: var(--paper);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
  }
  .brand-panel::after {
    content: "";
    position: absolute;
    right: -80px;
    bottom: -140px;
    width: 340px;
    height: 340px;
    border: 1px solid rgba(245,215,161,.22);
    border-radius: 50%;
  }
  .brand-kicker {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: #f5d7a1;
    font-size: .86rem;
    letter-spacing: .04em;
    text-transform: uppercase;
    margin-bottom: 22px;
  }
  .brand-logo {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, #f5d7a1, var(--accent-light));
    color: var(--ink);
    display: inline-grid;
    place-items: center;
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    box-shadow: inset 0 -5px 0 rgba(0,0,0,.1);
  }
  .brand-panel h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2.5rem, 5vw, 4.6rem);
    line-height: 1;
    max-width: 560px;
    margin-bottom: 18px;
  }
  .brand-panel p {
    color: rgba(246,243,238,.78);
    line-height: 1.75;
    max-width: 560px;
    font-size: 1rem;
  }
  .stat-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-top: 38px;
    position: relative;
    z-index: 1;
  }
  .stat-card {
    border: 1px solid rgba(255,255,255,.14);
    background: rgba(255,255,255,.08);
    border-radius: 10px;
    padding: 16px;
    backdrop-filter: blur(10px);
  }
  .stat-card strong {
    display: block;
    font-family: 'Playfair Display', serif;
    font-size: 1.7rem;
    margin-bottom: 4px;
  }
  .stat-card span { color: rgba(246,243,238,.72); font-size: .8rem; }
  .form-panel {
    padding: 48px 42px;
    display: flex;
    align-items: center;
    background: rgba(255,255,255,.94);
  }
  .card {
    width: 100%;
    max-width: 430px;
    margin: 0 auto;
    animation: softPop .42s ease .08s both;
  }
  .card h2 {
    font-family: 'Playfair Display', serif;
    color: var(--ink);
    font-size: 2.15rem;
    margin-bottom: 8px;
  }
  .subtitle { color: var(--muted); font-size: .95rem; margin-bottom: 30px; line-height: 1.6; }
  label { display: block; font-size: .85rem; font-weight: 600; color: var(--ink); margin-bottom: 7px; }
  input[type=text], input[type=password] {
    width: 100%; padding: 13px 15px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    background: var(--paper);
    font-family: 'Sarabun', sans-serif;
    font-size: 1rem;
    color: var(--ink);
    transition: border-color .2s, background .2s, box-shadow .2s, transform .18s;
    margin-bottom: 18px;
  }
  input:focus { outline: none; border-color: var(--accent); background: #fff; transform: translateY(-1px); box-shadow: 0 10px 24px rgba(139,75,47,.12); }
  .btn {
    width: 100%; padding: 13px;
    background: var(--accent);
    color: #fff;
    border: none; border-radius: 8px;
    font-family: 'Sarabun', sans-serif;
    font-size: 1rem; font-weight: 500;
    cursor: pointer;
    transition: background .2s, transform .18s, box-shadow .18s;
    box-shadow: 0 12px 24px rgba(139,75,47,.20);
  }
  .btn:hover { background: var(--accent-light); transform: translateY(-1px); box-shadow: 0 16px 30px rgba(139,75,47,.24); }
  .error {
    background: #fdf0f0; border: 1px solid #f5c6c6;
    color: #c0392b; border-radius: 8px;
    padding: 11px 14px; margin-bottom: 20px;
    font-size: .9rem;
    animation: softPop .24s ease both;
  }
  .hint { margin-top: 22px; text-align: center; color: var(--muted); font-size: .88rem; }
  .hint a { color: var(--accent); font-weight: 600; text-decoration: none; }
  .hint a:hover { text-decoration: underline; }
  .trust-row { display: flex; gap: 8px; flex-wrap: wrap; margin: 0 0 24px; }
  .trust-row span {
    display: inline-flex;
    padding: 6px 10px;
    border-radius: 999px;
    background: #f7f1e7;
    border: 1px solid var(--border);
    color: var(--muted);
    font-size: .78rem;
  }
  .lang-switch { position: fixed; top: 18px; right: 18px; z-index: 5; display: inline-flex; gap: 4px; padding: 4px; border: 1px solid var(--border); border-radius: 999px; background: rgba(255,255,255,.92); box-shadow: 0 8px 20px rgba(26,18,8,.12); backdrop-filter: blur(10px); }
  .lang-switch a { color: var(--accent); text-decoration: none; font-size: .75rem; font-weight: 700; padding: 7px 10px; border-radius: 999px; transition: background .18s, color .18s, transform .18s; }
  .lang-switch a:hover { transform: translateY(-1px); }
  .lang-switch a.active { color: #fff; background: var(--accent); }
  @media (max-width: 860px) {
    body { padding: 18px; }
    .login-shell { grid-template-columns: 1fr; }
    .brand-panel { min-height: auto; padding: 34px; }
    .brand-panel h1 { font-size: 2.6rem; }
    .stat-grid { grid-template-columns: 1fr; margin-top: 26px; }
    .form-panel { padding: 34px 24px; }
  }
</style>
</head>
<body>
<?php renderLanguageSwitch(); ?>
<div class="login-shell">
  <section class="brand-panel">
    <div>
      <div class="brand-kicker"><span class="brand-logo">B</span> BookShelf Library</div>
      <h1><?= htmlspecialchars(tt('อ่าน ยืม และจัดการหนังสือในที่เดียว', 'Read, borrow, and manage books in one place')) ?></h1>
      <p><?= htmlspecialchars(tt('ระบบห้องสมุดดิจิทัลพร้อมหนังสือไทย อังกฤษ มังงะ การยืมแบบจับเวลา และจัดการรายการยืมสำหรับผู้ใช้จริง', 'A digital library for Thai and English books, manga, timed borrowing, and account-based loan management built for real users.')) ?></p>
    </div>
    <div class="stat-grid">
      <div class="stat-card"><strong>100+</strong><span><?= htmlspecialchars(tt('หนังสือพร้อมยืม', 'books ready')) ?></span></div>
      <div class="stat-card"><strong>7d</strong><span><?= htmlspecialchars(tt('ยืมได้นานสูงสุด', 'max loan')) ?></span></div>
      <div class="stat-card"><strong>TH/EN</strong><span><?= htmlspecialchars(tt('รองรับสองภาษา', 'bilingual UI')) ?></span></div>
    </div>
  </section>

  <section class="form-panel">
    <div class="card">
      <h2><?= htmlspecialchars(t('login')) ?></h2>
      <p class="subtitle"><?= htmlspecialchars(t('login_subtitle')) ?></p>
      <div class="trust-row">
        <span><?= htmlspecialchars(tt('ยืมพร้อมจับเวลา', 'Timed loans')) ?></span>
        <span><?= htmlspecialchars(tt('จัดการในบัญชี', 'Account control')) ?></span>
        <span><?= htmlspecialchars(tt('แหล่งอ้างอิงจริง', 'Real sources')) ?></span>
      </div>

      <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <label for="username"><?= htmlspecialchars(t('username')) ?></label>
        <input type="text" id="username" name="username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               placeholder="<?= htmlspecialchars(t('username_placeholder')) ?>" autocomplete="username">

        <label for="password"><?= htmlspecialchars(t('password')) ?></label>
        <input type="password" id="password" name="password"
               placeholder="<?= htmlspecialchars(t('password_placeholder')) ?>" autocomplete="current-password">

        <button type="submit" class="btn"><?= htmlspecialchars(t('login')) ?></button>
      </form>
      <p class="hint"><?= htmlspecialchars(t('no_account')) ?> <a href="register.php"><?= htmlspecialchars(t('register')) ?></a></p>
    </div>
  </section>
</div>
</body>
</html>
