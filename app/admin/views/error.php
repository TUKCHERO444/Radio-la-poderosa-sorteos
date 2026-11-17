<?php
/** @var int $status */
/** @var string $message */
?>
<section class="empty">
    <span class="empty-icon empty-icon-danger" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M12 8v4"></path>
            <path d="M12 16h.01"></path>
        </svg>
    </span>
    <h1>Error <?= (int) $status ?></h1>
    <p><?= e($message) ?></p>
    <div class="empty-actions">
        <a class="btn btn-primary" href="/admin">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            Volver al dashboard
        </a>
    </div>
</section>
