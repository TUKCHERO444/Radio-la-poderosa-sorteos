<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo puede ejecutarse por consola.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

$force = in_array('--force', $argv ?? [], true);

if (!(bool) config('app.debug', false) && !$force) {
    fwrite(STDERR, "ERROR: el seeder solo corre en modo debug. Usa --force si sabes lo que haces.\n");
    exit(1);
}

function seed_demo_code(int $length = 14): string
{
    $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $max = strlen($alphabet) - 1;
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= $alphabet[random_int(0, $max)];
    }

    return $code;
}

function seed_find_id(PDO $pdo, string $table, string $column, string $value): ?int
{
    $allowed = ['prizes', 'raffles', 'payment_methods'];
    if (!in_array($table, $allowed, true)) {
        throw new RuntimeException('Tabla no permitida para busqueda: ' . $table);
    }

    $statement = $pdo->prepare("SELECT id FROM {$table} WHERE {$column} = ? LIMIT 1");
    $statement->execute([$value]);
    $id = $statement->fetchColumn();

    return $id === false ? null : (int) $id;
}

function seed_upsert_setting(PDO $pdo, string $key, string $value): void
{
    $statement = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $statement->execute([$key, $value]);
}

try {
    $pdo = db();
    $pdo->query('SELECT 1');
} catch (Throwable $exception) {
    fwrite(STDERR, "ERROR: no se pudo conectar a la base de datos: {$exception->getMessage()}\n");
    exit(1);
}

echo "Seeder demo del MVP de sorteos\n";
echo "Base de datos: " . (string) config('db.name', '') . "\n\n";

try {
    $pdo->beginTransaction();

    $adminId = $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1")->fetchColumn();
    $adminId = $adminId === false ? null : (int) $adminId;

    // Premios demo ---------------------------------------------------------
    $prizes = [
        [
            'name' => 'Combo desayuno demo',
            'description' => 'Producto ficticio para probar el catalogo de premios.',
            'prize_type' => 'product',
            'estimated_value_cents' => 4500,
            'delivery_method' => 'Presencial',
        ],
        [
            'name' => 'Parlante Bluetooth demo',
            'description' => 'Premio ficticio de prueba. No representa un premio real.',
            'prize_type' => 'product',
            'estimated_value_cents' => 12000,
            'delivery_method' => 'Delivery',
        ],
        [
            'name' => 'Kit de cuidado personal demo',
            'description' => 'Producto ficticio para pruebas internas del panel.',
            'prize_type' => 'product',
            'estimated_value_cents' => 8000,
            'delivery_method' => 'Presencial',
        ],
    ];

    $prizeIds = [];

    foreach ($prizes as $prize) {
        $existingId = seed_find_id($pdo, 'prizes', 'name', $prize['name']);

        if ($existingId !== null) {
            $prizeIds[] = $existingId;
            echo "[skip] Premio existente: {$prize['name']} (ID {$existingId})\n";
            continue;
        }

        $statement = $pdo->prepare(
            'INSERT INTO prizes
                (public_code, name, description, prize_type, estimated_value_cents, image_path, delivery_method, is_active)
             VALUES (?, ?, ?, ?, ?, NULL, ?, 1)'
        );
        $statement->execute([
            seed_demo_code(),
            $prize['name'],
            $prize['description'],
            $prize['prize_type'],
            $prize['estimated_value_cents'],
            $prize['delivery_method'],
        ]);

        $prizeIds[] = (int) $pdo->lastInsertId();
        echo "[new ] Premio demo: {$prize['name']}\n";
    }

    // Métodos de pago demo ------------------------------------------------
    $methods = [
        [
            'name' => 'Yape demo',
            'type' => 'wallet',
            'provider' => 'yape',
            'holder_name' => 'DEMO - Radio La Poderosa',
            'phone' => '999999999',
            'contact_phone' => '999999999',
            'instructions' => 'Pago demo por Yape. Escanea el QR o usa el numero. Si pagas de mas, contacta al numero indicado.',
        ],
        [
            'name' => 'Plin demo',
            'type' => 'wallet',
            'provider' => 'plin',
            'holder_name' => 'DEMO - Radio La Poderosa',
            'phone' => '999999999',
            'contact_phone' => '999999999',
            'instructions' => 'Pago demo por Plin. Escanea el QR o usa el numero. Si pagas de mas, contacta al numero indicado.',
        ],
        [
            'name' => 'BCP demo',
            'type' => 'bank',
            'provider' => 'bcp',
            'holder_name' => 'DEMO - Radio La Poderosa',
            'bank_name' => 'BCP',
            'account_number' => '000000000000000000',
            'cci' => '00000000000000000000',
            'contact_phone' => '999999999',
            'instructions' => 'Transferencia demo a la cuenta BCP. Conserva tu voucher. Si pagas de mas, contacta al numero indicado.',
        ],
    ];

    $methodIds = [];

    foreach ($methods as $method) {
        $existingId = seed_find_id($pdo, 'payment_methods', 'name', $method['name']);

        if ($existingId !== null) {
            $methodIds[] = $existingId;
            echo "[skip] Metodo existente: {$method['name']} (ID {$existingId})\n";
            continue;
        }

        $statement = $pdo->prepare(
            'INSERT INTO payment_methods
                (name, type, provider, holder_name, holder_document, phone, bank_name, account_number, cci,
                 qr_image_path, contact_phone, instructions, is_active)
             VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, NULL, ?, ?, 1)'
        );
        $statement->execute([
            $method['name'],
            $method['type'],
            $method['provider'],
            $method['holder_name'],
            $method['phone'] ?? null,
            $method['bank_name'] ?? null,
            $method['account_number'] ?? null,
            $method['cci'] ?? null,
            $method['contact_phone'] ?? null,
            $method['instructions'],
        ]);

        $methodIds[] = (int) $pdo->lastInsertId();
        echo "[new ] Metodo demo: {$method['name']}\n";
    }

    // Sorteo demo ---------------------------------------------------------
    $slug = 'sorteo-demo-lanzamiento';
    $raffleId = seed_find_id($pdo, 'raffles', 'slug', $slug);

    if ($raffleId === null) {
        $now = new DateTimeImmutable('now');
        $statement = $pdo->prepare(
            'INSERT INTO raffles
                (public_code, slug, name, summary, description, status, price_cents, currency,
                 total_tickets, max_tickets_per_participant, min_age, sales_start_at, sales_end_at,
                 draw_at, claim_deadline_at, current_rules_version_id, is_featured, featured_order,
                 cover_image_path, created_by, published_at, closed_at, drawn_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, 0, NULL, NULL, ?, NULL, NULL, NULL)'
        );
        $statement->execute([
            seed_demo_code(),
            $slug,
            'Sorteo demo de lanzamiento',
            'Datos ficticios para probar el panel de sorteos.',
            'Este sorteo es un borrador de demostración. No debe publicarse ni cobrarse.',
            'draft',
            500,
            'PEN',
            100,
            5,
            18,
            $now->format('Y-m-d H:i:s'),
            $now->modify('+30 days')->format('Y-m-d H:i:s'),
            $now->modify('+31 days')->format('Y-m-d H:i:s'),
            $now->modify('+38 days')->format('Y-m-d H:i:s'),
            $adminId,
        ]);
        $raffleId = (int) $pdo->lastInsertId();
        echo "[new ] Sorteo demo: {$slug} (ID {$raffleId})\n";
    } else {
        echo "[skip] Sorteo existente: {$slug} (ID {$raffleId})\n";
    }

    // Bases demo ----------------------------------------------------------
    $rulesContent = '<h2>Bases demo</h2>'
        . '<p>Documento ficticio para pruebas internas. No usar en produccion.</p>'
        . '<p>El sorteo es un borrador y no debe publicarse hasta completar el checklist legal.</p>'
        . '<p>Los pagos son manuales por Yape, Plin o BCP. El ticket se emite solo con pago aprobado.</p>'
        . '<p>El resultado se calcula con commit-reveal y se publica con hashes.</p>';
    $rulesHash = hash('sha256', $rulesContent);

    $rulesStatement = $pdo->prepare(
        'SELECT id FROM raffle_rules_versions WHERE raffle_id = ? AND version = 1 LIMIT 1'
    );
    $rulesStatement->execute([$raffleId]);
    $rulesId = $rulesStatement->fetchColumn();
    $rulesId = $rulesId === false ? null : (int) $rulesId;

    if ($rulesId === null) {
        $insertRules = $pdo->prepare(
            'INSERT INTO raffle_rules_versions
                (raffle_id, version, content, content_hash, status, published_at, created_by)
             VALUES (?, 1, ?, ?, ?, NULL, ?)'
        );
        $insertRules->execute([
            $raffleId,
            $rulesContent,
            $rulesHash,
            'draft',
            $adminId,
        ]);
        $rulesId = (int) $pdo->lastInsertId();
        echo "[new ] Bases demo version 1 (ID {$rulesId})\n";
    } else {
        echo "[skip] Bases demo existentes (ID {$rulesId})\n";
    }

    $updateRaffle = $pdo->prepare('UPDATE raffles SET current_rules_version_id = ? WHERE id = ?');
    $updateRaffle->execute([$rulesId, $raffleId]);

    // Asignar premios -----------------------------------------------------
    foreach ($prizeIds as $index => $prizeId) {
        $position = $index + 1;
        $statement = $pdo->prepare(
            'INSERT INTO raffle_prizes (raffle_id, prize_id, position, conditions)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE prize_id = VALUES(prize_id), conditions = VALUES(conditions)'
        );
        $statement->execute([
            $raffleId,
            $prizeId,
            $position,
            'Premio demo para pruebas internas.',
        ]);
    }
    echo "[ok  ] Premios asignados al sorteo: " . count($prizeIds) . "\n";

    // Asignar métodos de pago ---------------------------------------------
    foreach ($methodIds as $index => $methodId) {
        $statement = $pdo->prepare(
            'INSERT INTO raffle_payment_methods
                (raffle_id, payment_method_id, instructions_override, is_active, sort_order)
             VALUES (?, ?, NULL, 1, ?)
             ON DUPLICATE KEY UPDATE is_active = VALUES(is_active), sort_order = VALUES(sort_order)'
        );
        $statement->execute([$raffleId, $methodId, $index]);
    }
    echo "[ok  ] Metodos de pago asignados al sorteo: " . count($methodIds) . "\n";

    // Configuración demo --------------------------------------------------
    seed_upsert_setting($pdo, 'demo_seed', '1');
    seed_upsert_setting($pdo, 'demo_seeded_at', (new DateTimeImmutable('now'))->format('c'));
    seed_upsert_setting($pdo, 'support_phone', '999999999');
    seed_upsert_setting($pdo, 'payment_contact_phone', '999999999');

    $pdo->commit();

    echo "\nSeeder completado.\n";
    echo "Sorteo demo ID: {$raffleId} · slug: {$slug} · estado: draft\n";
    echo "Premios: " . count($prizeIds) . " · Metodos de pago: " . count($methodIds) . "\n";
    echo "No se crearon participantes, ordenes ni pagos reales.\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "ERROR: {$exception->getMessage()}\n");
    exit(1);
}
