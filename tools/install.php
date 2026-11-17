<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo puede ejecutarse por consola.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

$host = (string) config('db.host', 'localhost');
$name = (string) config('db.name', '');
$user = (string) config('db.user', '');
$pass = (string) config('db.pass', '');
$charset = (string) config('db.charset', 'utf8mb4');

if ($name === '') {
    fwrite(STDERR, "ERROR: configura db.name en app/inc/config.php o config.local.php.\n");
    exit(1);
}

$baseDsn = sprintf('mysql:host=%s;charset=%s', $host, $charset);
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $server = new PDO($baseDsn, $user, $pass, $options);
    $server->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        str_replace('`', '``', $name)
    ));
    echo "[ok] Base de datos '{$name}' disponible.\n";
} catch (PDOException $e) {
    echo "[aviso] No se pudo crear la base de datos ({$e->getMessage()}). ";
    echo "Se intentara usar una existente.\n";
}

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $name, $charset);

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    fwrite(STDERR, "ERROR: no se puede conectar a la base de datos '{$name}': {$e->getMessage()}\n");
    exit(1);
}

$schemaFile = APP_ROOT . '/database/schema.sql';
if (!is_file($schemaFile)) {
    fwrite(STDERR, "ERROR: no se encontro app/database/schema.sql.\n");
    exit(1);
}

$sql = (string) file_get_contents($schemaFile);
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if ($statement === '') {
        continue;
    }
    $pdo->exec($statement);
}
echo "[ok] Esquema aplicado ({$schemaFile}).\n";

$defaultUser = 'radiolp';
$defaultPass = 'admin123';
$defaultName = 'Radio La Poderosa';

$check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
$check->execute([$defaultUser]);

if ((int) $check->fetchColumn() === 0) {
    $insert = $pdo->prepare(
        'INSERT INTO users (username, name, password_hash, role) VALUES (?, ?, ?, ?)'
    );
    $insert->execute([
        $defaultUser,
        $defaultName,
        password_hash($defaultPass, PASSWORD_DEFAULT),
        'admin',
    ]);
    echo "[ok] Usuario inicial creado: {$defaultUser} / {$defaultPass}\n";
} else {
    echo "[ok] El usuario '{$defaultUser}' ya existe; no se modifico.\n";
}

echo "\nInstalacion completada. Accede en /admin\n";
