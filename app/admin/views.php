<?php
declare(strict_types=1);

function admin_view(string $view, array $data = []): void
{
    $file = APP_ROOT . '/admin/views/' . $view . '.php';

    if (!is_file($file)) {
        http_response_code(500);
        echo 'Vista no encontrada.';
        return;
    }

    extract($data, EXTR_SKIP);

    ob_start();
    require $file;
    $content = (string) ob_get_clean();

    $admin = admin_current_user();
    $flashes = admin_take_flashes();
    $title = isset($title) ? (string) $title : 'Panel';
    $active = isset($active) ? (string) $active : '';

    require APP_ROOT . '/admin/views/layout.php';
}

function admin_render_login(array $data = []): void
{
    extract($data, EXTR_SKIP);

    $flashes = admin_take_flashes();
    $username = isset($username) ? (string) $username : '';
    $error = isset($error) ? (string) $error : '';
    $locked = isset($locked) ? (int) $locked : 0;

    require APP_ROOT . '/admin/views/login.php';
}

function admin_render_system_page(
    int $status,
    string $heading,
    string $message,
    string $detail = '',
    string $linkHref = '/admin/login',
    string $linkLabel = 'Volver a iniciar sesion'
): void {
    http_response_code($status);

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }

    $pageTitle = $heading;
    require APP_ROOT . '/admin/views/system.php';
    exit;
}

function admin_abort(int $status, string $message): void
{
    http_response_code($status);
    admin_view('error', [
        'title' => 'Error ' . $status,
        'active' => '',
        'status' => $status,
        'message' => $message,
    ]);
    exit;
}
