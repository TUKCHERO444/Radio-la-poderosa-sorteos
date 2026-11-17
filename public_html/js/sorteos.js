(function () {
    'use strict';

    var page = document.querySelector('[data-sorteos]');

    if (!page) {
        return;
    }

    /* Cuenta regresiva y datos del registro ------------------------------ */

    var countdown = page.querySelector('[data-countdown]');
    var countdownGroup = page.querySelector('[data-countdown-group]');
    var countdownLabel = page.querySelector('[data-countdown-label]');
    var target = countdown ? new Date(countdown.getAttribute('data-deadline') || '').getTime() : NaN;
    var units = {
        days: countdown ? countdown.querySelector('[data-unit="days"]') : null,
        hours: countdown ? countdown.querySelector('[data-unit="hours"]') : null,
        minutes: countdown ? countdown.querySelector('[data-unit="minutes"]') : null,
        seconds: countdown ? countdown.querySelector('[data-unit="seconds"]') : null
    };

    var pad = function (value) {
        return String(value).padStart(2, '0');
    };

    var renderCountdown = function () {
        if (!countdown) {
            return;
        }

        if (!isFinite(target) || target <= 0) {
            if (units.days) {
                units.days.textContent = '00';
            }
            if (units.hours) {
                units.hours.textContent = '00';
            }
            if (units.minutes) {
                units.minutes.textContent = '00';
            }
            if (units.seconds) {
                units.seconds.textContent = '00';
            }
            return;
        }

        var remaining = Math.max(0, target - Date.now());
        var totalSeconds = Math.floor(remaining / 1000);
        var days = Math.floor(totalSeconds / 86400);
        var hours = Math.floor((totalSeconds % 86400) / 3600);
        var minutes = Math.floor((totalSeconds % 3600) / 60);
        var seconds = totalSeconds % 60;

        if (units.days) {
            units.days.textContent = pad(days);
        }
        if (units.hours) {
            units.hours.textContent = pad(hours);
        }
        if (units.minutes) {
            units.minutes.textContent = pad(minutes);
        }
        if (units.seconds) {
            units.seconds.textContent = pad(seconds);
        }
    };

    if (countdown) {
        renderCountdown();
        window.setInterval(renderCountdown, 1000);
    }

    document.addEventListener('sorteo:change', function (event) {
        var detail = event.detail || {};

        if (countdown) {
            target = detail.deadline ? new Date(detail.deadline).getTime() : NaN;

            if (countdownGroup) {
                countdownGroup.hidden = !detail.deadline;
            }

            if (countdownLabel) {
                countdownLabel.textContent = detail.deadlineLabel || 'Próximamente';
            }

            renderCountdown();
        }

        var price = page.querySelector('[data-registration-price]');
        var max = page.querySelector('[data-registration-max]');

        if (price && detail.price) {
            price.textContent = detail.price;
        }

        if (max && detail.max) {
            max.textContent = detail.max;
        }
    });

    /* Formulario de aviso ------------------------------------------------- */

    var form = page.querySelector('[data-notify-form]');

    if (form) {
        var note = form.querySelector('[data-form-note]');

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                return;
            }

            if (note) {
                note.textContent = 'El registro todavía no está habilitado. Muy pronto podrás participar; mientras tanto, escucha la radio en vivo.';
                note.hidden = false;
            }

            form.reset();
        });
    }
})();
