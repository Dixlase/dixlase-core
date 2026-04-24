/*
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

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
