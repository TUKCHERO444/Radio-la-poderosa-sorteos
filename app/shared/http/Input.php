<?php
declare(strict_types=1);

namespace App\Shared\Http;

final class Input
{
    public static function post(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    public static function rawPost(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    public static function get(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    public static function bool(string $key): bool
    {
        return isset($_POST[$key]) && $_POST[$key] !== '' && $_POST[$key] !== '0';
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function array(string $key): array
    {
        $value = $_POST[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;

        return is_array($file) ? $file : null;
    }
}
