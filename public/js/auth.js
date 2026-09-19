(function () {
    'use strict';
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(show));
            button.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
            var icon = button.querySelector('i');
            if (icon) icon.className = show ? 'far fa-eye-slash' : 'far fa-eye';
        });
    });
    document.querySelectorAll('[data-auth-form]').forEach(function (form) {
        var submit = form.querySelector('[type="submit"]');
        if (!submit) return;
        var label = submit.querySelector('[data-submit-label]');
        var originalLabel = label ? label.textContent : '';
        var reset = function () {
            submit.disabled = false;
            form.removeAttribute('aria-busy');
            if (label) label.textContent = originalLabel;
        };
        form.addEventListener('submit', function (event) {
            if (event.defaultPrevented || !form.checkValidity()) return;
            if (submit.disabled) {
                event.preventDefault();
                return;
            }
            submit.disabled = true;
            form.setAttribute('aria-busy', 'true');
            if (label) label.textContent = submit.dataset.loadingLabel || 'Memproses...';
        });
        window.addEventListener('pageshow', reset);
    });
    var clocks = document.querySelectorAll('[data-clock]');
    if (clocks.length) {
        var updateClock = function () {
            var now = new Date();
            var time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false }).replace('.', ':');
            var date = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            clocks.forEach(function (clock) {
                clock.textContent = time;
                clock.setAttribute('datetime', now.toISOString());
                clock.setAttribute('title', date + ' · waktu perangkat');
            });
        };
        updateClock();
        window.setInterval(updateClock, 1000);
    }
})();
