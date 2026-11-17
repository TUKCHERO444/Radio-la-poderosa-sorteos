<?php
/** @var array $raffles */
/** @var string $search */
/** @var string $status */
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
$statusBadges = [
    'draft' => 'badge-slate',
    'published' => 'badge-success',
    'paused' => 'badge-warning',
    'sales_closed' => 'badge-soft',
    'drawing' => 'badge-primary',
    'drawn' => 'badge-primary',
    'results_published' => 'badge-primary',
    'closed' => 'badge-slate',
    'cancelled' => 'badge-danger',
];
$hasFilters = $search !== '' || $status !== '';
?>
<section class="page-head">
    <div class="page-head-text">
        <h1>Sorteos</h1>
        <p>Crea, publica y administra los sorteos del sistema.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-primary" href="/admin/raffles/create">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="M12 5v14"></path>
                <path d="M5 12h14"></path>
            </svg>
            Nuevo sorteo
        </a>
    </div>
</section>

<form class="toolbar" method="get" action="/admin/raffles" role="search">
    <div class="toolbar-group">
        <div class="search">
            <label class="sr-only" for="raffleSearch">Buscar sorteos</label>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input id="raffleSearch" name="q" type="search" value="<?= e($search) ?>" placeholder="Buscar por nombre o slug..." autocomplete="off">
        </div>
        <select class="select" name="status" data-auto-submit aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            <?php foreach ($statusLabels as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= $status === $value ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($hasFilters): ?>
        <a class="btn btn-ghost btn-sm" href="/admin/raffles">Limpiar filtros</a>
    <?php endif; ?>
</form>

<?php if ($raffles === []): ?>
    <section class="empty">
        <span class="empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                <path d="M3 9a3 3 0 0 0 0 6v3a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-3a3 3 0 0 0 0-6V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1z"></path>
                <path d="M13 5v2"></path>
                <path d="M13 11v2"></path>
                <path d="M13 17v2"></path>
            </svg>
        </span>
        <h2><?= $hasFilters ? 'Sin resultados' : 'Aun no hay sorteos' ?></h2>
        <p><?= $hasFilters ? 'No encontramos sorteos con esos filtros. Prueba con otro termino.' : 'Crea el primer sorteo, asigna premios y prepara la publicacion.' ?></p>
        <div class="empty-actions">
            <?php if ($hasFilters): ?>
                <a class="btn btn-ghost" href="/admin/raffles">Limpiar filtros</a>
            <?php endif; ?>
            <a class="btn btn-primary" href="/admin/raffles/create">Crear sorteo</a>
        </div>
    </section>
<?php else: ?>
    <section class="card card-flush">
        <div class="table-wrap">
            <table class="table table-compact">
                <caption class="sr-only">Sorteos del sistema</caption>
                <thead>
                <tr>
                    <th scope="col">Sorteo</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Precio</th>
                    <th scope="col">Cupos</th>
                    <th scope="col">Configuracion</th>
                    <th scope="col">Fecha del sorteo</th>
                    <th scope="col" class="table-actions-col">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($raffles as $raffle): ?>
                    <?php
                    $id = (int) $raffle['id'];
                    $canPublish = $raffle['status'] === 'draft';
                    $canClose = in_array($raffle['status'], ['published', 'paused'], true);
                    $canDelete = $raffle['status'] === 'draft';
                    $prizesCount = (int) $raffle['prizes_count'];
                    $methodsCount = (int) $raffle['payment_methods_count'];
                    ?>
                    <tr>
                        <td data-label="Sorteo">
                            <div class="table-media">
                                <?php if (!empty($raffle['cover_image_path'])): ?>
                                    <img class="thumb" src="<?= e($raffle['cover_image_path']) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <span class="thumb-placeholder" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                            <path d="M3 9a3 3 0 0 0 0 6v3a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-3a3 3 0 0 0 0-6V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1z"></path>
                                            <path d="M13 5v2"></path>
                                            <path d="M13 11v2"></path>
                                            <path d="M13 17v2"></path>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                                <span>
                                    <strong><?= e($raffle['name']) ?></strong>
                                    <small>/sorteos/<?= e($raffle['slug']) ?></small>
                                </span>
                            </div>
                        </td>
                        <td data-label="Estado">
                            <span class="badge <?= e($statusBadges[$raffle['status']] ?? 'badge-slate') ?>">
                                <?= e($statusLabels[$raffle['status']] ?? $raffle['status']) ?>
                            </span>
                        </td>
                        <td data-label="Precio" class="num"><?= e(\App\Shared\Money::format((int) $raffle['price_cents'])) ?></td>
                        <td data-label="Cupos">
                            <span class="cell-stack">
                                <strong class="num"><?= (int) $raffle['total_tickets'] ?></strong>
                                <small>Max. <?= (int) $raffle['max_tickets_per_participant'] ?> por persona</small>
                            </span>
                        </td>
                        <td data-label="Configuracion">
                            <span class="cell-stack">
                                <strong><?= $prizesCount ?> <?= $prizesCount === 1 ? 'premio' : 'premios' ?></strong>
                                <small><?= $methodsCount ?> <?= $methodsCount === 1 ? 'metodo' : 'metodos' ?> de pago</small>
                            </span>
                        </td>
                        <td data-label="Fecha del sorteo"><?= e(\App\Shared\Dates::display($raffle['draw_at'])) ?></td>
                        <td data-label="Acciones">
                            <div class="row-actions">
                                <a class="btn btn-ghost btn-sm" href="/admin/raffles/<?= $id ?>/edit" title="Editar" aria-label="Editar <?= e($raffle['name']) ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                        <path d="M12 20h9"></path>
                                        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                    </svg>
                                    Editar
                                </a>
                                <?php if ($canPublish): ?>
                                    <a class="btn btn-primary btn-sm" href="/admin/raffles/<?= $id ?>/publish">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                            <path d="m5 12 4 4L19 6"></path>
                                        </svg>
                                        Publicar
                                    </a>
                                <?php endif; ?>
                                <?php if ($canClose): ?>
                                    <form method="post" action="/admin/raffles/<?= $id ?>/close" data-confirm="¿Cerrar las ventas de este sorteo? Los clientes ya no podran comprar." data-confirm-title="Cerrar ventas" data-confirm-label="Cerrar ventas">
                                        <?= admin_csrf_field() ?>
                                        <button type="submit" class="btn btn-ghost btn-sm">Cerrar</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                    <form method="post" action="/admin/raffles/<?= $id ?>/delete" data-confirm="¿Eliminar este sorteo en borrador? Esta accion no se puede deshacer." data-confirm-title="Eliminar sorteo" data-confirm-label="Eliminar">
                                        <?= admin_csrf_field() ?>
                                        <button type="submit" class="btn btn-danger-soft btn-sm" aria-label="Eliminar <?= e($raffle['name']) ?>">Eliminar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
