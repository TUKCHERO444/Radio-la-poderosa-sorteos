<?php
/** @var string $mode */
/** @var array|null $raffle */
/** @var array|null $rules */
/** @var array $old */
/** @var array $errors */
/** @var string $action */
/** @var bool $read_only */
/** @var array $prizes */
/** @var array $methods */
$isEdit = $mode === 'edit';
$status = (string) ($raffle['status'] ?? 'draft');
$statusLabels = [
    'draft' => 'Borrador',
    'published' => 'Publicado',
    'paused' => 'Pausado',
    'sales_closed' => 'Ventas cerradas',
    'drawing' => 'Sorteando',
    'drawn' => 'Sorteado',
    'results_published' => 'Resultados publicados',
    'closed' => 'Cerrado',
    'cancelled' => 'Cancelado',
];
$statusBadges = [
    'draft' => 'badge-slate',
    'published' => 'badge-success',
    'paused' => 'badge-warning',
    'sales_closed' => 'badge-soft',
    'drawing' => 'badge-primary',
    'drawn' => 'badge-primary',
    'results_published' => 'badge-primary',
    'closed' => 'badge-slate',
    'cancelled' => 'badge-danger',
];
$typeLabels = ['wallet' => 'Billetera digital', 'bank' => 'Cuenta bancaria'];
$providerLabels = ['yape' => 'Yape', 'plin' => 'Plin', 'bcp' => 'BCP', 'other' => 'Otro'];
$err = static fn(string $key): string => isset($errors[$key]) ? ' has-error' : '';
$invalid = static fn(string $key): string => isset($errors[$key]) ? 'true' : 'false';
$primaryPrizeId = (int) ($old['prize_ids'][0] ?? 0);
$additionalPrizeIds = array_slice(array_map('intval', $old['prize_ids'] ?? []), 1);
$selectedMethodIds = array_map('intval', $old['method_ids'] ?? []);
$canPublish = $isEdit && $status === 'draft';
$canClose = $isEdit && in_array($status, ['published', 'paused'], true);
$canCancel = $isEdit && in_array($status, ['draft', 'published', 'paused'], true);
$canDelete = $isEdit && $status === 'draft';
?>
<section class="page-head">
    <div class="page-head-text">
        <h1><?= $isEdit ? ($read_only ? 'Detalle del sorteo' : 'Editar sorteo') : 'Nuevo sorteo' ?></h1>
        <p><?= $read_only ? 'Este sorteo ya no está en borrador. Puedes consultar sus datos y ejecutar acciones de estado.' : 'Completa los datos, premios, metodos de pago y bases antes de publicar.' ?></p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="/admin/raffles">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            Volver a sorteos
        </a>
        <?php if ($canPublish): ?>
            <a class="btn btn-primary" href="/admin/raffles/<?= (int) $raffle['id'] ?>/publish">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="m5 12 4 4L19 6"></path>
                </svg>
                Publicar
            </a>
        <?php endif; ?>
    </div>
</section>

<?php if ($read_only): ?>
    <div class="alert alert-warning" role="status">
        <span class="alert-content">Este sorteo está en estado <strong><?= e($statusLabels[$status] ?? $status) ?></strong>. Los datos están en modo lectura; usa las acciones de estado de la columna lateral.</span>
    </div>
<?php endif; ?>

<?php if (isset($errors['general'])): ?>
    <div class="alert alert-error" role="alert">
        <span class="alert-content"><?= e($errors['general']) ?></span>
    </div>
<?php endif; ?>

<?php
$errorTargets = [
    'prize_ids' => 'primary_prize_id',
    'method_ids' => 'method_ids',
];
require APP_ROOT . '/admin/views/partials/error_summary.php';
?>

<div class="form-layout">
    <div class="form-layout-main">
        <form method="post" action="<?= e($action) ?>" class="form" enctype="multipart/form-data" novalidate data-validate-form>
            <?= admin_csrf_field() ?>
            <fieldset class="form-fieldset"<?= $read_only ? ' disabled' : '' ?>>
                <section class="form-section">
                    <header class="form-section-head">
                        <div>
                            <h2 class="form-section-title">Datos del sorteo</h2>
                            <p class="form-section-desc">Nombre, descripcion y URL publica del sorteo.</p>
                        </div>
                    </header>

                    <div class="form-grid">
                        <div class="field<?= $err('name') ?>">
                            <label for="name">Nombre del sorteo <span class="required">*</span></label>
                            <input id="name" name="name" type="text" class="input" value="<?= e($old['name']) ?>" maxlength="160" required
                                   data-slug-source="name" placeholder="Ej. Gran sorteo de lanzamiento"
                                   aria-invalid="<?= $invalid('name') ?>" aria-describedby="name-feedback"<?= field_attrs('display_name') ?>>
                            <small id="name-feedback" class="<?= isset($errors['name']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['name']) ? e($errors['name']) : 'Se muestra en el listado y en la pagina publica.' ?>
                            </small>
                        </div>

                        <div class="field<?= $err('slug') ?>">
                            <label for="slug">Slug / URL <span class="required">*</span></label>
                            <input id="slug" name="slug" type="text" class="input" value="<?= e($old['slug']) ?>" maxlength="120" required
                                   data-slug-target="slug" placeholder="gran-sorteo-de-lanzamiento"
                                   aria-invalid="<?= $invalid('slug') ?>" aria-describedby="slug-feedback"<?= field_attrs('slug') ?>>
                            <small id="slug-feedback" class="<?= isset($errors['slug']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['slug']) ? e($errors['slug']) : 'Se genera desde el nombre si lo dejas vacio. Debe ser unico.' ?>
                            </small>
                        </div>

                        <div class="field field-span-2<?= $err('summary') ?>">
                            <label for="summary">Resumen corto</label>
                            <input id="summary" name="summary" type="text" class="input" value="<?= e($old['summary']) ?>" maxlength="255"
                                   placeholder="Una linea que resuma el premio y la fecha."
                                   aria-invalid="<?= $invalid('summary') ?>" aria-describedby="summary-feedback">
                            <small id="summary-feedback" class="<?= isset($errors['summary']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['summary']) ? e($errors['summary']) : 'Opcional. Maximo 255 caracteres.' ?>
                            </small>
                        </div>

                        <div class="field field-span-2<?= $err('description') ?>">
                            <label for="description">Descripcion</label>
                            <textarea id="description" name="description" class="textarea" rows="4" maxlength="20000"
                                      placeholder="Describe el sorteo, condiciones generales o informacion adicional."
                                      aria-invalid="<?= $invalid('description') ?>" aria-describedby="description-feedback"><?= e($old['description']) ?></textarea>
                            <small id="description-feedback" class="<?= isset($errors['description']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['description']) ? e($errors['description']) : 'Opcional. La informacion legal va en las bases.' ?>
                            </small>
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <header class="form-section-head">
                        <div>
                            <h2 class="form-section-title">Premios</h2>
                            <p class="form-section-desc">Elige el premio principal y, si corresponde, premios adicionales.</p>
                        </div>
                    </header>

                    <?php if ($prizes === []): ?>
                        <div class="alert alert-warning" role="status">
                            <span class="alert-content">No hay premios activos. <a class="link" href="/admin/prizes/create">Crea un premio</a> antes de publicar el sorteo.</span>
                        </div>
                    <?php else: ?>
                        <div class="field<?= $err('prize_ids') ?>">
                            <label for="primary_prize_id">Premio principal <span class="required">*</span></label>
                            <select id="primary_prize_id" name="primary_prize_id" class="select" data-primary-prize required
                                    data-required-message="Selecciona el premio principal."
                                    aria-invalid="<?= $invalid('prize_ids') ?>" aria-describedby="prize-feedback" data-feedback="prize-feedback">
                                <option value="">Selecciona un premio</option>
                                <?php foreach ($prizes as $prize): ?>
                                    <option value="<?= (int) $prize['id'] ?>"<?= $primaryPrizeId === (int) $prize['id'] ? ' selected' : '' ?>>
                                        <?= e($prize['name']) ?> · <?= $prize['estimated_value_cents'] !== null ? e(\App\Shared\Money::format((int) $prize['estimated_value_cents'])) : 'Sin valor' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small id="prize-feedback" class="<?= isset($errors['prize_ids']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['prize_ids']) ? e($errors['prize_ids']) : 'Ocupa la posicion principal del sorteo.' ?>
                            </small>
                        </div>

                        <div class="field mt-4">
                            <span class="field-label">Premios adicionales</span>
                            <div class="assignment-list">
                                <?php foreach ($prizes as $prize): ?>
                                    <label class="assignment-item">
                                        <input type="checkbox" name="additional_prize_ids[]" value="<?= (int) $prize['id'] ?>"
                                               data-additional-prize<?= in_array((int) $prize['id'], $additionalPrizeIds, true) ? ' checked' : '' ?>>
                                        <span class="assignment-thumb">
                                            <?php if (!empty($prize['image_path'])): ?>
                                                <img src="<?= e($prize['image_path']) ?>" alt="">
                                            <?php else: ?>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                                    <path d="M20 12v10H4V12"></path>
                                                    <path d="M2 7h20v5H2z"></path>
                                                    <path d="M12 22V7"></path>
                                                    <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
                                                    <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
                                                </svg>
                                            <?php endif; ?>
                                        </span>
                                        <span class="assignment-info">
                                            <strong><?= e($prize['name']) ?></strong>
                                            <small><?= e($prize['prize_type'] === 'product' ? 'Producto' : $prize['prize_type']) ?> · <?= $prize['estimated_value_cents'] !== null ? e(\App\Shared\Money::format((int) $prize['estimated_value_cents'])) : 'Sin valor' ?></small>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <small class="field-hint">Opcional. El premio principal no se repite en esta lista.</small>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="form-section">
                    <header class="form-section-head">
                        <div>
                            <h2 class="form-section-title">Ventas y fechas</h2>
                            <p class="form-section-desc">Precio, cupos, limite por persona y calendario del sorteo.</p>
                        </div>
                    </header>

                    <div class="form-grid-3">
                        <div class="field<?= $err('price') ?>">
                            <label for="price">Precio por boleto <span class="required">*</span></label>
                            <div class="input-money">
                                <span class="input-prefix" aria-hidden="true">S/</span>
                                <input id="price" name="price" type="number" class="input" value="<?= e($old['price']) ?>" min="0.01" step="0.01" required
                                       inputmode="decimal" placeholder="0.00"
                                       aria-invalid="<?= $invalid('price') ?>" aria-describedby="price-feedback"<?= field_attrs('decimal') ?>>
                            </div>
                            <small id="price-feedback" class="<?= isset($errors['price']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['price']) ? e($errors['price']) : 'Moneda: Soles (PEN).' ?>
                            </small>
                        </div>

                        <div class="field<?= $err('total_tickets') ?>">
                            <label for="total_tickets">Cupos <span class="required">*</span></label>
                            <input id="total_tickets" name="total_tickets" type="number" class="input" value="<?= e($old['total_tickets']) ?>" min="1" step="1" required
                                   inputmode="numeric" placeholder="Ej. 100"
                                   aria-invalid="<?= $invalid('total_tickets') ?>" aria-describedby="total-tickets-feedback"<?= field_attrs('integer') ?>>
                            <small id="total-tickets-feedback" class="<?= isset($errors['total_tickets']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['total_tickets']) ? e($errors['total_tickets']) : 'Cantidad total de boletos disponibles.' ?>
                            </small>
                        </div>

                        <div class="field<?= $err('max_tickets_per_participant') ?>">
                            <label for="max_tickets_per_participant">Maximo por persona <span class="required">*</span></label>
                            <input id="max_tickets_per_participant" name="max_tickets_per_participant" type="number" class="input" value="<?= e($old['max_tickets_per_participant']) ?>" min="1" step="1" required
                                   inputmode="numeric" placeholder="Ej. 5"
                                   aria-invalid="<?= $invalid('max_tickets_per_participant') ?>" aria-describedby="max-tickets-feedback" data-feedback="max-tickets-feedback"<?= field_attrs('integer') ?>>
                            <small id="max-tickets-feedback" class="<?= isset($errors['max_tickets_per_participant']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['max_tickets_per_participant']) ? e($errors['max_tickets_per_participant']) : 'Se valida por DNI del participante.' ?>
                            </small>
                        </div>

                        <div class="field<?= $err('min_age') ?>">
                            <label for="min_age">Edad minima <span class="required">*</span></label>
                            <input id="min_age" name="min_age" type="number" class="input" value="<?= e($old['min_age']) ?>" min="18" max="100" step="1" required
                                   inputmode="numeric"
                                   aria-invalid="<?= $invalid('min_age') ?>" aria-describedby="min-age-feedback"<?= field_attrs('integer') ?>>
                            <small id="min-age-feedback" class="<?= isset($errors['min_age']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['min_age']) ? e($errors['min_age']) : 'Minimo 18 anos.' ?>
                            </small>
                        </div>

                        <div class="field<?= $err('sales_start') ?>">
                            <label for="sales_start">Inicio de ventas <span class="required">*</span></label>
                            <input id="sales_start" name="sales_start" type="datetime-local" class="input" value="<?= e($old['sales_start']) ?>" required
                                   aria-invalid="<?= $invalid('sales_start') ?>" aria-describedby="sales-start-feedback">
                            <small id="sales-start-feedback" class="<?= isset($errors['sales_start']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['sales_start']) ? e($errors['sales_start']) : 'Fecha y hora en America/Lima.' ?>
                            </small>
                        </div>

                        <div class="field<?= $err('sales_end') ?>">
                            <label for="sales_end">Cierre de ventas <span class="required">*</span></label>
                            <input id="sales_end" name="sales_end" type="datetime-local" class="input" value="<?= e($old['sales_end']) ?>" required
                                   aria-invalid="<?= $invalid('sales_end') ?>" aria-describedby="sales-end-feedback">
                            <small id="sales-end-feedback" class="<?= isset($errors['sales_end']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['sales_end']) ? e($errors['sales_end']) : 'Debe ser posterior al inicio.' ?>
                            </small>
                        </div>

                        <div class="field<?= $err('draw_at') ?>">
                            <label for="draw_at">Fecha del sorteo <span class="required">*</span></label>
                            <input id="draw_at" name="draw_at" type="datetime-local" class="input" value="<?= e($old['draw_at']) ?>" required
                                   aria-invalid="<?= $invalid('draw_at') ?>" aria-describedby="draw-at-feedback">
                            <small id="draw-at-feedback" class="<?= isset($errors['draw_at']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['draw_at']) ? e($errors['draw_at']) : 'Debe ser posterior o igual al cierre.' ?>
                            </small>
                        </div>

                        <div class="field<?= $err('claim_deadline') ?>">
                            <label for="claim_deadline">Limite de reclamo <span class="required">*</span></label>
                            <input id="claim_deadline" name="claim_deadline" type="datetime-local" class="input" value="<?= e($old['claim_deadline']) ?>" required
                                   aria-invalid="<?= $invalid('claim_deadline') ?>" aria-describedby="claim-feedback" data-feedback="claim-feedback">
                            <small id="claim-feedback" class="<?= isset($errors['claim_deadline']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['claim_deadline']) ? e($errors['claim_deadline']) : 'Fecha maxima para reclamar el premio.' ?>
                            </small>
                        </div>
                    </div>

                    <div class="form-grid mt-4">
                        <div class="field">
                            <label class="check">
                                <input type="checkbox" name="is_featured" value="1"<?= !empty($old['is_featured']) ? ' checked' : '' ?>>
                                <span>Destacar en la home</span>
                            </label>
                            <small class="field-hint">Maximo 3 sorteos destacados en la pagina principal.</small>
                        </div>
                        <div class="field<?= $err('featured_order') ?>">
                            <label for="featured_order">Orden destacado</label>
                            <input id="featured_order" name="featured_order" type="number" class="input" value="<?= e($old['featured_order']) ?>" min="1" max="3" step="1"
                                   placeholder="1, 2 o 3"
                                   aria-invalid="<?= $invalid('featured_order') ?>" aria-describedby="featured-order-feedback"<?= field_attrs('integer') ?>>
                            <small id="featured-order-feedback" class="<?= isset($errors['featured_order']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['featured_order']) ? e($errors['featured_order']) : 'Solo si el sorteo esta destacado.' ?>
                            </small>
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <header class="form-section-head">
                        <div>
                            <h2 class="form-section-title">Metodos de pago</h2>
                            <p class="form-section-desc">Selecciona los metodos que podra usar el cliente en este sorteo.</p>
                        </div>
                    </header>

                    <?php if ($methods === []): ?>
                        <div class="alert alert-warning" role="status">
                            <span class="alert-content">No hay metodos de pago activos. <a class="link" href="/admin/payment-methods/create">Crea un metodo</a> antes de publicar.</span>
                        </div>
                    <?php else: ?>
                        <div class="field<?= $err('method_ids') ?>" id="method_ids"
                             data-required-group="method_ids[]"
                             data-feedback="method-feedback"
                             data-message="Selecciona al menos un método de pago activo.">
                            <div class="assignment-list">
                                <?php foreach ($methods as $method): ?>
                                    <label class="assignment-item">
                                        <input type="checkbox" name="method_ids[]" value="<?= (int) $method['id'] ?>"<?= in_array((int) $method['id'], $selectedMethodIds, true) ? ' checked' : '' ?>>
                                        <span class="assignment-thumb">
                                            <?php if (!empty($method['qr_image_path'])): ?>
                                                <img src="<?= e($method['qr_image_path']) ?>" alt="">
                                            <?php else: ?>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                                    <path d="M2 10h20"></path>
                                                    <path d="M6 15h4"></path>
                                                </svg>
                                            <?php endif; ?>
                                        </span>
                                        <span class="assignment-info">
                                            <strong><?= e($method['name']) ?> · <?= e($providerLabels[$method['provider']] ?? $method['provider']) ?></strong>
                                            <small><?= e($typeLabels[$method['type']] ?? $method['type']) ?> · <?= e($method['holder_name']) ?></small>
                                            <small>
                                                <?php if ($method['type'] === 'wallet'): ?>
                                                    Tel. <?= e($method['phone']) ?>
                                                <?php else: ?>
                                                    <?= e($method['bank_name']) ?> · Cuenta <?= e($method['account_number']) ?><?= !empty($method['cci']) ? ' · CCI ' . e($method['cci']) : '' ?>
                                                <?php endif; ?>
                                            </small>
                                            <small>Contacto sobrepagos: <?= e($method['contact_phone']) ?></small>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <small id="method-feedback" class="<?= isset($errors['method_ids']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['method_ids']) ? e($errors['method_ids']) : 'Selecciona al menos un metodo activo.' ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="form-section">
                    <header class="form-section-head">
                        <div>
                            <h2 class="form-section-title">Bases y condiciones</h2>
                            <p class="form-section-desc">Texto que aceptara el participante y que se publicara con el sorteo.</p>
                        </div>
                    </header>

                    <?php if ($rules !== null): ?>
                        <div class="alert alert-info alert-inline" role="status">
                            <span class="alert-content">Version <?= (int) $rules['version'] ?> · Estado <?= ($rules['status'] ?? '') === 'published' ? 'publicada' : 'borrador' ?> · Hash <?= e(substr((string) $rules['content_hash'], 0, 16)) ?>…</span>
                        </div>
                    <?php endif; ?>

                    <div class="field<?= $err('rules_content') ?>">
                        <label for="rules_content">Bases y condiciones <span class="required">*</span></label>
                        <textarea id="rules_content" name="rules_content" class="textarea" rows="10" maxlength="50000" required minlength="20"
                                  data-required-message="Las bases y condiciones son obligatorias."
                                  data-message="Las bases deben tener al menos 20 caracteres."
                                  placeholder="Describe el premio, fechas, requisitos, forma de pago, sorteo, entrega, reclamos y privacidad."
                                  aria-invalid="<?= $invalid('rules_content') ?>" aria-describedby="rules-feedback" data-feedback="rules-feedback"><?= e($old['rules_content']) ?></textarea>
                        <small id="rules-feedback" class="<?= isset($errors['rules_content']) ? 'field-error' : 'field-hint' ?>">
                            <?= isset($errors['rules_content']) ? e($errors['rules_content']) : 'Texto plano. Se mostrara con saltos de linea en la pagina publica.' ?>
                        </small>
                    </div>
                </section>

                <?php if (!$read_only): ?>
                    <section class="form-section">
                        <header class="form-section-head">
                            <div>
                                <h2 class="form-section-title">Portada del sorteo</h2>
                                <p class="form-section-desc">Opcional. JPG, PNG o WebP, maximo 10 MB. Recomendado: 1600 x 900 px.</p>
                            </div>
                        </header>

                        <div class="media-field">
                            <div class="media-preview" id="raffleCoverPreview">
                                <?php if (!empty($raffle['cover_image_path'])): ?>
                                    <img src="<?= e($raffle['cover_image_path']) ?>" alt="">
                                <?php else: ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <path d="m21 15-5-5L5 21"></path>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="media-input">
                                <label class="label-main" for="cover_image">Subir portada</label>
                                <input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp"
                                       data-image-input data-preview-target="raffleCoverPreview"
                                       aria-describedby="cover-feedback">
                                <small id="cover-feedback" class="field-hint">Se mostrara en el listado y en la ficha publica.</small>
                                <?php if (isset($errors['cover_image'])): ?>
                                    <small class="field-error"><?= e($errors['cover_image']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if (!$read_only): ?>
                    <div class="form-actions">
                        <a class="btn btn-ghost" href="/admin/raffles">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <path d="M17 21v-8H7v8"></path>
                                <path d="M7 3v5h8"></path>
                            </svg>
                            Guardar borrador
                        </button>
                    </div>
                <?php endif; ?>
            </fieldset>
        </form>

        <?php if ($read_only): ?>
            <form method="post" action="/admin/raffles/<?= (int) $raffle['id'] ?>/cover" class="form" enctype="multipart/form-data" novalidate data-validate-form>
                <?= admin_csrf_field() ?>
                <section class="form-section">
                    <header class="form-section-head">
                        <div>
                            <h2 class="form-section-title">Portada del sorteo</h2>
                            <p class="form-section-desc">Se puede actualizar aunque el sorteo esté publicado. JPG, PNG o WebP, maximo 10 MB. Recomendado: 1600 x 900 px.</p>
                        </div>
                    </header>

                    <div class="media-field">
                        <div class="media-preview" id="raffleCoverPreviewAdmin">
                            <?php if (!empty($raffle['cover_image_path'])): ?>
                                <img src="<?= e($raffle['cover_image_path']) ?>" alt="">
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                    <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <path d="m21 15-5-5L5 21"></path>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="media-input">
                            <label class="label-main" for="cover_image_admin">Seleccionar portada</label>
                            <input id="cover_image_admin" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp" required
                                   data-required-message="Selecciona una imagen de portada."
                                   data-image-input data-preview-target="raffleCoverPreviewAdmin"
                                   aria-describedby="cover-admin-feedback">
                            <small id="cover-admin-feedback" class="field-hint">Se mostrara en el listado y en la ficha publica.</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <path d="M17 21v-8H7v8"></path>
                                <path d="M7 3v5h8"></path>
                            </svg>
                            Guardar portada
                        </button>
                    </div>
                </section>
            </form>
        <?php endif; ?>
    </div>

    <aside class="form-layout-side">
        <section class="card">
            <header class="card-header">
                <h2>Estado</h2>
                <span class="badge <?= e($statusBadges[$status] ?? 'badge-slate') ?>"><?= e($statusLabels[$status] ?? $status) ?></span>
            </header>
            <div class="stack-sm">
                <?php if ($raffle !== null): ?>
                    <div class="status-row"><span>Codigo publico</span><strong><?= e($raffle['public_code']) ?></strong></div>
                    <div class="status-row"><span>Creado</span><strong><?= e(\App\Shared\Dates::display($raffle['created_at'])) ?></strong></div>
                    <div class="status-row"><span>Actualizado</span><strong><?= e(\App\Shared\Dates::display($raffle['updated_at'])) ?></strong></div>
                    <?php if (!empty($raffle['legal_confirmed_at'])): ?>
                        <div class="status-row"><span>Legal confirmado</span><strong><?= e(\App\Shared\Dates::display($raffle['legal_confirmed_at'])) ?></strong></div>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="divider"></div>
                <div class="status-row"><span>Precio</span><strong><?= e($old['price'] !== '' ? 'S/ ' . $old['price'] : '—') ?></strong></div>
                <div class="status-row"><span>Cupos</span><strong><?= e($old['total_tickets'] !== '' ? $old['total_tickets'] : '—') ?></strong></div>
                <div class="status-row"><span>Premios</span><strong><?= count($old['prize_ids']) ?></strong></div>
                <div class="status-row"><span>Metodos</span><strong><?= count($old['method_ids']) ?></strong></div>
                <div class="status-row"><span>Sorteo</span><strong><?= e(\App\Shared\Dates::display($raffle['draw_at'] ?? null)) ?></strong></div>
            </div>

            <?php if ($isEdit): ?>
                <div class="divider my-4"></div>
                <div class="stack-sm">
                    <?php if ($canPublish): ?>
                        <a class="btn btn-primary btn-block" href="/admin/raffles/<?= (int) $raffle['id'] ?>/publish">Publicar sorteo</a>
                    <?php endif; ?>
                    <?php if ($canClose): ?>
                        <form method="post" action="/admin/raffles/<?= (int) $raffle['id'] ?>/close" data-confirm="¿Cerrar las ventas de este sorteo? Los clientes ya no podran comprar." data-confirm-title="Cerrar ventas" data-confirm-label="Cerrar ventas">
                            <?= admin_csrf_field() ?>
                            <button type="submit" class="btn btn-ghost btn-block">Cerrar ventas</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($canCancel): ?>
                        <details class="danger-zone">
                            <summary>Cancelar sorteo</summary>
                            <form method="post" action="/admin/raffles/<?= (int) $raffle['id'] ?>/cancel" data-confirm="¿Cancelar este sorteo? Se registrara el motivo y no se podra publicar." data-confirm-title="Cancelar sorteo" data-confirm-label="Cancelar sorteo">
                                <?= admin_csrf_field() ?>
                                <div class="field mt-4">
                                    <label for="cancel_reason">Motivo de la cancelacion <span class="required">*</span></label>
                                    <textarea id="cancel_reason" name="reason" class="textarea" rows="2" maxlength="255" required placeholder="Ej. Error en las fechas publicadas"></textarea>
                                    <small class="field-hint">Quedara registrado en la auditoria del sorteo.</small>
                                </div>
                                <button type="submit" class="btn btn-danger-soft btn-block mt-4">Cancelar sorteo</button>
                            </form>
                        </details>
                    <?php endif; ?>
                    <?php if ($canDelete): ?>
                        <form method="post" action="/admin/raffles/<?= (int) $raffle['id'] ?>/delete" data-confirm="¿Eliminar este sorteo en borrador? Esta accion no se puede deshacer." data-confirm-title="Eliminar sorteo" data-confirm-label="Eliminar">
                            <?= admin_csrf_field() ?>
                            <button type="submit" class="btn btn-danger-soft btn-block">Eliminar borrador</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <header class="card-header">
                <h2>Antes de publicar</h2>
            </header>
            <div class="stack-sm">
                <p class="help-text">El sorteo debe tener premio principal, metodos de pago, fechas validas y bases. La publicacion exige confirmar el checklist legal.</p>
                <?php if ($canPublish): ?>
                    <a class="btn btn-ghost btn-block" href="/admin/raffles/<?= (int) $raffle['id'] ?>/publish">Revisar checklist legal</a>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>
