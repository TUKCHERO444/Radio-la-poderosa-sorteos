<?php
/** @var string $mode */
/** @var array $user */
/** @var array $errors */
/** @var string $action */
/** @var bool $isSelf */

$isEdit = $mode === 'edit';
$hasErrors = $errors !== [];
$role = (string) ($user['role'] ?? 'admin');
$usernameError = isset($errors['username']);
$nameError = isset($errors['name']);
$roleError = isset($errors['role']);
$passwordError = isset($errors['password']);
$passwordConfirmError = isset($errors['password_confirm']);
?>
<section class="page-head">
    <div class="page-head-text">
        <h1><?= $isEdit ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
        <p><?= $isEdit ? 'Actualiza los datos y permisos de la cuenta.' : 'Crea una nueva cuenta de acceso al panel.' ?></p>
    </div>
    <div class="page-head-actions">
        <a class="btn btn-ghost" href="/admin/users">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            Volver al listado
        </a>
    </div>
</section>

<?php if ($hasErrors): ?>
    <div class="alert alert-error" role="alert">
        <span class="alert-content">Revisa los campos marcados antes de continuar.</span>
    </div>
<?php endif; ?>

<section class="card form-card">
    <form method="post" action="<?= e($action) ?>" class="form" autocomplete="off" novalidate data-loading data-validate-form>
        <?= admin_csrf_field() ?>

        <div class="form-grid">
            <div class="field<?= $usernameError ? ' has-error' : '' ?>">
                <label for="username">Usuario <span class="required">*</span></label>
                <input id="username" name="username" type="text"
                       class="input"
                       value="<?= e(admin_old($user, 'username')) ?>"
                       autocomplete="off" autocapitalize="none" spellcheck="false"
                       maxlength="50" placeholder="ej. radiolp" required
                       aria-invalid="<?= $usernameError ? 'true' : 'false' ?>"
                       aria-describedby="username-feedback"<?= field_attrs('username') ?>>
                <small id="username-feedback" class="<?= $usernameError ? 'field-error' : 'field-hint' ?>">
                    <?= $usernameError ? e($errors['username']) : 'De 3 a 50 caracteres. Letras, numeros, punto, guion y guion bajo.' ?>
                </small>
            </div>

            <div class="field<?= $nameError ? ' has-error' : '' ?>">
                <label for="name">Nombre</label>
                <input id="name" name="name" type="text"
                       class="input"
                       value="<?= e(admin_old($user, 'name')) ?>"
                       autocomplete="name" maxlength="120" placeholder="Nombre visible"
                       aria-invalid="<?= $nameError ? 'true' : 'false' ?>"
                       aria-describedby="name-feedback"<?= field_attrs('person_name') ?>>
                <small id="name-feedback" class="<?= $nameError ? 'field-error' : 'field-hint' ?>">
                    <?= $nameError ? e($errors['name']) : 'Opcional. Se muestra en el panel.' ?>
                </small>
            </div>

            <div class="field<?= $roleError ? ' has-error' : '' ?>">
                <label for="role">Rol</label>
                <select id="role" name="role" class="select"
                        aria-invalid="<?= $roleError ? 'true' : 'false' ?>"
                        aria-describedby="role-feedback">
                    <option value="admin"<?= $role === 'admin' ? ' selected' : '' ?>>Administrador</option>
                    <option value="editor"<?= $role === 'editor' ? ' selected' : '' ?>>Editor</option>
                </select>
                <small id="role-feedback" class="<?= $roleError ? 'field-error' : 'field-hint' ?>">
                    <?php if ($roleError): ?>
                        <?= e($errors['role']) ?>
                    <?php elseif ($isSelf): ?>
                        Estas editando tu propia cuenta.
                    <?php else: ?>
                        El administrador tiene control total del panel.
                    <?php endif; ?>
                </small>
            </div>
        </div>

        <div class="form-divider"></div>

        <div class="form-grid">
            <div class="field<?= $passwordError ? ' has-error' : '' ?>">
                <label for="password">Contrasena <?= $isEdit ? '' : '<span class="required">*</span>' ?></label>
                <div class="input-wrap has-action">
                    <input id="password" name="password" type="password"
                           class="input"
                           autocomplete="new-password" minlength="8" maxlength="72"
                           placeholder="<?= $isEdit ? 'Dejar en blanco para conservar' : 'Minimo 8 caracteres' ?>"<?= $isEdit ? '' : ' required' ?>
                           aria-invalid="<?= $passwordError ? 'true' : 'false' ?>"
                           aria-describedby="password-feedback" data-message="La contraseña debe tener entre 8 y 72 caracteres.">
                    <button type="button" class="input-action" data-toggle-password="password" aria-label="Ver contrasena">Ver</button>
                </div>
                <small id="password-feedback" class="<?= $passwordError ? 'field-error' : 'field-hint' ?>">
                    <?= $passwordError ? e($errors['password']) : ($isEdit ? 'Dejala vacia para conservar la contrasena actual.' : 'Minimo 8 caracteres.') ?>
                </small>
            </div>

            <div class="field<?= $passwordConfirmError ? ' has-error' : '' ?>">
                <label for="password_confirm">Confirmar contrasena <?= $isEdit ? '' : '<span class="required">*</span>' ?></label>
                <div class="input-wrap has-action">
                    <input id="password_confirm" name="password_confirm" type="password"
                           class="input"
                           autocomplete="new-password" minlength="8" maxlength="72"
                           placeholder="Repite la contrasena"<?= $isEdit ? '' : ' required' ?>
                           aria-invalid="<?= $passwordConfirmError ? 'true' : 'false' ?>"
                           data-match="password"
                           data-message="Las contraseñas no coinciden."
                           aria-describedby="password-confirm-feedback">
                    <button type="button" class="input-action" data-toggle-password="password_confirm" aria-label="Ver contrasena">Ver</button>
                </div>
                <small id="password-confirm-feedback" class="<?= $passwordConfirmError ? 'field-error' : 'field-hint' ?>">
                    <?= $passwordConfirmError ? e($errors['password_confirm']) : 'Repite la contrasena para confirmar el cambio.' ?>
                </small>
            </div>
        </div>

        <div class="form-actions">
            <a class="btn btn-ghost" href="/admin/users">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <path d="M17 21v-8H7v8"></path>
                    <path d="M7 3v5h8"></path>
                </svg>
                <?= $isEdit ? 'Guardar cambios' : 'Crear usuario' ?>
            </button>
        </div>
    </form>
</section>
