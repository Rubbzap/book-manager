<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/schema.php';

$pdo = getDB();
ensureAppSchema($pdo);

function fetchJson(string $url): ?array {
    $context = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header' => "User-Agent: BookShelf/1.0\r\n",
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    if ($body === false) {
        return null;
    }
    $json = json_decode($body, true);
    return is_array($json) ? $json : null;
}

function normalizeCoverUrl(?string $url): ?string {
    if (!$url) {
        return null;
    }
    $url = str_replace('http://', 'https://', $url);
    $url = preg_replace('/&edge=curl$/', '', $url);
    return $url ?: null;
}

function googleBooksCover(string $title, string $author): ?string {
    $queries = [
        trim('intitle:' . $title . ' inauthor:' . $author),
        trim($title . ' ' . $author),
        trim($title),
    ];

    foreach ($queries as $query) {
        $url = 'https://www.googleapis.com/books/v1/volumes?maxResults=5&q=' . rawurlencode($query);
        $json = fetchJson($url);
        foreach ($json['items'] ?? [] as $item) {
            $links = $item['volumeInfo']['imageLinks'] ?? [];
            $cover = $links['extraLarge'] ?? $links['large'] ?? $links['medium'] ?? $links['thumbnail'] ?? $links['smallThumbnail'] ?? null;
            if ($cover) {
                return normalizeCoverUrl($cover);
            }
        }
    }
    return null;
}

function openLibraryCover(string $title, string $author): ?string {
    $url = 'https://openlibrary.org/search.json?limit=3&title=' . rawurlencode($title) . '&author=' . rawurlencode($author);
    $json = fetchJson($url);
    foreach ($json['docs'] ?? [] as $doc) {
        if (!empty($doc['cover_i'])) {
            return 'https://covers.openlibrary.org/b/id/' . rawurlencode((string)$doc['cover_i']) . '-L.jpg';
        }
    }
    return null;
}

$onlyMissing = in_array('--missing-only', $argv ?? [], true);
$sql = "SELECT id, title, author, cover_image_url FROM books";
if ($onlyMissing) {
    $sql .= " WHERE cover_image_url IS NULL OR cover_image_url = '' OR cover_image_url LIKE '%placehold.co%'";
}
$sql .= " ORDER BY id";
$books = $pdo->query($sql)->fetchAll();
$update = $pdo->prepare("UPDATE books SET cover_image_url = ? WHERE id = ?");
$updated = 0;
$missed = 0;

foreach ($books as $book) {
    $cover = googleBooksCover($book['title'], $book['author']);
    if (!$cover) {
        $cover = openLibraryCover($book['title'], $book['author']);
    }

    if ($cover) {
        $update->execute([$cover, $book['id']]);
        $updated++;
        echo "updated #{$book['id']} {$book['title']}\n";
    } else {
        $missed++;
        echo "missed #{$book['id']} {$book['title']}\n";
    }

    usleep(120000);
}

echo "done updated={$updated} missed={$missed}\n";
