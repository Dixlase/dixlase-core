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
 * Admin Layout - Pure JavaScript Implementation
 * 
 * 管理画面レイアウトの状態管理（サイドバー開閉、ユーザーメニュー等）
 * Alpine.js非依存版（Livewire v4対応）
 */

// サイドバーの状態管理
let sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

// サイドバーの状態を更新する関数
function updateSidebarState(collapsed) {
    sidebarCollapsed = collapsed;
    localStorage.setItem('sidebarCollapsed', collapsed);

    // サイドバー要素
    const sidebar = document.querySelector('aside[role="navigation"]');
    // トグルボタン（デスクトップ用）
    const toggleButton = document.querySelector('[data-desktop-sidebar-toggle]');
    // メインコンテンツ
    const mainContent = document.querySelector('main');

    if (sidebar) {
        if (collapsed) {
            sidebar.classList.add('-translate-x-64');
            sidebar.classList.remove('translate-x-0');
        } else {
            sidebar.classList.remove('-translate-x-64');
            sidebar.classList.add('translate-x-0');
        }
    }

    if (toggleButton) {
        if (collapsed) {
            toggleButton.classList.add('translate-x-0');
            toggleButton.classList.remove('translate-x-64');
        } else {
            toggleButton.classList.remove('translate-x-0');
            toggleButton.classList.add('translate-x-64');
        }

        // デスクトップトグルボタンのアイコンの切り替え
        const icon = toggleButton.querySelector('i');
        if (icon) {
            if (collapsed) {
                icon.classList.remove('fa-chevron-left');
                icon.classList.add('fa-chevron-right');
            } else {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-left');
            }
        }
    }

    if (mainContent) {
        if (collapsed) {
            mainContent.classList.add('md:ml-0');
            mainContent.classList.remove('md:ml-64');
        } else {
            mainContent.classList.remove('md:ml-0');
            mainContent.classList.add('md:ml-64');
        }
    }
}

// サイドバートグルボタンのイベントリスナーを設定
function initSidebarToggle() {
    const toggleButton = document.querySelector('[data-desktop-sidebar-toggle]');

    if (toggleButton) {
        // 既存のイベントリスナーを削除（重複防止）
        toggleButton.replaceWith(toggleButton.cloneNode(true));
        const newButton = document.querySelector('[data-desktop-sidebar-toggle]');

        newButton.addEventListener('click', function (e) {
            e.preventDefault();
            console.log('[Admin Layout] Toggle button clicked, current state:', sidebarCollapsed);
            updateSidebarState(!sidebarCollapsed);
        });

        console.log('[Admin Layout] Toggle button event listener attached');
    } else {
        console.warn('[Admin Layout] Toggle button not found');
    }
}

// 初期化
document.addEventListener('DOMContentLoaded', function () {
    console.log('[Admin Layout] DOMContentLoaded, initializing...');

    // 少し遅延させて確実にDOMが準備できてから実行
    setTimeout(function () {
        // 初期状態を適用
        updateSidebarState(sidebarCollapsed);

        // トグルボタンのイベントリスナーを設定
        initSidebarToggle();

        console.log('[Admin Layout] Vanilla JS initialized, sidebar collapsed:', sidebarCollapsed);
    }, 150);
});

// Livewire v4ではSPAナビゲーションが無効なので、livewire:navigatedイベントは不要
// ページ遷移時は通常のページリロードが発生し、DOMContentLoadedで再初期化される
