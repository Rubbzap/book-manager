<?php
if (!function_exists('envValue')) {
    function envValue(array $keys): string {
        foreach ($keys as $key) {
            $value = getenv($key);
            if ($value !== false && $value !== '') {
                return $value;
            }
        }

        return '';
    }
}

$databaseUrl = envValue([
    'MYSQL_URL',
    'MYSQL_PRIVATE_URL',
    'MYSQL_PUBLIC_URL',
    'DATABASE_URL',
    'DATABASE_PRIVATE_URL',
    'DATABASE_PUBLIC_URL',
]);
$parsedDatabaseUrl = $databaseUrl ? parse_url($databaseUrl) : [];
$databaseParts = is_array($parsedDatabaseUrl) ? $parsedDatabaseUrl : [];
$databasePath = isset($databaseParts['path']) ? ltrim($databaseParts['path'], '/') : '';
$isRailway = envValue(['RAILWAY_ENVIRONMENT', 'RAILWAY_ENVIRONMENT_NAME', 'RAILWAY_PROJECT_ID']) !== '';
$defaultHost = $isRailway ? '' : 'mysql';

define('DB_HOST', envValue(['MYSQLHOST', 'DB_HOST']) ?: ($databaseParts['host'] ?? $defaultHost));
define('DB_PORT', envValue(['MYSQLPORT', 'DB_PORT']) ?: (string)($databaseParts['port'] ?? '3306'));
define('DB_USER', envValue(['MYSQLUSER', 'DB_USER']) ?: (isset($databaseParts['user']) ? rawurldecode($databaseParts['user']) : 'root'));
define('DB_PASS', envValue(['MYSQLPASSWORD', 'DB_PASS']) ?: (isset($databaseParts['pass']) ? rawurldecode($databaseParts['pass']) : 'secret'));
define('DB_NAME', envValue(['MYSQLDATABASE', 'DB_NAME']) ?: ($databasePath !== '' ? rawurldecode($databasePath) : 'book_manager'));

function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        if (DB_HOST === '') {
            http_response_code(500);
            die('Database is not configured. On Railway, add a MySQL service and set MYSQL_URL or MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE on the web service.');
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die("Database connection failed for host '" . DB_HOST . "': " . $e->getMessage());
        }
    }

    return $pdo;
}
