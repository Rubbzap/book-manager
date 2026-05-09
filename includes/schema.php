<?php

function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
    );
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function ensureAppSchema(PDO $pdo): void {
    if (!columnExists($pdo, 'users', 'gmail')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN gmail VARCHAR(255) NULL AFTER password");
    }

    $bookColumns = [
        'source' => "ALTER TABLE books ADD COLUMN source VARCHAR(40) NOT NULL DEFAULT 'Local' AFTER description",
        'source_url' => "ALTER TABLE books ADD COLUMN source_url VARCHAR(600) NULL AFTER source",
        'cover_color' => "ALTER TABLE books ADD COLUMN cover_color VARCHAR(20) NOT NULL DEFAULT '#8b4513' AFTER source_url",
        'language' => "ALTER TABLE books ADD COLUMN language VARCHAR(30) NOT NULL DEFAULT 'ไทย' AFTER cover_color",
        'format' => "ALTER TABLE books ADD COLUMN format VARCHAR(40) NOT NULL DEFAULT 'หนังสือ' AFTER language",
        'volume_options' => "ALTER TABLE books ADD COLUMN volume_options TEXT NULL AFTER format",
        'cover_image_url' => "ALTER TABLE books ADD COLUMN cover_image_url VARCHAR(700) NULL AFTER volume_options",
    ];

    foreach ($bookColumns as $column => $sql) {
        if (!columnExists($pdo, 'books', $column)) {
            $pdo->exec($sql);
        }
    }

    if (!tableExists($pdo, 'loans')) {
        $pdo->exec(
            "CREATE TABLE loans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                book_id INT NOT NULL,
                volume_label VARCHAR(80) NOT NULL DEFAULT 'เล่มเดียว',
                duration_days INT NOT NULL,
                borrowed_at DATETIME NOT NULL,
                due_at DATETIME NOT NULL,
                returned_at DATETIME NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_loans_user (user_id),
                INDEX idx_loans_book (book_id),
                CONSTRAINT fk_loans_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_loans_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    } else {
        if (!columnExists($pdo, 'loans', 'volume_label')) {
            $pdo->exec("ALTER TABLE loans ADD COLUMN volume_label VARCHAR(80) NOT NULL DEFAULT 'เล่มเดียว' AFTER book_id");
        }
    }

    seedExternalBooks($pdo);
    replaceLegacyLocalBooks($pdo);
    backfillBookCovers($pdo);
}

function bookSourceUrl(string $source, string $title): string {
    if ($source === 'meb') {
        return 'https://www.mebmarket.com/index.php?action=search_book&type=all&search=' . rawurlencode($title);
    }
    if ($source === 'นายอินทร์') {
        return 'https://www.google.com/search?q=' . rawurlencode('site:naiin.com ' . $title);
    }
    return 'https://www.google.com/search?q=' . rawurlencode($title);
}

function bookCoverImageUrl(string $title, string $author, string $coverColor): string {
    $hex = ltrim($coverColor, '#') ?: '8b4513';
    $text = mb_substr($title, 0, 36) . "\n" . mb_substr($author, 0, 20);
    return 'https://placehold.co/360x520/' . rawurlencode($hex) . '/fff/png?text=' . rawurlencode($text);
}

function backfillBookCovers(PDO $pdo): void {
    $stmt = $pdo->query("SELECT id, title, author, cover_color FROM books WHERE cover_image_url IS NULL OR cover_image_url = ''");
    $update = $pdo->prepare("UPDATE books SET cover_image_url = ? WHERE id = ?");
    foreach ($stmt->fetchAll() as $book) {
        $update->execute([
            bookCoverImageUrl($book['title'], $book['author'], $book['cover_color'] ?: '#8b4513'),
            $book['id'],
        ]);
    }
}

function replaceLegacyLocalBooks(PDO $pdo): void {
    $books = [
        [
            'old_title' => 'มนุษย์ต่างดาวในสวน',
            'title' => 'The War of the Worlds',
            'author' => 'H. G. Wells',
            'category' => "\u{0e19}\u{0e34}\u{0e22}\u{0e32}\u{0e22}\u{0e44}\u{0e0b}\u{0e44}\u{0e1f}",
            'year' => 1898,
            'description' => 'A classic science fiction novel about a Martian invasion of Earth, widely recognized as an early landmark of alien-invasion fiction.',
            'source' => 'Open Library',
            'source_url' => 'https://openlibrary.org/works/OL52114W/The_War_of_the_Worlds',
            'cover_color' => '#6a040f',
            'language' => "\u{0e2d}\u{0e31}\u{0e07}\u{0e01}\u{0e24}\u{0e29}",
            'format' => "\u{0e2b}\u{0e19}\u{0e31}\u{0e07}\u{0e2a}\u{0e37}\u{0e2d}",
            'volume_options' => json_encode(["\u{0e40}\u{0e25}\u{0e48}\u{0e21}\u{0e40}\u{0e14}\u{0e35}\u{0e22}\u{0e27}"], JSON_UNESCAPED_UNICODE),
            'cover_image_url' => 'https://covers.openlibrary.org/b/olid/OL1004006M-L.jpg',
        ],
        [
            'old_title' => 'โลกใบเล็กของซาลาวี',
            'title' => 'The Alchemist',
            'author' => 'Paulo Coelho',
            'category' => "\u{0e19}\u{0e27}\u{0e19}\u{0e34}\u{0e22}\u{0e32}\u{0e22}",
            'year' => 1988,
            'description' => 'A modern allegorical novel following Santiago, a shepherd who travels in search of treasure and a deeper sense of purpose.',
            'source' => 'Open Library',
            'source_url' => 'https://openlibrary.org/works/OL42604423W/The_Alchemist',
            'cover_color' => '#bc6c25',
            'language' => "\u{0e2d}\u{0e31}\u{0e07}\u{0e01}\u{0e24}\u{0e29}",
            'format' => "\u{0e2b}\u{0e19}\u{0e31}\u{0e07}\u{0e2a}\u{0e37}\u{0e2d}",
            'volume_options' => json_encode(["\u{0e40}\u{0e25}\u{0e48}\u{0e21}\u{0e40}\u{0e14}\u{0e35}\u{0e22}\u{0e27}"], JSON_UNESCAPED_UNICODE),
            'cover_image_url' => 'https://covers.openlibrary.org/b/olid/OL24647789M-L.jpg',
        ],
        [
            'old_title' => 'ประวัติศาสตร์ไทย',
            'title' => 'A History of Thailand',
            'author' => 'Chris Baker, Pasuk Phongpaichit',
            'category' => "\u{0e1b}\u{0e23}\u{0e30}\u{0e27}\u{0e31}\u{0e15}\u{0e34}\u{0e28}\u{0e32}\u{0e2a}\u{0e15}\u{0e23}\u{0e4c}",
            'year' => 2005,
            'description' => 'A readable academic history of Thailand covering political, economic, social, and cultural change from early kingdoms to the modern era.',
            'source' => 'Cambridge University Press',
            'source_url' => 'https://www.cambridge.org/core/books/history-of-thailand/history-of-thailand/1585D0A7E31B8B168F92729510BC2732',
            'cover_color' => '#1d3557',
            'language' => "\u{0e2d}\u{0e31}\u{0e07}\u{0e01}\u{0e24}\u{0e29}",
            'format' => "\u{0e2b}\u{0e19}\u{0e31}\u{0e07}\u{0e2a}\u{0e37}\u{0e2d}",
            'volume_options' => json_encode(["\u{0e40}\u{0e25}\u{0e48}\u{0e21}\u{0e40}\u{0e14}\u{0e35}\u{0e22}\u{0e27}"], JSON_UNESCAPED_UNICODE),
            'cover_image_url' => 'https://covers.openlibrary.org/b/isbn/9781009014830-L.jpg',
        ],
    ];

    $update = $pdo->prepare(
        "UPDATE books
         SET title = ?,
             author = ?,
             category = ?,
             year = ?,
             description = ?,
             source = ?,
             source_url = ?,
             cover_color = ?,
             language = ?,
             format = ?,
             volume_options = ?,
             cover_image_url = ?
         WHERE title = ? AND source = 'Local'"
    );

    foreach ($books as $book) {
        $update->execute([
            $book['title'],
            $book['author'],
            $book['category'],
            $book['year'],
            $book['description'],
            $book['source'],
            $book['source_url'],
            $book['cover_color'],
            $book['language'],
            $book['format'],
            $book['volume_options'],
            $book['cover_image_url'],
            $book['old_title'],
        ]);
    }
}

function seedExternalBooks(PDO $pdo): void {
    $books = [
        ['Atomic Habits เพราะชีวิตดีได้กว่าที่เป็น', 'James Clear', 'พัฒนาตนเอง', 'ไทย', 'eBook', 'meb', '#216869', ['เล่มเดียว']],
        ['Good Vibes Good Life ใช้คลื่นพลังบวกดึงดูดพลังสุข', 'Vex King', 'พัฒนาตนเอง', 'ไทย', 'eBook', 'meb', '#b85750', ['เล่มเดียว']],
        ['The Psychology of Money จิตวิทยาว่าด้วยเงิน', 'Morgan Housel', 'การเงินการลงทุน', 'ไทย', 'eBook', 'meb', '#2f4858', ['เล่มเดียว']],
        ['Ikigai ความหมายของการมีชีวิตอยู่', 'Hector Garcia', 'พัฒนาตนเอง', 'ไทย', 'หนังสือ', 'นายอินทร์', '#7b6f47', ['เล่มเดียว']],
        ['Deep Work', 'Cal Newport', 'Productivity', 'อังกฤษ', 'eBook', 'meb', '#1f4e5f', ['เล่มเดียว']],
        ['The 7 Habits of Highly Effective People', 'Stephen R. Covey', 'Productivity', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#294936', ['เล่มเดียว']],
        ['Thinking, Fast and Slow', 'Daniel Kahneman', 'จิตวิทยา', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#594157', ['เล่มเดียว']],
        ['How to Win Friends and Influence People', 'Dale Carnegie', 'พัฒนาตนเอง', 'อังกฤษ', 'eBook', 'meb', '#8d5a3b', ['เล่มเดียว']],
        ['Start With Why', 'Simon Sinek', 'ธุรกิจ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#2f4858', ['เล่มเดียว']],
        ['The Lean Startup', 'Eric Ries', 'ธุรกิจ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#246a73', ['เล่มเดียว']],
        ['Zero to One', 'Peter Thiel', 'ธุรกิจ', 'อังกฤษ', 'eBook', 'meb', '#4d4d4d', ['เล่มเดียว']],
        ['Hooked', 'Nir Eyal', 'ธุรกิจ', 'อังกฤษ', 'eBook', 'meb', '#6f4e37', ['เล่มเดียว']],
        ['The Brain Audit จิตวิทยาการขายที่สมองปฏิเสธไม่ลง', "Sean D'Souza", 'ธุรกิจ', 'ไทย', 'หนังสือ', 'นายอินทร์', '#7a4f2b', ['เล่มเดียว']],
        ['Never Split the Difference จิตวิทยาต่อรอง', 'Chris Voss', 'ธุรกิจ', 'ไทย', 'หนังสือ', 'นายอินทร์', '#383838', ['เล่มเดียว']],
        ['Rich Dad Poor Dad พ่อรวยสอนลูก', 'Robert T. Kiyosaki', 'การเงินการลงทุน', 'ไทย', 'หนังสือ', 'นายอินทร์', '#5a6f34', ['เล่มเดียว']],
        ['The Intelligent Investor', 'Benjamin Graham', 'การเงินการลงทุน', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#345995', ['เล่มเดียว']],
        ['Principles', 'Ray Dalio', 'ธุรกิจ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#1d3557', ['เล่มเดียว']],
        ['Outliers', 'Malcolm Gladwell', 'สารคดี', 'อังกฤษ', 'eBook', 'meb', '#8a817c', ['เล่มเดียว']],
        ['Sapiens เซเปียนส์', 'Yuval Noah Harari', 'ประวัติศาสตร์', 'ไทย', 'หนังสือ', 'นายอินทร์', '#6d597a', ['เล่มเดียว']],
        ['Homo Deus โฮโมดีอุส', 'Yuval Noah Harari', 'ประวัติศาสตร์', 'ไทย', 'หนังสือ', 'นายอินทร์', '#355070', ['เล่มเดียว']],
        ['21 Lessons for the 21st Century', 'Yuval Noah Harari', 'ประวัติศาสตร์', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#52796f', ['เล่มเดียว']],
        ['A Brief History of Time', 'Stephen Hawking', 'วิทยาศาสตร์', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#14213d', ['เล่มเดียว']],
        ['Cosmos', 'Carl Sagan', 'วิทยาศาสตร์', 'อังกฤษ', 'eBook', 'meb', '#3d5a80', ['เล่มเดียว']],
        ['The Selfish Gene', 'Richard Dawkins', 'วิทยาศาสตร์', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#4a5759', ['เล่มเดียว']],
        ['Factfulness', 'Hans Rosling', 'วิทยาศาสตร์', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#457b9d', ['เล่มเดียว']],
        ['Python Crash Course', 'Eric Matthes', 'คอมพิวเตอร์', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#264653', ['เล่มเดียว']],
        ['Clean Code', 'Robert C. Martin', 'คอมพิวเตอร์', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#2a9d8f', ['เล่มเดียว']],
        ['The Pragmatic Programmer', 'David Thomas', 'คอมพิวเตอร์', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#1b4332', ['เล่มเดียว']],
        ['Design Patterns', 'Erich Gamma', 'คอมพิวเตอร์', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#3a0ca3', ['เล่มเดียว']],
        ['JavaScript: The Good Parts', 'Douglas Crockford', 'คอมพิวเตอร์', 'อังกฤษ', 'eBook', 'meb', '#f4a261', ['เล่มเดียว']],
        ['You Don’t Know JS Yet', 'Kyle Simpson', 'คอมพิวเตอร์', 'อังกฤษ', 'eBook', 'meb', '#277da1', ['เล่ม 1', 'เล่ม 2']],
        ['Introduction to Algorithms', 'Thomas H. Cormen', 'คอมพิวเตอร์', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#023047', ['เล่มเดียว']],
        ['Artificial Intelligence: A Modern Approach', 'Stuart Russell', 'คอมพิวเตอร์', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#4c956c', ['เล่มเดียว']],
        ['ไอเอิร์นเฟลม Iron Flame', 'Rebecca Yarros', 'นิยายแฟนตาซี', 'ไทย', 'หนังสือ', 'นายอินทร์', '#603140', ['ปกอ่อน', 'ปกแข็ง', 'E-Book']],
        ['Fourth Wing', 'Rebecca Yarros', 'นิยายแฟนตาซี', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#8d0801', ['เล่มเดียว']],
        ['Harry Potter', 'J.K. Rowling', 'นิยายแฟนตาซี', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#5f0f40', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3', 'เล่ม 4', 'เล่ม 5', 'เล่ม 6', 'เล่ม 7']],
        ['The Lord of the Rings', 'J.R.R. Tolkien', 'นิยายแฟนตาซี', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#31572c', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3']],
        ['The Hobbit', 'J.R.R. Tolkien', 'นิยายแฟนตาซี', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#606c38', ['เล่มเดียว']],
        ['A Game of Thrones', 'George R.R. Martin', 'นิยายแฟนตาซี', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#582f0e', ['เล่มเดียว']],
        ['Dune', 'Frank Herbert', 'นิยายไซไฟ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#bc6c25', ['เล่ม 1', 'เล่ม 2']],
        ['Project Hail Mary', 'Andy Weir', 'นิยายไซไฟ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#15616d', ['เล่มเดียว']],
        ['The Martian', 'Andy Weir', 'นิยายไซไฟ', 'อังกฤษ', 'eBook', 'meb', '#0077b6', ['เล่มเดียว']],
        ['1984', 'George Orwell', 'วรรณกรรม', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#111111', ['เล่มเดียว']],
        ['Animal Farm', 'George Orwell', 'วรรณกรรม', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#a4161a', ['เล่มเดียว']],
        ['To Kill a Mockingbird', 'Harper Lee', 'วรรณกรรม', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#6a994e', ['เล่มเดียว']],
        ['The Great Gatsby', 'F. Scott Fitzgerald', 'วรรณกรรม', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#023e8a', ['เล่มเดียว']],
        ['Norwegian Wood', 'Haruki Murakami', 'วรรณกรรม', 'ไทย', 'หนังสือ', 'นายอินทร์', '#9d0208', ['เล่มเดียว']],
        ['Kafka on the Shore', 'Haruki Murakami', 'วรรณกรรม', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#7209b7', ['เล่มเดียว']],
        ['ความสุขของกะทิ', 'งามพรรณ เวชชาชีวะ', 'วรรณกรรมไทย', 'ไทย', 'หนังสือ', 'นายอินทร์', '#8f9779', ['เล่มเดียว']],
        ['เวลาในขวดแก้ว', 'ประภัสสร เสวิกุล', 'วรรณกรรมไทย', 'ไทย', 'หนังสือ', 'นายอินทร์', '#588157', ['เล่มเดียว']],
        ['เจ้าชายน้อย', 'Antoine de Saint-Exupery', 'วรรณกรรม', 'ไทย', 'หนังสือ', 'นายอินทร์', '#f77f00', ['เล่มเดียว']],
        ['The Little Prince', 'Antoine de Saint-Exupery', 'วรรณกรรม', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#fcbf49', ['เล่มเดียว']],
        ['Sherlock Holmes', 'Arthur Conan Doyle', 'สืบสวน', 'อังกฤษ', 'eBook', 'meb', '#343a40', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3']],
        ['The Da Vinci Code', 'Dan Brown', 'สืบสวน', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#6c584c', ['เล่มเดียว']],
        ['Angels & Demons', 'Dan Brown', 'สืบสวน', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#3c096c', ['เล่มเดียว']],
        ['The Silent Patient', 'Alex Michaelides', 'สืบสวน', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#8d99ae', ['เล่มเดียว']],
        ['Before the Coffee Gets Cold', 'Toshikazu Kawaguchi', 'วรรณกรรมแปล', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#6d6875', ['เล่ม 1', 'เล่ม 2']],
        ['The Midnight Library', 'Matt Haig', 'วรรณกรรม', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#4361ee', ['เล่มเดียว']],
        ['Educated', 'Tara Westover', 'ชีวประวัติ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#5e548e', ['เล่มเดียว']],
        ['Becoming', 'Michelle Obama', 'ชีวประวัติ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#9a8c98', ['เล่มเดียว']],
        ['Steve Jobs', 'Walter Isaacson', 'ชีวประวัติ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#adb5bd', ['เล่มเดียว']],
        ['Elon Musk', 'Walter Isaacson', 'ชีวประวัติ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#495057', ['เล่มเดียว']],
        ['The Diary of a Young Girl', 'Anne Frank', 'ชีวประวัติ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#774936', ['เล่มเดียว']],
        ['Blue Lock', 'Muneyuki Kaneshiro', 'มังงะ', 'ไทย', 'การ์ตูนมังงะ', 'นายอินทร์', '#0077b6', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3', 'เล่ม 4', 'เล่ม 5']],
        ['Jujutsu Kaisen มหาเวทย์ผนึกมาร', 'Gege Akutami', 'มังงะ', 'ไทย', 'การ์ตูนมังงะ', 'นายอินทร์', '#560bad', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3', 'เล่ม 4', 'เล่ม 5']],
        ['Demon Slayer ดาบพิฆาตอสูร', 'Koyoharu Gotouge', 'มังงะ', 'ไทย', 'การ์ตูนมังงะ', 'นายอินทร์', '#d00000', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3', 'เล่ม 4']],
        ['One Piece', 'Eiichiro Oda', 'มังงะ', 'ไทย', 'การ์ตูนมังงะ', 'นายอินทร์', '#ffb703', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3', 'เล่ม 4', 'เล่ม 5']],
        ['Naruto', 'Masashi Kishimoto', 'มังงะ', 'ไทย', 'การ์ตูนมังงะ', 'นายอินทร์', '#f77f00', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3', 'เล่ม 4', 'เล่ม 5']],
        ['Dragon Ball', 'Akira Toriyama', 'มังงะ', 'ไทย', 'การ์ตูนมังงะ', 'นายอินทร์', '#fb8500', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3']],
        ['Attack on Titan ผ่าพิภพไททัน', 'Hajime Isayama', 'มังงะ', 'ไทย', 'การ์ตูนมังงะ', 'นายอินทร์', '#6c757d', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3', 'เล่ม 4']],
        ['Chainsaw Man', 'Tatsuki Fujimoto', 'มังงะ', 'ไทย', 'การ์ตูนมังงะ', 'นายอินทร์', '#e85d04', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3']],
        ['Spy x Family', 'Tatsuya Endo', 'มังงะ', 'ไทย', 'การ์ตูนมังงะ', 'นายอินทร์', '#588157', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3']],
        ['My Hero Academia', 'Kohei Horikoshi', 'มังงะ', 'ไทย', 'การ์ตูนมังงะ', 'นายอินทร์', '#007f5f', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3']],
        ['Haikyu!!', 'Haruichi Furudate', 'มังงะ', 'ไทย', 'การ์ตูนมังงะ', 'นายอินทร์', '#f48c06', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3']],
        ['Tokyo Revengers', 'Ken Wakui', 'มังงะ', 'ไทย', 'การ์ตูนมังงะ', 'นายอินทร์', '#003566', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3']],
        ['Berserk', 'Kentaro Miura', 'มังงะ', 'อังกฤษ', 'การ์ตูนมังงะ', 'นายอินทร์', '#212529', ['เล่ม 1', 'เล่ม 2']],
        ['Vagabond', 'Takehiko Inoue', 'มังงะ', 'อังกฤษ', 'การ์ตูนมังงะ', 'นายอินทร์', '#7f5539', ['เล่ม 1', 'เล่ม 2']],
        ['Slam Dunk', 'Takehiko Inoue', 'มังงะ', 'ไทย', 'การ์ตูนมังงะ', 'นายอินทร์', '#c1121f', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3']],
        ['Death Note', 'Tsugumi Ohba', 'มังงะ', 'ไทย', 'การ์ตูนมังงะ', 'นายอินทร์', '#111111', ['เล่ม 1', 'เล่ม 2', 'เล่ม 3']],
        ['Monster', 'Naoki Urasawa', 'มังงะ', 'อังกฤษ', 'การ์ตูนมังงะ', 'นายอินทร์', '#4a4e69', ['เล่ม 1', 'เล่ม 2']],
        ['20th Century Boys', 'Naoki Urasawa', 'มังงะ', 'อังกฤษ', 'การ์ตูนมังงะ', 'นายอินทร์', '#9b2226', ['เล่ม 1', 'เล่ม 2']],
        ['Yotsuba&!', 'Kiyohiko Azuma', 'มังงะ', 'อังกฤษ', 'การ์ตูนมังงะ', 'นายอินทร์', '#80b918', ['เล่ม 1', 'เล่ม 2']],
        ['Your Name', 'Makoto Shinkai', 'นิยายญี่ปุ่น', 'ไทย', 'นิยาย', 'นายอินทร์', '#3a86ff', ['เล่มเดียว']],
        ['Weathering With You', 'Makoto Shinkai', 'นิยายญี่ปุ่น', 'ไทย', 'นิยาย', 'นายอินทร์', '#00b4d8', ['เล่มเดียว']],
        ['ปาฏิหาริย์ร้านชำของคุณนามิยะ', 'Keigo Higashino', 'วรรณกรรมแปล', 'ไทย', 'หนังสือ', 'นายอินทร์', '#99582a', ['เล่มเดียว']],
        ['ความลับใต้ทะเลสาบ', 'Keigo Higashino', 'สืบสวน', 'ไทย', 'หนังสือ', 'นายอินทร์', '#0a9396', ['เล่มเดียว']],
        ['The Devotion of Suspect X', 'Keigo Higashino', 'สืบสวน', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#005f73', ['เล่มเดียว']],
        ['Convenience Store Woman', 'Sayaka Murata', 'วรรณกรรมแปล', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#f28482', ['เล่มเดียว']],
        ['The Courage to Be Disliked', 'Ichiro Kishimi', 'จิตวิทยา', 'ไทย', 'หนังสือ', 'นายอินทร์', '#f6bd60', ['เล่มเดียว']],
        ['Man’s Search for Meaning', 'Viktor E. Frankl', 'จิตวิทยา', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#8ecae6', ['เล่มเดียว']],
        ['The Body Keeps the Score', 'Bessel van der Kolk', 'จิตวิทยา', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#219ebc', ['เล่มเดียว']],
        ['Quiet', 'Susan Cain', 'จิตวิทยา', 'อังกฤษ', 'eBook', 'meb', '#6b705c', ['เล่มเดียว']],
        ['Mindset', 'Carol S. Dweck', 'จิตวิทยา', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#b08968', ['เล่มเดียว']],
        ['Grit', 'Angela Duckworth', 'จิตวิทยา', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#a68a64', ['เล่มเดียว']],
        ['The Power of Now', 'Eckhart Tolle', 'จิตวิทยา', 'อังกฤษ', 'eBook', 'meb', '#52b788', ['เล่มเดียว']],
        ['The Subtle Art of Not Giving a F*ck', 'Mark Manson', 'พัฒนาตนเอง', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#f94144', ['เล่มเดียว']],
        ['Everything is F*cked', 'Mark Manson', 'พัฒนาตนเอง', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#577590', ['เล่มเดียว']],
        ['The Let Them Theory ทฤษฎีปล่อยเขา', 'Mel Robbins', 'พัฒนาตนเอง', 'ไทย', 'หนังสือ', 'นายอินทร์', '#9d4edd', ['เล่มเดียว']],
        ['Make Time', 'Jake Knapp', 'Productivity', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#ffbe0b', ['เล่มเดียว']],
        ['Sprint', 'Jake Knapp', 'ธุรกิจ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#3a86ff', ['เล่มเดียว']],
        ['Measure What Matters', 'John Doerr', 'ธุรกิจ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#4361ee', ['เล่มเดียว']],
        ['Good Strategy Bad Strategy', 'Richard Rumelt', 'ธุรกิจ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#023047', ['เล่มเดียว']],
        ['The Mom Test', 'Rob Fitzpatrick', 'ธุรกิจ', 'อังกฤษ', 'eBook', 'meb', '#ff006e', ['เล่มเดียว']],
        ['Inspired', 'Marty Cagan', 'ธุรกิจ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#8338ec', ['เล่มเดียว']],
        ['Escaping the Build Trap', 'Melissa Perri', 'ธุรกิจ', 'อังกฤษ', 'eBook', 'meb', '#3d405b', ['เล่มเดียว']],
        ['Don’t Make Me Think', 'Steve Krug', 'ออกแบบ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#ef476f', ['เล่มเดียว']],
        ['The Design of Everyday Things', 'Don Norman', 'ออกแบบ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#118ab2', ['เล่มเดียว']],
        ['Steal Like an Artist', 'Austin Kleon', 'ออกแบบ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#073b4c', ['เล่มเดียว']],
        ['Show Your Work!', 'Austin Kleon', 'ออกแบบ', 'อังกฤษ', 'หนังสือ', 'นายอินทร์', '#ffd166', ['เล่มเดียว']],
    ];

    $exists = $pdo->prepare("SELECT id FROM books WHERE title = ? AND author = ? LIMIT 1");
    $insert = $pdo->prepare(
        "INSERT INTO books (title, author, category, year, description, source, source_url, cover_color, language, format, volume_options, cover_image_url)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $update = $pdo->prepare(
        "UPDATE books
         SET source = ?,
             source_url = ?,
             cover_color = ?,
             language = ?,
             format = ?,
             volume_options = ?,
             cover_image_url = CASE
                WHEN cover_image_url IS NULL OR cover_image_url = '' OR cover_image_url LIKE '%placehold.co%' THEN ?
                ELSE cover_image_url
             END
         WHERE title = ? AND author = ?"
    );

    foreach ($books as $index => $book) {
        [$title, $author, $category, $language, $format, $source, $coverColor, $volumes] = $book;
        $description = bookDescription($category, $language, $format);
        $sourceUrl = bookSourceUrl($source, $title);
        $volumeJson = json_encode($volumes, JSON_UNESCAPED_UNICODE);
        $coverImageUrl = bookCoverImageUrl($title, $author, $coverColor);

        $exists->execute([$title, $author]);
        if ($exists->fetch()) {
            $update->execute([$source, $sourceUrl, $coverColor, $language, $format, $volumeJson, $coverImageUrl, $title, $author]);
            continue;
        }

        $insert->execute([
            $title,
            $author,
            $category,
            2010 + ($index % 16),
            $description,
            $source,
            $sourceUrl,
            $coverColor,
            $language,
            $format,
            $volumeJson,
            $coverImageUrl,
        ]);
    }
}

function bookDescription(string $category, string $language, string $format): string {
    $langText = $language === 'อังกฤษ' ? 'ภาษาอังกฤษ' : 'ภาษาไทย';
    if ($format === 'การ์ตูนมังงะ') {
        return "การ์ตูนมังงะ{$langText}ในหมวด{$category} เหมาะสำหรับเลือกยืมอ่านเป็นเล่มๆ และต่อภาคได้สะดวก";
    }
    if ($format === 'eBook') {
        return "หนังสือ eBook {$langText}หมวด{$category} คัดไว้สำหรับอ่านต่อยอดความรู้และความบันเทิง";
    }
    return "หนังสือ{$langText}หมวด{$category} พร้อมลิงก์ไปยังแหล่งต้นทางสำหรับดูรายละเอียดเพิ่มเติม";
}
