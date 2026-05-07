<?php
require_once '../config/db.php';

$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT title, author, cover_color, cover_image_url FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    exit;
}

function wrapCoverText(string $text, int $limit = 17, int $maxLines = 5): array {
    $words = preg_split('/\s+/u', trim($text)) ?: [];
    $lines = [];
    $line = '';

    foreach ($words as $word) {
        $candidate = trim($line . ' ' . $word);
        if (mb_strlen($candidate) > $limit && $line !== '') {
            $lines[] = $line;
            $line = $word;
        } else {
            $line = $candidate;
        }
        if (count($lines) >= $maxLines) break;
    }

    if ($line !== '' && count($lines) < $maxLines) {
        $lines[] = $line;
    }

    return array_slice($lines, 0, $maxLines);
}

function outputLocalCover(array $book): void {
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', $book['cover_color'] ?? '') ? $book['cover_color'] : '#8b4513';
    $titleLines = wrapCoverText($book['title'] ?? 'BookShelf', 15, 5);
    $author = mb_substr($book['author'] ?? '', 0, 28);

    $titleSvg = '';
    $y = 130;
    foreach ($titleLines as $line) {
        $titleSvg .= '<text x="180" y="' . $y . '" text-anchor="middle" font-size="30" font-weight="700" fill="#fff">' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</text>';
        $y += 38;
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="360" height="520" viewBox="0 0 360 520">
      <defs>
        <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0" stop-color="' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . '"/>
          <stop offset="1" stop-color="#18201c"/>
        </linearGradient>
      </defs>
      <rect width="360" height="520" rx="22" fill="url(#g)"/>
      <rect x="32" y="42" width="6" height="436" rx="3" fill="rgba(255,255,255,.34)"/>
      <text x="180" y="72" text-anchor="middle" font-size="20" letter-spacing="3" fill="rgba(255,255,255,.72)">BOOKSHELF</text>
      ' . $titleSvg . '
      <text x="180" y="456" text-anchor="middle" font-size="21" fill="rgba(255,255,255,.82)">' . htmlspecialchars($author, ENT_QUOTES, 'UTF-8') . '</text>
    </svg>';

    header('Content-Type: image/svg+xml; charset=UTF-8');
    header('Cache-Control: public, max-age=86400');
    echo $svg;
    exit;
}

$url = trim($book['cover_image_url'] ?? '');
if ($url === '') {
    outputLocalCover($book);
}

$context = stream_context_create([
    'http' => [
        'timeout' => 8,
        'follow_location' => 1,
        'header' => "User-Agent: Mozilla/5.0 BookShelf/1.0\r\nAccept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8\r\n",
    ],
]);

$image = @file_get_contents($url, false, $context);
if ($image === false || strlen($image) < 200) {
    outputLocalCover($book);
}

$info = @getimagesizefromstring($image);
if (!$info || empty($info['mime'])) {
    outputLocalCover($book);
}

$mime = $info['mime'];

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=86400');
echo $image;
