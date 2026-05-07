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

renderHeader($isAdmin ? 'จัดการหนังสือ' : 'BookShelf Library');
?>
</head>
<body>
<nav class="<?= $isAdmin ? '' : 'user-nav' ?>">
  <a class="nav-brand" href="index.php"><span class="logo-mark">B</span> BookShelf</a>
  <span class="nav-user"><?= htmlspecialchars($_SESSION['username']) ?></span>
  <?php if ($isAdmin): ?>
    <a class="nav-link primary" href="create.php">+ เพิ่มหนังสือ</a>
    <a class="nav-link" href="../auth/account.php">จัดการบัญชี</a>
  <?php else: ?>
    <a class="nav-link" href="../auth/account.php">บัญชีของฉัน</a>
  <?php endif; ?>
  <a class="nav-link" href="../auth/logout.php">ออกจากระบบ</a>
</nav>

<div class="container <?= $isAdmin ? '' : 'user-shell' ?>">
  <?php if ($isAdmin): ?>
    <h2>จัดการรายการหนังสือทั้งหมด</h2>
  <?php else: ?>
    <section class="hero">
      <div class="hero-main">
        <h1>BookShelf Library</h1>
        <p>เลือกอ่านหนังสือไทย อังกฤษ มังงะ นิยาย ธุรกิจ เทคโนโลยี และหมวดอื่นๆ พร้อมระบบยืมแบบมีเวลาและแจ้งเตือนผ่าน Gmail</p>
        <?php if (!$hasGmail): ?>
          <p style="margin-top:12px; color:var(--danger)">ก่อนยืมหนังสือ กรุณาเพิ่ม Gmail ในหน้าบัญชีของฉัน</p>
        <?php endif; ?>
      </div>
      <aside class="hero-side">
        <div>
          <strong><?= $totalBooks ?></strong>
          <span>เล่มในคลัง</span>
        </div>
        <div>
          <strong><?= $totalCategories ?></strong>
          <span>หมวดหมู่ให้เลือกอ่าน</span>
        </div>
      </aside>
    </section>
  <?php endif; ?>

  <?php if ($flash): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <form method="GET" class="search-bar user-search filters-panel">
    <input type="text" name="search" placeholder="ค้นหาชื่อหนังสือ ผู้แต่ง หรือคำอธิบาย..." value="<?= htmlspecialchars($search) ?>">
    <select name="category">
      <option value="">ทุกหมวดหมู่</option>
      <?php foreach ($cats as $cat): ?>
        <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="language">
      <option value="">ทุกภาษา</option>
      <?php foreach ($languages as $lang): ?>
        <option value="<?= htmlspecialchars($lang) ?>" <?= $language === $lang ? 'selected' : '' ?>><?= htmlspecialchars($lang) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="source">
      <option value="">ทุกแหล่ง</option>
      <?php foreach ($sources as $src): ?>
        <option value="<?= htmlspecialchars($src) ?>" <?= $source === $src ? 'selected' : '' ?>><?= htmlspecialchars($src) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-add">ค้นหา</button>
  </form>

  <div class="category-strip">
    <a class="category-pill <?= $category === '' ? 'active' : '' ?>" href="index.php">ทั้งหมด</a>
    <?php foreach ($cats as $cat): ?>
      <a class="category-pill <?= $category === $cat ? 'active' : '' ?>" href="<?= htmlspecialchars(categoryUrl($cat)) ?>">
        <?= htmlspecialchars($cat) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($isAdmin): ?>
    <div class="card">
      <?php if (empty($books)): ?>
        <div class="empty">ไม่พบหนังสือที่ตรงกับเงื่อนไข</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>ชื่อหนังสือ</th>
              <th>ผู้แต่ง</th>
              <th>หมวด</th>
              <th>ภาษา</th>
              <th>แหล่ง</th>
              <th>จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($books as $i => $book): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($book['title']) ?></strong></td>
                <td><?= htmlspecialchars($book['author']) ?></td>
                <td><span class="badge"><?= htmlspecialchars($book['category']) ?></span></td>
                <td><?= htmlspecialchars($book['language']) ?></td>
                <td><?= htmlspecialchars($book['source']) ?></td>
                <td style="white-space:nowrap">
                  <?php if (!empty($book['source_url'])): ?>
                    <a href="<?= htmlspecialchars($book['source_url']) ?>" class="btn-sm btn-edit" target="_blank" rel="noopener">ต้นทาง</a>
                    &nbsp;
                  <?php endif; ?>
                  <a href="edit.php?id=<?= $book['id'] ?>" class="btn-sm btn-edit">แก้ไข</a>
                  &nbsp;
                  <a href="delete.php?id=<?= $book['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('ยืนยันลบหนังสือ: <?= addslashes(htmlspecialchars($book['title'])) ?>')">ลบ</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <?php if (empty($books)): ?>
      <div class="card empty">ไม่พบหนังสือ ลองเปลี่ยนคำค้นหาหรือฟิลเตอร์อีกครั้ง</div>
    <?php else: ?>
      <section class="book-grid">
        <?php foreach ($books as $book): ?>
          <?php
            $description = trim($book['description'] ?? '');
            if ($description === '') {
                $description = 'ยังไม่มีคำอธิบายสำหรับหนังสือเล่มนี้';
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
                   alt="ปกหนังสือ <?= htmlspecialchars($book['title']) ?>"
                   loading="lazy"
                   onerror="this.onerror=null;this.src='cover.php?id=<?= (int)$book['id'] ?>';">
              <div>
                <h3><?= htmlspecialchars($book['title']) ?></h3>
                <?php if (!empty($book['source_url'])): ?>
                  <a class="source-link" href="<?= htmlspecialchars($book['source_url']) ?>" target="_blank" rel="noopener">ดูที่ <?= htmlspecialchars($book['source']) ?></a>
                <?php endif; ?>
              </div>
            </div>
            <div class="meta-row">
              <span class="mini-badge"><?= htmlspecialchars($book['category']) ?></span>
              <span class="mini-badge"><?= htmlspecialchars($book['language']) ?></span>
              <span class="mini-badge"><?= htmlspecialchars($book['format']) ?></span>
            </div>
            <p class="book-meta">โดย <?= htmlspecialchars($book['author']) ?></p>
            <p class="book-description"><?= htmlspecialchars($description) ?></p>

            <?php if ($activeByVolume): ?>
              <?php foreach ($activeByVolume as $loan): ?>
                <div class="borrow-status">
                  <?= htmlspecialchars($loan['volume_label']) ?> เหลือเวลา <span class="countdown" data-due="<?= htmlspecialchars($loan['due_at']) ?>"></span>
                  <div class="loan-actions">
                    <form method="POST" action="loan_action.php">
                      <input type="hidden" name="loan_id" value="<?= (int)$loan['id'] ?>">
                      <input type="hidden" name="action" value="return">
                      <input type="hidden" name="back" value="index">
                      <button class="btn-sm btn-ghost" type="submit">คืน</button>
                    </form>
                    <form method="POST" action="loan_action.php">
                      <input type="hidden" name="loan_id" value="<?= (int)$loan['id'] ?>">
                      <input type="hidden" name="action" value="cancel">
                      <input type="hidden" name="back" value="index">
                      <button class="btn-sm btn-danger" type="submit" onclick="return confirm('ยืนยันยกเลิกการยืมเล่มนี้?')">ยกเลิก</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!$hasGmail): ?>
              <a class="btn-add" style="text-align:center; background:var(--muted); margin-top:auto" href="../auth/account.php">เพิ่ม Gmail เพื่อยืม</a>
            <?php else: ?>
              <form method="POST" action="borrow.php" style="margin-top:auto">
                <input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>">
                <div class="volume-checks" aria-label="เลือกเล่มที่ต้องการยืม">
                  <?php foreach ($volumes as $volume): ?>
                    <label class="volume-check">
                      <input type="checkbox" name="volume_labels[]" value="<?= htmlspecialchars($volume) ?>" <?= count($volumes) === 1 ? 'checked' : '' ?>>
                      <?= htmlspecialchars($volume) ?>
                    </label>
                  <?php endforeach; ?>
                </div>
                <div class="borrow-panel">
                  <select name="duration_days" aria-label="เลือกระยะเวลายืม">
                    <option value="1">1 วัน</option>
                    <option value="3">3 วัน</option>
                    <option value="5">5 วัน</option>
                    <option value="7">7 วัน</option>
                  </select>
                  <button class="btn-add" type="submit">ยืม</button>
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
        node.textContent = 'หมดเวลาแล้ว';
        return;
      }
      var days = Math.floor(diff / 86400000);
      var hours = Math.floor((diff % 86400000) / 3600000);
      var minutes = Math.floor((diff % 3600000) / 60000);
      var seconds = Math.floor((diff % 60000) / 1000);
      node.textContent = days + ' วัน ' + hours + ' ชม. ' + minutes + ' นาที ' + seconds + ' วิ';
    });
  }
  updateCountdowns();
  setInterval(updateCountdowns, 1000);
</script>
<?php renderFooter(); ?>
