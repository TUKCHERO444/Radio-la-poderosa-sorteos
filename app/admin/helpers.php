<?php
declare(strict_types=1);

/**
 * Versión de los assets del panel. Se cambia al desplegar modificaciones de
 * CSS/JS para invalidar la caché del navegador y del service worker.
 */
const ADMIN_ASSET_VERSION = '20261116-11';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function admin_asset(string $path): string
{
    return '/assets/admin/' . ltrim($path, '/') . '?v=' . ADMIN_ASSET_VERSION;
}

function admin_len(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function admin_url(string $path = ''): string
{
    if ($path === '' || $path === '/') {
        return '/admin';
    }

    return '/admin/' . ltrim($path, '/');
}

function admin_redirect(string $path): void
{
    header('Location: ' . $path, true, 302);
    exit;
}

function admin_flash(string $type, string $message): void
{
    $_SESSION['admin_flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function admin_take_flashes(): array
{
    $flashes = $_SESSION['admin_flash'] ?? [];
    unset($_SESSION['admin_flash']);

    return is_array($flashes) ? $flashes : [];
}

function admin_old(array $source, string $key, string $default = ''): string
{
    $value = $source[$key] ?? $default;

    return $value === null ? $default : (string) $value;
}

function admin_search_term(string $key = 'q', int $maxLength = 80): string
{
    $value = trim((string) ($_GET[$key] ?? ''));

    if ($value === '') {
        return '';
    }

    return function_exists('mb_substr')
        ? mb_substr($value, 0, $maxLength)
        : substr($value, 0, $maxLength);
}

/**
 * Atributos de validación compartidos entre el formulario y el JavaScript.
 * El patrón y el mensaje salen de FieldRules para no duplicar reglas.
 */
function field_attrs(string $rule): string
{
    $attributes = [
        'data-pattern' => \App\Shared\Validation\FieldRules::pattern($rule),
        'data-message' => \App\Shared\Validation\FieldRules::message($rule),
        'data-sanitize' => \App\Shared\Validation\FieldRules::sanitizeMode($rule),
        'inputmode' => \App\Shared\Validation\FieldRules::inputmode($rule),
    ];

    $html = '';

    foreach ($attributes as $name => $value) {
        if ($value === '') {
            continue;
        }

        $html .= ' ' . $name . '="' . e($value) . '"';
    }

    return $html;
}

