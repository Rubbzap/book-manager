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

    if ($data['title'] === '') $errors[] = tt('กรุณากรอกชื่อหนังสือ', 'Please enter a book title.');
    if ($data['author'] === '') $errors[] = tt('กรุณากรอกชื่อผู้แต่ง', 'Please enter an author.');
    if ($data['category'] === '') $errors[] = tt('กรุณากรอกหมวดหมู่', 'Please enter a category.');
    if ($data['cover_image_url'] !== '' && !filter_var($data['cover_image_url'], FILTER_VALIDATE_URL)) {
        $errors[] = tt('URL รูปปกไม่ถูกต้อง', 'The cover image URL is invalid.');
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

        $_SESSION['flash'] = tt('แก้ไขหนังสือ', 'Updated book') . ' "' . $data['title'] . '" ' . tt('เรียบร้อยแล้ว', 'successfully.');
        header('Location: index.php');
        exit;
    }
}

renderHeader(t('edit_book'));
?>
</head>
<body>
<nav>
  <a class="nav-brand" href="index.php"><span class="logo-mark">B</span> BookShelf</a>
  <span class="nav-user"><?= htmlspecialchars($_SESSION['username']) ?></span>
  <?php renderLanguageSwitch(); ?>
  <a class="nav-link" href="index.php"><?= htmlspecialchars(t('back')) ?></a>
  <a class="nav-link" href="../auth/logout.php"><?= htmlspecialchars(t('logout')) ?></a>
</nav>

<div class="container">
  <h2><?= htmlspecialchars(t('edit_book')) ?></h2>

  <?php if ($errors): ?>
    <div class="alert alert-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
  <?php endif; ?>

  <div class="card" style="max-width:720px">
    <form method="POST">
      <div class="form-group">
        <label for="title"><?= htmlspecialchars(t('required_title')) ?></label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($data['title']) ?>">
      </div>

      <div class="form-group">
        <label for="author"><?= htmlspecialchars(t('required_author')) ?></label>
        <input type="text" id="author" name="author" value="<?= htmlspecialchars($data['author']) ?>">
      </div>

      <div style="display:flex; gap:16px;">
        <div class="form-group" style="flex:2">
          <label for="category"><?= htmlspecialchars(t('required_category')) ?></label>
          <input type="text" id="category" name="category" value="<?= htmlspecialchars($data['category']) ?>">
        </div>
        <div class="form-group" style="flex:1">
          <label for="year"><?= htmlspecialchars(t('year')) ?></label>
          <input type="number" id="year" name="year" value="<?= htmlspecialchars($data['year'] ?? '') ?>" min="1800" max="2100">
        </div>
      </div>

      <div class="form-group">
        <label for="description"><?= htmlspecialchars(t('description')) ?></label>
        <textarea id="description" name="description"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label for="cover_image_url"><?= htmlspecialchars(t('cover_url')) ?></label>
        <input type="text" id="cover_image_url" name="cover_image_url" value="<?= htmlspecialchars($data['cover_image_url'] ?? '') ?>" placeholder="https://example.com/book-cover.jpg">
        <small style="color:var(--muted)"><?= htmlspecialchars(t('cover_note_edit')) ?></small>
      </div>

      <img id="cover-preview" src="" alt="<?= htmlspecialchars(t('cover_preview')) ?>" style="display:none; width:96px; height:136px; object-fit:cover; border-radius:8px; margin-bottom:18px; box-shadow:0 10px 24px rgba(0,0,0,.16)">

      <div style="display:flex; gap:12px; align-items:center">
        <button type="submit" class="btn-add"><?= htmlspecialchars(t('save_changes')) ?></button>
        <a href="index.php" style="color:var(--muted); text-decoration:none; font-size:.9rem"><?= htmlspecialchars(t('cancel')) ?></a>
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
