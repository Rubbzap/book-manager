<?php
define('DB_HOST', 'mysql');   // ← ชื่อ service ใน docker-compose
define('DB_USER', 'root');
define('DB_PASS', 'secret');  // ← ตรงกับ MYSQL_ROOT_PASSWORD
define('DB_NAME', 'book_manager');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die("เชื่อมต่อฐานข้อมูลไม่ได้: " . $e->getMessage());
        }
    }
    return $pdo;
}
