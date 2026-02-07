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
                // 表示：opacity 0 → 1
                menu.classList.remove('hidden');
                menu.style.opacity = '0';
                menu.style.transform = 'scale(0.95)';

                // 次のフレームでアニメーション開始
                requestAnimationFrame(() => {
                    menu.style.transition = 'opacity 100ms ease-out, transform 100ms ease-out';
                    menu.style.opacity = '1';
                    menu.style.transform = 'scale(1)';
                });
            } else {
                // 非表示：opacity 1 → 0
                menu.style.transition = 'opacity 75ms ease-in, transform 75ms ease-in';
                menu.style.opacity = '0';
                menu.style.transform = 'scale(0.95)';

                // アニメーション完了後にhiddenを追加
                setTimeout(() => {
                    menu.classList.add('hidden');
                }, 75);
            }

            // Desktop user menu toggled
        });

        // メニュー外をクリックしたら閉じる
        document.addEventListener('click', function (e) {
            if (isOpen && !menu.contains(e.target) && !toggleButton.contains(e.target)) {
                isOpen = false;

                // 非表示：opacity 1 → 0
                menu.style.transition = 'opacity 75ms ease-in, transform 75ms ease-in';
                menu.style.opacity = '0';
                menu.style.transform = 'scale(0.95)';

                // アニメーション完了後にhiddenを追加
                setTimeout(() => {
                    menu.classList.add('hidden');
                }, 75);

                // Desktop user menu closed
            }
        });

        // Desktop user menu initialized
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

        // 初期状態を確実に設定（トランジションなし）
        menu.style.transition = 'none';
        menu.classList.add('translate-x-full');
        menu.classList.remove('translate-x-0');
        overlay.classList.add('hidden');

        // トランジションを再有効化
        setTimeout(() => {
            menu.style.transition = '';
        }, 50);

        // トグル（開く/閉じる）
        toggleButton.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            isOpen = !isOpen;

            if (isOpen) {
                menu.classList.remove('translate-x-full');
                menu.classList.add('translate-x-0');
                overlay.classList.remove('hidden');
                // Mobile user menu opened
            } else {
                menu.classList.add('translate-x-full');
                menu.classList.remove('translate-x-0');
                overlay.classList.add('hidden');
                // Mobile user menu closed
            }
        });

        // 閉じる
        const closeMenu = function () {
            if (isOpen) {
                isOpen = false;
                menu.classList.add('translate-x-full');
                menu.classList.remove('translate-x-0');
                overlay.classList.add('hidden');
                // Mobile user menu closed
            }
        };

        if (closeButton) {
            closeButton.addEventListener('click', function (e) {
                e.preventDefault();
                closeMenu();
            });
        }

        // オーバーレイをクリックしたら閉じる
        overlay.addEventListener('click', closeMenu);

        // Mobile user menu initialized
    }
}

// 初期化
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        initDesktopUserMenu();
        initMobileUserMenu();
    }, 100);
});

// Livewire v4ではSPAナビゲーションが無効なので、livewire:navigatedイベントは不要
// ページ遷移時は通常のページリロードが発生し、DOMContentLoadedで再初期化される
