<?php
/** @var int $status */
/** @var string $heading */
/** @var string $message */
/** @var string $detail */
/** @var string $linkHref */
/** @var string $linkLabel */
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
    <title><?= e($heading) ?> &middot; Panel &middot; Radio La Poderosa</title>
    <link rel="icon" type="image/png" href="/icons/icon-192.png">
    <script src="<?= e(admin_asset('theme-init.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(admin_asset('admin.css')) ?>">
</head>
<body class="system-body">
<main class="system-card">
    <div class="system-brand">
        <span class="brand-mark" aria-hidden="true">LP</span>
        <span>Radio La Poderosa</span>
    </div>

    <span class="system-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" focusable="false">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M12 8v4"></path>
            <path d="M12 16h.01"></path>
        </svg>
    </span>

    <h1><?= e($heading) ?></h1>
    <p><?= e($message) ?></p>

    <?php if ($detail !== ''): ?>
        <p class="system-detail"><?= e($detail) ?></p>
    <?php endif; ?>

    <div class="system-actions">
        <a class="btn btn-primary" href="<?= e($linkHref) ?>"><?= e($linkLabel) ?></a>
        <?php if ($status >= 500): ?>
            <button type="button" class="btn btn-ghost" data-reload>Reintentar</button>
        <?php endif; ?>
    </div>
</main>
<script src="<?= e(admin_asset('admin.js')) ?>" defer></script>
</body>
</html>
