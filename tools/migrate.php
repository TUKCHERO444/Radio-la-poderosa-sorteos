<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo puede ejecutarse por consola.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

/**
 * Divide un archivo SQL en sentencias simples.
 * El esquema del MVP no usa procedimientos ni triggers, por lo que separar
 * por punto y coma al final de línea es suficiente.
 *
 * @return string[]
 */
function migrate_split_sql(string $sql): array
{
    $statements = [];
    $buffer = '';
    $lines = preg_split('/\r\n|\r|\n/', $sql) ?: [];

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '--')) {
            continue;
        }

        $buffer .= $line . "\n";

        if (str_ends_with($trimmed, ';')) {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
        }
    }

    $tail = trim($buffer);
    if ($tail !== '') {
        $statements[] = $tail;
    }

    return $statements;
}

function migrate_execute_file(PDO $pdo, string $file): void
{
    $sql = (string) file_get_contents($file);
    $statements = migrate_split_sql($sql);

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
}

try {
    $pdo = db();
    $pdo->query('SELECT 1');
} catch (Throwable $exception) {
    fwrite(STDERR, "ERROR: no se pudo conectar a la base de datos: {$exception->getMessage()}\n");
    exit(1);
}

echo "Migrador del panel de sorteos\n";
echo "Base de datos: " . (string) config('db.name', '') . "\n\n";

// Esquema base: users + settings
$hasUsers = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn() !== false;

if (!$hasUsers) {
    $baseSchema = APP_ROOT . '/database/schema.sql';

    if (!is_file($baseSchema)) {
        fwrite(STDERR, "ERROR: no se encontro app/database/schema.sql.\n");
        exit(1);
    }

    echo "[base] Aplicando esquema base (users, settings)...\n";
    try {
        migrate_execute_file($pdo, $baseSchema);
    } catch (Throwable $exception) {
        fwrite(STDERR, "ERROR al aplicar el esquema base: {$exception->getMessage()}\n");
        exit(1);
    }
    echo "[base] OK\n\n";
}

// Tabla de control
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        migration VARCHAR(255) NOT NULL,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY schema_migrations_migration_unique (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$applied = array_map('strval', $applied);

$migrationFiles = glob(APP_ROOT . '/database/migrations/*.sql') ?: [];
sort($migrationFiles);

if ($migrationFiles === []) {
    echo "No hay migraciones pendientes.\n";
    exit(0);
}

$count = 0;

foreach ($migrationFiles as $file) {
    $name = basename($file);

    if (in_array($name, $applied, true)) {
        echo "[skip] {$name}\n";
        continue;
    }

    echo "[run ] {$name}\n";

    try {
        migrate_execute_file($pdo, $file);
    } catch (Throwable $exception) {
        fwrite(STDERR, "ERROR en {$name}: {$exception->getMessage()}\n");
        fwrite(STDERR, "La migracion no se registro. Corrige el SQL y vuelve a intentar.\n");
        exit(1);
    }

    $statement = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
    $statement->execute([$name]);

    echo "[ok  ] {$name}\n";
    $count++;
}

echo "\nMigraciones aplicadas: {$count}\n";
