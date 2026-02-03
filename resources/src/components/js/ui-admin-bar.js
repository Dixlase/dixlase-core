/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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
        }
    };
};
