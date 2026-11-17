<?php
/** @var array $methods */
/** @var string $search */
/** @var string $type */
/** @var string $status */
$typeLabels = [
    'wallet' => 'Billetera digital',
    'bank' => 'Cuenta bancaria',
];
$providerLabels = [
    'yape' => 'Yape',
    'plin' => 'Plin',
    'bcp' => 'BCP',
    'other' => 'Otro',
];
$hasFilters = $search !== '' || $type !== '' || $status !== '';
?>
<section class="page-head">
    <div class="page-head-text">
        <h1>Metodos de pago</h1>
        <p>Configura las billeteras y cuentas bancarias que se mostraran en los sorteos.</p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-primary" href="/admin/payment-methods/create">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="M12 5v14"></path>
                <path d="M5 12h14"></path>
            </svg>
            Nuevo metodo
        </a>
    </div>
</section>

<form class="toolbar" method="get" action="/admin/payment-methods" role="search">
    <div class="toolbar-group">
        <div class="search">
            <label class="sr-only" for="methodSearch">Buscar metodos de pago</label>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input id="methodSearch" name="q" type="search" value="<?= e($search) ?>" placeholder="Buscar por nombre o titular..." autocomplete="off">
        </div>
        <select class="select" name="type" data-auto-submit aria-label="Filtrar por tipo">
            <option value="">Todos los tipos</option>
            <option value="wallet"<?= $type === 'wallet' ? ' selected' : '' ?>>Billetera digital</option>
            <option value="bank"<?= $type === 'bank' ? ' selected' : '' ?>>Cuenta bancaria</option>
        </select>
        <select class="select" name="status" data-auto-submit aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            <option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Activos</option>
            <option value="inactive"<?= $status === 'inactive' ? ' selected' : '' ?>>Inactivos</option>
        </select>
    </div>
    <?php if ($hasFilters): ?>
        <a class="btn btn-ghost btn-sm" href="/admin/payment-methods">Limpiar filtros</a>
    <?php endif; ?>
</form>

<?php if ($methods === []): ?>
    <section class="empty">
        <span class="empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                <path d="M2 10h20"></path>
                <path d="M6 15h4"></path>
            </svg>
        </span>
        <h2><?= $hasFilters ? 'Sin resultados' : 'Aun no hay metodos de pago' ?></h2>
        <p><?= $hasFilters ? 'No encontramos metodos con esos filtros. Prueba con otro termino.' : 'Crea el primer metodo para que los clientes puedan pagar por Yape, Plin o BCP.' ?></p>
        <div class="empty-actions">
            <?php if ($hasFilters): ?>
                <a class="btn btn-ghost" href="/admin/payment-methods">Limpiar filtros</a>
            <?php endif; ?>
            <a class="btn btn-primary" href="/admin/payment-methods/create">Crear metodo</a>
        </div>
    </section>
<?php else: ?>
    <section class="card card-flush">
        <div class="table-wrap">
            <table class="table table-compact">
                <caption class="sr-only">Metodos de pago configurados</caption>
                <thead>
                <tr>
                    <th scope="col">Metodo</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Titular</th>
                    <th scope="col">Datos de pago</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Sorteos</th>
                    <th scope="col" class="table-actions-col">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($methods as $method): ?>
                    <?php
                    $id = (int) $method['id'];
                    $paymentData = (string) ($method['phone'] ?? '');
                    if ($paymentData === '') {
                        $paymentData = (string) ($method['account_number'] ?? '');
                    }
                    if (!empty($method['cci'])) {
                        $paymentData .= ' · CCI ' . $method['cci'];
                    }
                    ?>
                    <tr>
                        <td data-label="Metodo">
                            <div class="table-media">
                                <?php if (!empty($method['qr_image_path'])): ?>
                                    <img class="thumb" src="<?= e($method['qr_image_path']) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <span class="thumb-placeholder" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                            <path d="M2 10h20"></path>
                                            <path d="M6 15h4"></path>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                                <span>
                                    <strong><?= e($method['name']) ?></strong>
                                    <small><?= e($providerLabels[$method['provider']] ?? $method['provider']) ?></small>
                                </span>
                            </div>
                        </td>
                        <td data-label="Tipo">
                            <span class="badge <?= $method['type'] === 'wallet' ? 'badge-primary' : 'badge-slate' ?>">
                                <?= e($typeLabels[$method['type']] ?? $method['type']) ?>
                            </span>
                        </td>
                        <td data-label="Titular">
                            <?= e($method['holder_name']) ?>
                            <?php if (!empty($method['holder_document'])): ?>
                                <small class="muted"> · <?= e($method['holder_document']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td data-label="Datos de pago">
                            <span class="cell-stack">
                                <strong class="num"><?= e($paymentData) ?></strong>
                                <small>Sobrepagos: <?= e($method['contact_phone'] ?: '—') ?></small>
                            </span>
                        </td>
                        <td data-label="Estado">
                            <span class="badge <?= (int) $method['is_active'] === 1 ? 'badge-success' : 'badge-slate' ?>">
                                <?= (int) $method['is_active'] === 1 ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td data-label="Sorteos" class="num"><?= (int) $method['assignments_count'] ?></td>
                        <td data-label="Acciones">
                            <div class="row-actions">
                                <a class="btn btn-ghost btn-sm" href="/admin/payment-methods/<?= $id ?>/edit" title="Editar" aria-label="Editar <?= e($method['name']) ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                        <path d="M12 20h9"></path>
                                        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                    </svg>
                                    Editar
                                </a>
                                <form method="post" action="/admin/payment-methods/<?= $id ?>/toggle">
                                    <?= admin_csrf_field() ?>
                                    <input type="hidden" name="active" value="<?= (int) $method['is_active'] === 1 ? '0' : '1' ?>">
                                    <button type="submit" class="btn btn-ghost btn-sm" aria-label="<?= (int) $method['is_active'] === 1 ? 'Desactivar' : 'Activar' ?> <?= e($method['name']) ?>">
                                        <?= (int) $method['is_active'] === 1 ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                                <form method="post" action="/admin/payment-methods/<?= $id ?>/delete"
                                      data-confirm="¿Eliminar el metodo <?= e($method['name']) ?>? Esta accion no se puede deshacer."
                                      data-confirm-title="Eliminar metodo de pago"
                                      data-confirm-label="Eliminar">
                                    <?= admin_csrf_field() ?>
                                    <button type="submit" class="btn btn-danger-soft btn-sm" aria-label="Eliminar <?= e($method['name']) ?>"<?= ((int) $method['assignments_count'] > 0 || (int) $method['payments_count'] > 0) ? ' disabled title="Tiene sorteos o pagos asociados"' : '' ?>>
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
