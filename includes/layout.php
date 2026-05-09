<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/i18n.php';
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
<html lang="<?= htmlspecialchars(appLang()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> - Book Manager</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Sarabun:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --ink: #18201c; --paper: #f6f3ee; --cream: #ebe4d8;
    --accent: #8b4b2f; --accent-light: #b96d45;
    --muted: #716a60; --border: #d8d0c3;
    --danger: #c0392b; --success: #1f8a4c;
  }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes softPop { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: scale(1); } }
  @keyframes shimmer { 0% { background-position: -220px 0; } 100% { background-position: 220px 0; } }
  @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes floatLift { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
  @keyframes glowPulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(185,109,69,.24); } 50% { box-shadow: 0 0 0 8px rgba(185,109,69,0); } }
  @keyframes rowReveal { from { opacity: 0; transform: translateX(-8px); } to { opacity: 1; transform: translateX(0); } }
  body { background: radial-gradient(circle at top left, rgba(185,109,69,.16), transparent 32vw), linear-gradient(180deg, #fbfaf7 0%, var(--paper) 42%, #eee8dc 100%); min-height: 100vh; font-family: "Sarabun", sans-serif; color: var(--ink); }
  nav { background: rgba(24,32,28,.96); backdrop-filter: blur(12px); padding: 0 32px; display: flex; align-items: center; gap: 16px; min-height: 64px; box-shadow: 0 12px 30px rgba(24,32,28,.18); position: sticky; top: 0; z-index: 20; animation: slideDown .45s ease both; }
  .nav-brand { font-family: "Playfair Display", serif; color: #f5f0e8; font-size: 1.2rem; text-decoration: none; margin-right: auto; display: inline-flex; align-items: center; gap: 10px; font-weight: 700; transition: transform .2s ease, opacity .2s ease; }
  .nav-brand:hover { transform: translateY(-1px); opacity: .94; }
  .logo-mark { width: 34px; height: 34px; border-radius: 10px; background: linear-gradient(135deg, #f5d7a1, #b96d45); color: var(--ink); display: inline-grid; place-items: center; font-family: "Playfair Display", serif; font-weight: 700; box-shadow: inset 0 -4px 0 rgba(0,0,0,.08); animation: softPop .5s ease both, glowPulse 2.8s ease-in-out 1s infinite; transition: transform .24s ease; }
  .nav-brand:hover .logo-mark { transform: rotate(-4deg) scale(1.05); }
  .nav-user { color: var(--border); font-size: .98rem; line-height: 1; font-weight: 600; letter-spacing: 0; padding: 8px 12px; border-radius: 999px; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12); max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; animation: fadeUp .48s ease .05s both; }
  .nav-link { color: var(--border); text-decoration: none; font-size: .85rem; padding: 6px 12px; border: 1px solid rgba(255,255,255,.15); border-radius: 3px; transition: background .2s ease, color .2s ease, transform .18s ease, box-shadow .18s ease, border-color .18s ease; animation: fadeUp .4s ease both; }
  .nav-link:nth-of-type(2) { animation-delay: .04s; }
  .nav-link:nth-of-type(3) { animation-delay: .08s; }
  .nav-link:nth-of-type(4) { animation-delay: .12s; }
  .nav-link:hover { background: rgba(255,255,255,.1); color: #fff; transform: translateY(-1px); box-shadow: 0 8px 18px rgba(0,0,0,.15); }
  .nav-link.primary { background: var(--accent); border-color: var(--accent); color: #fff; }
  .lang-switch { display: inline-flex; align-items: center; gap: 3px; padding: 3px; border: 1px solid rgba(255,255,255,.18); border-radius: 999px; background: rgba(255,255,255,.08); animation: fadeUp .4s ease .14s both; }
  .lang-switch a { color: var(--border); text-decoration: none; font-size: .74rem; font-weight: 600; line-height: 1; padding: 7px 9px; border-radius: 999px; transition: background .18s ease, color .18s ease, transform .18s ease; }
  .lang-switch a:hover { transform: translateY(-1px); color: #fff; }
  .lang-switch a.active { color: var(--ink); background: #f5d7a1; }
  .container { max-width: 1100px; margin: 0 auto; padding: 32px 24px; animation: fadeUp .55s ease both; }
  h2 { font-family: "Playfair Display", serif; font-size: 1.8rem; margin-bottom: 24px; animation: fadeUp .45s ease both; }
  .card { background: rgba(255,255,255,.92); border: 1px solid var(--border); border-radius: 8px; padding: 24px; box-shadow: 0 14px 34px rgba(24,32,28,.08); animation: fadeUp .5s ease both; transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease; }
  .card:hover { box-shadow: 0 18px 42px rgba(24,32,28,.1); border-color: rgba(185,109,69,.32); }
  table { width: 100%; border-collapse: collapse; animation: fadeUp .45s ease both; }
  th { text-align: left; padding: 10px 14px; background: var(--ink); color: var(--paper); font-weight: 500; font-size: .85rem; }
  td { padding: 10px 14px; border-bottom: 1px solid var(--cream); font-size: .95rem; }
  tbody tr { animation: rowReveal .34s ease both; }
  tbody tr:nth-child(2n) { animation-delay: .03s; }
  tbody tr:nth-child(3n) { animation-delay: .06s; }
  tr:hover td { background: var(--paper); }
  .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: .75rem; font-weight: 500; background: var(--cream); border: 1px solid var(--border); color: var(--accent); transition: transform .18s ease, background .18s ease; }
  .badge:hover { transform: translateY(-1px); background: #fff; }
  .btn-sm { padding: 4px 12px; font-size: .8rem; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; font-family: "Sarabun", sans-serif; transition: opacity .2s, transform .18s ease, box-shadow .18s ease; }
  .btn-sm:hover { opacity: .9; transform: translateY(-1px); box-shadow: 0 7px 14px rgba(24,32,28,.12); }
  .btn-edit { background: var(--ink); color: #fff; }
  .btn-delete { background: var(--danger); color: #fff; }
  .btn-add { background: var(--accent); color: #fff; padding: 10px 20px; border-radius: 3px; text-decoration: none; font-size: .9rem; border: none; cursor: pointer; font-family: "Sarabun", sans-serif; transition: background .2s, transform .18s, box-shadow .18s; display: inline-block; }
  .btn-add:hover { background: var(--accent-light); transform: translateY(-1px); box-shadow: 0 8px 20px rgba(139,75,47,.18); }
  .form-group { margin-bottom: 20px; animation: fadeUp .38s ease both; }
  .form-group:nth-of-type(2) { animation-delay: .03s; }
  .form-group:nth-of-type(3) { animation-delay: .06s; }
  .form-group:nth-of-type(4) { animation-delay: .09s; }
  label { display: block; font-size: .85rem; font-weight: 500; margin-bottom: 6px; transition: color .18s ease; }
  input[type=text], input[type=number], input[type=email], input[type=password], select, textarea { width: 100%; padding: 10px 14px; border: 1.5px solid var(--border); border-radius: 3px; background: var(--paper); font-family: "Sarabun", sans-serif; font-size: 1rem; color: var(--ink); transition: border-color .2s ease, background .2s ease, box-shadow .2s ease, transform .18s ease; }
  input:focus, select:focus, textarea:focus { outline: none; border-color: var(--accent); background: #fff; transform: translateY(-1px); box-shadow: 0 8px 18px rgba(139,75,47,.12); }
  textarea { resize: vertical; min-height: 100px; }
  .alert { padding: 10px 16px; border-radius: 3px; margin-bottom: 20px; font-size: .9rem; animation: softPop .26s ease both; }
  .alert-success { background: #eafaf1; border: 1px solid #a9dfbf; color: var(--success); }
  .alert-error { background: #fdf0f0; border: 1px solid #f5c6c6; color: var(--danger); }
  .search-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: flex-end; }
  .search-bar input, .search-bar select { flex: 1; min-width: 160px; }
  .empty { text-align: center; padding: 40px; color: var(--muted); animation: fadeUp .45s ease both; }
  .user-shell { max-width: 1180px; }
  .user-nav { background: rgba(255,255,255,.88); border-bottom: 1px solid var(--border); box-shadow: 0 8px 24px rgba(26,18,8,.08); }
  .user-nav .nav-brand { color: var(--ink); }
  .user-nav .nav-user { color: var(--ink); background: #f7f1e7; border-color: var(--border); box-shadow: 0 6px 14px rgba(26,18,8,.06); }
  .user-nav .nav-link { color: var(--accent); border-color: var(--border); background: #fff; }
  .user-nav .lang-switch { border-color: var(--border); background: #fff; }
  .user-nav .lang-switch a { color: var(--accent); }
  .user-nav .lang-switch a.active { color: #fff; background: var(--ink); }
  .hero { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(240px, .6fr); gap: 24px; align-items: stretch; margin-bottom: 24px; animation: fadeUp .62s ease both; }
  .hero-main { background: linear-gradient(135deg, rgba(255,255,255,.96), rgba(246,243,238,.9)); border: 1px solid var(--border); border-radius: 12px; padding: 32px; box-shadow: 0 18px 44px rgba(26,18,8,.12); animation: fadeUp .58s ease both; transition: transform .24s ease, box-shadow .24s ease; }
  .hero-main:hover { transform: translateY(-2px); box-shadow: 0 22px 54px rgba(26,18,8,.14); }
  .hero-main h1 { font-family: "Playfair Display", serif; font-size: 2.5rem; line-height: 1.15; margin-bottom: 10px; }
  .hero-main p { color: var(--muted); max-width: 620px; line-height: 1.65; }
  .hero-side { background: linear-gradient(145deg, #18201c, #354439); color: var(--paper); border-radius: 12px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between; min-height: 210px; animation: fadeUp .58s ease .08s both; transition: transform .24s ease, box-shadow .24s ease; }
  .hero-side:hover { transform: translateY(-2px); box-shadow: 0 20px 48px rgba(24,32,28,.18); }
  .hero-side strong { display: block; font-family: "Playfair Display", serif; font-size: 2.4rem; line-height: 1; margin-bottom: 6px; animation: floatLift 3.6s ease-in-out infinite; }
  .hero-side span { color: var(--border); font-size: .9rem; }
  .user-search { background: #fff; border: 1px solid var(--border); border-radius: 8px; padding: 14px; box-shadow: 0 10px 24px rgba(26,18,8,.08); animation: fadeUp .48s ease .04s both; }
  .user-search .btn-add { border-radius: 6px; }
  .category-strip { display: flex; gap: 8px; flex-wrap: wrap; margin: 18px 0 22px; }
  .category-pill { color: var(--accent); background: rgba(255,255,255,.72); border: 1px solid var(--border); border-radius: 999px; padding: 7px 13px; text-decoration: none; font-size: .86rem; animation: softPop .3s ease both; transition: transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease; }
  .category-pill:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(26,18,8,.1); background: #fff; }
  .category-pill.active { background: var(--ink); color: var(--paper); border-color: var(--ink); animation: softPop .24s ease both, glowPulse 2.8s ease-in-out infinite; }
  .book-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 18px; }
  .book-card { background: rgba(255,255,255,.96); border: 1px solid rgba(216,208,195,.9); border-radius: 12px; padding: 18px; min-height: 430px; box-shadow: 0 14px 34px rgba(26,18,8,.1); display: flex; flex-direction: column; transition: transform .18s, box-shadow .18s; overflow: hidden; animation: fadeUp .48s ease both; }
  .book-card:nth-child(2n) { animation-delay: .04s; }
  .book-card:nth-child(3n) { animation-delay: .08s; }
  .book-card:nth-child(4n) { animation-delay: .12s; }
  .book-card:hover { transform: translateY(-5px); box-shadow: 0 22px 52px rgba(26,18,8,.15); }
  .book-top { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 14px; }
  .book-cover { flex: 0 0 auto; width: 96px; height: 136px; border-radius: 8px; background: linear-gradient(135deg, var(--accent), var(--ink)); box-shadow: 8px 10px 20px rgba(26,18,8,.18); position: relative; object-fit: cover; border: 1px solid rgba(0,0,0,.08); transition: transform .24s ease, filter .24s ease, box-shadow .24s ease; }
  .book-cover::after { content: ""; position: absolute; top: 10px; bottom: 10px; left: 10px; width: 2px; background: rgba(255,255,255,.35); }
  img.book-cover { display: block; background: linear-gradient(90deg, var(--cream), #fff, var(--cream)); background-size: 220px 100%; animation: shimmer 1.4s linear infinite; }
  img.book-cover::after { display: none; }
  .book-card:hover .book-cover { transform: rotate(-1deg) scale(1.035); filter: saturate(1.05); box-shadow: 10px 14px 24px rgba(26,18,8,.22); }
  .book-card h3 { font-family: "Playfair Display", serif; font-size: 1.16rem; line-height: 1.25; margin-bottom: 8px; }
  .book-meta { color: var(--muted); font-size: .9rem; margin-bottom: 12px; }
  .book-description { color: #4d4033; line-height: 1.55; font-size: .92rem; margin-bottom: 16px; flex: 1; }
  .book-footer { display: flex; justify-content: space-between; gap: 12px; align-items: center; color: var(--muted); font-size: .84rem; border-top: 1px solid var(--cream); padding-top: 12px; margin-bottom: 14px; }
  .source-link { color: var(--accent); border: 1px solid var(--border); border-radius: 6px; padding: 7px 10px; text-decoration: none; font-size: .82rem; background: #fff; white-space: nowrap; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
  .source-link:hover { transform: translateY(-1px); border-color: rgba(139,75,47,.45); box-shadow: 0 7px 16px rgba(26,18,8,.1); }
  .filters-panel { display: grid; grid-template-columns: minmax(220px, 1.5fr) repeat(3, minmax(150px, 1fr)) auto; gap: 10px; align-items: end; }
  .filters-panel .btn-add { height: 43px; }
  .meta-row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
  .mini-badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: .72rem; background: #f7f1e7; color: var(--muted); border: 1px solid var(--border); transition: transform .18s ease, background .18s ease; }
  .mini-badge:hover { transform: translateY(-1px); background: #fff; }
  .borrow-panel { display: grid; grid-template-columns: 1fr auto; gap: 8px; align-items: center; margin-top: auto; }
  .volume-checks { display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0; }
  .volume-check { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--border); border-radius: 999px; padding: 7px 10px; background: #fff; font-size: .82rem; cursor: pointer; transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease; }
  .volume-check:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(26,18,8,.09); }
  .volume-check:has(input:checked) { background: #f7f1e7; border-color: var(--accent-light); color: var(--accent); animation: softPop .2s ease both; }
  .volume-check input { width: auto; accent-color: var(--accent); }
  .borrow-panel select { min-width: 92px; }
  .borrow-status { background: #eef8f1; color: #1f7a43; border: 1px solid #b9dfc5; border-radius: 6px; padding: 10px 12px; font-size: .86rem; margin-top: auto; animation: softPop .28s ease both; }
  .loan-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
  .btn-ghost { background: #fff; color: var(--accent); border: 1px solid var(--border); }
  .btn-danger { background: var(--danger); color: #fff; }
  .countdown { font-weight: 600; color: var(--ink); }
  .account-grid { display: grid; grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr); gap: 20px; align-items: start; }
  .loan-list { display: grid; gap: 12px; }
  .loan-item { border: 1px solid var(--border); border-radius: 8px; padding: 14px; background: #fff; animation: fadeUp .36s ease both; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
  .loan-item:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(26,18,8,.1); border-color: rgba(185,109,69,.35); }
  .loan-item strong { display: block; margin-bottom: 4px; }
  .loan-meta { color: var(--muted); font-size: .86rem; margin-bottom: 6px; }
  .toolbar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 18px; }
  .modal-backdrop { position: fixed; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(24,32,28,.48); z-index: 50; padding: 20px; }
  .modal-backdrop.active { display: flex; animation: softPop .18s ease both; }
  .modal { width: min(480px, 100%); background: #fff; border-radius: 14px; border: 1px solid var(--border); padding: 24px; box-shadow: 0 24px 80px rgba(0,0,0,.28); animation: softPop .22s cubic-bezier(.2,.9,.2,1) both; }
  .modal h3 { margin-bottom: 10px; }
  .modal-actions { display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap; margin-top: 18px; }
  .locked-field { display: flex; gap: 8px; align-items: center; padding: 11px 13px; border: 1px solid var(--border); border-radius: 8px; background: #f7f1e7; color: var(--ink); word-break: break-all; animation: softPop .28s ease both; }
  .user-edit-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation: none !important; transition: none !important; }
  }
  @media (max-width: 760px) {
    nav { height: auto; padding: 14px 18px; flex-wrap: wrap; }
    .nav-brand { width: 100%; }
    .nav-user { max-width: 100%; }
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
