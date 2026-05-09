<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

function appLang(): string {
    return $_SESSION['lang'] ?? 'th';
}

function isEnglish(): bool {
    return appLang() === 'en';
}

function tt(string $th, string $en): string {
    return isEnglish() ? $en : $th;
}

function t(string $key, array $replace = []): string {
    static $dict = [
        'bookshelf' => ['th' => 'คลังหนังสือ', 'en' => 'Library'],
        'account' => ['th' => 'บัญชีของฉัน', 'en' => 'My Account'],
        'manage_account' => ['th' => 'จัดการบัญชี', 'en' => 'Account Management'],
        'logout' => ['th' => 'ออกจากระบบ', 'en' => 'Log out'],
        'back' => ['th' => 'กลับ', 'en' => 'Back'],
        'cancel' => ['th' => 'ยกเลิก', 'en' => 'Cancel'],
        'save' => ['th' => 'บันทึก', 'en' => 'Save'],
        'search' => ['th' => 'ค้นหา', 'en' => 'Search'],
        'all' => ['th' => 'ทั้งหมด', 'en' => 'All'],
        'all_categories' => ['th' => 'ทุกหมวดหมู่', 'en' => 'All categories'],
        'all_languages' => ['th' => 'ทุกภาษา', 'en' => 'All languages'],
        'all_sources' => ['th' => 'ทุกแหล่ง', 'en' => 'All sources'],
        'search_placeholder' => ['th' => 'ค้นหาชื่อหนังสือ ผู้แต่ง หรือคำอธิบาย...', 'en' => 'Search title, author, or description...'],
        'add_book' => ['th' => '+ เพิ่มหนังสือ', 'en' => '+ Add book'],
        'manage_books' => ['th' => 'จัดการหนังสือ', 'en' => 'Manage Books'],
        'manage_all_books' => ['th' => 'จัดการรายการหนังสือทั้งหมด', 'en' => 'Manage All Books'],
        'book_title' => ['th' => 'ชื่อหนังสือ', 'en' => 'Title'],
        'author' => ['th' => 'ผู้แต่ง', 'en' => 'Author'],
        'category' => ['th' => 'หมวด', 'en' => 'Category'],
        'language' => ['th' => 'ภาษา', 'en' => 'Language'],
        'source' => ['th' => 'แหล่ง', 'en' => 'Source'],
        'actions' => ['th' => 'จัดการ', 'en' => 'Actions'],
        'origin' => ['th' => 'ต้นทาง', 'en' => 'Source'],
        'edit' => ['th' => 'แก้ไข', 'en' => 'Edit'],
        'delete' => ['th' => 'ลบ', 'en' => 'Delete'],
        'empty_books' => ['th' => 'ไม่พบหนังสือที่ตรงกับเงื่อนไข', 'en' => 'No books match your filters.'],
        'empty_books_user' => ['th' => 'ไม่พบหนังสือ ลองเปลี่ยนคำค้นหาหรือฟิลเตอร์อีกครั้ง', 'en' => 'No books found. Try another search or filter.'],
        'hero_text' => ['th' => 'เลือกอ่านหนังสือไทย อังกฤษ มังงะ นิยาย ธุรกิจ เทคโนโลยี และหมวดอื่นๆ พร้อมระบบยืมแบบมีเวลาและจัดการรายการยืมได้ในบัญชี', 'en' => 'Browse Thai and English books, manga, novels, business, technology, and more with timed borrowing and account-based loan management.'],
        'gmail_required_hero' => ['th' => 'ก่อนยืมหนังสือ กรุณาเพิ่ม Gmail ในหน้าบัญชีของฉัน', 'en' => 'Add your Gmail in My Account before borrowing books.'],
        'books_in_library' => ['th' => 'เล่มในคลัง', 'en' => 'books in library'],
        'categories_to_browse' => ['th' => 'หมวดหมู่ให้เลือกอ่าน', 'en' => 'browse categories'],
        'by_author' => ['th' => 'โดย', 'en' => 'by'],
        'view_at' => ['th' => 'ดูที่', 'en' => 'View at'],
        'default_description' => ['th' => 'ยังไม่มีคำอธิบายสำหรับหนังสือเล่มนี้', 'en' => 'No description is available for this book yet.'],
        'single_volume' => ['th' => 'เล่มเดียว', 'en' => 'Single volume'],
        'choose_volume' => ['th' => 'เลือกเล่มที่ต้องการยืม', 'en' => 'Choose volumes to borrow'],
        'choose_duration' => ['th' => 'เลือกระยะเวลายืม', 'en' => 'Choose borrow duration'],
        'borrow' => ['th' => 'ยืม', 'en' => 'Borrow'],
        'return' => ['th' => 'คืน', 'en' => 'Return'],
        'return_book' => ['th' => 'คืนหนังสือ', 'en' => 'Return book'],
        'cancel_borrow' => ['th' => 'ยกเลิกยืม', 'en' => 'Cancel loan'],
        'add_gmail_to_borrow' => ['th' => 'เพิ่ม Gmail เพื่อยืม', 'en' => 'Add Gmail to borrow'],
        'time_left' => ['th' => 'เหลือเวลา', 'en' => 'Time left'],
        'expired' => ['th' => 'หมดเวลาแล้ว', 'en' => 'Expired'],
        'day' => ['th' => 'วัน', 'en' => 'day'],
        'days' => ['th' => 'วัน', 'en' => 'days'],
        'hour_short' => ['th' => 'ชม.', 'en' => 'h'],
        'minute' => ['th' => 'นาที', 'en' => 'm'],
        'second' => ['th' => 'วิ', 'en' => 's'],
        'confirm_cancel_book' => ['th' => 'ยืนยันยกเลิกการยืมเล่มนี้?', 'en' => 'Cancel this loan?'],
        'confirm_delete_book' => ['th' => 'ยืนยันลบหนังสือ:', 'en' => 'Delete book:'],
        'account_info' => ['th' => 'ข้อมูลบัญชี', 'en' => 'Account Info'],
        'all_users' => ['th' => 'บัญชีผู้ใช้ทั้งหมด', 'en' => 'All User Accounts'],
        'user' => ['th' => 'ผู้ใช้', 'en' => 'User'],
        'username' => ['th' => 'ชื่อผู้ใช้', 'en' => 'Username'],
        'password' => ['th' => 'รหัสผ่าน', 'en' => 'Password'],
        'old_password' => ['th' => 'รหัสผ่านเก่า', 'en' => 'Current password'],
        'new_password' => ['th' => 'รหัสผ่านใหม่', 'en' => 'New password'],
        'confirm_new_password' => ['th' => 'ยืนยันรหัสผ่านใหม่', 'en' => 'Confirm new password'],
        'change_password' => ['th' => 'เปลี่ยนรหัสผ่าน', 'en' => 'Change password'],
        'active_loans' => ['th' => 'กำลังยืม', 'en' => 'Active loans'],
        'joined_at' => ['th' => 'สมัครเมื่อ', 'en' => 'Joined'],
        'items' => ['th' => 'รายการ', 'en' => 'items'],
        'save_account' => ['th' => 'บันทึกบัญชี', 'en' => 'Save account'],
        'delete_account' => ['th' => 'ลบบัญชี', 'en' => 'Delete account'],
        'delete_my_account' => ['th' => 'ลบบัญชีของฉัน', 'en' => 'Delete my account'],
        'gmail_locked_note' => ['th' => 'Gmail จะถูกล็อกหลังบันทึก หากต้องการเปลี่ยนให้ลบอีเมลเดิมออกก่อน', 'en' => 'Gmail is locked after saving. Delete the current email first if you need to change it.'],
        'locked_gmail' => ['th' => 'Gmail ที่ล็อกไว้', 'en' => 'Locked Gmail'],
        'remove_gmail' => ['th' => 'ลบ Gmail ออก', 'en' => 'Remove Gmail'],
        'add_gmail' => ['th' => 'เพิ่ม Gmail', 'en' => 'Add Gmail'],
        'save_lock_gmail' => ['th' => 'บันทึกและล็อก Gmail', 'en' => 'Save and lock Gmail'],
        'delete_popup_note' => ['th' => 'เปิด popup เพื่อยืนยันการลบบัญชีถาวร', 'en' => 'Open a popup to confirm permanent account deletion.'],
        'loan_history' => ['th' => 'ประวัติการยืม', 'en' => 'Borrowing History'],
        'borrow_more' => ['th' => 'กลับไปยืมหนังสือ', 'en' => 'Borrow more books'],
        'empty_loans' => ['th' => 'ยังไม่มีประวัติการยืม', 'en' => 'No borrowing history yet.'],
        'status' => ['th' => 'สถานะ', 'en' => 'Status'],
        'due_at' => ['th' => 'กำหนดคืน', 'en' => 'Due'],
        'confirm_delete_account' => ['th' => 'ยืนยันลบบัญชี', 'en' => 'Confirm Account Deletion'],
        'delete_account_warning' => ['th' => 'บัญชีและประวัติการยืมทั้งหมดจะถูกลบถาวร', 'en' => 'Your account and all borrowing history will be permanently deleted.'],
        'type_delete' => ['th' => 'พิมพ์ DELETE เพื่อยืนยัน', 'en' => 'Type DELETE to confirm'],
        'delete_permanently' => ['th' => 'ลบบัญชีถาวร', 'en' => 'Delete permanently'],
        'login' => ['th' => 'เข้าสู่ระบบ', 'en' => 'Log in'],
        'register' => ['th' => 'สมัครบัญชี', 'en' => 'Create account'],
        'login_subtitle' => ['th' => 'ระบบจัดการหนังสือ — กรุณาเข้าสู่ระบบ', 'en' => 'Book management system — please log in'],
        'register_subtitle' => ['th' => 'สมัครบัญชีใหม่', 'en' => 'Create a new account'],
        'username_placeholder' => ['th' => 'กรอกชื่อผู้ใช้', 'en' => 'Enter username'],
        'password_placeholder' => ['th' => 'กรอกรหัสผ่าน', 'en' => 'Enter password'],
        'confirm_password_placeholder' => ['th' => 'กรอกรหัสผ่านอีกครั้ง', 'en' => 'Enter password again'],
        'min_3_chars' => ['th' => 'อย่างน้อย 3 ตัวอักษร', 'en' => 'At least 3 characters'],
        'min_6_chars' => ['th' => 'อย่างน้อย 6 ตัวอักษร', 'en' => 'At least 6 characters'],
        'no_account' => ['th' => 'ยังไม่มีบัญชี?', 'en' => 'No account yet?'],
        'have_account' => ['th' => 'มีบัญชีแล้ว?', 'en' => 'Already have an account?'],
        'add_new_book' => ['th' => 'เพิ่มหนังสือใหม่', 'en' => 'Add New Book'],
        'edit_book' => ['th' => 'แก้ไขหนังสือ', 'en' => 'Edit Book'],
        'required_title' => ['th' => 'ชื่อหนังสือ *', 'en' => 'Title *'],
        'required_author' => ['th' => 'ผู้แต่ง *', 'en' => 'Author *'],
        'required_category' => ['th' => 'หมวดหมู่ *', 'en' => 'Category *'],
        'year' => ['th' => 'ปีพิมพ์', 'en' => 'Year'],
        'description' => ['th' => 'คำอธิบาย', 'en' => 'Description'],
        'cover_url' => ['th' => 'URL รูปปกหนังสือ', 'en' => 'Book cover image URL'],
        'cover_note_create' => ['th' => 'วางลิงก์รูปปกจริงจากเว็บต้นทางหรือ CDN ถ้าไม่ใส่ ระบบจะสร้างปกสำรองให้', 'en' => 'Paste a real cover image URL from the source site or CDN. If empty, a fallback cover is generated.'],
        'cover_note_edit' => ['th' => 'วางลิงก์รูปปกจริง ถ้าล้างช่องนี้ ระบบจะสร้างปกสำรองให้หลังบันทึก', 'en' => 'Paste a real cover image URL. If cleared, a fallback cover will be generated after saving.'],
        'cover_preview' => ['th' => 'ตัวอย่างปก', 'en' => 'Cover preview'],
        'save_changes' => ['th' => 'บันทึกการแก้ไข', 'en' => 'Save changes'],
    ];

    $text = $dict[$key][appLang()] ?? $key;
    foreach ($replace as $name => $value) {
        $text = str_replace(':' . $name, (string)$value, $text);
    }
    return $text;
}

function localizeValue(?string $value): string {
    if ($value === null || $value === '') {
        return '';
    }
    if (strpos($value, '?') !== false) {
        return isEnglish() ? 'Unknown' : 'ไม่ระบุ';
    }

    $map = [
        'ไทย' => 'Thai',
        'อังกฤษ' => 'English',
        'หนังสือ' => 'Book',
        'การ์ตูนมังงะ' => 'Manga',
        'นวนิยาย' => 'Novel',
        'นิยาย' => 'Novel',
        'เล่มเดียว' => 'Single volume',
        'เล่ม 1' => 'Volume 1',
        'เล่ม 2' => 'Volume 2',
        'เล่ม 3' => 'Volume 3',
        'เล่ม 4' => 'Volume 4',
        'เล่ม 5' => 'Volume 5',
        'ปกอ่อน' => 'Paperback',
        'ปกแข็ง' => 'Hardcover',
        'นิยายแฟนตาซี' => 'Fantasy',
        'พัฒนาตนเอง' => 'Self-improvement',
        'การเงินการลงทุน' => 'Finance & Investing',
        'จิตวิทยา' => 'Psychology',
        'ธุรกิจ' => 'Business',
        'ชีวประวัติ' => 'Biography',
        'นวนิยาย' => 'Novel',
        'สารคดี' => 'Nonfiction',
        'ประวัติศาสตร์' => 'History',
        'วิทยาศาสตร์' => 'Science',
        'คอมพิวเตอร์' => 'Computing',
        'นิยาย' => 'Novel',
        'นิยายญี่ปุ่น' => 'Japanese Fiction',
        'สืบสวน' => 'Mystery',
        'นิยายไซไฟ' => 'Science Fiction',
        'มังงะ' => 'Manga',
        'วรรณกรรม' => 'Literature',
        'วรรณกรรมแปล' => 'Translated Literature',
        'วรรณกรรมไทย' => 'Thai Literature',
        'ออกแบบ' => 'Design',
        'นายอินทร์' => 'Naiin',
        'active' => 'Active',
        'returned' => 'Returned',
        'cancelled' => 'Cancelled',
    ];

    return isEnglish() ? ($map[$value] ?? $value) : $value;
}

function localizeDescription(?string $description, ?string $category, ?string $language, ?string $format): string {
    $description = trim($description ?? '');
    if (!isEnglish()) {
        return $description !== '' ? $description : t('default_description');
    }

    $categoryText = localizeValue($category ?: 'books');
    $languageText = localizeValue($language ?: '');
    $formatText = localizeValue($format ?: 'Book');
    $article = strtolower(substr($languageText, 0, 1)) === 'e' ? 'An' : 'A';

    if (($format ?? '') === 'การ์ตูนมังงะ') {
        return "{$article} {$languageText} manga title in {$categoryText}, organized by volume so readers can borrow the exact part they want.";
    }

    if (($format ?? '') === 'eBook') {
        return "{$article} {$languageText} eBook in {$categoryText}, selected for easy digital reading and quick discovery from the source store.";
    }

    if (($category ?? '') === 'นิยายแฟนตาซี') {
        return "A {$languageText} fantasy novel for readers who enjoy immersive worlds, high-stakes journeys, and continuing stories.";
    }

    if (($category ?? '') === 'นิยายไซไฟ') {
        return "A {$languageText} science fiction book for readers interested in future ideas, technology, space, and speculative worlds.";
    }

    if (($category ?? '') === 'วรรณกรรมไทย') {
        return "A Thai literature title selected for readers who want cultural stories, reflective prose, and locally rooted writing.";
    }

    return "{$article} {$languageText} {$formatText} in {$categoryText}, with borrowing options and a source link for more details.";
}

function langSwitchUrl(string $targetLang): string {
    $query = $_GET;
    $query['lang'] = $targetLang;
    return basename($_SERVER['PHP_SELF']) . '?' . http_build_query($query);
}

function renderLanguageSwitch(): void {
    $isEn = isEnglish();
    ?>
    <div class="lang-switch" aria-label="<?= htmlspecialchars(tt('เลือกภาษา', 'Choose language')) ?>">
      <a class="<?= !$isEn ? 'active' : '' ?>" href="<?= htmlspecialchars(langSwitchUrl('th')) ?>">TH</a>
      <a class="<?= $isEn ? 'active' : '' ?>" href="<?= htmlspecialchars(langSwitchUrl('en')) ?>">EN</a>
    </div>
    <?php
}
?>
