/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
 * Admin Layout Alpine.js Component
 * 
 * 管理画面レイアウトの状態管理（サイドバー開閉、ユーザーメニュー等）
 */
window.adminLayout = function() {
    return {
        openSidebar: false,
        openUserMenu: false,
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        sidebarReady: false,

        // 右サイドバー（ページエディタ等で使用）
        rightSidebarActive: false,
        rightSidebarCollapsed: false,
        rightSidebarReady: false,

        init() {
            // モバイル（< lg ブレークポイント）では右サイドバーをデフォルトで折りたたむ
            if (window.innerWidth < 1024) {
                this.rightSidebarCollapsed = true;
            }

            // サイドバーの準備完了フラグを次のティックで設定
            this.$nextTick(() => {
                this.sidebarReady = true;
                this.rightSidebarReady = true;
            });

            // サイドバーの折りたたみ状態をlocalStorageに保存
            this.$watch('sidebarCollapsed', value => {
                localStorage.setItem('sidebarCollapsed', value);
            });

        },

        /**
         * 右サイドバーの開閉をトグルする
         */
        toggleRightSidebar() {
            this.rightSidebarCollapsed = !this.rightSidebarCollapsed;
        }
    };
};
