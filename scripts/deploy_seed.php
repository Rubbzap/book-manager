<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found');
}

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/schema.php';

$adminPassword = getenv('SEED_ADMIN_PASSWORD') ?: 'admin1234';

$pdo = getDB();
ensureAppSchema($pdo);

$adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);
$adminStmt = $pdo->prepare(
    "INSERT INTO users (username, password)
     VALUES ('admin', ?)
     ON DUPLICATE KEY UPDATE password = VALUES(password)"
);
$adminStmt->execute([$adminHash]);

$counts = [
    'users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'books' => (int)$pdo->query('SELECT COUNT(*) FROM books')->fetchColumn(),
    'loans' => (int)$pdo->query('SELECT COUNT(*) FROM loans')->fetchColumn(),
];

echo "Database seed completed.\n";
echo "Admin username: admin\n";
echo "Admin password: {$adminPassword}\n";
echo "Users: {$counts['users']}\n";
echo "Books: {$counts['books']}\n";
echo "Loans: {$counts['loans']}\n";
