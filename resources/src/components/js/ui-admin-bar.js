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
 * Admin Bar Component
 * Alpine.js component for admin bar initialization and management
 */

/**
 * Alpine.js用の管理バー初期化関数
 * @returns {Object} Alpine.jsコンポーネント
 */
window.adminBar = function () {
    return {
        // サイドバーとメニューの開閉状態
        openSidebar: false,
        openUserMenu: false,
        userMenuOpen: false,

        init() {
            // 管理バーが存在する場合、bodyにクラスを追加
            this.$nextTick(() => {
                if (document.getElementById('admin-bar')) {
                    document.body.classList.add('has-admin-bar');
                }
            });

            // 外観設定ページかどうかを判定
            const isAppearancePage = window.location.pathname.includes('/profile/appearance');

            if (isAppearancePage) {
                // 外観設定ページ: 初回表示後にトランジションを追加
                setTimeout(() => {
                    this.$el.classList.add('transition-colors', 'duration-[150ms]');
                }, 100);
            }
        },

        /**
         * Close both mobile drawers (the overlay behind them was clicked).
         */
        closeDrawers() {
            this.openSidebar = false;
            this.openUserMenu = false;
        },

        /**
         * "Back to the editor" on a preview (#1079): close the preview tab,
         * which returns to the editor's tab. A browser closes only a tab a
         * script opened, so when this one stays (opened from a bookmark,
         * or with a middle click), the link opens the edit screen instead.
         *
         * @param {MouseEvent} event
         */
        backToEditor(event) {
            const href = event.currentTarget.href;
            window.close();
            setTimeout(() => {
                if (!window.closed) {
                    window.location.href = href;
                }
            }, 150);
        },
    };
};
