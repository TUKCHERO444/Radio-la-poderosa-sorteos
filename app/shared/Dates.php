<?php
declare(strict_types=1);

namespace App\Shared;

final class Dates
{
    public static function now(): string
    {
        return (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
    }

    public static function toInput(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d\TH:i');
        } catch (\Throwable $exception) {
            return '';
        }
    }

    public static function fromInput(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);

        if ($date instanceof \DateTimeImmutable) {
            return $date->format('Y-m-d H:i:s');
        }

        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d H:i:s');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public static function display(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        try {
            return (new \DateTimeImmutable($value))->format('d/m/Y H:i');
        } catch (\Throwable $exception) {
            return $value;
        }
    }
}
