/**
 * RiseFlow Dark / Light Mode Toggle
 * Persists preference to localStorage.
 * Works on all pages: homepage, auth pages, and the app.
 */
(function () {
    var STORAGE_KEY = 'riseflow_theme';
    var DARK_CLASS  = 'dark-mode';

    function getPreference() {
        var saved = localStorage.getItem(STORAGE_KEY);
        if (saved) return saved;
        // Respect OS preference on first visit
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    function applyTheme(theme) {
        if (theme === 'dark') {
            document.body.classList.add(DARK_CLASS);
        } else {
            document.body.classList.remove(DARK_CLASS);
        }
        updateIcons(theme);
        localStorage.setItem(STORAGE_KEY, theme);
    }

    function updateIcons(theme) {
        var isDark = theme === 'dark';
        var icons = document.querySelectorAll('#theme-icon, #theme-icon-home, .theme-icon-dynamic');
        for (var i = 0; i < icons.length; i++) {
            icons[i].textContent = isDark ? '☀️' : '🌙';
        }
        var btns = document.querySelectorAll('#theme-toggle, #theme-toggle-home');
        for (var j = 0; j < btns.length; j++) {
            btns[j].setAttribute('aria-pressed', isDark ? 'true' : 'false');
            btns[j].title = isDark ? 'Switch to light mode' : 'Switch to dark mode';
        }
    }

    function toggleTheme() {
        var current = localStorage.getItem(STORAGE_KEY) || 'light';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    }

    // ── Apply saved/preferred theme immediately (before render) ──
    var initialTheme = getPreference();
    // We can't add class before body exists if script is in <head>, so we
    // use a <style> injection trick to prevent FOUC.
    if (initialTheme === 'dark') {
        var style = document.createElement('style');
        style.id = 'rf-fouc-guard';
        style.textContent = 'body{visibility:hidden!important}';
        document.head.appendChild(style);
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Remove FOUC guard
        var guard = document.getElementById('rf-fouc-guard');
        if (guard) guard.parentNode.removeChild(guard);

        // Apply theme (adds/removes class)
        applyTheme(getPreference());

        // Attach click handlers
        var toggleBtns = document.querySelectorAll('#theme-toggle, #theme-toggle-home');
        for (var i = 0; i < toggleBtns.length; i++) {
            toggleBtns[i].addEventListener('click', toggleTheme);
        }
    });
})();
