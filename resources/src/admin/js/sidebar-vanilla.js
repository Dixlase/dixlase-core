/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 * Alpine.js 非依存版
 */

// アコーディオンの開閉状態を管理
const accordionStates = {};

// 親アコーディオンの高さを再計算する
function updateParentAccordionHeight(element) {
    let parent = element.parentElement;
    let level = 0;
    while (parent) {
        // data-accordion-contentを持つ親要素を探す
        if (parent.id && parent.id.startsWith('accordion-content-')) {
            const parentKey = parent.id.replace('accordion-content-', '');
            const isOpen = accordionStates[parentKey];

            if (isOpen) {
                const oldHeight = parent.style.maxHeight;
                // 親アコーディオンは十分に大きな値に設定（子のアニメーションを妨げない）
                const newHeight = '2000px';
                parent.style.maxHeight = newHeight;
            }
            level++;
        }
        parent = parent.parentElement;
    }
}

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
            // scrollHeightを取得（現在の内容の高さ）
            const targetHeight = content.scrollHeight;
            content.style.maxHeight = targetHeight + 'px';
            content.style.opacity = '1';

            // 親アコーディオンの高さを即座に更新（アニメーション開始と同時）
            updateParentAccordionHeight(content);

            // アニメーション中も定期的に親の高さを更新（100ms, 200ms）
            setTimeout(() => {
                updateParentAccordionHeight(content);
            }, 100);

            setTimeout(() => {
                updateParentAccordionHeight(content);
            }, 200);

            // アニメーション完了後にも再更新（より正確な高さに）
            setTimeout(() => {
                updateParentAccordionHeight(content);
            }, 320);
        }, 10);
    } else {
        content.style.maxHeight = '0';
        content.style.opacity = '0';
        // 親アコーディオンの高さを即座に更新
        updateParentAccordionHeight(content);
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
            // 初期表示時はscrollHeightを使用
            setTimeout(() => {
                content.style.maxHeight = content.scrollHeight + 'px';
            }, 0);
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

    // Accordion initialized
}

// サイドバートグルの初期化（モバイル・デスクトップ共通）
function initSidebarToggle() {
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.querySelector('[data-mobile-sidebar]');
    const mainContent = document.querySelector('main');

    if (sidebarToggle && sidebar) {
        // localStorageから初期状態を読み込む
        let isOpen = localStorage.getItem('sidebarCollapsed') !== 'true';

        // 初期状態を適用（デスクトップのみ）
        if (window.innerWidth >= 768) { // md breakpoint
            if (isOpen) {
                sidebar.classList.remove('-translate-x-64');
                sidebar.classList.add('translate-x-0');
                if (mainContent) {
                    mainContent.classList.remove('md:ml-0');
                    mainContent.classList.add('md:ml-64');
                }
            } else {
                sidebar.classList.add('-translate-x-64');
                sidebar.classList.remove('translate-x-0');
                if (mainContent) {
                    mainContent.classList.add('md:ml-0');
                    mainContent.classList.remove('md:ml-64');
                }
            }
        }

        // トランジションを再有効化（サイドバーとメインコンテンツ）
        setTimeout(() => {
            sidebar.style.transition = '';
            sidebar.classList.add('transition-transform', 'duration-300');

            if (mainContent) {
                mainContent.style.transition = '';
                mainContent.classList.add('transition-all', 'duration-300');
            }
        }, 50);

        sidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            isOpen = !isOpen;

            // サイドバーの表示/非表示
            if (isOpen) {
                sidebar.classList.remove('-translate-x-64');
                sidebar.classList.add('translate-x-0');
            } else {
                sidebar.classList.add('-translate-x-64');
                sidebar.classList.remove('translate-x-0');
            }

            // メインコンテンツのマージン調整（デスクトップのみ）
            if (mainContent && window.innerWidth >= 768) {
                if (isOpen) {
                    mainContent.classList.remove('md:ml-0');
                    mainContent.classList.add('md:ml-64');
                } else {
                    mainContent.classList.add('md:ml-0');
                    mainContent.classList.remove('md:ml-64');
                }
            }

            // アイコンの切り替え
            const icon = sidebarToggle.querySelector('i');
            if (icon) {
                if (isOpen) {
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-left');
                } else {
                    icon.classList.remove('fa-chevron-left');
                    icon.classList.add('fa-chevron-right');
                }
            }

            // localStorageに保存
            localStorage.setItem('sidebarCollapsed', !isOpen);
        });

        // Sidebar toggle initialized

        // ウィンドウリサイズ時の処理
        let resizeTimer;
        let previousIsMobile = window.innerWidth < 768;

        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                const isMobile = window.innerWidth < 768;

                // モバイル⇔デスクトップの切り替え時のみ処理
                if (isMobile !== previousIsMobile) {
                    previousIsMobile = isMobile;

                    if (isMobile) {
                        // モバイル表示に切り替わったらサイドバーを非表示
                        sidebar.classList.add('-translate-x-64');
                        sidebar.classList.remove('translate-x-0');
                        isOpen = false;

                        // メインコンテンツのマージンをリセット
                        if (mainContent) {
                            mainContent.classList.remove('md:ml-0', 'md:ml-64');
                        }

                        // アイコンを右向きに
                        const icon = sidebarToggle.querySelector('i');
                        if (icon) {
                            icon.classList.remove('fa-chevron-left');
                            icon.classList.add('fa-chevron-right');
                        }
                    } else {
                        // デスクトップ表示に切り替わったらlocalStorageの状態を復元
                        const savedIsOpen = localStorage.getItem('sidebarCollapsed') !== 'true';
                        isOpen = savedIsOpen;

                        if (isOpen) {
                            sidebar.classList.remove('-translate-x-64');
                            sidebar.classList.add('translate-x-0');
                            if (mainContent) {
                                mainContent.classList.remove('md:ml-0');
                                mainContent.classList.add('md:ml-64');
                            }
                        } else {
                            sidebar.classList.add('-translate-x-64');
                            sidebar.classList.remove('translate-x-0');
                            if (mainContent) {
                                mainContent.classList.add('md:ml-0');
                                mainContent.classList.remove('md:ml-64');
                            }
                        }

                        // アイコンを更新
                        const icon = sidebarToggle.querySelector('i');
                        if (icon) {
                            if (isOpen) {
                                icon.classList.remove('fa-chevron-right');
                                icon.classList.add('fa-chevron-left');
                            } else {
                                icon.classList.remove('fa-chevron-left');
                                icon.classList.add('fa-chevron-right');
                            }
                        }
                    }
                }
            }, 100);
        });
    }
}

// 初期化
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        initSidebarAccordions();
        initSidebarToggle();
    }, 100);
});

// ページ遷移時は通常のページリロードが発生し、DOMContentLoadedで再初期化される
