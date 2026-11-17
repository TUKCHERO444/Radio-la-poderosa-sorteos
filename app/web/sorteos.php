<?php
declare(strict_types=1);

use App\Modules\Raffles\RaffleRepository;
use App\Shared\Dates;
use App\Shared\Money;

header('Content-Type: text/html; charset=utf-8');

$e = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$statusLabels = [
    'draft' => 'Borrador',
    'published' => 'En venta',
    'paused' => 'Pausado',
    'sales_closed' => 'Ventas cerradas',
    'drawing' => 'Sorteando',
    'drawn' => 'Sorteado',
    'results_published' => 'Resultados publicados',
    'closed' => 'Cerrado',
    'cancelled' => 'Cancelado',
];

$positionLabel = static function (int $position): string {
    if ($position === 1) {
        return '1.er lugar';
    }

    if ($position === 2) {
        return '2.º lugar';
    }

    if ($position === 3) {
        return '3.er lugar';
    }

    return $position . '.º lugar';
};

$repository = new RaffleRepository(db());
$raffles = $repository->publicRaffles();
$preview = false;

if ($raffles === [] && (bool) config('public.preview_drafts', false)) {
    $draft = $repository->latestDraft();

    if ($draft !== null) {
        $raffles = [$draft];
        $preview = true;
    }
}

$now = new DateTimeImmutable('now');

foreach ($raffles as $index => $raffle) {
    $status = (string) $raffle['status'];
    $isPreview = $preview || $status === 'draft';
    $prizes = $repository->prizesFor((int) $raffle['id']);
    $mainPrize = $prizes[0] ?? null;

    $heroImage = trim((string) ($raffle['cover_image_path'] ?? ''));

    if ($heroImage === '') {
        foreach ($prizes as $prize) {
            $candidate = trim((string) ($prize['image_path'] ?? ''));

            if ($candidate !== '') {
                $heroImage = $candidate;
                break;
            }
        }
    }

    $deadline = null;
    $deadlineLabel = '';

    foreach ([
        ['sales_start_at', 'Apertura de ventas'],
        ['sales_end_at', 'Cierre de ventas'],
        ['draw_at', 'Fecha del sorteo'],
    ] as [$field, $label]) {
        if (empty($raffle[$field])) {
            continue;
        }

        try {
            $date = new DateTimeImmutable((string) $raffle[$field]);
        } catch (Throwable $exception) {
            continue;
        }

        if ($date > $now) {
            $deadline = $date;
            $deadlineLabel = $label;
            break;
        }
    }

    $lead = trim((string) ($raffle['summary'] ?? ''));

    if ($lead === '') {
        $mainName = $mainPrize['name'] ?? 'premios reales';
        $lead = 'Participa por ' . $mainName . '. Compra tus números y sigue el sorteo en vivo; los ganadores se publican con verificación.';
    }

    $dateParts = [];

    if (!empty($raffle['sales_start_at']) || !empty($raffle['sales_end_at'])) {
        $dateParts[] = 'Ventas: ' . Dates::display($raffle['sales_start_at']) . ' – ' . Dates::display($raffle['sales_end_at']);
    }

    if (!empty($raffle['draw_at'])) {
        $dateParts[] = 'Sorteo: ' . Dates::display($raffle['draw_at']);
    }

    $prizesData = [];

    foreach ($prizes as $prize) {
        $position = (int) ($prize['position'] ?? (count($prizesData) + 1));
        $description = trim((string) ($prize['description'] ?? ''));

        $prizesData[] = [
            'name' => (string) $prize['name'],
            'description' => $description !== '' ? $description : 'Premio del sorteo.',
            'image' => trim((string) ($prize['image_path'] ?? '')),
            'value' => $prize['estimated_value_cents'] !== null
                ? Money::format((int) $prize['estimated_value_cents'])
                : '',
            'position_label' => $positionLabel($position),
        ];
    }

    $statusLabel = $statusLabels[$status] ?? $status;

    $raffles[$index]['prizes'] = $prizesData;
    $raffles[$index]['hero_image'] = $heroImage;
    $raffles[$index]['is_preview'] = $isPreview;
    $raffles[$index]['status_label'] = $statusLabel;
    $raffles[$index]['tag'] = $isPreview
        ? 'Vista previa · Borrador'
        : 'Gran sorteo de temporada · ' . $statusLabel;
    $raffles[$index]['lead'] = $lead;
    $raffles[$index]['dates_line'] = implode(' · ', $dateParts);
    $raffles[$index]['price_formatted'] = Money::format((int) $raffle['price_cents']);
    $raffles[$index]['tickets'] = (int) $raffle['total_tickets'];
    $raffles[$index]['max_per_person'] = (int) $raffle['max_tickets_per_participant'];
    $raffles[$index]['deadline_iso'] = $deadline instanceof DateTimeImmutable ? $deadline->format('c') : '';
    $raffles[$index]['deadline_label'] = $deadlineLabel;
}

$activeStatuses = ['published', 'paused', 'sales_closed'];
$prizeRaffles = array_values(array_filter(
    $raffles,
    static fn (array $raffle): bool => in_array((string) $raffle['status'], $activeStatuses, true)
));

if ($prizeRaffles === []) {
    $prizeRaffles = $raffles;
}

$primary = $raffles[0] ?? null;
$totalRaffles = count($raffles);

if ($primary === null) {
    $topBar = 'Muy pronto · Próximo sorteo';
} elseif ($preview) {
    $topBar = 'Vista previa · Sorteo en borrador · Aún no está publicado';
} elseif ($totalRaffles === 1) {
    $topBar = 'Sorteo activo · ' . $primary['name'];
} else {
    $topBar = 'Sorteos activos · ' . $totalRaffles . ' sorteos para participar';
}

$pageTitle = $primary !== null
    ? ($preview
        ? 'Vista previa · ' . $primary['name'] . ' · Sorteos'
        : $primary['name'] . ' · Sorteos · Radio La Poderosa')
    : 'Sorteos · Radio La Poderosa';

$pageDescription = $primary !== null ? trim((string) ($primary['summary'] ?? '')) : '';

if ($pageDescription === '') {
    $pageDescription = $primary !== null
        ? 'Participa en ' . $primary['name'] . ' de Radio La Poderosa. Compra tus números y sigue el sorteo en vivo.'
        : 'Sorteos y promociones de Radio La Poderosa.';
}

$metaImage = $primary['hero_image'] ?? '';

if ($metaImage !== '' && str_starts_with($metaImage, '/')) {
    $metaImage = rtrim((string) config('app.url'), '/') . $metaImage;
}

$robots = ($preview || $primary === null) ? 'noindex, follow' : '';

require APP_ROOT . '/web/views/sorteos.php';
