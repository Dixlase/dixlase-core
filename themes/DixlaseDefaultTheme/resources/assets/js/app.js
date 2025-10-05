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

// =============================================================================
// Dixlase Default Theme - Main JavaScript
// =============================================================================

/**
 * ダークモード切り替え
 */
function initDarkMode() {
    const darkModeToggle = document.querySelector('[data-dark-mode-toggle]');
    
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            
            // ローカルストレージに保存
            const isDark = document.documentElement.classList.contains('dark');
            localStorage.setItem('darkMode', isDark ? 'dark' : 'light');
        });
    }
    
    // 初期状態の設定
    const savedMode = localStorage.getItem('darkMode');
    if (savedMode === 'dark') {
        document.documentElement.classList.add('dark');
    } else if (savedMode === 'light') {
        document.documentElement.classList.remove('dark');
    }
}

/**
 * スクロールトップボタン
 */
function initScrollToTop() {
    const scrollTopBtn = document.querySelector('[data-scroll-top]');
    
    if (scrollTopBtn) {
        // スクロール位置に応じて表示/非表示
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollTopBtn.classList.remove('hidden');
            } else {
                scrollTopBtn.classList.add('hidden');
            }
        });
        
        // クリックでトップへスクロール
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

/**
 * 外部リンクに target="_blank" を自動追加
 */
function initExternalLinks() {
    const links = document.querySelectorAll('a[href^="http"]');
    
    links.forEach(link => {
        const url = new URL(link.href);
        
        // 外部リンクの場合
        if (url.hostname !== window.location.hostname) {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        }
    });
}

/**
 * 画像の遅延読み込み
 */
function initLazyLoading() {
    if ('loading' in HTMLImageElement.prototype) {
        // ネイティブ lazy loading をサポート
        const images = document.querySelectorAll('img[data-src]');
        images.forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    } else {
        // Intersection Observer でフォールバック
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });
        
        const images = document.querySelectorAll('img[data-src]');
        images.forEach(img => imageObserver.observe(img));
    }
}

/**
 * ページ読み込み時の初期化
 */
document.addEventListener('DOMContentLoaded', () => {
    initDarkMode();
    initScrollToTop();
    initExternalLinks();
    initLazyLoading();
    
    console.log('Dixlase Default Theme Loaded');
});
