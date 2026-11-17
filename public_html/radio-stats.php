<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

$cacheFile = storage_path('cache/radio-stats.json');
$ttl = 5;

if (is_file($cacheFile) && (time() - (int) filemtime($cacheFile)) < $ttl) {
    header('X-Cache: HIT');
    readfile($cacheFile);
    exit;
}

$url = (string) config('radio.stats_url', '');
$body = false;
$status = 0;

if ($url !== '' && function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'RadioLaPoderosa/1.0');
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
} elseif ($url !== '') {
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'RadioLaPoderosa/1.0',
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    $status = $body !== false ? 200 : 0;
}

if ($body !== false && $status >= 200 && $status < 300) {
    json_decode($body);
    if (json_last_error() === JSON_ERROR_NONE) {
        file_put_contents($cacheFile, $body, LOCK_EX);
        header('X-Cache: MISS');
        echo $body;
        exit;
    }
}

if (is_file($cacheFile)) {
    header('X-Cache: STALE');
    readfile($cacheFile);
    exit;
}

http_response_code(502);
echo json_encode(['error' => 'stats_unavailable']);
