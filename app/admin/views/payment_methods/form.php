<?php
/** @var string $mode */
/** @var array|null $method */
/** @var array $old */
/** @var array $errors */
/** @var string $action */
$isEdit = $mode === 'edit';
$err = static fn(string $key): string => isset($errors[$key]) ? ' has-error' : '';
$invalid = static fn(string $key): string => isset($errors[$key]) ? 'true' : 'false';
$typeLabels = ['wallet' => 'Billetera digital', 'bank' => 'Cuenta bancaria'];
$providerLabels = ['yape' => 'Yape', 'plin' => 'Plin', 'bcp' => 'BCP', 'other' => 'Otro'];
$isWallet = $old['type'] === 'wallet';
?>
<section class="page-head">
    <div class="page-head-text">
        <h1><?= $isEdit ? 'Editar metodo de pago' : 'Nuevo metodo de pago' ?></h1>
        <p><?= $isEdit ? 'Actualiza los datos de pago y el contacto para sobrepagos.' : 'Configura una billetera digital o una cuenta bancaria para los sorteos.' ?></p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="/admin/payment-methods">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            Volver a metodos
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
                        <h2 class="form-section-title">Datos del metodo</h2>
                        <p class="form-section-desc">El cliente vera estos datos en la pagina de pago del sorteo.</p>
                    </div>
                </header>

                <div class="form-grid">
                    <div class="field<?= $err('name') ?>">
                        <label for="name">Nombre visible <span class="required">*</span></label>
                        <input id="name" name="name" type="text" class="input" value="<?= e($old['name']) ?>" maxlength="120" required
                               placeholder="Ej. Yape principal"
                               aria-invalid="<?= $invalid('name') ?>" aria-describedby="name-feedback"<?= field_attrs('display_name') ?>>
                        <small id="name-feedback" class="<?= isset($errors['name']) ? 'field-error' : 'field-hint' ?>">
                            <?= isset($errors['name']) ? e($errors['name']) : 'Nombre corto para identificar el metodo.' ?>
                        </small>
                    </div>

                    <div class="field<?= $err('type') ?>">
                        <label for="type">Tipo <span class="required">*</span></label>
                        <select id="type" name="type" class="select" required data-payment-type
                                aria-invalid="<?= $invalid('type') ?>" aria-describedby="type-feedback">
                            <?php foreach ($typeLabels as $value => $label): ?>
                                <option value="<?= e($value) ?>"<?= $old['type'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small id="type-feedback" class="<?= isset($errors['type']) ? 'field-error' : 'field-hint' ?>">
                            <?= isset($errors['type']) ? e($errors['type']) : 'Billetera (Yape/Plin) o cuenta bancaria (BCP).' ?>
                        </small>
                    </div>

                    <div class="field<?= $err('provider') ?>">
                        <label for="provider">Proveedor <span class="required">*</span></label>
                        <select id="provider" name="provider" class="select" required
                                aria-invalid="<?= $invalid('provider') ?>" aria-describedby="provider-feedback">
                            <?php foreach ($providerLabels as $value => $label): ?>
                                <option value="<?= e($value) ?>"<?= $old['provider'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small id="provider-feedback" class="<?= isset($errors['provider']) ? 'field-error' : 'field-hint' ?>">
                            <?= isset($errors['provider']) ? e($errors['provider']) : 'Selecciona el proveedor real del pago.' ?>
                        </small>
                    </div>

                    <div class="field<?= $err('holder_name') ?>">
                        <label for="holder_name">Titular <span class="required">*</span></label>
                        <input id="holder_name" name="holder_name" type="text" class="input" value="<?= e($old['holder_name']) ?>" maxlength="160" required
                               placeholder="Nombre completo del titular"
                               aria-invalid="<?= $invalid('holder_name') ?>" aria-describedby="holder-feedback"<?= field_attrs('person_name') ?>>
                        <small id="holder-feedback" class="<?= isset($errors['holder_name']) ? 'field-error' : 'field-hint' ?>">
                            <?= isset($errors['holder_name']) ? e($errors['holder_name']) : 'Nombre completo. Solo letras, espacios, apóstrofo, punto y guion.' ?>
                        </small>
                    </div>

                    <div class="field<?= $err('holder_document') ?>">
                        <label for="holder_document">Documento del titular</label>
                        <input id="holder_document" name="holder_document" type="text" class="input" value="<?= e($old['holder_document']) ?>" maxlength="11"
                               placeholder="DNI (8) o RUC (11)"
                               aria-invalid="<?= $invalid('holder_document') ?>" aria-describedby="holder-document-feedback"<?= field_attrs('document') ?>>
                        <small id="holder-document-feedback" class="<?= isset($errors['holder_document']) ? 'field-error' : 'field-hint' ?>">
                            <?= isset($errors['holder_document']) ? e($errors['holder_document']) : 'Opcional. DNI de 8 dígitos o RUC de 11, sin letras.' ?>
                        </small>
                    </div>

                    <div class="field<?= $err('contact_phone') ?>">
                        <label for="contact_phone">Contacto para sobrepagos <span class="required">*</span></label>
                        <input id="contact_phone" name="contact_phone" type="tel" class="input" value="<?= e($old['contact_phone']) ?>" maxlength="9" required
                               placeholder="Ej. 987654321"
                               aria-invalid="<?= $invalid('contact_phone') ?>" aria-describedby="contact-phone-feedback"<?= field_attrs('phone') ?>>
                        <small id="contact-phone-feedback" class="<?= isset($errors['contact_phone']) ? 'field-error' : 'field-hint' ?>">
                            <?= isset($errors['contact_phone']) ? e($errors['contact_phone']) : 'Celular de 9 dígitos, empezando por 9. Se muestra si el cliente paga de más.' ?>
                        </small>
                    </div>
                </div>

                <div class="js-wallet-fields"<?= $isWallet ? '' : ' hidden' ?>>
                    <div class="form-grid mt-4">
                        <div class="field<?= $err('phone') ?>">
                            <label for="phone">Telefono de la billetera <span class="required">*</span></label>
                            <input id="phone" name="phone" type="tel" class="input" value="<?= e($old['phone']) ?>" maxlength="9"
                                   placeholder="Ej. 987654321"
                                   aria-invalid="<?= $invalid('phone') ?>" aria-describedby="phone-feedback"<?= field_attrs('phone') ?>>
                            <small id="phone-feedback" class="<?= isset($errors['phone']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['phone']) ? e($errors['phone']) : 'Número de 9 dígitos asociado a Yape o Plin.' ?>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="js-bank-fields"<?= $isWallet ? ' hidden' : '' ?>>
                    <div class="form-grid mt-4">
                        <div class="field<?= $err('bank_name') ?>">
                            <label for="bank_name">Banco <span class="required">*</span></label>
                            <input id="bank_name" name="bank_name" type="text" class="input" value="<?= e($old['bank_name']) ?>" maxlength="80"
                                   placeholder="Ej. BCP"
                                   aria-invalid="<?= $invalid('bank_name') ?>" aria-describedby="bank-name-feedback"<?= field_attrs('display_name') ?>>
                            <small id="bank-name-feedback" class="<?= isset($errors['bank_name']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['bank_name']) ? e($errors['bank_name']) : 'Banco donde se recibe la transferencia.' ?>
                            </small>
                        </div>
                        <div class="field<?= $err('account_number') ?>">
                            <label for="account_number">Cuenta o tarjeta <span class="required">*</span></label>
                            <input id="account_number" name="account_number" type="text" class="input" value="<?= e($old['account_number']) ?>" maxlength="20" minlength="8"
                                   placeholder="Solo números (8 a 20 dígitos)"
                                   aria-invalid="<?= $invalid('account_number') ?>" aria-describedby="account-number-feedback"<?= field_attrs('account') ?>>
                            <small id="account-number-feedback" class="<?= isset($errors['account_number']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['account_number']) ? e($errors['account_number']) : 'Solo números, de 8 a 20 dígitos.' ?>
                            </small>
                        </div>
                        <div class="field<?= $err('cci') ?>">
                            <label for="cci">CCI</label>
                            <input id="cci" name="cci" type="text" class="input" value="<?= e($old['cci']) ?>" maxlength="20" minlength="20"
                                   placeholder="20 dígitos"
                                   aria-invalid="<?= $invalid('cci') ?>" aria-describedby="cci-feedback"<?= field_attrs('cci') ?>>
                            <small id="cci-feedback" class="<?= isset($errors['cci']) ? 'field-error' : 'field-hint' ?>">
                                <?= isset($errors['cci']) ? e($errors['cci']) : 'Opcional. Exactamente 20 dígitos.' ?>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="field<?= $err('instructions') ?> mt-4">
                    <label for="instructions">Instrucciones para el cliente</label>
                    <textarea id="instructions" name="instructions" class="textarea" rows="4" maxlength="2000"
                              placeholder="Ej. Paga el monto exacto y sube tu comprobante. Si pagas de mas, contacta al numero indicado."
                              aria-invalid="<?= $invalid('instructions') ?>" aria-describedby="instructions-feedback"><?= e($old['instructions']) ?></textarea>
                    <small id="instructions-feedback" class="<?= isset($errors['instructions']) ? 'field-error' : 'field-hint' ?>">
                        <?= isset($errors['instructions']) ? e($errors['instructions']) : 'Opcional. Se mostrara en la pagina de pago.' ?>
                    </small>
                </div>
            </section>

            <section class="form-section">
                <header class="form-section-head">
                    <div>
                        <h2 class="form-section-title">QR de pago</h2>
                        <p class="form-section-desc">Opcional. JPG, PNG o WebP, maximo 10 MB.</p>
                    </div>
                </header>

                <div class="media-field" data-image-field>
                    <div class="media-preview" id="qrImagePreview">
                        <?php if (!empty($method['qr_image_path'])): ?>
                            <img src="<?= e($method['qr_image_path']) ?>" alt="">
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                                <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                                <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                                <path d="M14 14h3v3h-3z"></path>
                                <path d="M21 14v3"></path>
                                <path d="M14 21h3"></path>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="media-input">
                        <label class="label-main" for="qr_image">Subir QR</label>
                        <input id="qr_image" name="qr_image" type="file" accept="image/jpeg,image/png,image/webp"
                               data-image-input data-preview-target="qrImagePreview"
                               aria-describedby="qr-feedback">
                        <small id="qr-feedback" class="field-hint">Si no subes QR, se mostraran el telefono o la cuenta.</small>
                        <?php if (isset($errors['qr_image'])): ?>
                            <small class="field-error"><?= e($errors['qr_image']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <a class="btn btn-ghost" href="/admin/payment-methods">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <path d="M17 21v-8H7v8"></path>
                        <path d="M7 3v5h8"></path>
                    </svg>
                    <?= $isEdit ? 'Guardar cambios' : 'Crear metodo' ?>
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
                        <span>Metodo activo</span>
                    </label>
                    <p class="help-text">Solo los metodos activos pueden asignarse a un sorteo.</p>
                    <?php if ($isEdit && $method !== null): ?>
                        <div class="divider"></div>
                        <div class="status-row"><span>Creado</span><strong><?= e(\App\Shared\Dates::display($method['created_at'])) ?></strong></div>
                        <div class="status-row"><span>Actualizado</span><strong><?= e(\App\Shared\Dates::display($method['updated_at'])) ?></strong></div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card">
                <header class="card-header">
                    <h2>Sobrepagos</h2>
                </header>
                <p class="help-text">El telefono de contacto se mostrara en la pagina de pago y confirmacion. Si el cliente paga de mas, podra contactarte para regularizar.</p>
            </section>
        </aside>
    </div>
</form>
