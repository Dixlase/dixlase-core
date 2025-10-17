{{--
This file is part of MySoftware.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}


<script>
    /**
     * 通知を表示する関数
     * @param {string} type - 'success' または 'error'
     * @param {string} message - 表示するメッセージ
     * @param {number} duration - 表示時間（ミリ秒、デフォルト: 5000）
     */
    function showNotification(type, message, duration = 5000) {
        // 既存の通知を削除
        const existingNotification = document.getElementById('app-notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        // 通知要素を作成
        const notification = document.createElement('div');
        notification.id = 'app-notification';
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-opacity duration-300 ${
            type === 'success' 
                ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200'
                : type === 'error'
                ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200'
                : type === 'warning'
                ? 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200'
                : 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200'
        }`;
        
        // アイコンを選択
        const icon = type === 'success' 
            ? 'fas fa-check-circle text-green-400' 
            : type === 'error'
            ? 'fas fa-times-circle text-red-400'
            : type === 'warning'
            ? 'fas fa-exclamation-triangle text-yellow-400'
            : 'fas fa-info-circle text-blue-400';
        
        const closeButtonColor = type === 'success'
            ? 'text-green-400 hover:text-green-500'
            : type === 'error'
            ? 'text-red-400 hover:text-red-500'
            : type === 'warning'
            ? 'text-yellow-400 hover:text-yellow-500'
            : 'text-blue-400 hover:text-blue-500';
        
        notification.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="${icon} text-xl"></i>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <div class="ml-auto pl-3">
                    <button onclick="this.closest('#app-notification').remove()" class="inline-flex ${closeButtonColor}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        
        // ボディに追加
        document.body.appendChild(notification);
        
        // フェードイン効果
        setTimeout(() => {
            notification.style.opacity = '1';
        }, 10);
        
        // 自動で削除
        if (duration > 0) {
            setTimeout(() => {
                if (notification && notification.parentElement) {
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        if (notification.parentElement) {
                            notification.remove();
                        }
                    }, 300);
                }
            }, duration);
        }
    }
    
    /**
     * 成功通知のショートカット
     */
    function showSuccess(message, duration = 5000) {
        showNotification('success', message, duration);
    }
    
    /**
     * エラー通知のショートカット
     */
    function showError(message, duration = 5000) {
        showNotification('error', message, duration);
    }
    
    /**
     * 警告通知のショートカット
     */
    function showWarning(message, duration = 5000) {
        showNotification('warning', message, duration);
    }
    
    /**
     * 情報通知のショートカット
     */
    function showInfo(message, duration = 5000) {
        showNotification('info', message, duration);
    }
</script>
