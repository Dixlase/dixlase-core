/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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
