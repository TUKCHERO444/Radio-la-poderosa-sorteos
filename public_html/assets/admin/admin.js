(function () {
    'use strict';

    var body = document.body;
    var root = document.documentElement;
    var storageKey = 'radiolp-admin-theme';
    var mobileQuery = window.matchMedia('(max-width: 900px)');

    /* ---------------------------------------------------------------------
       Resumen de errores de formulario
       --------------------------------------------------------------------- */

    var formErrors = document.getElementById('formErrors');

    if (formErrors) {
        formErrors.focus();
    }

    /* ---------------------------------------------------------------------
       Navegación lateral
       --------------------------------------------------------------------- */

    var sidebar = document.getElementById('adminSidebar');
    var overlay = document.querySelector('[data-sidebar-close]');
    var toggle = document.querySelector('[data-sidebar-toggle]');

    function setNav(open) {
        if (!sidebar) {
            return;
        }

        sidebar.classList.toggle('is-open', open);
        body.classList.toggle('nav-open', open);

        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        if (overlay) {
            overlay.hidden = !open;
        }
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            setNav(!sidebar.classList.contains('is-open'));
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            setNav(false);
        });
    }

    if (sidebar) {
        sidebar.querySelectorAll('a.side-nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (mobileQuery.matches) {
                    setNav(false);
                }
            });
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setNav(false);
        }
    });

    if (typeof mobileQuery.addEventListener === 'function') {
        mobileQuery.addEventListener('change', function (event) {
            if (!event.matches) {
                setNav(false);
            }
        });
    }

    /* ---------------------------------------------------------------------
       Tema claro / oscuro
       --------------------------------------------------------------------- */

    var themeButtons = document.querySelectorAll('[data-theme-toggle]');

    function applyTheme(theme, persist) {
        root.classList.add('theme-switching');
        root.setAttribute('data-theme', theme);

        window.setTimeout(function () {
            root.classList.remove('theme-switching');
        }, 60);

        if (persist) {
            try {
                window.localStorage.setItem(storageKey, theme);
            } catch (error) {
                /* Sin almacenamiento disponible: el tema dura la sesión. */
            }
        }

        var meta = document.querySelector('meta[name="theme-color"]');

        if (meta) {
            meta.setAttribute('content', theme === 'dark' ? '#0b1626' : '#f4f6f9');
        }

        themeButtons.forEach(function (button) {
            var isDark = theme === 'dark';
            button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            button.setAttribute('aria-label', isDark ? 'Activar tema claro' : 'Activar tema oscuro');
        });
    }

    applyTheme(root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light', false);

    themeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(next, true);
        });
    });

    /* ---------------------------------------------------------------------
       Mostrar / ocultar contraseña
       --------------------------------------------------------------------- */

    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('data-toggle-password'));

            if (!input) {
                return;
            }

            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.textContent = show ? 'Ocultar' : 'Ver';
            button.setAttribute('aria-label', show ? 'Ocultar contrasena' : 'Ver contrasena');
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
        });
    });

    /* ---------------------------------------------------------------------
       Mensajes flash
       --------------------------------------------------------------------- */

    document.querySelectorAll('[data-dismiss-alert]').forEach(function (button) {
        button.addEventListener('click', function () {
            var alert = button.closest('.alert');

            if (alert) {
                alert.remove();
            }
        });
    });

    /* ---------------------------------------------------------------------
       Diálogo de confirmación nativo
       --------------------------------------------------------------------- */

    var dialog = document.getElementById('confirmDialog');
    var dialogTitle = dialog ? dialog.querySelector('#confirmDialogTitle') : null;
    var dialogMessage = dialog ? dialog.querySelector('[data-dialog-message]') : null;
    var dialogConfirm = dialog ? dialog.querySelector('[data-dialog-confirm]') : null;
    var dialogCancel = dialog ? dialog.querySelector('[data-dialog-cancel]') : null;
    var pendingForm = null;
    var confirmed = false;
    var lastTrigger = null;

    function submitForm(form) {
        if (!form) {
            return;
        }

        confirmed = true;

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }

        form.submit();
    }

    function closeDialog(returnValue) {
        if (dialog && dialog.open) {
            dialog.close(returnValue || 'cancel');
        }
    }

    if (dialog) {
        dialog.querySelectorAll('[data-dialog-close], [data-dialog-cancel]').forEach(function (button) {
            button.addEventListener('click', function () {
                closeDialog('cancel');
            });
        });

        if (dialogConfirm) {
            dialogConfirm.addEventListener('click', function () {
                var form = pendingForm;
                pendingForm = null;
                closeDialog('confirm');
                submitForm(form);
            });
        }

        dialog.addEventListener('close', function () {
            if (dialog.returnValue !== 'confirm') {
                pendingForm = null;
            }

            if (lastTrigger && document.contains(lastTrigger)) {
                lastTrigger.focus();
            }
        });
    }

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (confirmed) {
                confirmed = false;
                return;
            }

            event.preventDefault();

            if (!dialog || typeof dialog.showModal !== 'function') {
                if (window.confirm(form.getAttribute('data-confirm'))) {
                    submitForm(form);
                }

                return;
            }

            pendingForm = form;
            lastTrigger = document.activeElement;

            if (dialogTitle) {
                dialogTitle.textContent = form.getAttribute('data-confirm-title') || 'Confirmar accion';
            }

            if (dialogMessage) {
                dialogMessage.textContent = form.getAttribute('data-confirm') || '¿Deseas continuar con esta accion?';
            }

            if (dialogConfirm) {
                dialogConfirm.textContent = form.getAttribute('data-confirm-label') || 'Confirmar';
            }

            dialog.returnValue = '';
            dialog.showModal();

            if (dialogCancel) {
                dialogCancel.focus();
            }
        });
    });

    /* ---------------------------------------------------------------------
       Validación de escritura y formato
       --------------------------------------------------------------------- */

    var patternCache = {};

    function regexFor(field) {
        var pattern = field.getAttribute('data-pattern');

        if (!pattern) {
            return null;
        }

        if (!Object.prototype.hasOwnProperty.call(patternCache, pattern)) {
            try {
                patternCache[pattern] = new RegExp('^(?:' + pattern + ')$');
            } catch (error) {
                patternCache[pattern] = null;
            }
        }

        return patternCache[pattern];
    }

    function feedbackFor(field) {
        var id = field.getAttribute('data-feedback');

        if (id) {
            return document.getElementById(id);
        }

        if (!field.id) {
            return null;
        }

        var direct = document.getElementById(field.id + '-feedback');

        if (direct) {
            return direct;
        }

        return document.getElementById(field.id.replace(/_/g, '-') + '-feedback');
    }

    function setFeedback(feedback, message, isError) {
        if (!feedback) {
            return;
        }

        feedback.textContent = message;
        feedback.classList.toggle('field-error', isError);
        feedback.classList.toggle('field-hint', !isError);
    }

    function baseHintFor(field, feedback) {
        if (!feedback) {
            return '';
        }

        if (typeof feedback.dataset.baseHint !== 'string') {
            feedback.dataset.baseHint = feedback.classList.contains('field-error')
                ? (field.getAttribute('data-message') || '')
                : feedback.textContent.trim();
        }

        return feedback.dataset.baseHint;
    }

    function markField(field, message) {
        var wrapper = field.closest('.field');
        var feedback = feedbackFor(field);

        if (wrapper) {
            wrapper.classList.add('has-error');
        }

        field.setAttribute('aria-invalid', 'true');
        baseHintFor(field, feedback);
        setFeedback(feedback, message, true);
    }

    function clearField(field) {
        var wrapper = field.closest('.field');
        var feedback = feedbackFor(field);

        if (wrapper) {
            wrapper.classList.remove('has-error');
        }

        field.setAttribute('aria-invalid', 'false');

        if (feedback) {
            setFeedback(feedback, baseHintFor(field, feedback) || field.getAttribute('data-message') || '', false);
        }
    }

    function validateControl(field) {
        if (!field || field.matches(':disabled') || field.type === 'hidden' || field.type === 'submit') {
            return '';
        }

        if (field.type === 'file') {
            if (field.hasAttribute('required') && (!field.files || field.files.length === 0)) {
                return field.getAttribute('data-required-message') || 'Selecciona un archivo.';
            }

            if (field.dataset.invalid === '1') {
                return field.dataset.invalidMessage || 'Revisa la imagen seleccionada.';
            }

            return '';
        }

        var value = String(field.value || '').trim();
        var required = field.hasAttribute('required');

        if (required && value === '') {
            return field.getAttribute('data-required-message') || 'Este campo es obligatorio.';
        }

        if (value === '') {
            return '';
        }

        var matchId = field.getAttribute('data-match');

        if (matchId) {
            var other = document.getElementById(matchId);

            if (other && value !== String(other.value || '')) {
                return field.getAttribute('data-message') || 'Los valores no coinciden.';
            }
        }

        var regex = regexFor(field);

        if (regex && !regex.test(value)) {
            return field.getAttribute('data-message') || 'El formato ingresado no es válido.';
        }

        var minLength = parseInt(field.getAttribute('minlength') || '0', 10);

        if (minLength > 0 && value.length < minLength) {
            return field.getAttribute('data-message') || ('Mínimo ' + minLength + ' caracteres.');
        }

        var maxLength = parseInt(field.getAttribute('maxlength') || '0', 10);

        if (maxLength > 0 && value.length > maxLength) {
            return 'Máximo ' + maxLength + ' caracteres.';
        }

        if (field.type === 'number') {
            var number = Number(field.value);
            var min = field.getAttribute('min');
            var max = field.getAttribute('max');

            if (!isNaN(number) && min !== null && number < Number(min)) {
                return field.getAttribute('data-message') || ('El valor mínimo es ' + min + '.');
            }

            if (!isNaN(number) && max !== null && number > Number(max)) {
                return field.getAttribute('data-message') || ('El valor máximo es ' + max + '.');
            }
        }

        return '';
    }

    function validateGroup(group) {
        var name = group.getAttribute('data-required-group');
        var feedbackId = group.getAttribute('data-feedback');
        var feedback = feedbackId ? document.getElementById(feedbackId) : null;
        var checked = group.querySelectorAll('input[name="' + name + '"]:checked').length;

        if (checked > 0) {
            group.classList.remove('has-error');
            setFeedback(feedback, baseHintFor(group, feedback), false);
            return null;
        }

        var message = group.getAttribute('data-message') || 'Selecciona al menos una opción.';
        group.classList.add('has-error');
        baseHintFor(group, feedback);
        setFeedback(feedback, message, true);

        return group.querySelector('input');
    }

    function collectErrors(form) {
        var errors = [];

        form.querySelectorAll('input, select, textarea').forEach(function (field) {
            if (field.closest('[data-required-group]') && field.type === 'checkbox') {
                return;
            }

            var message = validateControl(field);

            if (message !== '') {
                markField(field, message);
                errors.push({ field: field, message: message });
            } else {
                clearField(field);
            }
        });

        form.querySelectorAll('[data-required-group]').forEach(function (group) {
            var invalid = validateGroup(group);

            if (invalid) {
                errors.push({
                    field: invalid,
                    message: group.getAttribute('data-message') || 'Selecciona al menos una opción.'
                });
            }
        });

        return errors;
    }

    function renderSummary(form, errors) {
        var summary = document.getElementById('formErrors');

        if (errors.length === 0) {
            if (summary) {
                summary.remove();
            }

            return null;
        }

        if (!summary) {
            summary = document.createElement('div');
            summary.id = 'formErrors';
            summary.className = 'alert alert-error';
            summary.setAttribute('role', 'alert');
            summary.setAttribute('tabindex', '-1');
            form.parentNode.insertBefore(summary, form);
        }

        var content = document.createElement('div');
        content.className = 'alert-content';
        var inner = document.createElement('div');
        var title = document.createElement('strong');
        title.textContent = 'Revisa los siguientes campos:';
        var list = document.createElement('ul');
        list.className = 'alert-list';

        errors.forEach(function (item) {
            var entry = document.createElement('li');
            var link = document.createElement('a');
            link.className = 'link';
            link.textContent = item.message;

            if (item.field.id) {
                link.href = '#' + item.field.id;
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    item.field.focus();
                });
            }

            entry.appendChild(link);
            list.appendChild(entry);
        });

        inner.appendChild(title);
        inner.appendChild(list);
        content.appendChild(inner);
        summary.innerHTML = '';
        summary.appendChild(content);

        return summary;
    }

    function sanitizeField(field) {
        var mode = field.getAttribute('data-sanitize');

        if (!mode) {
            return;
        }

        var value = field.value;
        var clean = value;

        if (mode === 'digits') {
            clean = value.replace(/[^0-9]/g, '');
        } else if (mode === 'decimal') {
            clean = value.replace(/,/g, '.').replace(/[^0-9.]/g, '');
            var parts = clean.split('.');

            if (parts.length > 2) {
                clean = parts.shift() + '.' + parts.join('');
                parts = clean.split('.');
            }

            if (parts.length === 2) {
                clean = parts[0] + '.' + parts[1].slice(0, 2);
            }
        } else if (mode === 'person-name') {
            clean = value.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ' .-]/g, '').replace(/\s{2,}/g, ' ');
        } else if (mode === 'display-name') {
            clean = value.replace(/[^A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ .,&°%()\/+'#:-]/g, '').replace(/\s{2,}/g, ' ');
        } else if (mode === 'username') {
            clean = value.replace(/[^A-Za-z0-9._-]/g, '');
        } else if (mode === 'slug') {
            clean = value
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        if (clean !== value) {
            field.value = clean;
        }
    }

    function isSanitizedTarget(field) {
        return field && field.matches && field.matches('[data-sanitize]');
    }

    function isValidationTarget(field) {
        return field && field.matches && field.matches('input, select, textarea')
            && field.form && field.form.hasAttribute('data-validate-form');
    }

    document.addEventListener('input', function (event) {
        var field = event.target;

        if (!isValidationTarget(field)) {
            return;
        }

        if (isSanitizedTarget(field)) {
            sanitizeField(field);
        }

        var wrapper = field.closest('.field') || field.closest('[data-required-group]');

        if (wrapper && wrapper.classList.contains('has-error') && field.type !== 'checkbox') {
            var message = validateControl(field);

            if (message !== '') {
                markField(field, message);
            } else {
                clearField(field);
            }
        }

        if (field.type === 'checkbox' && field.closest('[data-required-group]')) {
            validateGroup(field.closest('[data-required-group]'));
        }
    }, false);

    document.addEventListener('focusout', function (event) {
        var field = event.target;

        if (!isValidationTarget(field) || field.type === 'checkbox' || field.type === 'file') {
            return;
        }

        var value = String(field.value || '').trim();

        if (value === '' && !field.hasAttribute('required')) {
            clearField(field);
            return;
        }

        var message = validateControl(field);

        if (message !== '') {
            markField(field, message);
        } else {
            clearField(field);
        }
    }, true);

    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (!form || form.nodeName !== 'FORM' || !form.hasAttribute('data-validate-form')) {
            return;
        }

        var errors = collectErrors(form);
        var summary = renderSummary(form, errors);

        if (errors.length > 0) {
            event.preventDefault();
            event.stopPropagation();

            if (summary && typeof summary.focus === 'function') {
                summary.focus();
            }
        }
    }, true);

    /* ---------------------------------------------------------------------
       Formularios: foco, envío automático y estado de carga
       --------------------------------------------------------------------- */

    var firstInvalid = document.querySelector('.field.has-error input, .field.has-error select, .field.has-error textarea');

    if (firstInvalid && !formErrors) {
        firstInvalid.focus();
    }

    document.querySelectorAll('[data-auto-submit]').forEach(function (control) {
        control.addEventListener('change', function () {
            var form = control.closest('form');

            if (!form) {
                return;
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    });

    document.querySelectorAll('form[data-loading]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (event.defaultPrevented) {
                return;
            }

            var submit = form.querySelector('button[type="submit"], input[type="submit"]');

            if (!submit) {
                return;
            }

            submit.classList.add('is-loading');
            submit.disabled = true;
        });
    });

    /* ---------------------------------------------------------------------
       Formularios de catálogo y sorteos
       --------------------------------------------------------------------- */

    function imageFeedback(input) {
        var id = input.getAttribute('aria-describedby');

        return id ? document.getElementById(id) : null;
    }

    function setImageError(input, message) {
        input.dataset.invalid = '1';
        input.dataset.invalidMessage = message;

        var feedback = imageFeedback(input);

        if (feedback) {
            if (!feedback.dataset.baseHint) {
                feedback.dataset.baseHint = feedback.textContent.trim();
                feedback.dataset.baseClass = feedback.className;
            }

            feedback.textContent = message;
            feedback.classList.remove('field-hint');
            feedback.classList.add('field-error');
        }

        var field = input.closest('.field');

        if (field) {
            field.classList.add('has-error');
        }
    }

    function clearImageError(input) {
        delete input.dataset.invalid;
        delete input.dataset.invalidMessage;

        var feedback = imageFeedback(input);

        if (feedback && feedback.dataset.baseHint) {
            feedback.textContent = feedback.dataset.baseHint;
            feedback.className = feedback.dataset.baseClass || 'field-hint';
        }

        var field = input.closest('.field');

        if (field) {
            field.classList.remove('has-error');
        }
    }

    document.querySelectorAll('[data-image-input]').forEach(function (input) {
        input.addEventListener('change', function () {
            var targetId = input.getAttribute('data-preview-target');
            var preview = targetId ? document.getElementById(targetId) : null;
            var file = input.files && input.files[0];

            if (!file) {
                clearImageError(input);
                return;
            }

            var allowed = ['image/jpeg', 'image/png', 'image/webp'];
            var maxBytes = 10 * 1024 * 1024;

            if (allowed.indexOf(file.type) === -1) {
                setImageError(input, 'Formato no permitido. Usa JPG, PNG o WebP.');
                return;
            }

            if (file.size > maxBytes) {
                setImageError(input, 'La imagen no puede superar los 10 MB.');
                return;
            }

            clearImageError(input);

            if (!preview || !window.FileReader) {
                return;
            }

            var reader = new FileReader();
            reader.addEventListener('load', function () {
                preview.innerHTML = '';
                var image = document.createElement('img');
                image.src = String(reader.result);
                image.alt = '';
                preview.appendChild(image);
            });
            reader.readAsDataURL(file);
        });
    });

    document.querySelectorAll('[data-slug-source]').forEach(function (source) {
        var targetId = source.getAttribute('data-slug-target') || 'slug';
        var target = document.getElementById(targetId);

        if (!target) {
            return;
        }

        var touched = target.value !== '';

        target.addEventListener('input', function () {
            touched = target.value !== '';
        });

        source.addEventListener('input', function () {
            if (touched) {
                return;
            }

            var slug = source.value
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');

            target.value = slug;
        });
    });

    var primaryPrize = document.querySelector('[data-primary-prize]');

    function syncPrimaryPrize() {
        if (!primaryPrize) {
            return;
        }

        var selected = primaryPrize.value;
        document.querySelectorAll('[data-additional-prize]').forEach(function (checkbox) {
            var isPrimary = selected !== '' && checkbox.value === selected;

            if (isPrimary) {
                checkbox.checked = false;
                checkbox.disabled = true;
            } else {
                checkbox.disabled = false;
            }
        });
    }

    if (primaryPrize) {
        primaryPrize.addEventListener('change', syncPrimaryPrize);
        syncPrimaryPrize();
    }

    document.querySelectorAll('[data-payment-type]').forEach(function (select) {
        var form = select.closest('form');

        if (!form) {
            return;
        }

        var walletFields = form.querySelectorAll('.js-wallet-fields');
        var bankFields = form.querySelectorAll('.js-bank-fields');
        var phone = form.querySelector('#phone');
        var bankName = form.querySelector('#bank_name');
        var accountNumber = form.querySelector('#account_number');

        function syncPaymentType() {
            var isWallet = select.value === 'wallet';

            walletFields.forEach(function (block) {
                block.hidden = !isWallet;
            });

            bankFields.forEach(function (block) {
                block.hidden = isWallet;
            });

            if (phone) {
                phone.required = isWallet;
            }

            if (bankName) {
                bankName.required = !isWallet;
            }

            if (accountNumber) {
                accountNumber.required = !isWallet;
            }
        }

        select.addEventListener('change', syncPaymentType);
        syncPaymentType();
    });

    /* ---------------------------------------------------------------------
       Página de sistema
       --------------------------------------------------------------------- */

    document.querySelectorAll('[data-reload]').forEach(function (button) {
        button.addEventListener('click', function () {
            window.location.reload();
        });
    });
})();
