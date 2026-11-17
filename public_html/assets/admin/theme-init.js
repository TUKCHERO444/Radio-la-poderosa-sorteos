(function () {
    'use strict';

    var storageKey = 'radiolp-admin-theme';
    var theme = 'light';

    try {
        var saved = window.localStorage.getItem(storageKey);

        if (saved === 'light' || saved === 'dark') {
            theme = saved;
        }
    } catch (error) {
        theme = 'light';
    }

    document.documentElement.setAttribute('data-theme', theme);

    var meta = document.querySelector('meta[name="theme-color"]');

    if (meta) {
        meta.setAttribute('content', theme === 'dark' ? '#0b1626' : '#f4f6f9');
    }
})();
