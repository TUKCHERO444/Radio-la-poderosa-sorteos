<?php
/** @var int $usersCount */
/** @var int $adminCount */
/** @var int $rafflesCount */
/** @var int $publishedRafflesCount */
/** @var int $prizesCount */
/** @var int $paymentMethodsCount */
$current = admin_current_user();
$firstName = $current !== null && $current['name'] !== null && $current['name'] !== ''
    ? $current['name']
    : ($current['username'] ?? 'administrador');
?>
<section class="hero" aria-labelledby="dashboardTitle">
    <div class="hero-text">
        <span class="eyebrow">Bienvenido</span>
        <h1 id="dashboardTitle">Hola, <?= e($firstName) ?></h1>
        <p>Administra sorteos, premios y metodos de pago desde este panel. La web publica y el motor de sorteo se conectaran en las siguientes fases.</p>
    </div>
    <a class="btn btn-light" href="/admin/raffles/create">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M12 5v14"></path>
            <path d="M5 12h14"></path>
        </svg>
        Crear sorteo
    </a>
</section>

<section class="stat-grid" aria-label="Resumen del panel">
    <article class="stat">
        <span class="stat-icon stat-icon-accent" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                <path d="M3 9a3 3 0 0 0 0 6v3a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-3a3 3 0 0 0 0-6V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1z"></path>
                <path d="M13 5v2"></path>
                <path d="M13 11v2"></path>
                <path d="M13 17v2"></path>
            </svg>
        </span>
        <div class="stat-body">
            <span class="stat-label">Sorteos</span>
            <strong class="stat-value"><?= (int) $rafflesCount ?></strong>
            <span class="stat-trend"><?= (int) $publishedRafflesCount ?> publicados</span>
        </div>
    </article>

    <article class="stat">
        <span class="stat-icon stat-icon-info" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                <path d="M20 12v10H4V12"></path>
                <path d="M2 7h20v5H2z"></path>
                <path d="M12 22V7"></path>
                <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
                <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
            </svg>
        </span>
        <div class="stat-body">
            <span class="stat-label">Premios</span>
            <strong class="stat-value"><?= (int) $prizesCount ?></strong>
            <span class="stat-trend">Catalogo disponible</span>
        </div>
    </article>

    <article class="stat">
        <span class="stat-icon stat-icon-success" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                <path d="M2 10h20"></path>
                <path d="M6 15h4"></path>
            </svg>
        </span>
        <div class="stat-body">
            <span class="stat-label">Metodos de pago</span>
            <strong class="stat-value"><?= (int) $paymentMethodsCount ?></strong>
            <span class="stat-trend">Configurados</span>
        </div>
    </article>

    <article class="stat">
        <span class="stat-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </span>
        <div class="stat-body">
            <span class="stat-label">Usuarios</span>
            <strong class="stat-value"><?= (int) $usersCount ?></strong>
            <span class="stat-trend"><?= (int) $adminCount ?> administradores</span>
        </div>
    </article>
</section>

<div class="grid-2">
    <section class="card" aria-labelledby="quickLinksTitle">
        <header class="card-header">
            <h2 id="quickLinksTitle">Accesos rapidos</h2>
        </header>
        <div class="quick-links">
            <a class="quick-link" href="/admin/raffles/create">
                <span class="quick-link-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                        <path d="M3 9a3 3 0 0 0 0 6v3a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-3a3 3 0 0 0 0-6V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1z"></path>
                        <path d="M13 5v2"></path>
                        <path d="M13 11v2"></path>
                        <path d="M13 17v2"></path>
                    </svg>
                </span>
                <span>
                    <strong>Crear sorteo</strong>
                    <small>Configura premios, precios y fechas</small>
                </span>
            </a>
            <a class="quick-link" href="/admin/prizes/create">
                <span class="quick-link-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                        <path d="M20 12v10H4V12"></path>
                        <path d="M2 7h20v5H2z"></path>
                        <path d="M12 22V7"></path>
                        <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
                        <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
                    </svg>
                </span>
                <span>
                    <strong>Nuevo premio</strong>
                    <small>Agrega productos al catalogo</small>
                </span>
            </a>
            <a class="quick-link" href="/admin/payment-methods">
                <span class="quick-link-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <path d="M2 10h20"></path>
                        <path d="M6 15h4"></path>
                    </svg>
                </span>
                <span>
                    <strong>Metodos de pago</strong>
                    <small>Yape, Plin y cuentas bancarias</small>
                </span>
            </a>
            <a class="quick-link" href="/" target="_blank" rel="noopener">
                <span class="quick-link-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M2 12h20"></path>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                </span>
                <span>
                    <strong>Ver sitio</strong>
                    <small>Abrir la pagina publica</small>
                </span>
            </a>
        </div>
    </section>

    <section class="card" aria-labelledby="roadmapTitle">
        <header class="card-header">
            <h2 id="roadmapTitle">Hoja de ruta</h2>
        </header>
        <ul class="roadmap">
            <li class="is-done">
                <span class="roadmap-dot" aria-hidden="true"></span>
                <span><strong>Acceso y usuarios</strong><small>Login, sesiones y CRUD de usuarios</small></span>
            </li>
            <li class="is-done">
                <span class="roadmap-dot" aria-hidden="true"></span>
                <span><strong>Catalogo y pagos</strong><small>Premios y metodos de pago configurables</small></span>
            </li>
            <li>
                <span class="roadmap-dot" aria-hidden="true"></span>
                <span><strong>Sorteo y transparencia</strong><small>Participaciones, commit-reveal y resultados</small></span>
            </li>
            <li>
                <span class="roadmap-dot" aria-hidden="true"></span>
                <span><strong>Web publica</strong><small>Home mixta, fichas y verificacion</small></span>
            </li>
        </ul>
    </section>
</div>
