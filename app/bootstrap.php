<?php
declare(strict_types=1);

define('APP_ROOT', __DIR__);
define('PUBLIC_ROOT', dirname(__DIR__) . '/public_html');

/**
 * Autoload manual para el monolito modular.
 * Los namespaces usan PascalCase y las carpetas snake_case:
 * App\Modules\Prizes\PrizeService               -> app/modules/prizes/PrizeService.php
 * App\Modules\PaymentMethods\PaymentMethodService -> app/modules/payment_methods/PaymentMethodService.php
 * App\Shared\Audit\AuditService                 -> app/shared/audit/AuditService.php
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $parts = explode('\\', $relative);
    $classFile = array_pop($parts);

    $segments = [];
    foreach ($parts as $part) {
        $segment = preg_replace('/(?<!^)[A-Z]/', '_$0', $part) ?? $part;
        $segments[] = strtolower($segment);
    }

    $directory = implode('/', $segments);
    $file = APP_ROOT . '/' . ($directory !== '' ? $directory . '/' : '') . $classFile . '.php';

    if (is_file($file)) {
        require $file;
    }
});

function config(string $key, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require APP_ROOT . '/inc/config.php';
        $localConfig = APP_ROOT . '/inc/config.local.php';
        if (is_file($localConfig)) {
            $local = require $localConfig;
            if (is_array($local)) {
                $config = array_replace_recursive($config, $local);
            }
        }
    }
    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function storage_path(string $path = ''): string
{
    return APP_ROOT . '/storage' . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function render_template(string $template): void
{
    $file = APP_ROOT . '/templates/' . $template;
    if (!is_file($file)) {
        not_found();
        return;
    }
    header('Content-Type: text/html; charset=utf-8');
    readfile($file);
}

function not_found(): void
{
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    $file = APP_ROOT . '/templates/404.html';
    if (is_file($file)) {
        readfile($file);
    } else {
        echo '404';
    }
    exit;
}

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}
date_default_timezone_set((string) config('app.timezone', 'America/Lima'));

if ((bool) config('app.debug', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

ini_set('log_errors', '1');
ini_set('error_log', storage_path('logs/php-error.log'));

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

require APP_ROOT . '/inc/db.php';
