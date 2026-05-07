<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

function isAdmin(): bool {
    return ($_SESSION['username'] ?? '') === 'admin';
}

function renderHeader(string $pageTitle = 'Book Manager'): void {
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — Book Manager</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Sarabun:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --ink: #18201c; --paper: #f6f3ee; --cream: #ebe4d8;
    --accent: #8b4b2f; --accent-light: #b96d45;
    --muted: #716a60; --border: #d8d0c3;
    --danger: #c0392b; --success: #1f8a4c;
  }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { background: radial-gradient(circle at top left, rgba(185,109,69,.16), transparent 32vw), linear-gradient(180deg, #fbfaf7 0%, var(--paper) 42%, #eee8dc 100%); min-height: 100vh; font-family: "Sarabun", sans-serif; color: var(--ink); }
  nav { background: rgba(24,32,28,.96); backdrop-filter: blur(12px); padding: 0 32px; display: flex; align-items: center; gap: 16px; min-height: 64px; box-shadow: 0 12px 30px rgba(24,32,28,.18); position: sticky; top: 0; z-index: 20; }
  .nav-brand { font-family: "Playfair Display", serif; color: #f5f0e8; font-size: 1.2rem; text-decoration: none; margin-right: auto; display: inline-flex; align-items: center; gap: 10px; font-weight: 700; }
  .logo-mark { width: 34px; height: 34px; border-radius: 10px; background: linear-gradient(135deg, #f5d7a1, #b96d45); color: var(--ink); display: inline-grid; place-items: center; font-family: "Playfair Display", serif; font-weight: 700; box-shadow: inset 0 -4px 0 rgba(0,0,0,.08); }
  .nav-user { color: var(--border); font-size: .85rem; }
  .nav-link { color: var(--border); text-decoration: none; font-size: .85rem; padding: 6px 12px; border: 1px solid rgba(255,255,255,.15); border-radius: 3px; transition: all .2s; }
  .nav-link:hover { background: rgba(255,255,255,.1); color: #fff; }
  .nav-link.primary { background: var(--accent); border-color: var(--accent); color: #fff; }
  .container { max-width: 1100px; margin: 0 auto; padding: 32px 24px; }
  h2 { font-family: "Playfair Display", serif; font-size: 1.8rem; margin-bottom: 24px; }
  .card { background: rgba(255,255,255,.92); border: 1px solid var(--border); border-radius: 8px; padding: 24px; box-shadow: 0 14px 34px rgba(24,32,28,.08); }
  table { width: 100%; border-collapse: collapse; }
  th { text-align: left; padding: 10px 14px; background: var(--ink); color: var(--paper); font-weight: 500; font-size: .85rem; }
  td { padding: 10px 14px; border-bottom: 1px solid var(--cream); font-size: .95rem; }
  tr:hover td { background: var(--paper); }
  .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: .75rem; font-weight: 500; background: var(--cream); border: 1px solid var(--border); color: var(--accent); }
  .btn-sm { padding: 4px 12px; font-size: .8rem; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; font-family: "Sarabun", sans-serif; transition: opacity .2s; }
  .btn-sm:hover { opacity: .8; }
  .btn-edit { background: var(--ink); color: #fff; }
  .btn-delete { background: var(--danger); color: #fff; }
  .btn-add { background: var(--accent); color: #fff; padding: 10px 20px; border-radius: 3px; text-decoration: none; font-size: .9rem; border: none; cursor: pointer; font-family: "Sarabun", sans-serif; transition: background .2s; display: inline-block; }
  .btn-add:hover { background: var(--accent-light); }
  .form-group { margin-bottom: 20px; }
  label { display: block; font-size: .85rem; font-weight: 500; margin-bottom: 6px; }
  input[type=text], input[type=number], input[type=email], input[type=password], select, textarea { width: 100%; padding: 10px 14px; border: 1.5px solid var(--border); border-radius: 3px; background: var(--paper); font-family: "Sarabun", sans-serif; font-size: 1rem; color: var(--ink); transition: border-color .2s; }
  input:focus, select:focus, textarea:focus { outline: none; border-color: var(--accent); background: #fff; }
  textarea { resize: vertical; min-height: 100px; }
  .alert { padding: 10px 16px; border-radius: 3px; margin-bottom: 20px; font-size: .9rem; }
  .alert-success { background: #eafaf1; border: 1px solid #a9dfbf; color: var(--success); }
  .alert-error { background: #fdf0f0; border: 1px solid #f5c6c6; color: var(--danger); }
  .search-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: flex-end; }
  .search-bar input, .search-bar select { flex: 1; min-width: 160px; }
  .empty { text-align: center; padding: 40px; color: var(--muted); }
  .user-shell { max-width: 1180px; }
  .user-nav { background: rgba(255,255,255,.88); border-bottom: 1px solid var(--border); box-shadow: 0 8px 24px rgba(26,18,8,.08); }
  .user-nav .nav-brand { color: var(--ink); }
  .user-nav .nav-user { color: var(--muted); }
  .user-nav .nav-link { color: var(--accent); border-color: var(--border); background: #fff; }
  .hero { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(240px, .6fr); gap: 24px; align-items: stretch; margin-bottom: 24px; }
  .hero-main { background: linear-gradient(135deg, rgba(255,255,255,.96), rgba(246,243,238,.9)); border: 1px solid var(--border); border-radius: 12px; padding: 32px; box-shadow: 0 18px 44px rgba(26,18,8,.12); }
  .hero-main h1 { font-family: "Playfair Display", serif; font-size: 2.5rem; line-height: 1.15; margin-bottom: 10px; }
  .hero-main p { color: var(--muted); max-width: 620px; line-height: 1.65; }
  .hero-side { background: linear-gradient(145deg, #18201c, #354439); color: var(--paper); border-radius: 12px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between; min-height: 210px; }
  .hero-side strong { display: block; font-family: "Playfair Display", serif; font-size: 2.4rem; line-height: 1; margin-bottom: 6px; }
  .hero-side span { color: var(--border); font-size: .9rem; }
  .user-search { background: #fff; border: 1px solid var(--border); border-radius: 8px; padding: 14px; box-shadow: 0 10px 24px rgba(26,18,8,.08); }
  .user-search .btn-add { border-radius: 6px; }
  .category-strip { display: flex; gap: 8px; flex-wrap: wrap; margin: 18px 0 22px; }
  .category-pill { color: var(--accent); background: rgba(255,255,255,.72); border: 1px solid var(--border); border-radius: 999px; padding: 7px 13px; text-decoration: none; font-size: .86rem; }
  .category-pill.active { background: var(--ink); color: var(--paper); border-color: var(--ink); }
  .book-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 18px; }
  .book-card { background: rgba(255,255,255,.96); border: 1px solid rgba(216,208,195,.9); border-radius: 12px; padding: 18px; min-height: 430px; box-shadow: 0 14px 34px rgba(26,18,8,.1); display: flex; flex-direction: column; transition: transform .18s, box-shadow .18s; overflow: hidden; }
  .book-card:hover { transform: translateY(-3px); box-shadow: 0 20px 46px rgba(26,18,8,.14); }
  .book-top { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 14px; }
  .book-cover { flex: 0 0 auto; width: 96px; height: 136px; border-radius: 8px; background: linear-gradient(135deg, var(--accent), var(--ink)); box-shadow: 8px 10px 20px rgba(26,18,8,.18); position: relative; object-fit: cover; border: 1px solid rgba(0,0,0,.08); }
  .book-cover::after { content: ""; position: absolute; top: 10px; bottom: 10px; left: 10px; width: 2px; background: rgba(255,255,255,.35); }
  img.book-cover { display: block; background: var(--cream); }
  img.book-cover::after { display: none; }
  .book-card h3 { font-family: "Playfair Display", serif; font-size: 1.16rem; line-height: 1.25; margin-bottom: 8px; }
  .book-meta { color: var(--muted); font-size: .9rem; margin-bottom: 12px; }
  .book-description { color: #4d4033; line-height: 1.55; font-size: .92rem; margin-bottom: 16px; flex: 1; }
  .book-footer { display: flex; justify-content: space-between; gap: 12px; align-items: center; color: var(--muted); font-size: .84rem; border-top: 1px solid var(--cream); padding-top: 12px; margin-bottom: 14px; }
  .source-link { color: var(--accent); border: 1px solid var(--border); border-radius: 6px; padding: 7px 10px; text-decoration: none; font-size: .82rem; background: #fff; white-space: nowrap; }
  .filters-panel { display: grid; grid-template-columns: minmax(220px, 1.5fr) repeat(3, minmax(150px, 1fr)) auto; gap: 10px; align-items: end; }
  .filters-panel .btn-add { height: 43px; }
  .meta-row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
  .mini-badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: .72rem; background: #f7f1e7; color: var(--muted); border: 1px solid var(--border); }
  .borrow-panel { display: grid; grid-template-columns: 1fr auto; gap: 8px; align-items: center; margin-top: auto; }
  .volume-checks { display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0; }
  .volume-check { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--border); border-radius: 999px; padding: 7px 10px; background: #fff; font-size: .82rem; cursor: pointer; }
  .volume-check input { width: auto; accent-color: var(--accent); }
  .borrow-panel select { min-width: 92px; }
  .borrow-status { background: #eef8f1; color: #1f7a43; border: 1px solid #b9dfc5; border-radius: 6px; padding: 10px 12px; font-size: .86rem; margin-top: auto; }
  .loan-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
  .btn-ghost { background: #fff; color: var(--accent); border: 1px solid var(--border); }
  .btn-danger { background: var(--danger); color: #fff; }
  .countdown { font-weight: 600; color: var(--ink); }
  .account-grid { display: grid; grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr); gap: 20px; align-items: start; }
  .loan-list { display: grid; gap: 12px; }
  .loan-item { border: 1px solid var(--border); border-radius: 8px; padding: 14px; background: #fff; }
  .loan-item strong { display: block; margin-bottom: 4px; }
  .loan-meta { color: var(--muted); font-size: .86rem; margin-bottom: 6px; }
  .toolbar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 18px; }
  .modal-backdrop { position: fixed; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(24,32,28,.48); z-index: 50; padding: 20px; }
  .modal-backdrop.active { display: flex; }
  .modal { width: min(480px, 100%); background: #fff; border-radius: 14px; border: 1px solid var(--border); padding: 24px; box-shadow: 0 24px 80px rgba(0,0,0,.28); }
  .modal h3 { margin-bottom: 10px; }
  .modal-actions { display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap; margin-top: 18px; }
  .locked-field { display: flex; gap: 8px; align-items: center; padding: 11px 13px; border: 1px solid var(--border); border-radius: 8px; background: #f7f1e7; color: var(--ink); word-break: break-all; }
  .user-edit-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
  @media (max-width: 760px) {
    nav { height: auto; padding: 14px 18px; flex-wrap: wrap; }
    .nav-brand { width: 100%; }
    .container { padding: 24px 16px; }
    .hero { grid-template-columns: 1fr; }
    .hero-main h1 { font-size: 2rem; }
    .hero-side { min-height: 150px; }
    .search-bar { align-items: stretch; }
    .search-bar input, .search-bar select, .search-bar button, .search-bar a { width: 100%; }
    .filters-panel { grid-template-columns: 1fr; }
    .account-grid { grid-template-columns: 1fr; }
    .borrow-panel { grid-template-columns: 1fr; }
    .user-edit-grid { grid-template-columns: 1fr; }
    table { display: block; overflow-x: auto; }
  }
</style>
<?php
}

function renderFooter(): void {
?>
</body>
</html>
<?php
}
