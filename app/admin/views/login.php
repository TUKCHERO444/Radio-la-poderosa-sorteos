<?php
/** @var string $username */
/** @var string $error */
/** @var int $locked */
/** @var array $flashes */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Acceso privado al panel de administracion de Radio La Poderosa.">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f4f6f9">
    <title>Acceso &middot; Panel &middot; Radio La Poderosa</title>
    <link rel="icon" type="image/png" href="/icons/icon-192.png">
    <script src="<?= e(admin_asset('theme-init.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(admin_asset('admin.css')) ?>">
</head>
<body class="auth-body">
<main class="auth">
    <section class="auth-brand" aria-label="Radio La Poderosa">
        <div class="auth-brand-inner">
            <span class="brand-mark brand-mark-lg" aria-hidden="true">LP</span>
            <p class="auth-brand-name">Radio La Poderosa</p>
            <p>Panel de administracion de sorteos, productos y contenido del sitio.</p>
            <ul class="auth-list">
                <li>Gestion de usuarios y accesos</li>
                <li>Preparado para sorteos y productos</li>
                <li>Diseno adaptable a cualquier pantalla</li>
            </ul>
        </div>
        <span class="auth-glow" aria-hidden="true"></span>
    </section>

    <section class="auth-panel">
        <div class="auth-theme">
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
        </div>

        <div class="auth-card">
            <header class="auth-header">
                <span class="auth-eyebrow">Acceso privado</span>
                <h1>Iniciar sesion</h1>
                <p>Ingresa tus credenciales para administrar el sitio.</p>
            </header>

            <?php if ($flashes !== []): ?>
                <div class="flashes">
                    <?php foreach ($flashes as $flash): ?>
                        <?php
                        $type = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info';
                        $role = in_array($type, ['error', 'warning'], true) ? 'alert' : 'status';
                        ?>
                        <div class="alert alert-<?= e($type) ?>" role="<?= $role ?>">
                            <span class="alert-content"><?= e($flash['message']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error" role="alert">
                    <span class="alert-content"><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="/admin/login" class="form" autocomplete="on" novalidate data-loading>
                <?= admin_csrf_field() ?>

                <div class="field">
                    <label for="username">Usuario</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                        <input id="username" name="username" type="text" class="input" value="<?= e($username) ?>"
                               autocomplete="username" autocapitalize="none" spellcheck="false"
                               placeholder="Tu usuario" required autofocus>
                    </div>
                </div>

                <div class="field">
                    <label for="password">Contrasena</label>
                    <div class="input-wrap has-action">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <input id="password" name="password" type="password" class="input"
                               autocomplete="current-password" placeholder="Tu contrasena" required>
                        <button type="button" class="input-action" data-toggle-password="password" aria-label="Ver contrasena" aria-pressed="false">Ver</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg"<?= $locked > 0 ? ' disabled' : '' ?>>
                    Entrar al panel
                </button>

                <?php if ($locked > 0): ?>
                    <p class="auth-hint" role="status">Por seguridad, espera <?= (int) $locked ?> segundos antes de reintentar.</p>
                <?php endif; ?>
            </form>

            <footer class="auth-footer">
                <a href="/">&larr; Volver al sitio</a>
            </footer>
        </div>
    </section>
</main>
<script src="<?= e(admin_asset('admin.js')) ?>" defer></script>
</body>
</html>
