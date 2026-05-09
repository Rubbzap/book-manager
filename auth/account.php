<?php
require_once '../includes/layout.php';
require_once '../config/db.php';
require_once '../includes/schema.php';

$pdo = getDB();
ensureAppSchema($pdo);

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$errors = [];

function isValidGmail(?string $gmail): bool {
    return $gmail !== null && filter_var($gmail, FILTER_VALIDATE_EMAIL) && strtolower(substr($gmail, -10)) === '@gmail.com';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_gmail' && !isAdmin()) {
        $gmail = trim($_POST['gmail'] ?? '');
        if (!isValidGmail($gmail)) {
            $errors[] = tt('กรุณากรอก Gmail ให้ถูกต้อง เช่น name@gmail.com', 'Please enter a valid Gmail address, such as name@gmail.com.');
        } else {
            $pdo->prepare("UPDATE users SET gmail = ? WHERE id = ?")->execute([$gmail, $_SESSION['user_id']]);
            $_SESSION['flash'] = tt('บันทึกและล็อก Gmail เรียบร้อยแล้ว', 'Gmail saved and locked.');
            header('Location: account.php');
            exit;
        }
    }

    if ($action === 'remove_gmail' && !isAdmin()) {
        $pdo->prepare("UPDATE users SET gmail = NULL WHERE id = ?")->execute([$_SESSION['user_id']]);
        $_SESSION['flash'] = tt('ลบ Gmail ออกจากบัญชีเรียบร้อยแล้ว', 'Gmail removed from your account.');
        header('Location: account.php');
        exit;
    }

    if ($action === 'change_password') {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $hash = (string)$stmt->fetchColumn();

        if (!password_verify($oldPassword, $hash)) {
            $errors[] = tt('รหัสผ่านเก่าไม่ถูกต้อง', 'The current password is incorrect.');
        } elseif (strlen($newPassword) < 6) {
            $errors[] = tt('รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร', 'The new password must be at least 6 characters.');
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = tt('รหัสผ่านใหม่ไม่ตรงกัน', 'The new passwords do not match.');
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $_SESSION['user_id']]);
            $_SESSION['flash'] = tt('เปลี่ยนรหัสผ่านเรียบร้อยแล้ว', 'Password changed successfully.');
            header('Location: account.php');
            exit;
        }
    }

    if ($action === 'delete_self' && !isAdmin()) {
        $confirmText = trim($_POST['confirm_text'] ?? '');
        if ($confirmText !== 'DELETE') {
            $errors[] = tt('กรุณาพิมพ์ DELETE เพื่อยืนยันการลบบัญชี', 'Please type DELETE to confirm account deletion.');
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_SESSION['user_id']]);
            session_destroy();
            header('Location: login.php');
            exit;
        }
    }

    if ($action === 'admin_update_user' && isAdmin()) {
        $userId = (int)($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $gmail = trim($_POST['gmail'] ?? '');
        $gmailValue = $gmail === '' ? null : $gmail;

        if ($userId <= 0 || $username === '') {
            $errors[] = tt('ข้อมูลผู้ใช้ไม่ถูกต้อง', 'Invalid user information.');
        } elseif ($gmailValue !== null && !isValidGmail($gmailValue)) {
            $errors[] = tt('Gmail ของผู้ใช้ไม่ถูกต้อง', 'The user Gmail address is invalid.');
        } else {
            try {
                $pdo->prepare("UPDATE users SET username = ?, gmail = ? WHERE id = ?")->execute([$username, $gmailValue, $userId]);
                $_SESSION['flash'] = tt('แก้ไขบัญชีผู้ใช้เรียบร้อยแล้ว', 'User account updated.');
                header('Location: account.php');
                exit;
            } catch (PDOException $e) {
                $errors[] = tt('ชื่อผู้ใช้นี้ถูกใช้งานแล้ว', 'This username is already taken.');
            }
        }
    }

    if ($action === 'admin_delete_user' && isAdmin()) {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === (int)$_SESSION['user_id']) {
            $errors[] = tt('ไม่สามารถลบบัญชี admin ที่กำลังใช้งานอยู่ได้', 'You cannot delete the admin account currently in use.');
        } elseif ($userId > 0) {
            $pdo->prepare("DELETE FROM users WHERE id = ? AND username <> 'admin'")->execute([$userId]);
            $_SESSION['flash'] = tt('ลบบัญชีผู้ใช้เรียบร้อยแล้ว', 'User account deleted.');
            header('Location: account.php');
            exit;
        }
    }
}

$userStmt = $pdo->prepare("SELECT username, gmail, created_at FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch();

$loanStmt = $pdo->prepare(
    "SELECT loans.*, books.title, books.author, books.source, books.source_url
     FROM loans
     JOIN books ON books.id = loans.book_id
     WHERE loans.user_id = ?
     ORDER BY loans.created_at DESC
     LIMIT 30"
);
$loanStmt->execute([$_SESSION['user_id']]);
$loans = $loanStmt->fetchAll();

$users = [];
if (isAdmin()) {
    $users = $pdo->query(
        "SELECT users.id, users.username, users.gmail, users.created_at,
                COUNT(loans.id) AS active_loans
         FROM users
         LEFT JOIN loans ON loans.user_id = users.id AND loans.status = 'active' AND loans.due_at > NOW()
         GROUP BY users.id, users.username, users.gmail, users.created_at
         ORDER BY users.created_at DESC"
    )->fetchAll();
}

renderHeader(t('manage_account'));
?>
</head>
<body>
<nav class="<?= isAdmin() ? '' : 'user-nav' ?>">
  <a class="nav-brand" href="../books/index.php"><span class="logo-mark">B</span> BookShelf</a>
  <span class="nav-user"><?= htmlspecialchars($_SESSION['username']) ?></span>
  <?php renderLanguageSwitch(); ?>
  <a class="nav-link" href="../books/index.php"><?= htmlspecialchars(t('bookshelf')) ?></a>
  <a class="nav-link" href="logout.php"><?= htmlspecialchars(t('logout')) ?></a>
</nav>

<div class="container user-shell">
  <h2><?= htmlspecialchars(t('manage_account')) ?></h2>

  <?php if ($flash): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
  <?php endif; ?>

  <?php if (isAdmin()): ?>
    <div class="card">
      <div class="toolbar">
        <h3 style="margin:0; margin-right:auto"><?= htmlspecialchars(t('all_users')) ?></h3>
      </div>
      <table>
        <thead>
          <tr>
            <th><?= htmlspecialchars(t('user')) ?></th>
            <th>Gmail</th>
            <th><?= htmlspecialchars(t('active_loans')) ?></th>
            <th><?= htmlspecialchars(t('joined_at')) ?></th>
            <th><?= htmlspecialchars(t('actions')) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $row): ?>
            <tr>
              <td colspan="5">
                <form method="POST" class="user-edit-grid">
                  <input type="hidden" name="action" value="admin_update_user">
                  <input type="hidden" name="user_id" value="<?= (int)$row['id'] ?>">
                  <div>
                    <label><?= htmlspecialchars(t('username')) ?></label>
                    <input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>" <?= $row['username'] === 'admin' ? 'readonly' : '' ?>>
                  </div>
                  <div>
                    <label>Gmail</label>
                    <input type="email" name="gmail" value="<?= htmlspecialchars($row['gmail'] ?? '') ?>" placeholder="name@gmail.com">
                  </div>
                  <div>
                    <label><?= htmlspecialchars(t('active_loans')) ?></label>
                    <div class="locked-field"><?= (int)$row['active_loans'] ?> <?= htmlspecialchars(t('items')) ?></div>
                  </div>
                  <div>
                    <label><?= htmlspecialchars(t('joined_at')) ?></label>
                    <div class="locked-field"><?= htmlspecialchars($row['created_at']) ?></div>
                  </div>
                  <div style="grid-column:1 / -1; display:flex; gap:8px; flex-wrap:wrap">
                    <button class="btn-add" type="submit"><?= htmlspecialchars(t('save_account')) ?></button>
                    <?php if ($row['username'] !== 'admin'): ?>
                      <button class="btn-add btn-danger" type="submit" name="action" value="admin_delete_user" onclick="return confirm('<?= addslashes(htmlspecialchars(t('confirm_delete_account'))) ?> <?= addslashes(htmlspecialchars($row['username'])) ?> ?')"><?= htmlspecialchars(t('delete_account')) ?></button>
                    <?php endif; ?>
                  </div>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="account-grid">
      <section class="card">
        <h3 style="margin-bottom:14px"><?= htmlspecialchars(t('account_info')) ?></h3>
        <p style="color:var(--muted); margin-bottom:18px"><?= htmlspecialchars(t('gmail_locked_note')) ?></p>
        <div class="toolbar">
          <button class="btn-add btn-ghost" type="button" data-modal-open="passwordModal"><?= htmlspecialchars(t('change_password')) ?></button>
        </div>

        <div class="form-group">
          <label><?= htmlspecialchars(t('username')) ?></label>
          <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
        </div>

        <?php if (!empty($user['gmail'])): ?>
          <div class="form-group">
            <label><?= htmlspecialchars(t('locked_gmail')) ?></label>
            <div class="locked-field"><?= htmlspecialchars($user['gmail']) ?></div>
          </div>
          <form method="POST" onsubmit="return confirm('<?= addslashes(htmlspecialchars(tt('ยืนยันลบ Gmail ออกจากบัญชี? หากไม่มี Gmail จะยืมหนังสือไม่ได้', 'Remove Gmail from this account? You cannot borrow books without Gmail.'))) ?>')">
            <input type="hidden" name="action" value="remove_gmail">
            <button class="btn-add btn-ghost" type="submit"><?= htmlspecialchars(t('remove_gmail')) ?></button>
          </form>
        <?php else: ?>
          <form method="POST">
            <input type="hidden" name="action" value="update_gmail">
            <div class="form-group">
              <label for="gmail"><?= htmlspecialchars(t('add_gmail')) ?></label>
              <input type="email" id="gmail" name="gmail" placeholder="name@gmail.com" required>
            </div>
            <button class="btn-add" type="submit"><?= htmlspecialchars(t('save_lock_gmail')) ?></button>
          </form>
        <?php endif; ?>

        <div style="border-top:1px solid var(--border); margin-top:24px; padding-top:20px">
          <h3 style="margin-bottom:10px; color:var(--danger)"><?= htmlspecialchars(t('delete_account')) ?></h3>
          <p style="color:var(--muted); margin-bottom:14px"><?= htmlspecialchars(t('delete_popup_note')) ?></p>
          <button class="btn-add btn-danger" type="button" data-modal-open="deleteAccountModal"><?= htmlspecialchars(t('delete_my_account')) ?></button>
        </div>
      </section>

      <section class="card">
        <div class="toolbar">
          <h3 style="margin:0; margin-right:auto"><?= htmlspecialchars(t('loan_history')) ?></h3>
          <a class="source-link" href="../books/index.php"><?= htmlspecialchars(t('borrow_more')) ?></a>
        </div>
        <?php if (!$loans): ?>
          <div class="empty"><?= htmlspecialchars(t('empty_loans')) ?></div>
        <?php else: ?>
          <div class="loan-list">
            <?php foreach ($loans as $loan): ?>
              <?php $isActive = $loan['status'] === 'active' && strtotime($loan['due_at']) > time(); ?>
              <article class="loan-item">
                <strong><?= htmlspecialchars($loan['title']) ?> <span class="mini-badge"><?= htmlspecialchars(localizeValue($loan['volume_label'])) ?></span></strong>
                <div class="loan-meta">
                  <?= htmlspecialchars(t('by_author')) ?> <?= htmlspecialchars($loan['author']) ?> · <?= (int)$loan['duration_days'] ?> <?= htmlspecialchars(t((int)$loan['duration_days'] === 1 ? 'day' : 'days')) ?> · <?= htmlspecialchars(localizeValue($loan['source'] ?: 'Local')) ?>
                </div>
                <?php if ($isActive): ?>
                  <div><?= htmlspecialchars(t('time_left')) ?> <span class="countdown" data-due="<?= htmlspecialchars($loan['due_at']) ?>"></span></div>
                  <div class="loan-actions">
                    <form method="POST" action="../books/loan_action.php">
                      <input type="hidden" name="loan_id" value="<?= (int)$loan['id'] ?>">
                      <input type="hidden" name="action" value="return">
                      <button class="btn-sm btn-ghost" type="submit"><?= htmlspecialchars(t('return_book')) ?></button>
                    </form>
                    <form method="POST" action="../books/loan_action.php">
                      <input type="hidden" name="loan_id" value="<?= (int)$loan['id'] ?>">
                      <input type="hidden" name="action" value="cancel">
                      <button class="btn-sm btn-danger" type="submit" onclick="return confirm('<?= addslashes(htmlspecialchars(tt('ยืนยันยกเลิกการยืมรายการนี้?', 'Cancel this loan?'))) ?>')"><?= htmlspecialchars(t('cancel_borrow')) ?></button>
                    </form>
                  </div>
                <?php else: ?>
                  <div style="color:var(--muted)"><?= htmlspecialchars(t('status')) ?>: <?= htmlspecialchars(localizeValue($loan['status'])) ?> · <?= htmlspecialchars(t('due_at')) ?> <?= htmlspecialchars($loan['due_at']) ?></div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </div>
  <?php endif; ?>
</div>

<div class="modal-backdrop" id="passwordModal">
  <div class="modal">
    <h3><?= htmlspecialchars(t('change_password')) ?></h3>
    <form method="POST">
      <input type="hidden" name="action" value="change_password">
      <div class="form-group">
        <label><?= htmlspecialchars(t('old_password')) ?></label>
        <input type="password" name="old_password" required>
      </div>
      <div class="form-group">
        <label><?= htmlspecialchars(t('new_password')) ?></label>
        <input type="password" name="new_password" minlength="6" required>
      </div>
      <div class="form-group">
        <label><?= htmlspecialchars(t('confirm_new_password')) ?></label>
        <input type="password" name="confirm_password" minlength="6" required>
      </div>
      <div class="modal-actions">
        <button class="btn-add btn-ghost" type="button" data-modal-close><?= htmlspecialchars(t('cancel')) ?></button>
        <button class="btn-add" type="submit"><?= htmlspecialchars(t('change_password')) ?></button>
      </div>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="deleteAccountModal">
  <div class="modal">
    <h3 style="color:var(--danger)"><?= htmlspecialchars(t('confirm_delete_account')) ?></h3>
    <p style="color:var(--muted); margin-bottom:16px"><?= htmlspecialchars(t('delete_account_warning')) ?></p>
    <form method="POST">
      <input type="hidden" name="action" value="delete_self">
      <div class="form-group">
        <label><?= htmlspecialchars(t('type_delete')) ?></label>
        <input type="text" name="confirm_text" placeholder="DELETE" required>
      </div>
      <div class="modal-actions">
        <button class="btn-add btn-ghost" type="button" data-modal-close><?= htmlspecialchars(t('cancel')) ?></button>
        <button class="btn-add btn-danger" type="submit"><?= htmlspecialchars(t('delete_permanently')) ?></button>
      </div>
    </form>
  </div>
</div>

<script>
  document.querySelectorAll('[data-modal-open]').forEach(function (button) {
    button.addEventListener('click', function () {
      var modal = document.getElementById(button.dataset.modalOpen);
      if (modal) modal.classList.add('active');
    });
  });
  document.querySelectorAll('[data-modal-close], .modal-backdrop').forEach(function (item) {
    item.addEventListener('click', function (event) {
      if (event.target === item) item.closest('.modal-backdrop').classList.remove('active');
    });
  });
  document.querySelectorAll('.modal').forEach(function (modal) {
    modal.addEventListener('click', function (event) {
      event.stopPropagation();
    });
  });
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
