<?php
/** @var array $prizes */
/** @var string $search */
/** @var string $status */
$typeLabels = [
    'product' => 'Producto',
    'service' => 'Servicio',
    'cash' => 'Efectivo',
    'other' => 'Otro',
];
$hasFilters = $search !== '' || $status !== '';
?>
<section class="page-head">
    <div class="page-head-text">
        <h1>Premios</h1>
        <p>Catalogo de productos y premios disponibles para asignar a los sorteos.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-primary" href="/admin/prizes/create">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="M12 5v14"></path>
                <path d="M5 12h14"></path>
            </svg>
            Nuevo premio
        </a>
    </div>
</section>

<form class="toolbar" method="get" action="/admin/prizes" role="search">
    <div class="toolbar-group">
        <div class="search">
            <label class="sr-only" for="prizeSearch">Buscar premios</label>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input id="prizeSearch" name="q" type="search" value="<?= e($search) ?>" placeholder="Buscar por nombre o descripcion..." autocomplete="off">
        </div>
        <select class="select" name="status" data-auto-submit aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            <option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Activos</option>
            <option value="inactive"<?= $status === 'inactive' ? ' selected' : '' ?>>Inactivos</option>
        </select>
    </div>
    <?php if ($hasFilters): ?>
        <a class="btn btn-ghost btn-sm" href="/admin/prizes">Limpiar filtros</a>
    <?php endif; ?>
</form>

<?php if ($prizes === []): ?>
    <section class="empty">
        <span class="empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                <path d="M20 12v10H4V12"></path>
                <path d="M2 7h20v5H2z"></path>
                <path d="M12 22V7"></path>
                <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
                <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
            </svg>
        </span>
        <h2><?= $hasFilters ? 'Sin resultados' : 'Aun no hay premios' ?></h2>
        <p><?= $hasFilters ? 'No encontramos premios con esos filtros. Prueba con otro termino.' : 'Crea el primer premio para asignarlo a un sorteo.' ?></p>
        <div class="empty-actions">
            <?php if ($hasFilters): ?>
                <a class="btn btn-ghost" href="/admin/prizes">Limpiar filtros</a>
            <?php endif; ?>
            <a class="btn btn-primary" href="/admin/prizes/create">Crear premio</a>
        </div>
    </section>
<?php else: ?>
    <section class="card card-flush">
        <div class="table-wrap">
            <table class="table table-compact">
                <caption class="sr-only">Catalogo de premios</caption>
                <thead>
                <tr>
                    <th scope="col">Premio</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Valor y entrega</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Sorteos</th>
                    <th scope="col" class="table-actions-col">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($prizes as $prize): ?>
                    <?php $id = (int) $prize['id']; ?>
                    <tr>
                        <td data-label="Premio">
                            <div class="table-media">
                                <?php if (!empty($prize['image_path'])): ?>
                                    <img class="thumb" src="<?= e($prize['image_path']) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <span class="thumb-placeholder" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                            <path d="M20 12v10H4V12"></path>
                                            <path d="M2 7h20v5H2z"></path>
                                            <path d="M12 22V7"></path>
                                            <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
                                            <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                                <span>
                                    <strong><?= e($prize['name']) ?></strong>
                                    <small><?= e($prize['description'] !== null && $prize['description'] !== '' ? (string) $prize['description'] : 'Sin descripcion') ?></small>
                                </span>
                            </div>
                        </td>
                        <td data-label="Tipo"><?= e($typeLabels[$prize['prize_type']] ?? $prize['prize_type']) ?></td>
                        <td data-label="Valor">
                            <span class="cell-stack">
                                <strong class="num"><?= $prize['estimated_value_cents'] !== null ? e(\App\Shared\Money::format((int) $prize['estimated_value_cents'])) : '—' ?></strong>
                                <small><?= e($prize['delivery_method'] !== null && $prize['delivery_method'] !== '' ? 'Entrega: ' . $prize['delivery_method'] : 'Sin metodo de entrega') ?></small>
                            </span>
                        </td>
                        <td data-label="Estado">
                            <span class="badge <?= (int) $prize['is_active'] === 1 ? 'badge-success' : 'badge-slate' ?>">
                                <?= (int) $prize['is_active'] === 1 ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td data-label="Sorteos" class="num">
                            <span title="<?= (int) $prize['assignments_count'] ?> sorteo(s) usan este premio"><?= (int) $prize['assignments_count'] ?></span>
                        </td>
                        <td data-label="Acciones">
                            <div class="row-actions">
                                <a class="btn btn-ghost btn-sm" href="/admin/prizes/<?= $id ?>/edit" title="Editar" aria-label="Editar <?= e($prize['name']) ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                        <path d="M12 20h9"></path>
                                        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                    </svg>
                                    Editar
                                </a>
                                <form method="post" action="/admin/prizes/<?= $id ?>/toggle">
                                    <?= admin_csrf_field() ?>
                                    <input type="hidden" name="active" value="<?= (int) $prize['is_active'] === 1 ? '0' : '1' ?>">
                                    <button type="submit" class="btn btn-ghost btn-sm" aria-label="<?= (int) $prize['is_active'] === 1 ? 'Desactivar' : 'Activar' ?> <?= e($prize['name']) ?>">
                                        <?= (int) $prize['is_active'] === 1 ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                                <form method="post" action="/admin/prizes/<?= $id ?>/delete"
                                      data-confirm="¿Eliminar el premio <?= e($prize['name']) ?>? Esta accion no se puede deshacer."
                                      data-confirm-title="Eliminar premio"
                                      data-confirm-label="Eliminar">
                                    <?= admin_csrf_field() ?>
                                    <button type="submit" class="btn btn-danger-soft btn-sm" aria-label="Eliminar <?= e($prize['name']) ?>"<?= (int) $prize['assignments_count'] > 0 ? ' disabled title="Asignado a un sorteo"' : '' ?>>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                            <path d="M3 6h18"></path>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <path d="M10 11v6"></path>
                                            <path d="M14 11v6"></path>
                                        </svg>
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
