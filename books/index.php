<?php
require_once '../includes/layout.php';
require_once '../config/db.php';
require_once '../includes/schema.php';

$pdo = getDB();
ensureAppSchema($pdo);
$isAdmin = isAdmin();

$userStmt = $pdo->prepare("SELECT gmail FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$currentUser = $userStmt->fetch() ?: ['gmail' => null];
$hasGmail = !empty($currentUser['gmail']) && strtolower(substr($currentUser['gmail'], -10)) === '@gmail.com';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$language = trim($_GET['language'] ?? '');
$source = trim($_GET['source'] ?? '');

$cats = $pdo->query("SELECT DISTINCT category FROM books ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$languages = $pdo->query("SELECT DISTINCT language FROM books ORDER BY language")->fetchAll(PDO::FETCH_COLUMN);
$sources = $pdo->query("SELECT DISTINCT source FROM books ORDER BY source")->fetchAll(PDO::FETCH_COLUMN);

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(title LIKE ? OR author LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category !== '') {
    $where[] = "category = ?";
    $params[] = $category;
}
if ($language !== '') {
    $where[] = "language = ?";
    $params[] = $language;
}
if ($source !== '') {
    $where[] = "source = ?";
    $params[] = $source;
}

$sql = "SELECT * FROM books";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY FIELD(format, 'การ์ตูนมังงะ') DESC, created_at DESC, title ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$activeLoans = [];
if (!$isAdmin) {
    $loanStmt = $pdo->prepare(
        "SELECT loans.*, books.title
         FROM loans
         JOIN books ON books.id = loans.book_id
         WHERE loans.user_id = ?
           AND loans.status = 'active'
           AND loans.due_at > NOW()
         ORDER BY loans.due_at ASC"
    );
    $loanStmt->execute([$_SESSION['user_id']]);
    foreach ($loanStmt->fetchAll() as $loan) {
        $activeLoans[(int)$loan['book_id']][] = $loan;
    }
}

$totalBooks = (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalCategories = count($cats);
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

function filterUrl(array $overrides): string {
    $query = array_merge($_GET, $overrides);
    foreach ($query as $key => $value) {
        if ($value === '') {
            unset($query[$key]);
        }
    }
    return 'index.php' . ($query ? '?' . http_build_query($query) : '');
}

function categoryUrl(string $category): string {
    return 'index.php' . ($category !== '' ? '?category=' . urlencode($category) : '');
}

renderHeader($isAdmin ? t('manage_books') : 'BookShelf Library');
?>
</head>
<body>
<nav class="<?= $isAdmin ? '' : 'user-nav' ?>">
  <a class="nav-brand" href="index.php"><span class="logo-mark">B</span> BookShelf</a>
  <span class="nav-user"><?= htmlspecialchars($_SESSION['username']) ?></span>
  <?php renderLanguageSwitch(); ?>
  <?php if ($isAdmin): ?>
    <a class="nav-link primary" href="create.php"><?= htmlspecialchars(t('add_book')) ?></a>
    <a class="nav-link" href="../auth/account.php"><?= htmlspecialchars(t('manage_account')) ?></a>
  <?php else: ?>
    <a class="nav-link" href="../auth/account.php"><?= htmlspecialchars(t('account')) ?></a>
  <?php endif; ?>
  <a class="nav-link" href="../auth/logout.php"><?= htmlspecialchars(t('logout')) ?></a>
</nav>

<div class="container <?= $isAdmin ? '' : 'user-shell' ?>">
  <?php if ($isAdmin): ?>
    <h2><?= htmlspecialchars(t('manage_all_books')) ?></h2>
  <?php else: ?>
    <section class="hero">
      <div class="hero-main">
        <h1>BookShelf Library</h1>
        <p><?= htmlspecialchars(t('hero_text')) ?></p>
        <?php if (!$hasGmail): ?>
          <p style="margin-top:12px; color:var(--danger)"><?= htmlspecialchars(t('gmail_required_hero')) ?></p>
        <?php endif; ?>
      </div>
      <aside class="hero-side">
        <div>
          <strong><?= $totalBooks ?></strong>
          <span><?= htmlspecialchars(t('books_in_library')) ?></span>
        </div>
        <div>
          <strong><?= $totalCategories ?></strong>
          <span><?= htmlspecialchars(t('categories_to_browse')) ?></span>
        </div>
      </aside>
    </section>
  <?php endif; ?>

  <?php if ($flash): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <form method="GET" class="search-bar user-search filters-panel">
    <input type="text" name="search" placeholder="<?= htmlspecialchars(t('search_placeholder')) ?>" value="<?= htmlspecialchars($search) ?>">
    <select name="category">
      <option value=""><?= htmlspecialchars(t('all_categories')) ?></option>
      <?php foreach ($cats as $cat): ?>
        <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars(localizeValue($cat)) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="language">
      <option value=""><?= htmlspecialchars(t('all_languages')) ?></option>
      <?php foreach ($languages as $lang): ?>
        <option value="<?= htmlspecialchars($lang) ?>" <?= $language === $lang ? 'selected' : '' ?>><?= htmlspecialchars(localizeValue($lang)) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="source">
      <option value=""><?= htmlspecialchars(t('all_sources')) ?></option>
      <?php foreach ($sources as $src): ?>
        <option value="<?= htmlspecialchars($src) ?>" <?= $source === $src ? 'selected' : '' ?>><?= htmlspecialchars(localizeValue($src)) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-add"><?= htmlspecialchars(t('search')) ?></button>
  </form>

  <div class="category-strip">
    <a class="category-pill <?= $category === '' ? 'active' : '' ?>" href="index.php"><?= htmlspecialchars(t('all')) ?></a>
    <?php foreach ($cats as $cat): ?>
      <a class="category-pill <?= $category === $cat ? 'active' : '' ?>" href="<?= htmlspecialchars(categoryUrl($cat)) ?>">
        <?= htmlspecialchars(localizeValue($cat)) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($isAdmin): ?>
    <div class="card">
      <?php if (empty($books)): ?>
        <div class="empty"><?= htmlspecialchars(t('empty_books')) ?></div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th><?= htmlspecialchars(t('book_title')) ?></th>
              <th><?= htmlspecialchars(t('author')) ?></th>
              <th><?= htmlspecialchars(t('category')) ?></th>
              <th><?= htmlspecialchars(t('language')) ?></th>
              <th><?= htmlspecialchars(t('source')) ?></th>
              <th><?= htmlspecialchars(t('actions')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($books as $i => $book): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($book['title']) ?></strong></td>
                <td><?= htmlspecialchars($book['author']) ?></td>
                <td><span class="badge"><?= htmlspecialchars(localizeValue($book['category'])) ?></span></td>
                <td><?= htmlspecialchars(localizeValue($book['language'])) ?></td>
                <td><?= htmlspecialchars(localizeValue($book['source'])) ?></td>
                <td style="white-space:nowrap">
                  <?php if (!empty($book['source_url'])): ?>
                    <a href="<?= htmlspecialchars($book['source_url']) ?>" class="btn-sm btn-edit" target="_blank" rel="noopener"><?= htmlspecialchars(t('origin')) ?></a>
                    &nbsp;
                  <?php endif; ?>
                  <a href="edit.php?id=<?= $book['id'] ?>" class="btn-sm btn-edit"><?= htmlspecialchars(t('edit')) ?></a>
                  &nbsp;
                  <a href="delete.php?id=<?= $book['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('<?= addslashes(htmlspecialchars(t('confirm_delete_book'))) ?> <?= addslashes(htmlspecialchars($book['title'])) ?>')"><?= htmlspecialchars(t('delete')) ?></a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <?php if (empty($books)): ?>
      <div class="card empty"><?= htmlspecialchars(t('empty_books_user')) ?></div>
    <?php else: ?>
      <section class="book-grid">
        <?php foreach ($books as $book): ?>
          <?php
            $description = localizeDescription($book['description'] ?? '', $book['category'] ?? '', $book['language'] ?? '', $book['format'] ?? '');
            if ($description === '') {
                $description = t('default_description');
            } elseif (mb_strlen($description) > 135) {
                $description = mb_substr($description, 0, 135) . '...';
            }
            $volumes = json_decode($book['volume_options'] ?? '[]', true);
            if (!is_array($volumes) || !$volumes) {
                $volumes = ['เล่มเดียว'];
            }
            $activeByVolume = [];
            foreach ($activeLoans[(int)$book['id']] ?? [] as $loan) {
                $activeByVolume[$loan['volume_label']] = $loan;
            }
          ?>
          <article class="book-card">
            <div class="book-top">
              <img class="book-cover"
                   src="<?= htmlspecialchars($book['cover_image_url'] ?: 'cover.php?id=' . (int)$book['id']) ?>"
                   alt="<?= htmlspecialchars(tt('ปกหนังสือ', 'Book cover')) ?> <?= htmlspecialchars($book['title']) ?>"
                   loading="lazy"
                   onerror="this.onerror=null;this.src='cover.php?id=<?= (int)$book['id'] ?>';">
              <div>
                <h3><?= htmlspecialchars($book['title']) ?></h3>
                <?php if (!empty($book['source_url'])): ?>
                  <a class="source-link" href="<?= htmlspecialchars($book['source_url']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars(t('view_at')) ?> <?= htmlspecialchars(localizeValue($book['source'])) ?></a>
                <?php endif; ?>
              </div>
            </div>
            <div class="meta-row">
              <span class="mini-badge"><?= htmlspecialchars(localizeValue($book['category'])) ?></span>
              <span class="mini-badge"><?= htmlspecialchars(localizeValue($book['language'])) ?></span>
              <span class="mini-badge"><?= htmlspecialchars(localizeValue($book['format'])) ?></span>
            </div>
            <p class="book-meta"><?= htmlspecialchars(t('by_author')) ?> <?= htmlspecialchars($book['author']) ?></p>
            <p class="book-description"><?= htmlspecialchars($description) ?></p>

            <?php if ($activeByVolume): ?>
              <?php foreach ($activeByVolume as $loan): ?>
                <div class="borrow-status">
                  <?= htmlspecialchars(localizeValue($loan['volume_label'])) ?> <?= htmlspecialchars(t('time_left')) ?> <span class="countdown" data-due="<?= htmlspecialchars($loan['due_at']) ?>"></span>
                  <div class="loan-actions">
                    <form method="POST" action="loan_action.php">
                      <input type="hidden" name="loan_id" value="<?= (int)$loan['id'] ?>">
                      <input type="hidden" name="action" value="return">
                      <input type="hidden" name="back" value="index">
                      <button class="btn-sm btn-ghost" type="submit"><?= htmlspecialchars(t('return')) ?></button>
                    </form>
                    <form method="POST" action="loan_action.php">
                      <input type="hidden" name="loan_id" value="<?= (int)$loan['id'] ?>">
                      <input type="hidden" name="action" value="cancel">
                      <input type="hidden" name="back" value="index">
                      <button class="btn-sm btn-danger" type="submit" onclick="return confirm('<?= addslashes(htmlspecialchars(t('confirm_cancel_book'))) ?>')"><?= htmlspecialchars(t('cancel')) ?></button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!$hasGmail): ?>
              <a class="btn-add" style="text-align:center; background:var(--muted); margin-top:auto" href="../auth/account.php"><?= htmlspecialchars(t('add_gmail_to_borrow')) ?></a>
            <?php else: ?>
              <form method="POST" action="borrow.php" style="margin-top:auto">
                <input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>">
                <div class="volume-checks" aria-label="<?= htmlspecialchars(t('choose_volume')) ?>">
                  <?php foreach ($volumes as $volume): ?>
                    <label class="volume-check">
                      <input type="checkbox" name="volume_labels[]" value="<?= htmlspecialchars($volume) ?>" <?= count($volumes) === 1 ? 'checked' : '' ?>>
                      <?= htmlspecialchars(localizeValue($volume)) ?>
                    </label>
                  <?php endforeach; ?>
                </div>
                <div class="borrow-panel">
                  <select name="duration_days" aria-label="<?= htmlspecialchars(t('choose_duration')) ?>">
                    <option value="1">1 <?= htmlspecialchars(t('day')) ?></option>
                    <option value="3">3 <?= htmlspecialchars(t('days')) ?></option>
                    <option value="5">5 <?= htmlspecialchars(t('days')) ?></option>
                    <option value="7">7 <?= htmlspecialchars(t('days')) ?></option>
                  </select>
                  <button class="btn-add" type="submit"><?= htmlspecialchars(t('borrow')) ?></button>
                </div>
              </form>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script>
  function updateCountdowns() {
    document.querySelectorAll('.countdown[data-due]').forEach(function (node) {
      var due = new Date(node.dataset.due.replace(' ', 'T')).getTime();
      var diff = due - Date.now();
      if (diff <= 0) {
        node.textContent = <?= json_encode(t('expired'), JSON_UNESCAPED_UNICODE) ?>;
        return;
      }
      var days = Math.floor(diff / 86400000);
      var hours = Math.floor((diff % 86400000) / 3600000);
      var minutes = Math.floor((diff % 3600000) / 60000);
      var seconds = Math.floor((diff % 60000) / 1000);
      node.textContent = days + ' <?= addslashes(t('days')) ?> ' + hours + ' <?= addslashes(t('hour_short')) ?> ' + minutes + ' <?= addslashes(t('minute')) ?> ' + seconds + ' <?= addslashes(t('second')) ?>';
    });
  }
  updateCountdowns();
  setInterval(updateCountdowns, 1000);
</script>
<?php renderFooter(); ?>
