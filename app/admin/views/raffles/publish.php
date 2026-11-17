<?php
/** @var array $raffle */
/** @var array|null $rules */
/** @var int[] $prize_ids */
/** @var int[] $method_ids */
/** @var array $prizes */
/** @var array $methods */
/** @var array $errors */
$statusLabels = [
    'draft' => 'Borrador',
    'published' => 'Publicado',
    'paused' => 'Pausado',
    'sales_closed' => 'Ventas cerradas',
    'drawing' => 'Sorteando',
    'drawn' => 'Sorteado',
    'results_published' => 'Resultados publicados',
    'closed' => 'Cerrado',
    'cancelled' => 'Cancelado',
];
$prizeNames = [];
foreach ($prizes as $prize) {
    $prizeNames[(int) $prize['id']] = (string) $prize['name'];
}
$methodNames = [];
foreach ($methods as $method) {
    $methodNames[(int) $method['id']] = (string) $method['name'];
}
$hasPrizes = $prize_ids !== [];
$hasMethods = $method_ids !== [];
$rulesLength = $rules !== null ? (function_exists('mb_strlen') ? mb_strlen(trim((string) $rules['content'])) : strlen(trim((string) $rules['content']))) : 0;
$hasRules = $rules !== null && $rulesLength >= 20;
$hasPrice = (int) $raffle['price_cents'] > 0;
$hasTickets = (int) $raffle['total_tickets'] > 0;
$hasDates = !empty($raffle['sales_start_at']) && !empty($raffle['sales_end_at']) && !empty($raffle['draw_at']) && !empty($raffle['claim_deadline_at']);
$requirementsMet = $hasPrizes && $hasMethods && $hasRules && $hasPrice && $hasTickets && $hasDates;
$requirements = [
    ['ok' => $hasPrizes, 'label' => 'Premio asignado', 'detail' => $hasPrizes ? count($prize_ids) . ' premio(s) en el sorteo.' : 'Selecciona un premio principal.'],
    ['ok' => $hasMethods, 'label' => 'Metodo de pago activo', 'detail' => $hasMethods ? count($method_ids) . ' metodo(s) asignado(s).' : 'Selecciona al menos un metodo.'],
    ['ok' => $hasRules, 'label' => 'Bases y condiciones', 'detail' => $hasRules ? 'Version ' . (int) $rules['version'] . ' disponible.' : 'Las bases deben tener al menos 20 caracteres.'],
    ['ok' => $hasPrice, 'label' => 'Precio del boleto', 'detail' => $hasPrice ? \App\Shared\Money::format((int) $raffle['price_cents']) : 'Define un precio mayor a cero.'],
    ['ok' => $hasTickets, 'label' => 'Cupos', 'detail' => $hasTickets ? (int) $raffle['total_tickets'] . ' cupos disponibles.' : 'Define los cupos del sorteo.'],
    ['ok' => $hasDates, 'label' => 'Fechas completas', 'detail' => $hasDates ? 'Inicio, cierre, sorteo y reclamo definidos.' : 'Completa todas las fechas.'],
];
?>
<section class="page-head">
    <div class="page-head-text">
        <h1>Publicar sorteo</h1>
        <p>Revisa los requisitos y confirma el checklist legal antes de hacer visible el sorteo.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="/admin/raffles/<?= (int) $raffle['id'] ?>/edit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            Volver a editar
        </a>
    </div>
</section>

<?php if ($errors !== []): ?>
    <div class="alert alert-error" role="alert">
        <div class="alert-content">
            <div>
                <strong>No se pudo publicar el sorteo.</strong>
                <ul class="alert-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="form-layout">
    <div class="form-layout-main">
        <section class="form-section">
            <header class="form-section-head">
                <div>
                    <h2 class="form-section-title">Requisitos del sorteo</h2>
                    <p class="form-section-desc">Estos puntos se validan antes de publicar.</p>
                </div>
            </header>

            <div class="publish-requirements">
                <?php foreach ($requirements as $requirement): ?>
                    <div class="publish-requirement<?= $requirement['ok'] ? ' is-ok' : ' is-missing' ?>">
                        <span class="publish-requirement-icon" aria-hidden="true">
                            <?php if ($requirement['ok']): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                    <path d="m5 12 4 4L19 6"></path>
                                </svg>
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                    <path d="M12 8v4"></path>
                                    <path d="M12 16h.01"></path>
                                </svg>
                            <?php endif; ?>
                        </span>
                        <span>
                            <strong><?= e($requirement['label']) ?></strong>
                            <small><?= e($requirement['detail']) ?></small>
                        </span>
                        <span class="badge <?= $requirement['ok'] ? 'badge-success' : 'badge-warning' ?>"><?= $requirement['ok'] ? 'Listo' : 'Pendiente' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="form-section">
            <header class="form-section-head">
                <div>
                    <h2 class="form-section-title">Checklist legal</h2>
                    <p class="form-section-desc">Confirma cada punto. Esta accion quedara auditada con tu usuario y fecha.</p>
                </div>
            </header>

            <div class="alert alert-warning" role="status">
                <span class="alert-content">Publicar no reemplaza la validacion legal. Si el cobro es una venta de participaciones, confirma la calificacion con un abogado antes de continuar.</span>
            </div>

            <form method="post" action="/admin/raffles/<?= (int) $raffle['id'] ?>/publish" class="form" novalidate>
                <?= admin_csrf_field() ?>

                <div class="publish-checklist">
                    <label class="publish-check">
                        <input type="checkbox" name="legal_validated" value="1">
                        <span><strong>Calificacion legal validada</strong><small>Revise si el sorteo es promocional o venta de participaciones y si requiere autorizacion.</small></span>
                    </label>
                    <label class="publish-check">
                        <input type="checkbox" name="bases_approved" value="1">
                        <span><strong>Bases y condiciones aprobadas</strong><small>La version publicada es la que aceptara el participante.</small></span>
                    </label>
                    <label class="publish-check">
                        <input type="checkbox" name="privacy_approved" value="1">
                        <span><strong>Politica de privacidad aprobada</strong><small>Incluye tratamiento de DNI, celular, correo y comprobantes.</small></span>
                    </label>
                    <label class="publish-check">
                        <input type="checkbox" name="claims_available" value="1">
                        <span><strong>Libro de Reclamaciones disponible</strong><small>El canal de reclamos esta visible en la web publica.</small></span>
                    </label>
                    <label class="publish-check">
                        <input type="checkbox" name="authorization_checked" value="1">
                        <span><strong>Autorizacion revisada</strong><small>Se verifico si corresponde numero de resolucion o si no aplica.</small></span>
                    </label>
                </div>

                <div class="form-actions">
                    <a class="btn btn-ghost" href="/admin/raffles/<?= (int) $raffle['id'] ?>/edit">Cancelar</a>
                    <button type="submit" class="btn btn-primary"<?= $requirementsMet ? '' : ' disabled' ?>>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <path d="m5 12 4 4L19 6"></path>
                        </svg>
                        Publicar sorteo
                    </button>
                </div>
            </form>
        </section>
    </div>

    <aside class="form-layout-side">
        <section class="card">
            <header class="card-header">
                <h2>Resumen</h2>
                <span class="badge badge-slate"><?= e($statusLabels[$raffle['status']] ?? $raffle['status']) ?></span>
            </header>
            <div class="stack-sm">
                <div class="status-row"><span>Sorteo</span><strong><?= e($raffle['name']) ?></strong></div>
                <div class="status-row"><span>Slug</span><strong>/sorteos/<?= e($raffle['slug']) ?></strong></div>
                <div class="status-row"><span>Precio</span><strong><?= e(\App\Shared\Money::format((int) $raffle['price_cents'])) ?></strong></div>
                <div class="status-row"><span>Cupos</span><strong><?= (int) $raffle['total_tickets'] ?></strong></div>
                <div class="status-row"><span>Maximo por persona</span><strong><?= (int) $raffle['max_tickets_per_participant'] ?></strong></div>
                <div class="status-row"><span>Inicio ventas</span><strong><?= e(\App\Shared\Dates::display($raffle['sales_start_at'])) ?></strong></div>
                <div class="status-row"><span>Cierre ventas</span><strong><?= e(\App\Shared\Dates::display($raffle['sales_end_at'])) ?></strong></div>
                <div class="status-row"><span>Sorteo</span><strong><?= e(\App\Shared\Dates::display($raffle['draw_at'])) ?></strong></div>
                <div class="status-row"><span>Limite de reclamo</span><strong><?= e(\App\Shared\Dates::display($raffle['claim_deadline_at'])) ?></strong></div>
            </div>
        </section>

        <section class="card">
            <header class="card-header">
                <h2>Premios</h2>
            </header>
            <div class="stack-sm">
                <?php if ($prize_ids === []): ?>
                    <p class="help-text">Sin premios asignados.</p>
                <?php else: ?>
                    <?php foreach ($prize_ids as $index => $prizeId): ?>
                        <div class="status-row"><span>Posicion <?= $index + 1 ?></span><strong><?= e($prizeNames[$prizeId] ?? 'Premio no disponible') ?></strong></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <header class="card-header">
                <h2>Metodos de pago</h2>
            </header>
            <div class="stack-sm">
                <?php if ($method_ids === []): ?>
                    <p class="help-text">Sin metodos asignados.</p>
                <?php else: ?>
                    <?php foreach ($method_ids as $methodId): ?>
                        <div class="status-row"><span>Metodo</span><strong><?= e($methodNames[$methodId] ?? 'Metodo no disponible') ?></strong></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>
