/**
 * CSP Safe Mode Banner Layout Adjustment
 * 
 * セーフモードバナーが表示されている場合、Admin Barと各要素を下にずらす
 */

document.addEventListener('DOMContentLoaded', function() {
    const banner = document.getElementById('csp-safe-mode-banner');
    const adminBar = document.getElementById('admin-bar');
    
    if (banner) {
        const bannerHeight = banner.offsetHeight;
        
        // CSSカスタムプロパティにバナーの高さを設定
        document.documentElement.style.setProperty('--csp-banner-height', bannerHeight + 'px');
        
        // Admin Barをバナーの下に配置
        if (adminBar) {
            adminBar.style.top = bannerHeight + 'px';
        }
        
        // サイドバーとメインコンテンツのmargin-topを調整
        const sidebar = document.querySelector('aside[role="navigation"]');
        const mainContent = document.querySelector('main');
        
        if (sidebar) {
            const currentMarginTop = parseFloat(getComputedStyle(sidebar).marginTop) || 0;
            sidebar.style.marginTop = (currentMarginTop + bannerHeight) + 'px';
        }
        
        if (mainContent) {
            const currentMarginTop = parseFloat(getComputedStyle(mainContent).marginTop) || 0;
            mainContent.style.marginTop = (currentMarginTop + bannerHeight) + 'px';
        }
    }
});
