/**
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
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * Theme Switcher Component
 * Manages appearance theme (light/dark/auto) switching functionality
 */

/**
 * Alpine.js用の外観テーマ管理関数
 * @param {string} defaultValue - データベースから取得したデフォルト値
 * @returns {Object} Alpine.jsコンポーネント
 */
window.appearanceMode = function (defaultValue) {
    // FOUC防止: Alpine初期化時に正しいdark/lightクラスが設定されるよう事前計算
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initialIsDark = defaultValue === '2' || (defaultValue === '0' && prefersDark);

    return {
        theme: defaultValue,
        isDark: initialIsDark,
        themeReady: false,

        applyTheme(enableTransition = false) {
            this.isDark = this.theme === '2' || (this.theme === '0' && window.matchMedia('(prefers-color-scheme: dark)').matches);

            if (document.querySelector('[data-profile-theme]')) {
                localStorage.setItem('appearance', this.theme);
            }

            if (enableTransition) {
                this.themeReady = true;
            }

            document.documentElement.classList.toggle('dark', this.isDark);
            document.documentElement.classList.toggle('light', !this.isDark);
        },

        init() {
            if (document.querySelector('[data-profile-theme]')) {
                const storedTheme = localStorage.getItem('appearance');
                if (storedTheme !== null) {
                    this.theme = storedTheme;
                }
            }

            this.applyTheme(false);

            document.querySelectorAll('input[name="appearance"]').forEach((el) => {
                if (el.closest('[data-profile-theme]') || el.closest('[data-member-theme]')) {
                    return;
                }
                el.addEventListener('change', (e) => {
                    this.theme = e.target.value;
                    this.applyTheme(true);
                });
            });
        }
    };
};

/**
 * テーマストアを初期化（後方互換性のため）
 */
function initThemeStore() {
    const defaultAppearance = document.body?.dataset.defaultAppearance ?? '0';
    const storedTheme = localStorage.getItem('appearance');
    const finalTheme = storedTheme ?? defaultAppearance;

    window.themeStore = {
        theme: finalTheme,
        isDark: false,

        applyTheme() {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.isDark = this.theme === '2' || (this.theme === '0' && prefersDark);

            localStorage.setItem('appearance', this.theme);
            document.documentElement.classList.toggle('dark', this.isDark);
            document.documentElement.classList.toggle('light', !this.isDark);
        }
    };

    // 初期テーマを適用
    window.themeStore.applyTheme();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initThemeStore);
} else {
    initThemeStore();
}
