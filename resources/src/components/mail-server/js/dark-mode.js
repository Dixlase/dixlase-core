/**
 * Mail Server Verification Pages - Dark Mode Detection
 * Shared utility for applying dark mode before page render
 */

(function () {
    'use strict';

    /**
     * Apply dark mode based on parent window, localStorage, or system preference
     */
    function applyDarkMode() {
        let theme = null;

        // 1. Try to get theme from parent window
        if (window.opener && !window.opener.closed) {
            try {
                theme = window.opener.localStorage.getItem('theme');
            } catch (e) {
                console.log('[Dark Mode] Failed to get theme from parent window:', e);
            }
        }

        // 2. Try to get theme from own localStorage
        if (!theme) {
            theme = localStorage.getItem('theme');
        }

        // 3. Fall back to system preference
        if (!theme) {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                theme = 'dark';
            } else {
                theme = 'light';
            }
        }

        // 4. Apply theme
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        console.log('[Dark Mode] Applied theme:', theme);
    }

    // Execute immediately to prevent flash
    applyDarkMode();
})();
