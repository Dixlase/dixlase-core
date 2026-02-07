/**
 * Admin Bar - Pure JavaScript Implementation
 * 
 * 管理バーのユーザーメニュー機能を実装
 * Alpine.js非依存版（Livewire v4対応）
 */

// デスクトップ用ユーザーメニューの開閉
function initDesktopUserMenu() {
    const toggleButton = document.querySelector('[data-desktop-user-menu-toggle]');
    const menu = document.querySelector('[data-desktop-user-menu]');

    if (toggleButton && menu) {
        let isOpen = false;

        toggleButton.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            isOpen = !isOpen;

            if (isOpen) {
                menu.classList.remove('hidden');
            } else {
                menu.classList.add('hidden');
            }

            console.log('[Admin Bar] Desktop user menu toggled:', isOpen);
        });

        // メニュー外をクリックしたら閉じる
        document.addEventListener('click', function (e) {
            if (isOpen && !menu.contains(e.target) && !toggleButton.contains(e.target)) {
                isOpen = false;
                menu.classList.add('hidden');
                console.log('[Admin Bar] Desktop user menu closed (click outside)');
            }
        });

        console.log('[Admin Bar] Desktop user menu initialized');
    }
}

// モバイル用ユーザーメニューの開閉
function initMobileUserMenu() {
    const toggleButton = document.querySelector('[data-mobile-user-menu-toggle]');
    const menu = document.querySelector('[data-mobile-user-menu]');
    const closeButton = document.querySelector('[data-mobile-user-menu-close]');
    const overlay = document.querySelector('[data-mobile-overlay]');

    if (toggleButton && menu && overlay) {
        let isOpen = false;

        // 開く
        toggleButton.addEventListener('click', function (e) {
            e.preventDefault();
            isOpen = true;
            menu.classList.remove('translate-x-full');
            menu.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
            console.log('[Admin Bar] Mobile user menu opened');
        });

        // 閉じる
        const closeMenu = function () {
            isOpen = false;
            menu.classList.add('translate-x-full');
            menu.classList.remove('translate-x-0');
            overlay.classList.add('hidden');
            console.log('[Admin Bar] Mobile user menu closed');
        };

        if (closeButton) {
            closeButton.addEventListener('click', closeMenu);
        }

        // オーバーレイをクリックしたら閉じる
        overlay.addEventListener('click', closeMenu);

        console.log('[Admin Bar] Mobile user menu initialized');
    }
}

// 初期化
document.addEventListener('DOMContentLoaded', function () {
    console.log('[Admin Bar] DOMContentLoaded, initializing...');

    setTimeout(function () {
        initDesktopUserMenu();
        initMobileUserMenu();
        console.log('[Admin Bar] Vanilla JS initialized');
    }, 100);
});

// Livewire v4ではSPAナビゲーションが無効なので、livewire:navigatedイベントは不要
// ページ遷移時は通常のページリロードが発生し、DOMContentLoadedで再初期化される
