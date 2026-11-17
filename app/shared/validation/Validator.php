<?php
declare(strict_types=1);

namespace App\Shared\Validation;

final class Validator
{
    public static function required(string $value, string $message): ?string
    {
        return trim($value) === '' ? $message : null;
    }

    public static function minLength(string $value, int $min, string $message): ?string
    {
        $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);

        return $length < $min ? $message : null;
    }

    public static function maxLength(string $value, int $max, string $message): ?string
    {
        $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);

        return $length > $max ? $message : null;
    }

    public static function integer(string $value, string $message): ?string
    {
        return filter_var($value, FILTER_VALIDATE_INT) === false ? $message : null;
    }

    public static function decimal(string $value, string $message): ?string
    {
        return is_numeric($value) ? null : $message;
    }

    public static function minInt(int $value, int $min, string $message): ?string
    {
        return $value < $min ? $message : null;
    }

    public static function maxInt(int $value, int $max, string $message): ?string
    {
        return $value > $max ? $message : null;
    }

    /**
     * @param array<int, string> $allowed
     */
    public static function enum(string $value, array $allowed, string $message): ?string
    {
        return in_array($value, $allowed, true) ? null : $message;
    }

    public static function datetime(string $value, string $message): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);

        if ($date instanceof \DateTimeImmutable) {
            return null;
        }

        try {
            new \DateTimeImmutable($value);

            return null;
        } catch (\Throwable $exception) {
            return $message;
        }
    }
}
