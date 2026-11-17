<?php
/** @var string $title */
/** @var string $active */
/** @var string $content */
/** @var array|null $admin */
/** @var array $flashes */
$displayName = $admin !== null
    ? ($admin['name'] !== null && $admin['name'] !== '' ? $admin['name'] : $admin['username'])
    : 'Invitado';
$initial = strtoupper(function_exists('mb_substr') ? mb_substr((string) ($admin['username'] ?? 'A'), 0, 1) : substr((string) ($admin['username'] ?? 'A'), 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Panel privado de administracion de Radio La Poderosa.">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f4f6f9">
    <title><?= e($title) ?> &middot; Panel &middot; Radio La Poderosa</title>
    <link rel="icon" type="image/png" href="/icons/icon-192.png">
    <script src="<?= e(admin_asset('theme-init.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(admin_asset('admin.css')) ?>">
</head>
<body class="app-body">
<a class="skip-link" href="#contenido">Saltar al contenido</a>

<div class="app-shell">
    <div class="app-overlay" data-sidebar-close hidden></div>

    <aside class="app-sidebar" id="adminSidebar" aria-label="Navegacion principal">
        <div class="brand">
            <span class="brand-mark" aria-hidden="true">LP</span>
            <span class="brand-text">
                <strong>La Poderosa</strong>
                <small>Panel de administracion</small>
            </span>
        </div>

        <nav class="side-nav" aria-label="Secciones del panel">
            <span class="side-nav-label">General</span>
            <a class="side-nav-link<?= $active === 'dashboard' ? ' is-active' : '' ?>" href="/admin"<?= $active === 'dashboard' ? ' aria-current="page"' : '' ?>>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <rect x="3" y="3" width="7" height="9" rx="1.5"></rect>
                    <rect x="14" y="3" width="7" height="5" rx="1.5"></rect>
                    <rect x="14" y="12" width="7" height="9" rx="1.5"></rect>
                    <rect x="3" y="16" width="7" height="5" rx="1.5"></rect>
                </svg>
                <span>Dashboard</span>
            </a>

            <span class="side-nav-label">Gestion</span>
            <a class="side-nav-link<?= $active === 'raffles' ? ' is-active' : '' ?>" href="/admin/raffles"<?= $active === 'raffles' ? ' aria-current="page"' : '' ?>>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M3 9a3 3 0 0 0 0 6v3a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-3a3 3 0 0 0 0-6V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1z"></path>
                    <path d="M13 5v2"></path>
                    <path d="M13 11v2"></path>
                    <path d="M13 17v2"></path>
                </svg>
                <span>Sorteos</span>
            </a>
            <a class="side-nav-link<?= $active === 'prizes' ? ' is-active' : '' ?>" href="/admin/prizes"<?= $active === 'prizes' ? ' aria-current="page"' : '' ?>>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M20 12v10H4V12"></path>
                    <path d="M2 7h20v5H2z"></path>
                    <path d="M12 22V7"></path>
                    <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
                    <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
                </svg>
                <span>Premios</span>
            </a>
            <a class="side-nav-link<?= $active === 'payment_methods' ? ' is-active' : '' ?>" href="/admin/payment-methods"<?= $active === 'payment_methods' ? ' aria-current="page"' : '' ?>>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                    <path d="M2 10h20"></path>
                    <path d="M6 15h4"></path>
                </svg>
                <span>Metodos de pago</span>
            </a>

            <span class="side-nav-label">Administracion</span>
            <a class="side-nav-link<?= $active === 'users' ? ' is-active' : '' ?>" href="/admin/users"<?= $active === 'users' ? ' aria-current="page"' : '' ?>>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Usuarios</span>
            </a>

            <span class="side-nav-label">Proximamente</span>
            <span class="side-nav-link is-disabled" aria-disabled="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.14.61.68 1.05 1.32 1.05H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                <span>Ajustes</span>
            </span>
        </nav>

        <div class="sidebar-footer">
            <div class="user-chip">
                <span class="avatar" aria-hidden="true"><?= e($initial) ?></span>
                <span class="user-chip-text">
                    <strong><?= e($displayName) ?></strong>
                    <small>@<?= e($admin['username'] ?? '') ?></small>
                </span>
            </div>
            <form method="post" action="/admin/logout">
                <?= admin_csrf_field() ?>
                <button type="submit" class="btn btn-ghost btn-block">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <path d="m16 17 5-5-5-5"></path>
                        <path d="M21 12H9"></path>
                    </svg>
                    Cerrar sesion
                </button>
            </form>
        </div>
    </aside>

    <div class="app-main">
        <header class="topbar">
            <button type="button" class="menu-btn" data-sidebar-toggle aria-label="Abrir menu" aria-controls="adminSidebar" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false">
                    <path d="M3 6h18"></path>
                    <path d="M3 12h18"></path>
                    <path d="M3 18h18"></path>
                </svg>
            </button>

            <div class="topbar-title">
                <span class="topbar-eyebrow">Radio La Poderosa</span>
                <strong class="topbar-title-text"><?= e($title) ?></strong>
            </div>

            <div class="topbar-actions">
                <button type="button" class="theme-toggle" data-theme-toggle aria-label="Activar tema oscuro" aria-pressed="false">
                    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <circle cx="12" cy="12" r="4"></circle>
                        <path d="M12 2v2"></path>
                        <path d="M12 20v2"></path>
                        <path d="m4.93 4.93 1.41 1.41"></path>
                        <path d="m17.66 17.66 1.41 1.41"></path>
                        <path d="M2 12h2"></path>
                        <path d="M20 12h2"></path>
                        <path d="m6.34 17.66-1.41 1.41"></path>
                        <path d="m19.07 4.93-1.41 1.41"></path>
                    </svg>
                </button>

                <a class="btn btn-ghost btn-sm" href="/" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <path d="M15 3h6v6"></path>
                        <path d="M10 14 21 3"></path>
                    </svg>
                    <span class="btn-label">Ver sitio</span>
                </a>
            </div>
        </header>

        <main class="app-content" id="contenido" tabindex="-1">
            <?php if ($flashes !== []): ?>
                <div class="flashes">
                    <?php foreach ($flashes as $flash): ?>
                        <?php
                        $type = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info';
                        $role = in_array($type, ['error', 'warning'], true) ? 'alert' : 'status';
                        ?>
                        <div class="alert alert-<?= e($type) ?>" role="<?= $role ?>">
                            <span class="alert-content"><?= e($flash['message']) ?></span>
                            <button type="button" class="alert-close" data-dismiss-alert aria-label="Cerrar aviso">&times;</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>
</div>

<dialog class="dialog" id="confirmDialog" aria-labelledby="confirmDialogTitle" aria-describedby="confirmDialogMessage">
    <div class="dialog-panel">
        <header class="dialog-header">
            <h2 id="confirmDialogTitle">Confirmar accion</h2>
            <button type="button" class="btn btn-icon dialog-close" data-dialog-close aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </header>
        <div class="dialog-body">
            <p id="confirmDialogMessage" data-dialog-message>¿Deseas continuar con esta accion?</p>
        </div>
        <footer class="dialog-footer">
            <button type="button" class="btn btn-ghost" data-dialog-cancel autofocus>Cancelar</button>
            <button type="button" class="btn btn-danger" data-dialog-confirm>Confirmar</button>
        </footer>
    </div>
</dialog>

<script src="<?= e(admin_asset('admin.js')) ?>" defer></script>
</body>
</html>
