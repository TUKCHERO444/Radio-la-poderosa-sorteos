<?php
/** @var array $users */
/** @var int $currentUserId */
/** @var int $adminTotal */
/** @var string $search */
$search = isset($search) ? (string) $search : '';
$isSearching = $search !== '';

$formatDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '&mdash;';
    }
    try {
        $date = new DateTime($value);
        return $date->format('d/m/Y H:i');
    } catch (Exception $exception) {
        return e($value);
    }
};

$roleLabel = static function (string $role): string {
    return $role === 'admin' ? 'Administrador' : 'Editor';
};
?>
<section class="page-head">
    <div class="page-head-text">
        <h1>Usuarios del panel</h1>
        <p>Administra las cuentas que pueden acceder a este panel.</p>
    </div>
    <div class="page-head-actions">
        <form class="search" method="get" action="/admin/users" role="search">
            <label class="sr-only" for="userSearch">Buscar usuarios</label>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input id="userSearch" name="q" type="search" value="<?= e($search) ?>" placeholder="Buscar por usuario o nombre..." autocomplete="off">
        </form>
        <a class="btn btn-primary" href="/admin/users/create">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="M12 5v14"></path>
                <path d="M5 12h14"></path>
            </svg>
            Nuevo usuario
        </a>
    </div>
</section>

<?php if ($isSearching): ?>
    <p class="result-count" role="status">
        <strong><?= count($users) ?></strong> <?= count($users) === 1 ? 'resultado' : 'resultados' ?> para &ldquo;<?= e($search) ?>&rdquo;.
        <a href="/admin/users">Limpiar busqueda</a>
    </p>
<?php endif; ?>

<?php if ($users === []): ?>
    <section class="empty">
        <span class="empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <?php if ($isSearching): ?>
                    <path d="m16 16 5 5"></path>
                    <path d="m21 16-5 5"></path>
                <?php endif; ?>
            </svg>
        </span>
        <h2><?= $isSearching ? 'Sin resultados' : 'No hay usuarios todavia' ?></h2>
        <p>
            <?= $isSearching
                ? 'No encontramos usuarios que coincidan con tu busqueda. Prueba con otro termino.'
                : 'Crea la primera cuenta para comenzar a administrar el panel.' ?>
        </p>
        <div class="empty-actions">
            <?php if ($isSearching): ?>
                <a class="btn btn-ghost" href="/admin/users">Limpiar busqueda</a>
            <?php endif; ?>
            <a class="btn btn-primary" href="/admin/users/create">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M12 5v14"></path>
                    <path d="M5 12h14"></path>
                </svg>
                Crear usuario
            </a>
        </div>
    </section>
<?php else: ?>
    <section class="card card-flush">
        <div class="table-wrap">
            <table class="table">
                <caption class="sr-only">Usuarios registrados en el panel</caption>
                <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Usuario</th>
                    <th scope="col">Nombre</th>
                    <th scope="col">Rol</th>
                    <th scope="col">Ultimo acceso</th>
                    <th scope="col">Creado</th>
                    <th scope="col" class="table-actions-col">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <?php
                    $id = (int) $user['id'];
                    $isSelf = $id === $currentUserId;
                    $isLastAdmin = $user['role'] === 'admin' && $adminTotal <= 1;
                    $canDelete = !$isSelf && !$isLastAdmin;
                    ?>
                    <tr>
                        <td data-label="ID"><span class="mono">#<?= $id ?></span></td>
                        <td data-label="Usuario">
                            <div class="cell-user">
                                <span class="avatar avatar-sm" aria-hidden="true"><?= e(strtoupper(function_exists('mb_substr') ? mb_substr($user['username'], 0, 1) : substr($user['username'], 0, 1))) ?></span>
                                <span>
                                    <strong><?= e($user['username']) ?></strong>
                                    <?php if ($isSelf): ?><small class="badge badge-soft">Tu</small><?php endif; ?>
                                </span>
                            </div>
                        </td>
                        <td data-label="Nombre"><?= $user['name'] !== null && $user['name'] !== '' ? e($user['name']) : '<span class="muted">&mdash;</span>' ?></td>
                        <td data-label="Rol">
                            <span class="badge <?= $user['role'] === 'admin' ? 'badge-primary' : 'badge-slate' ?>"><?= e($roleLabel($user['role'])) ?></span>
                        </td>
                        <td data-label="Ultimo acceso" class="cell-muted"><?= $formatDate($user['last_login_at']) ?></td>
                        <td data-label="Creado" class="cell-muted"><?= $formatDate($user['created_at']) ?></td>
                        <td data-label="Acciones">
                            <div class="row-actions">
                                <a class="btn btn-ghost btn-sm" href="/admin/users/<?= $id ?>/edit" title="Editar" aria-label="Editar <?= e($user['username']) ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                        <path d="M12 20h9"></path>
                                        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                    </svg>
                                    Editar
                                </a>
                                <form method="post" action="/admin/users/<?= $id ?>/delete"
                                      data-confirm="¿Eliminar al usuario <?= e($user['username']) ?>? Esta accion no se puede deshacer."
                                      data-confirm-title="Eliminar usuario"
                                      data-confirm-label="Eliminar">
                                    <?= admin_csrf_field() ?>
                                    <button type="submit" class="btn btn-danger-soft btn-sm" title="Eliminar" aria-label="Eliminar <?= e($user['username']) ?>"<?= $canDelete ? '' : ' disabled' ?>>
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
