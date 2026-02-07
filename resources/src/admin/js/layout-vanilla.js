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

// メインコンテンツの左マージンを更新する
function updateMainContentMargin(collapsed) {
    const mainContent = document.querySelector('main');

    if (mainContent) {
        if (collapsed) {
            mainContent.classList.add('md:ml-0');
            mainContent.classList.remove('md:ml-72');
        } else {
            mainContent.classList.remove('md:ml-0');
            mainContent.classList.add('md:ml-72');
        }
    }
}

// サイドバーの状態変更を監視
document.addEventListener('DOMContentLoaded', function () {
    console.log('[Admin Layout] DOMContentLoaded, initializing...');

    // localStorageから保存された状態を読み込む
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

    // 初期状態を適用
    updateMainContentMargin(sidebarCollapsed);

    console.log('[Admin Layout] Main content margin initialized, sidebar collapsed:', sidebarCollapsed);
});
