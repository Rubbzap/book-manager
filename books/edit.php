<?php
require_once '../includes/layout.php';
require_once '../config/db.php';
require_once '../includes/schema.php';

if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$pdo = getDB();
ensureAppSchema($pdo);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: index.php');
    exit;
}

$errors = [];
$data = $book;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['title', 'author', 'category', 'year', 'description', 'cover_image_url'];
    foreach ($fields as $field) {
        $data[$field] = trim($_POST[$field] ?? '');
    }

    if ($data['title'] === '') $errors[] = 'กรุณากรอกชื่อหนังสือ';
    if ($data['author'] === '') $errors[] = 'กรุณากรอกชื่อผู้แต่ง';
    if ($data['category'] === '') $errors[] = 'กรุณากรอกหมวดหมู่';
    if ($data['cover_image_url'] !== '' && !filter_var($data['cover_image_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'URL รูปปกไม่ถูกต้อง';
    }

    if (!$errors) {
        $coverUrl = $data['cover_image_url'] !== ''
            ? $data['cover_image_url']
            : bookCoverImageUrl($data['title'], $data['author'], $data['cover_color'] ?? '#8b4513');

        $stmt = $pdo->prepare(
            "UPDATE books
             SET title = ?, author = ?, category = ?, year = ?, description = ?, cover_image_url = ?
             WHERE id = ?"
        );
        $stmt->execute([
            $data['title'],
            $data['author'],
            $data['category'],
            $data['year'] !== '' ? (int)$data['year'] : null,
            $data['description'],
            $coverUrl,
            $id,
        ]);

        $_SESSION['flash'] = 'แก้ไขหนังสือ "' . $data['title'] . '" เรียบร้อยแล้ว';
        header('Location: index.php');
        exit;
    }
}

renderHeader('แก้ไขหนังสือ');
?>
</head>
<body>
<nav>
  <a class="nav-brand" href="index.php"><span class="logo-mark">B</span> BookShelf</a>
  <span class="nav-user"><?= htmlspecialchars($_SESSION['username']) ?></span>
  <a class="nav-link" href="index.php">กลับ</a>
  <a class="nav-link" href="../auth/logout.php">ออกจากระบบ</a>
</nav>

<div class="container">
  <h2>แก้ไขหนังสือ</h2>

  <?php if ($errors): ?>
    <div class="alert alert-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
  <?php endif; ?>

  <div class="card" style="max-width:720px">
    <form method="POST">
      <div class="form-group">
        <label for="title">ชื่อหนังสือ *</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($data['title']) ?>">
      </div>

      <div class="form-group">
        <label for="author">ผู้แต่ง *</label>
        <input type="text" id="author" name="author" value="<?= htmlspecialchars($data['author']) ?>">
      </div>

      <div style="display:flex; gap:16px;">
        <div class="form-group" style="flex:2">
          <label for="category">หมวดหมู่ *</label>
          <input type="text" id="category" name="category" value="<?= htmlspecialchars($data['category']) ?>">
        </div>
        <div class="form-group" style="flex:1">
          <label for="year">ปีพิมพ์</label>
          <input type="number" id="year" name="year" value="<?= htmlspecialchars($data['year'] ?? '') ?>" min="1800" max="2100">
        </div>
      </div>

      <div class="form-group">
        <label for="description">คำอธิบาย</label>
        <textarea id="description" name="description"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label for="cover_image_url">URL รูปปกหนังสือ</label>
        <input type="text" id="cover_image_url" name="cover_image_url" value="<?= htmlspecialchars($data['cover_image_url'] ?? '') ?>" placeholder="https://example.com/book-cover.jpg">
        <small style="color:var(--muted)">วางลิงก์รูปปกจริง ถ้าล้างช่องนี้ ระบบจะสร้างปกสำรองให้หลังบันทึก</small>
      </div>

      <img id="cover-preview" src="" alt="ตัวอย่างปก" style="display:none; width:96px; height:136px; object-fit:cover; border-radius:8px; margin-bottom:18px; box-shadow:0 10px 24px rgba(0,0,0,.16)">

      <div style="display:flex; gap:12px; align-items:center">
        <button type="submit" class="btn-add">บันทึกการแก้ไข</button>
        <a href="index.php" style="color:var(--muted); text-decoration:none; font-size:.9rem">ยกเลิก</a>
      </div>
    </form>
  </div>
</div>

<script>
  const coverInput = document.getElementById('cover_image_url');
  const coverPreview = document.getElementById('cover-preview');
  function updateCoverPreview() {
    const value = coverInput.value.trim();
    coverPreview.style.display = value ? 'block' : 'none';
    coverPreview.src = value;
  }
  coverInput.addEventListener('input', updateCoverPreview);
  updateCoverPreview();
</script>
<?php renderFooter(); ?>
