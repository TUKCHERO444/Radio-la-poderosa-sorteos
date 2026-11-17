<?php
declare(strict_types=1);

/**
 * Pruebas de las reglas de escritura por tipo de dato.
 * Ejecutar con: php tools/tests/field_rules.php
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Shared\Validation\FieldRules;

$passed = 0;
$failed = 0;

function check(string $label, bool $condition, string $detail = ''): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo "  PASS  {$label}\n";
        return;
    }

    $failed++;
    echo "  FAIL  {$label}" . ($detail !== '' ? "  ({$detail})" : '') . "\n";
}

echo "Telefono\n";
check('acepta 9 digitos que empiezan por 9', FieldRules::matches('phone', '987654321'));
check('rechaza 8 digitos', !FieldRules::matches('phone', '98765432'));
check('rechaza 10 digitos', !FieldRules::matches('phone', '9876543210'));
check('rechaza inicio distinto de 9', !FieldRules::matches('phone', '887654321'));
check('rechaza letras', !FieldRules::matches('phone', '98765abcd'));
check('normaliza guiones y espacios', FieldRules::normalize('phone', ' 987-654 321 ') === '987654321');

echo "Documento\n";
check('acepta DNI de 8', FieldRules::matches('document', '12345678'));
check('acepta RUC de 11', FieldRules::matches('document', '12345678901'));
check('rechaza 9 digitos', !FieldRules::matches('document', '123456789'));
check('rechaza letras', !FieldRules::matches('document', '1234567A'));
check('normaliza puntos y guiones', FieldRules::normalize('document', '12.345.678') === '12345678');

echo "Cuenta y CCI\n";
check('acepta cuenta de 8 a 20 digitos', FieldRules::matches('account', '1930000123'));
check('rechaza cuenta con letras', !FieldRules::matches('account', 'FSDVD3'));
check('rechaza cuenta de 7 digitos', !FieldRules::matches('account', '1234567'));
check('acepta CCI de 20', FieldRules::matches('cci', str_repeat('0', 20)));
check('rechaza CCI de 19', !FieldRules::matches('cci', str_repeat('0', 19)));
check('rechaza CCI con letras', !FieldRules::matches('cci', 'DZXVD543'));

echo "Nombres\n";
check('acepta nombre de persona con tildes', FieldRules::matches('person_name', 'Juan Pérez Ñuñez'));
check('rechaza nombre con numeros', !FieldRules::matches('person_name', 'Juan 123'));
check('rechaza nombre con simbolos', !FieldRules::matches('person_name', 'Juan <Perez>'));
check('cuenta letras', FieldRules::letterCount('Ana') === 3);
check('acepta nombre de premio con numeros', FieldRules::matches('display_name', 'Sorteo 2027'));
check('acepta nombre con guion y parentesis', FieldRules::matches('display_name', 'Combo 2x1 (grande)'));
check('rechaza etiquetas en nombre visible', !FieldRules::matches('display_name', 'Premio <script>'));
check('normaliza espacios repetidos', FieldRules::normalize('display_name', '  Combo   desayuno  ') === 'Combo desayuno');

echo "Slug\n";
check('acepta slug valido', FieldRules::matches('slug', 'gran-sorteo-2027'));
check('rechaza slug con mayusculas', !FieldRules::matches('slug', 'Gran-Sorteo'));
check('normaliza texto a slug', FieldRules::normalize('slug', '¡Gran Sorteo de Año!') === 'gran-sorteo-de-ano');

echo "Numeros y dinero\n";
check('acepta entero', FieldRules::matches('integer', '100'));
check('rechaza entero con decimales', !FieldRules::matches('integer', '10.5'));
check('normaliza separador de miles', FieldRules::normalize('integer', '1.000') === '1000');
check('acepta decimal simple', FieldRules::matches('decimal', '5'));
check('acepta decimal con dos cifras', FieldRules::matches('decimal', '5.55'));
check('rechaza tres decimales', !FieldRules::matches('decimal', '5.555'));
check('rechaza decimal con letras', !FieldRules::matches('decimal', '5a'));
check('normaliza prefijo de moneda', FieldRules::normalize('decimal', 'S/ 10.50') === '10.50');
check('normaliza formato 1.234,56', FieldRules::normalize('decimal', '1.234,56') === '1234.56');
check('normaliza formato 1,234.56', FieldRules::normalize('decimal', '1,234.56') === '1234.56');

echo "Usuario y texto\n";
check('acepta usuario permitido', FieldRules::matches('username', 'radio.lp_1'));
check('rechaza usuario con espacio', !FieldRules::matches('username', 'radio lp'));
check('limpia caracteres de control', FieldRules::normalizeText("Hola\x00 mundo") === 'Hola mundo');

echo "\nResultado: {$passed} correctos, {$failed} fallidos\n";

exit($failed === 0 ? 0 : 1);
