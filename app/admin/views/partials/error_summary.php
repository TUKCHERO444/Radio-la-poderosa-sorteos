<?php
/**
 * Resumen de errores de formulario con enlaces a cada campo.
 *
 * @var array<string, string> $errors
 * @var array<string, string> $errorTargets Mapa campo => id del control.
 */
$errorTargets = $errorTargets ?? [];
$errorItems = [];

foreach ($errors as $key => $message) {
    if ($key === 'general' || $message === '') {
        continue;
    }

    $errorItems[$errorTargets[$key] ?? $key] = $message;
}
?>
<?php if ($errorItems !== []): ?>
    <div class="alert alert-error" id="formErrors" role="alert" tabindex="-1">
        <div class="alert-content">
            <div>
                <strong>Revisa los siguientes campos:</strong>
                <ul class="alert-list">
                    <?php foreach ($errorItems as $target => $message): ?>
                        <li><a class="link" href="#<?= e($target) ?>"><?= e($message) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>
