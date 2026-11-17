<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rawurldecode($path);
$path = rtrim($path, '/');
if ($path === '') {
    $path = '/';
}

if ($path === '/admin' || strpos($path, '/admin/') === 0) {
    require APP_ROOT . '/admin/router.php';
    exit;
}

$routes = [
    '/' => 'pages/home.html',
    '/subpage' => 'pages/subpage.html',
    '/legal-notice' => 'pages/legal-notice.html',
    '/privacy' => 'pages/privacy.html',
];

if ($path === '/sorteos') {
    require APP_ROOT . '/web/sorteos.php';
    exit;
}

if ($path === '/index.html') {
    header('Location: /', true, 301);
    exit;
}

if (isset($routes[$path])) {
    render_template($routes[$path]);
    exit;
}

$collections = [
    '/real-estate-single-page-layout/' => 'real-estate-single-page-layout',
    '/jobs-1-item/' => 'jobs-1-item',
    '/modules-1-item/' => 'modules-1-item',
];

foreach ($collections as $prefix => $folder) {
    if (strpos($path, $prefix) === 0) {
        $slug = substr($path, strlen($prefix));
        if (preg_match('/^[a-z0-9-]+$/', $slug)) {
            $template = $folder . '/' . $slug . '.html';
            if (is_file(APP_ROOT . '/templates/' . $template)) {
                render_template($template);
                exit;
            }
        }
    }
}

not_found();
