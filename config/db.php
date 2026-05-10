<?php
$databaseUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: '';
$databaseParts = $databaseUrl ? parse_url($databaseUrl) : [];

define('DB_HOST', getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: ($databaseParts['host'] ?? 'mysql'));
define('DB_PORT', getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: ($databaseParts['port'] ?? '3306'));
define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: ($databaseParts['user'] ?? 'root'));
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: ($databaseParts['pass'] ?? 'secret'));
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: (isset($databaseParts['path']) ? ltrim($databaseParts['path'], '/') : 'book_manager'));

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
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
