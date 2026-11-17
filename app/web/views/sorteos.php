<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($pageTitle) ?></title>
    <meta name="description" content="<?= $e($pageDescription) ?>">
    <?php if ($robots !== ''): ?>
        <meta name="robots" content="<?= $e($robots) ?>">
    <?php endif; ?>
    <meta name="theme-color" content="#17243A">
    <link rel="icon" type="image/png" href="/icons/icon-192.png">
    <link rel="manifest" href="/manifest.json">
    <meta property="og:title" content="<?= $e($pageTitle) ?>">
    <meta property="og:description" content="<?= $e($pageDescription) ?>">
    <?php if ($metaImage !== ''): ?>
        <meta property="og:image" content="<?= $e($metaImage) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Space+Grotesk:wght@500;700&amp;display=swap">
    <link rel="stylesheet" href="/css/sorteos.css?v=20261116-12">
</head>
<body class="sorteo-body">
<a class="skip" href="#contenido">Saltar al contenido</a>

<div class="sorteo-page" data-sorteos>
    <div class="top"><?= $e($topBar) ?></div>

    <header class="wrap">
        <nav class="nav" aria-label="Navegación del sorteo">
            <a class="logo" href="/">Radio La Poderosa</a>
            <ul>
                <?php if ($primary !== null): ?>
                    <li><a href="#premios">Premios</a></li>
                    <li><a href="#como">Cómo participar</a></li>
                <?php endif; ?>
                <li><a href="#faq">Preguntas</a></li>
            </ul>
            <?php if ($primary !== null): ?>
                <a class="btn" href="#participar">Participar ahora</a>
            <?php else: ?>
                <a class="btn" href="/">Escuchar la radio</a>
            <?php endif; ?>
        </nav>

        <section class="hero" aria-label="Sorteos de Radio La Poderosa">
            <?php if ($primary !== null): ?>
                <div class="prize" data-carousel role="region" aria-roledescription="carrusel" aria-label="Sorteos disponibles">
                    <div class="prize-media" aria-hidden="true">
                        <?php foreach ($raffles as $index => $raffle): ?>
                            <div class="prize-slide<?= $index === 0 ? ' is-active' : '' ?><?= $raffle['hero_image'] === '' ? ' prize-slide--empty' : '' ?>">
                                <?php if ($raffle['hero_image'] !== ''): ?>
                                    <img src="<?= $e($raffle['hero_image']) ?>" alt="" width="1600" height="900"<?= $index === 0 ? ' fetchpriority="high"' : '' ?> decoding="async">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="prize-overlay" aria-hidden="true"></div>

                    <?php foreach ($raffles as $index => $raffle): ?>
                        <div class="prize-content<?= $index === 0 ? ' is-active' : '' ?>"
                             data-raffle-content
                             data-deadline="<?= $e($raffle['deadline_iso']) ?>"
                             data-deadline-label="<?= $e($raffle['deadline_label']) ?>"
                             data-price="<?= $e($raffle['price_formatted']) ?>"
                             data-max="<?= (int) $raffle['max_per_person'] ?>"
                             data-name="<?= $e($raffle['name']) ?>">
                            <span class="tag"><?= $e($raffle['tag']) ?></span>
                            <h1><?= $e($raffle['name']) ?></h1>
                            <p class="lead"><?= $e($raffle['lead']) ?></p>
                            <p class="v">
                                <span><?= $e($raffle['price_formatted']) ?> por número · <?= (int) $raffle['tickets'] ?> cupos · Máx. <?= (int) $raffle['max_per_person'] ?> por persona</span>
                                <?php if ($raffle['dates_line'] !== ''): ?>
                                    <span class="prize-dates"><?= $e($raffle['dates_line']) ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($totalRaffles > 1): ?>
                        <div class="prize-controls">
                            <button class="prize-arrow" type="button" data-carousel-prev aria-label="Sorteo anterior">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                    <path d="m15 18-6-6 6-6"></path>
                                </svg>
                            </button>
                            <div class="prize-dots" data-carousel-dots role="group" aria-label="Elegir sorteo"></div>
                            <button class="prize-arrow" type="button" data-carousel-next aria-label="Sorteo siguiente">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                    <path d="m9 18 6-6-6-6"></path>
                                </svg>
                            </button>
                            <button class="prize-toggle" type="button" data-carousel-toggle aria-label="Pausar carrusel" aria-pressed="false">
                                <svg class="icon-pause" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                                    <path d="M6 5h4v14H6zM14 5h4v14h-4z"></path>
                                </svg>
                                <svg class="icon-play" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                                    <path d="M8 5v14l11-7z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="cta">
                    <div class="cd-group" data-countdown-group<?= $primary['deadline_iso'] === '' ? ' hidden' : '' ?>>
                        <p class="cd-label" data-countdown-label><?= $e($primary['deadline_label'] !== '' ? $primary['deadline_label'] : 'Próximamente') ?></p>
                        <div class="cd" data-countdown data-deadline="<?= $e($primary['deadline_iso']) ?>" role="timer" aria-label="Tiempo para la apertura o cierre del sorteo">
                            <div><b data-unit="days">00</b><span>Días</span></div>
                            <div><b data-unit="hours">00</b><span>Horas</span></div>
                            <div><b data-unit="minutes">00</b><span>Min</span></div>
                            <div><b data-unit="seconds">00</b><span>Seg</span></div>
                        </div>
                    </div>
                    <a class="btn lg" href="#participar">Quiero participar</a>
                </div>
            <?php else: ?>
                <div class="prize prize--empty">
                    <span class="tag">Sorteos</span>
                    <h1>Muy pronto</h1>
                    <p class="lead">Estamos preparando el próximo sorteo de Radio La Poderosa. Mientras tanto, escucha la radio en vivo.</p>
                    <p class="v">Vuelve pronto para conocer premios, fechas y bases.</p>
                </div>
                <div class="cta">
                    <a class="btn lg" href="/">Escuchar la radio</a>
                </div>
            <?php endif; ?>
        </section>
    </header>

    <main id="contenido">
        <?php if ($primary !== null): ?>
            <section class="sec w" id="premios">
                <div class="wrap">
                    <div class="sh3">
                        <h2>Premios de nuestros sorteos</h2>
                        <p><?= count($prizeRaffles) > 1
                            ? 'Estos son los premios de los sorteos activos. Elige el tuyo y participa.'
                            : 'Estos son los premios del sorteo activo.' ?></p>
                    </div>
                    <div class="raffles-grid">
                        <?php foreach ($prizeRaffles as $raffle): ?>
                            <?php
                            $mainPrize = $raffle['prizes'][0] ?? null;
                            $extraPrizes = max(0, count($raffle['prizes']) - 1);
                            ?>
                            <article class="card raffle-card">
                                <div class="img" aria-hidden="true">
                                    <?php if ($raffle['hero_image'] !== ''): ?>
                                        <img src="<?= $e($raffle['hero_image']) ?>" alt="" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                            <path d="M20 12v9H4v-9"></path>
                                            <path d="M2 7h20v5H2z"></path>
                                            <path d="M12 22V7"></path>
                                            <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7Z"></path>
                                            <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7Z"></path>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="bd">
                                    <span class="rk"><?= $e($raffle['status_label']) ?></span>
                                    <h3><?= $e($raffle['name']) ?></h3>
                                    <p class="raffle-summary"><?= $e($raffle['lead']) ?></p>
                                    <?php if ($mainPrize !== null): ?>
                                        <p class="prize-value">Premio principal: <?= $e($mainPrize['name']) ?></p>
                                        <?php if ($extraPrizes > 0): ?>
                                            <?php $extraNames = array_column(array_slice($raffle['prizes'], 1), 'name'); ?>
                                            <p class="raffle-extra">También: <?= $e(implode(', ', $extraNames)) ?></p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="prize-value">Premios por anunciar.</p>
                                    <?php endif; ?>
                                    <p class="raffle-meta">
                                        <strong><?= $e($raffle['price_formatted']) ?></strong> por número
                                        <span aria-hidden="true">·</span>
                                        Hasta <strong><?= (int) $raffle['max_per_person'] ?></strong> por persona
                                    </p>
                                    <?php if ($raffle['dates_line'] !== ''): ?>
                                        <p class="raffle-dates"><?= $e($raffle['dates_line']) ?></p>
                                    <?php endif; ?>
                                    <div class="raffle-card-actions">
                                        <a class="btn raffle-card-cta" href="#participar">Participar</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="sec" id="como">
                <div class="wrap">
                    <div class="sh3">
                        <h2>Cómo participar</h2>
                        <p>Tres pasos y estarás dentro.</p>
                    </div>
                    <div class="grid">
                        <div class="st">
                            <div class="n">1</div>
                            <h3>Regístrate</h3>
                            <p>Déjanos tus datos y te avisaremos cuando abra la venta de números.</p>
                        </div>
                        <div class="st">
                            <div class="n">2</div>
                            <h3>Compra tus números</h3>
                            <p>Elige tus números y paga con Yape, Plin o transferencia BCP.</p>
                        </div>
                        <div class="st">
                            <div class="n">3</div>
                            <h3>Sigue el sorteo</h3>
                            <p>Publicaremos a los ganadores y la verificación del sorteo en vivo.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="sec w" id="participar">
                <div class="wrap fm">
                    <div>
                        <span class="tag">Registro</span>
                        <h2>Asegura tu lugar en el sorteo</h2>
                        <p class="lead">Déjanos tus datos y te avisaremos en cuanto abra la venta de números.</p>
                        <ul class="ck">
                            <li>Participación desde <strong data-registration-price><?= $e($primary['price_formatted']) ?></strong></li>
                            <li>Hasta <strong data-registration-max><?= (int) $primary['max_per_person'] ?></strong> números por persona</li>
                            <li>Ganadores anunciados públicamente</li>
                        </ul>
                    </div>
                    <form class="form" data-notify-form>
                        <label class="f">Nombre completo
                            <input type="text" name="name" autocomplete="name" placeholder="Ej. María Pérez" minlength="2" maxlength="160" required>
                        </label>
                        <label class="f">Correo electrónico
                            <input type="email" name="email" autocomplete="email" placeholder="tu@correo.com" maxlength="160" required>
                        </label>
                        <label class="f">Teléfono / WhatsApp
                            <input type="tel" name="phone" autocomplete="tel" inputmode="numeric" placeholder="Ej. 987654321" pattern="9[0-9]{8}" maxlength="9" title="Ingresa 9 dígitos empezando por 9." required>
                        </label>
                        <label class="chk">
                            <input type="checkbox" name="terms" required>
                            <span>Acepto las bases y condiciones del sorteo.</span>
                        </label>
                        <button class="btn" type="submit">Avísame cuando abra</button>
                        <p class="form-note" data-form-note role="status" hidden></p>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <section class="sec<?= $primary !== null ? '' : ' w' ?>" id="faq">
            <div class="wrap">
                <div class="sh3">
                    <h2>Preguntas frecuentes</h2>
                </div>
                <div class="fa">
                    <details>
                        <summary>¿Quién puede participar?</summary>
                        <p>Personas mayores de 18 años, con DNI y residencia en Perú. Una participación por persona.</p>
                    </details>
                    <details>
                        <summary>¿Cómo se elige al ganador?</summary>
                        <p>Con un sorteo verificable: publicamos el hash de la lista de participantes y la semilla del sorteo para que cualquiera pueda auditarlo.</p>
                    </details>
                    <details id="bases">
                        <summary>¿Dónde veo las bases completas?</summary>
                        <p>Publicaremos las bases y el aviso de privacidad antes de abrir la venta de números. Si tienes dudas, escríbenos a contacto@radiolapoderosa.com.</p>
                    </details>
                </div>
            </div>
        </section>
    </main>

    <footer class="ft">
        <div class="wrap">
            <div>© 2026 Radio La Poderosa. Todos los derechos reservados.</div>
            <div>
                <a href="#bases">Bases</a>
                <a href="/privacy">Privacidad</a>
                <a href="mailto:contacto@radiolapoderosa.com">Contacto</a>
            </div>
        </div>
    </footer>
</div>

<script src="/js/sorteos.js?v=20261116-2" defer></script>
<script src="/js/sorteos-carousel.js?v=20261116-2" defer></script>
</body>
</html>
