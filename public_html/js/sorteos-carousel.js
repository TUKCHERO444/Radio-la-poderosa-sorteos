(function () {
    'use strict';

    var carousel = document.querySelector('[data-carousel]');

    if (!carousel) {
        return;
    }

    var slides = Array.prototype.slice.call(carousel.querySelectorAll('.prize-slide'));
    var contents = Array.prototype.slice.call(carousel.querySelectorAll('[data-raffle-content]'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('[data-prizes-panel]'));

    if (slides.length < 2) {
        return;
    }

    var prev = carousel.querySelector('[data-carousel-prev]');
    var next = carousel.querySelector('[data-carousel-next]');
    var toggle = carousel.querySelector('[data-carousel-toggle]');
    var dotsWrap = carousel.querySelector('[data-carousel-dots]');
    var reducedQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    var intervalMs = 5500;
    var index = 0;
    var timer = null;
    var paused = reducedQuery.matches;
    var hovering = false;
    var focused = false;

    function createDots() {
        if (!dotsWrap) {
            return;
        }

        slides.forEach(function (_, position) {
            var dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'prize-dot';
            dot.setAttribute('aria-label', 'Ir al sorteo ' + (position + 1));
            dot.addEventListener('click', function () {
                index = position;
                render();
                restart();
            });
            dotsWrap.appendChild(dot);
        });
    }

    function render() {
        slides.forEach(function (slide, position) {
            slide.classList.toggle('is-active', position === index);
        });

        contents.forEach(function (content, position) {
            content.classList.toggle('is-active', position === index);
        });

        panels.forEach(function (panel, position) {
            panel.hidden = position !== index;
        });

        if (dotsWrap) {
            Array.prototype.forEach.call(dotsWrap.children, function (dot, position) {
                var active = position === index;
                dot.classList.toggle('is-active', active);

                if (active) {
                    dot.setAttribute('aria-current', 'true');
                } else {
                    dot.removeAttribute('aria-current');
                }
            });
        }

        dispatchChange();
    }

    function dispatchChange() {
        var active = contents[index];

        if (!active) {
            return;
        }

        document.dispatchEvent(new CustomEvent('sorteo:change', {
            detail: {
                index: index,
                deadline: active.getAttribute('data-deadline') || '',
                deadlineLabel: active.getAttribute('data-deadline-label') || '',
                price: active.getAttribute('data-price') || '',
                max: active.getAttribute('data-max') || '',
                name: active.getAttribute('data-name') || ''
            }
        }));
    }

    function stop() {
        if (timer !== null) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    function canAutoplay() {
        return !paused && !hovering && !focused && !document.hidden;
    }

    function start() {
        stop();

        if (!canAutoplay()) {
            return;
        }

        timer = window.setInterval(function () {
            move(1);
        }, intervalMs);
    }

    function restart() {
        stop();
        start();
    }

    function move(delta) {
        index = (index + delta + slides.length) % slides.length;
        render();
    }

    function updateToggle() {
        if (!toggle) {
            return;
        }

        toggle.setAttribute('data-paused', paused ? 'true' : 'false');
        toggle.setAttribute('aria-pressed', paused ? 'true' : 'false');
        toggle.setAttribute('aria-label', paused ? 'Reproducir carrusel' : 'Pausar carrusel');
    }

    createDots();
    render();
    updateToggle();

    if (prev) {
        prev.addEventListener('click', function () {
            move(-1);
            restart();
        });
    }

    if (next) {
        next.addEventListener('click', function () {
            move(1);
            restart();
        });
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            paused = !paused;
            updateToggle();
            restart();
        });
    }

    carousel.addEventListener('mouseenter', function () {
        hovering = true;
        stop();
    });

    carousel.addEventListener('mouseleave', function () {
        hovering = false;
        start();
    });

    carousel.addEventListener('focusin', function () {
        focused = true;
        stop();
    });

    carousel.addEventListener('focusout', function () {
        window.setTimeout(function () {
            if (!carousel.contains(document.activeElement)) {
                focused = false;
                start();
            }
        }, 0);
    });

    carousel.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            move(-1);
            restart();
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            move(1);
            restart();
        }
    });

    var touchStartX = null;

    carousel.addEventListener('touchstart', function (event) {
        if (event.touches.length === 1) {
            touchStartX = event.touches[0].clientX;
        }
    }, { passive: true });

    carousel.addEventListener('touchend', function (event) {
        if (touchStartX === null || event.changedTouches.length !== 1) {
            return;
        }

        var delta = event.changedTouches[0].clientX - touchStartX;
        touchStartX = null;

        if (Math.abs(delta) > 40) {
            move(delta < 0 ? 1 : -1);
            restart();
        }
    }, { passive: true });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stop();
        } else {
            start();
        }
    });

    if (typeof reducedQuery.addEventListener === 'function') {
        reducedQuery.addEventListener('change', function (event) {
            paused = event.matches;
            updateToggle();
            restart();
        });
    }

    start();
})();
