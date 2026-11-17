<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$query = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$query = function_exists('mb_substr') ? mb_substr($query, 0, 200) : substr($query, 0, 200);

if ($query === '') {
    echo json_encode(['cover' => null]);
    exit;
}

$cacheFile = storage_path('cache/covers/' . hash('sha256', function_exists('mb_strtolower') ? mb_strtolower($query) : strtolower($query)) . '.json');
$ttl = 86400;

if (is_file($cacheFile) && (time() - (int) filemtime($cacheFile)) < $ttl) {
    header('X-Cache: HIT');
    readfile($cacheFile);
    exit;
}

$url = 'https://itunes.apple.com/search?term=' . rawurlencode($query) . '&entity=song&limit=1';
$body = false;
$status = 0;

if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'RadioLaPoderosa/1.0');
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
} else {
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'RadioLaPoderosa/1.0',
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    $status = $body !== false ? 200 : 0;
}

$cover = null;
$success = false;
if ($body !== false && $status >= 200 && $status < 300) {
    $success = true;
    $data = json_decode($body, true);
    if (isset($data['results'][0]['artworkUrl100'])) {
        $cover = str_replace('100x100bb', '600x600bb', (string) $data['results'][0]['artworkUrl100']);
    }
}

$payload = json_encode(['cover' => $cover], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
if ($success) {
    file_put_contents($cacheFile, $payload, LOCK_EX);
    header('X-Cache: MISS');
}
echo $payload;
