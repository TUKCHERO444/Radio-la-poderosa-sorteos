<?php
/** @var string $mode */
/** @var array|null $prize */
/** @var array $old */
/** @var array $errors */
/** @var string $action */
$isEdit = $mode === 'edit';
$err = static fn(string $key): string => isset($errors[$key]) ? ' has-error' : '';
$invalid = static fn(string $key): string => isset($errors[$key]) ? 'true' : 'false';
$typeOptions = [
    'product' => 'Producto',
    'service' => 'Servicio',
    'cash' => 'Efectivo',
    'other' => 'Otro',
];
?>
<section class="page-head">
    <div class="page-head-text">
        <h1><?= $isEdit ? 'Editar premio' : 'Nuevo premio' ?></h1>
        <p><?= $isEdit ? 'Actualiza la informacion del premio del catalogo.' : 'Registra un producto o premio para asignarlo a un sorteo.' ?></p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="/admin/prizes">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            Volver al catalogo
        </a>
    </div>
</section>

<?php if (isset($errors['general'])): ?>
    <div class="alert alert-error" role="alert">
        <span class="alert-content"><?= e($errors['general']) ?></span>
    </div>
<?php endif; ?>

<?php require APP_ROOT . '/admin/views/partials/error_summary.php'; ?>

<form method="post" action="<?= e($action) ?>" class="form" enctype="multipart/form-data" novalidate data-validate-form>
    <?= admin_csrf_field() ?>

    <div class="form-layout">
        <div class="form-layout-main">
            <section class="form-section">
                <header class="form-section-head">
                    <div>
                        <h2 class="form-section-title">Informacion del premio</h2>
                        <p class="form-section-desc">Datos visibles en el catalogo y en la pagina publica del sorteo.</p>
                    </div>
                </header>

                <div class="form-grid">
                    <div class="field<?= $err('name') ?>">
                        <label for="name">Nombre del premio <span class="required">*</span></label>
                        <input id="name" name="name" type="text" class="input"
                               value="<?= e($old['name']) ?>" maxlength="160" required
                               placeholder="Ej. Combo desayuno"
                               aria-invalid="<?= $invalid('name') ?>" aria-describedby="name-feedback"<?= field_attrs('display_name') ?>>
                        <small id="name-feedback" class="<?= isset($errors['name']) ? 'field-error' : 'field-hint' ?>">
                            <?= isset($errors['name']) ? e($errors['name']) : 'Se muestra en el listado y en la pagina del sorteo.' ?>
                        </small>
                    </div>

                    <div class="field<?= $err('prize_type') ?>">
                        <label for="prize_type">Tipo de premio <span class="required">*</span></label>
                        <select id="prize_type" name="prize_type" class="select" required
                                aria-invalid="<?= $invalid('prize_type') ?>" aria-describedby="prize-type-feedback">
                            <?php foreach ($typeOptions as $value => $label): ?>
                                <option value="<?= e($value) ?>"<?= $old['prize_type'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small id="prize-type-feedback" class="<?= isset($errors['prize_type']) ? 'field-error' : 'field-hint' ?>">
                            <?= isset($errors['prize_type']) ? e($errors['prize_type']) : 'Para este MVP se usara principalmente Producto.' ?>
                        </small>
                    </div>

                    <div class="field<?= $err('estimated_value') ?>">
                        <label for="estimated_value">Valor estimado</label>
                        <div class="input-money">
                            <span class="input-prefix" aria-hidden="true">S/</span>
                            <input id="estimated_value" name="estimated_value" type="number" class="input"
                                   value="<?= e($old['estimated_value']) ?>" min="0" step="0.01"
                                   placeholder="0.00"
                                   aria-invalid="<?= $invalid('estimated_value') ?>" aria-describedby="estimated-value-feedback"<?= field_attrs('decimal') ?>>
                        </div>
                        <small id="estimated-value-feedback" class="<?= isset($errors['estimated_value']) ? 'field-error' : 'field-hint' ?>">
                            <?= isset($errors['estimated_value']) ? e($errors['estimated_value']) : 'Valor referencial para la ficha del premio.' ?>
                        </small>
                    </div>

                    <div class="field<?= $err('delivery_method') ?>">
                        <label for="delivery_method">Metodo de entrega</label>
                        <input id="delivery_method" name="delivery_method" type="text" class="input"
                               value="<?= e($old['delivery_method']) ?>" maxlength="60"
                               placeholder="Ej. Presencial, delivery"
                               aria-invalid="<?= $invalid('delivery_method') ?>" aria-describedby="delivery-feedback" data-feedback="delivery-feedback"<?= field_attrs('display_name') ?>>
                        <small id="delivery-feedback" class="<?= isset($errors['delivery_method']) ? 'field-error' : 'field-hint' ?>">
                            <?= isset($errors['delivery_method']) ? e($errors['delivery_method']) : 'Opcional. Se muestra en las bases y en la ficha.' ?>
                        </small>
                    </div>
                </div>

                <div class="field<?= $err('description') ?> mt-4">
                    <label for="description">Descripcion</label>
                    <textarea id="description" name="description" class="textarea" rows="5" maxlength="5000"
                              placeholder="Describe el premio, contenido, condiciones o detalles relevantes."
                              aria-invalid="<?= $invalid('description') ?>" aria-describedby="description-feedback"><?= e($old['description']) ?></textarea>
                    <small id="description-feedback" class="<?= isset($errors['description']) ? 'field-error' : 'field-hint' ?>">
                        <?= isset($errors['description']) ? e($errors['description']) : 'Opcional. Maximo 5000 caracteres.' ?>
                    </small>
                </div>
            </section>

            <section class="form-section">
                <header class="form-section-head">
                    <div>
                        <h2 class="form-section-title">Imagen del premio</h2>
                        <p class="form-section-desc">JPG, PNG o WebP. Tamano maximo 10 MB. Recomendado: 1200 x 800 px o superior.</p>
                    </div>
                </header>

                <div class="media-field" data-image-field>
                    <div class="media-preview" id="prizeImagePreview">
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
                    </div>
                    <div class="media-input">
                        <label class="label-main" for="image">Subir imagen</label>
                        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"
                               data-image-input data-preview-target="prizeImagePreview"
                               aria-describedby="image-feedback">
                        <small id="image-feedback" class="field-hint">La vista previa se actualiza al seleccionar una imagen.</small>
                        <?php if (isset($errors['image'])): ?>
                            <small class="field-error"><?= e($errors['image']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <a class="btn btn-ghost" href="/admin/prizes">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <path d="M17 21v-8H7v8"></path>
                        <path d="M7 3v5h8"></path>
                    </svg>
                    <?= $isEdit ? 'Guardar cambios' : 'Crear premio' ?>
                </button>
            </div>
        </div>

        <aside class="form-layout-side">
            <section class="card">
                <header class="card-header">
                    <h2>Estado</h2>
                </header>
                <div class="stack-sm">
                    <label class="check">
                        <input type="checkbox" name="is_active" value="1"<?= !empty($old['is_active']) ? ' checked' : '' ?>>
                        <span>Premio activo</span>
                    </label>
                    <p class="help-text">Solo los premios activos pueden asignarse a un sorteo.</p>
                    <?php if ($isEdit && $prize !== null): ?>
                        <div class="divider"></div>
                        <div class="status-row"><span>Codigo publico</span><strong><?= e($prize['public_code']) ?></strong></div>
                        <div class="status-row"><span>Creado</span><strong><?= e(\App\Shared\Dates::display($prize['created_at'])) ?></strong></div>
                        <div class="status-row"><span>Actualizado</span><strong><?= e(\App\Shared\Dates::display($prize['updated_at'])) ?></strong></div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card">
                <header class="card-header">
                    <h2>Buenas practicas</h2>
                </header>
                <p class="help-text">Usa nombres claros, una foto horizontal y un valor referencial realista. No publiques datos personales del ganador en la imagen.</p>
            </section>
        </aside>
    </div>
</form>
