/**
 * Livewire Notification System (Alpine.js不要)
 * 
 * Livewireイベントをリスニングして通知を表示します。
 * Alpine.jsの$store.notificationの代替として動作します。
 */

(function() {
    const containerId = 'livewire-notification-container';
    const iconId = 'livewire-notification-icon';
    const messageId = 'livewire-notification-message';
    const closeBtnId = 'livewire-notification-close';
    
    let hideTimeout = null;
    
    // 通知スタイル定義
    const styles = {
        success: {
            containerClass: 'fixed top-4 right-4 z-50 max-w-md p-4 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg shadow-lg',
            iconClass: 'fas fa-check-circle text-green-600 dark:text-green-400',
            closeClass: 'text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200'
        },
        error: {
            containerClass: 'fixed top-4 right-4 z-50 max-w-md p-4 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg shadow-lg',
            iconClass: 'fas fa-exclamation-circle text-red-600 dark:text-red-400',
            closeClass: 'text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200'
        },
        warning: {
            containerClass: 'fixed top-4 right-4 z-50 max-w-md p-4 bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg shadow-lg',
            iconClass: 'fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400',
            closeClass: 'text-yellow-600 dark:text-yellow-400 hover:text-yellow-800 dark:hover:text-yellow-200'
        },
        info: {
            containerClass: 'fixed top-4 right-4 z-50 max-w-md p-4 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg shadow-lg',
            iconClass: 'fas fa-info-circle text-blue-600 dark:text-blue-400',
            closeClass: 'text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200'
        }
    };
    
    /**
     * 通知を表示
     */
    function showNotification(type, msg, duration = 5000) {
        const container = document.getElementById(containerId);
        const icon = document.getElementById(iconId);
        const message = document.getElementById(messageId);
        const closeBtn = document.getElementById(closeBtnId);
        
        if (!container || !icon || !message || !closeBtn) {
            console.error('[Livewire Notification] Required elements not found');
            return;
        }
        
        const style = styles[type] || styles.info;
        
        // スタイルを適用
        container.className = style.containerClass;
        icon.className = style.iconClass + ' text-xl';
        closeBtn.className = style.closeClass;
        message.textContent = msg;
        
        // 表示アニメーション
        container.style.display = 'block';
        container.style.opacity = '0';
        container.style.transform = 'translateY(0.5rem)';
        container.style.transition = 'opacity 300ms ease-out, transform 300ms ease-out';
        
        setTimeout(() => {
            container.style.opacity = '1';
            container.style.transform = 'translateY(0)';
        }, 10);
        
        // 自動非表示
        if (hideTimeout) clearTimeout(hideTimeout);
        if (duration > 0) {
            hideTimeout = setTimeout(hideNotification, duration);
        }
    }
    
    /**
     * 通知を非表示
     */
    function hideNotification() {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        container.style.opacity = '0';
        container.style.transform = 'translateY(0.5rem)';
        container.style.transition = 'opacity 200ms ease-in, transform 200ms ease-in';
        
        setTimeout(() => {
            container.style.display = 'none';
        }, 200);
        
        if (hideTimeout) {
            clearTimeout(hideTimeout);
            hideTimeout = null;
        }
    }
    
    /**
     * 初期化
     */
    function init() {
        const closeBtn = document.getElementById(closeBtnId);
        if (closeBtn) {
            closeBtn.addEventListener('click', hideNotification);
        }
        
        // Livewireイベントリスナー
        if (typeof Livewire !== 'undefined') {
            Livewire.on('notification', function(data) {
                showNotification(data.type || 'info', data.message, data.duration || 5000);
            });
        } else {
            // Livewire初期化後に登録
            document.addEventListener('livewire:init', function() {
                Livewire.on('notification', function(data) {
                    showNotification(data.type || 'info', data.message, data.duration || 5000);
                });
            });
        }
    }
    
    // DOM読み込み後に初期化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // グローバル関数として公開（Alpine.jsストアの代替）
    window.showNotification = showNotification;
    window.hideNotification = hideNotification;
})();
