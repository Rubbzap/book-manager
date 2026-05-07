CREATE DATABASE IF NOT EXISTS book_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE book_manager;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    gmail VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    year INT,
    description TEXT,
    source VARCHAR(40) NOT NULL DEFAULT 'Local',
    source_url VARCHAR(600) NULL,
    cover_color VARCHAR(20) NOT NULL DEFAULT '#8b4513',
    language VARCHAR(30) NOT NULL DEFAULT 'ไทย',
    format VARCHAR(40) NOT NULL DEFAULT 'หนังสือ',
    volume_options TEXT NULL,
    cover_image_url VARCHAR(700) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    volume_label VARCHAR(80) NOT NULL DEFAULT 'เล่มเดียว',
    duration_days INT NOT NULL,
    borrowed_at DATETIME NOT NULL,
    due_at DATETIME NOT NULL,
    returned_at DATETIME NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    email_sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_loans_user (user_id),
    INDEX idx_loans_book (book_id),
    CONSTRAINT fk_loans_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_loans_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- admin / admin1234
INSERT INTO users (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- รายการหนังสือประมาณ 100 เล่มจะถูก seed/อัปเดตอัตโนมัติจาก includes/schema.php เมื่อเปิดแอป
