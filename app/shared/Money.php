<?php
declare(strict_types=1);

namespace App\Shared;

final class Money
{
    public static function toCents(string $value): ?int
    {
        $value = trim($value);
        $value = str_replace(['S/', 's/', ' '], '', $value);

        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (int) round(((float) $value) * 100);
    }

    public static function format(int $cents): string
    {
        return 'S/ ' . number_format($cents / 100, 2, '.', ',');
    }

    public static function input(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
