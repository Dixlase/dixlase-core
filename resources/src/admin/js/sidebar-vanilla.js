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
 * Admin Sidebar - Pure JavaScript Implementation
 * 
 * サイドバーのアコーディオン機能を実装
 * Alpine.js非依存版（Livewire v4対応）
 */

// アコーディオンの開閉状態を管理
const accordionStates = {};

// アコーディオンの開閉を切り替える
function toggleAccordion(key, button, content) {
    const isOpen = accordionStates[key] || false;
    const newState = !isOpen;

    accordionStates[key] = newState;

    // ボタンのaria-expanded属性を更新
    button.setAttribute('aria-expanded', newState);

    // 矢印アイコンの回転
    const arrow = button.querySelector('svg');
    if (arrow) {
        if (newState) {
            arrow.classList.add('rotate-180');
        } else {
            arrow.classList.remove('rotate-180');
        }
    }

    // コンテンツの表示/非表示
    if (newState) {
        content.style.display = 'block';
        // アニメーション用に少し遅延
        setTimeout(() => {
            content.style.maxHeight = content.scrollHeight + 'px';
            content.style.opacity = '1';
        }, 10);
    } else {
        content.style.maxHeight = '0';
        content.style.opacity = '0';
        setTimeout(() => {
            content.style.display = 'none';
        }, 300);
    }
}

// サイドバーのアコーディオンを初期化
function initSidebarAccordions() {
    // すべてのアコーディオンボタンを取得
    const accordionButtons = document.querySelectorAll('[data-accordion-button]');

    accordionButtons.forEach(button => {
        const key = button.getAttribute('data-accordion-key');
        const contentId = button.getAttribute('data-accordion-content');
        const content = document.getElementById(contentId);

        if (!content) {
            console.warn('[Sidebar] Content not found for key:', key);
            return;
        }

        // 初期状態を設定
        const initialState = button.getAttribute('data-accordion-open') === 'true';
        accordionStates[key] = initialState;

        // 初期表示を設定
        if (initialState) {
            content.style.display = 'block';
            content.style.maxHeight = content.scrollHeight + 'px';
            content.style.opacity = '1';
            button.setAttribute('aria-expanded', 'true');
            const arrow = button.querySelector('svg');
            if (arrow) {
                arrow.classList.add('rotate-180');
            }
        } else {
            content.style.display = 'none';
            content.style.maxHeight = '0';
            content.style.opacity = '0';
            button.setAttribute('aria-expanded', 'false');
        }

        // CSSトランジションを設定
        content.style.transition = 'max-height 300ms ease-in-out, opacity 300ms ease-in-out';
        content.style.overflow = 'hidden';

        // クリックイベントを設定
        button.addEventListener('click', function (e) {
            e.preventDefault();
            toggleAccordion(key, button, content);
        });
    });

    console.log('[Sidebar] Accordion initialized, states:', accordionStates);
}

// モバイルサイドバートグルの初期化
function initMobileSidebarToggle() {
    const mobileToggle = document.querySelector('[data-mobile-sidebar-toggle]');
    const sidebar = document.querySelector('[data-mobile-sidebar]');

    if (mobileToggle && sidebar) {
        let isOpen = false;

        mobileToggle.addEventListener('click', function (e) {
            e.preventDefault();
            isOpen = !isOpen;

            // サイドバーの表示/非表示
            if (isOpen) {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
            } else {
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
            }

            // アイコンの切り替え
            const icon = mobileToggle.querySelector('i');
            if (icon) {
                if (isOpen) {
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-left');
                } else {
                    icon.classList.remove('fa-chevron-left');
                    icon.classList.add('fa-chevron-right');
                }
            }
        });

        console.log('[Sidebar] Mobile toggle initialized');
    }
}

// 初期化
document.addEventListener('DOMContentLoaded', function () {
    console.log('[Sidebar] DOMContentLoaded, initializing...');

    setTimeout(function () {
        initSidebarAccordions();
        initMobileSidebarToggle();
        console.log('[Sidebar] Vanilla JS initialized');
    }, 100);
});

// Livewire v4ではSPAナビゲーションが無効なので、livewire:navigatedイベントは不要
// ページ遷移時は通常のページリロードが発生し、DOMContentLoadedで再初期化される
