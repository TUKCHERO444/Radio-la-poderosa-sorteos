<?php
declare(strict_types=1);

namespace App\Shared\Validation;

/**
 * Reglas de escritura por tipo de dato.
 *
 * Cada regla define el patrón que se comparte con el cliente (atributos
 * `data-pattern`/`data-message`) y la normalización que aplica el backend
 * antes de validar y guardar.
 */
final class FieldRules
{
    /**
     * @var array<string, array{pattern: string, message: string, sanitize: string, inputmode: string}>
     */
    private const RULES = [
        'phone' => [
            'pattern' => '9[0-9]{8}',
            'message' => 'Ingresa 9 dígitos empezando por 9, sin letras ni símbolos.',
            'sanitize' => 'digits',
            'inputmode' => 'numeric',
        ],
        'document' => [
            'pattern' => '(?:[0-9]{8}|[0-9]{11})',
            'message' => 'Ingresa un DNI de 8 dígitos o un RUC de 11 dígitos.',
            'sanitize' => 'digits',
            'inputmode' => 'numeric',
        ],
        'account' => [
            'pattern' => '[0-9]{8,20}',
            'message' => 'Ingresa solo números, entre 8 y 20 dígitos.',
            'sanitize' => 'digits',
            'inputmode' => 'numeric',
        ],
        'cci' => [
            'pattern' => '[0-9]{20}',
            'message' => 'El CCI debe tener exactamente 20 dígitos.',
            'sanitize' => 'digits',
            'inputmode' => 'numeric',
        ],
        'person_name' => [
            'pattern' => "[A-Za-zÁÉÍÓÚÜÑáéíóúüñ' .-]{2,160}",
            'message' => 'Usa solo letras, espacios, apóstrofo, punto y guion.',
            'sanitize' => 'person-name',
            'inputmode' => 'text',
        ],
        'display_name' => [
            'pattern' => "[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ .,&°%()/+'#:-]{2,160}",
            'message' => 'Usa letras, números y signos básicos; sin < > ni comillas.',
            'sanitize' => 'display-name',
            'inputmode' => 'text',
        ],
        'slug' => [
            'pattern' => '[a-z0-9]+(?:-[a-z0-9]+)*',
            'message' => 'Usa minúsculas, números y guiones simples.',
            'sanitize' => 'slug',
            'inputmode' => 'text',
        ],
        'decimal' => [
            'pattern' => '[0-9]{1,9}(?:[.,][0-9]{1,2})?',
            'message' => 'Usa solo números con hasta 2 decimales.',
            'sanitize' => 'decimal',
            'inputmode' => 'decimal',
        ],
        'integer' => [
            'pattern' => '[0-9]{1,9}',
            'message' => 'Usa solo números enteros, sin puntos ni letras.',
            'sanitize' => 'digits',
            'inputmode' => 'numeric',
        ],
        'username' => [
            'pattern' => '[A-Za-z0-9._-]{3,50}',
            'message' => 'De 3 a 50 caracteres: letras, números, punto, guion y guion bajo.',
            'sanitize' => 'username',
            'inputmode' => 'text',
        ],
    ];

    public static function pattern(string $rule): string
    {
        return self::RULES[$rule]['pattern'] ?? '';
    }

    public static function message(string $rule): string
    {
        return self::RULES[$rule]['message'] ?? 'El formato ingresado no es válido.';
    }

    public static function sanitizeMode(string $rule): string
    {
        return self::RULES[$rule]['sanitize'] ?? '';
    }

    public static function inputmode(string $rule): string
    {
        return self::RULES[$rule]['inputmode'] ?? '';
    }

    public static function matches(string $rule, string $value): bool
    {
        $pattern = self::pattern($rule);

        if ($pattern === '') {
            return true;
        }

        return preg_match('~^(?:' . $pattern . ')$~u', $value) === 1;
    }

    /**
     * Devuelve el mensaje de error o null cuando el valor es válido.
     * Los valores vacíos se consideran válidos: la obligatoriedad se valida aparte.
     */
    public static function validate(string $rule, string $value, ?string $message = null): ?string
    {
        if ($value === '') {
            return null;
        }

        if (!self::matches($rule, $value)) {
            return $message ?? self::message($rule);
        }

        return null;
    }

    /**
     * Limpia el valor para que coincida con el tipo esperado.
     * Solo elimina separadores de formato; los caracteres inválidos se dejan
     * para que la validación los detecte y muestre un error claro.
     */
    public static function normalize(string $rule, string $value): string
    {
        $value = trim($value);

        switch ($rule) {
            case 'phone':
            case 'document':
            case 'account':
            case 'cci':
                return preg_replace('/[\s\-().+]+/', '', $value) ?? '';

            case 'integer':
                return preg_replace('/[\s.,]+/', '', $value) ?? '';

            case 'decimal':
                return self::normalizeDecimal($value);

            case 'person_name':
            case 'display_name':
            case 'username':
                $value = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value) ?? '';
                return preg_replace('/\s+/', ' ', trim($value)) ?? '';

            case 'slug':
                return self::slugify($value);

            default:
                return $value;
        }
    }

    /**
     * Texto libre de varias líneas: elimina caracteres de control.
     */
    public static function normalizeText(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/', '', $value) ?? '';

        return trim($value);
    }

    public static function letterCount(string $value): int
    {
        $count = preg_match_all('/[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/u', $value);

        return is_int($count) ? $count : 0;
    }

    public static function hasLetter(string $value): bool
    {
        return self::letterCount($value) > 0;
    }

    private static function normalizeDecimal(string $value): string
    {
        $value = str_replace(['S/', 's/', ' '], '', $value);

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                // 1.234,56: el punto separa miles y la coma decimales.
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                // 1,234.56: la coma separa miles y el punto decimales.
                $value = str_replace(',', '', $value);
            }
        } elseif ($lastComma !== false) {
            $commaCount = substr_count($value, ',');
            $decimals = substr($value, $lastComma + 1);

            if ($commaCount > 1 || strlen($decimals) === 3) {
                // 1,234 o 1,234,567: separadores de miles.
                $value = str_replace(',', '', $value);
            } else {
                $value = str_replace(',', '.', $value);
            }
        } elseif ($lastDot !== false && substr_count($value, '.') > 1) {
            // 1.234.567: puntos de miles.
            $value = str_replace('.', '', $value);
        }

        return $value;
    }

    private static function slugify(string $value): string
    {
        $value = strtr($value, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A',
            'É' => 'E', 'È' => 'E', 'Ë' => 'E', 'Ê' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I', 'Î' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O', 'Ô' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U', 'Û' => 'U',
            'Ñ' => 'N', 'Ç' => 'C',
        ]);

        if (function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($transliterated) && $transliterated !== '') {
                $value = $transliterated;
            }
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return substr($value, 0, 120);
    }
}
