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
 * Alpine.js Notification Store
 * 
 * Usage:
 * - showNotification('success', 'Message')
 * - showSuccess('Message')
 * - showError('Message')
 * - showWarning('Message')
 * - showInfo('Message')
 */

document.addEventListener('alpine:init', () => {
    Alpine.store('notification', {
        show: false,
        type: 'info',
        message: '',
        timer: null,

        /**
         * 通知を表示
         * @param {string} type - 'success', 'error', 'warning', 'info'
         * @param {string} message - 表示するメッセージ
         * @param {number} duration - 表示時間（ミリ秒、デフォルト: 5000）
         */
        display(type, message, duration = 5000) {
            // 既存のタイマーをクリア
            if (this.timer) {
                clearTimeout(this.timer);
            }

            // 通知を設定
            this.type = type;
            this.message = message;
            this.show = true;

            // 自動で非表示
            if (duration > 0) {
                this.timer = setTimeout(() => {
                    this.hide();
                }, duration);
            }
        },

        /**
         * 通知を非表示
         */
        hide() {
            this.show = false;
            if (this.timer) {
                clearTimeout(this.timer);
                this.timer = null;
            }
        },

        /**
         * 通知のスタイルクラスを取得
         */
        get containerClass() {
            const baseClass = 'fixed top-16 right-4 z-[9999] p-4 rounded-lg shadow-lg max-w-sm';
            const typeClasses = {
                success: 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200',
                error: 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200',
                warning: 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200',
                info: 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200'
            };
            return `${baseClass} ${typeClasses[this.type] || typeClasses.info}`;
        },

        /**
         * アイコンクラスを取得
         */
        get iconClass() {
            const icons = {
                success: 'fas fa-check-circle text-green-400',
                error: 'fas fa-times-circle text-red-400',
                warning: 'fas fa-exclamation-triangle text-yellow-400',
                info: 'fas fa-info-circle text-blue-400'
            };
            return icons[this.type] || icons.info;
        },

        /**
         * 閉じるボタンのクラスを取得
         */
        get closeButtonClass() {
            const colors = {
                success: 'text-green-400 hover:text-green-500',
                error: 'text-red-400 hover:text-red-500',
                warning: 'text-yellow-400 hover:text-yellow-500',
                info: 'text-blue-400 hover:text-blue-500'
            };
            return `inline-flex ${colors[this.type] || colors.info}`;
        }
    });
});

/**
 * グローバル関数（後方互換性のため）
 */
window.showNotification = function (type, message, duration = 5000) {
    Alpine.store('notification').display(type, message, duration);
};

window.showSuccess = function (message, duration = 5000) {
    Alpine.store('notification').display('success', message, duration);
};

window.showError = function (message, duration = 5000) {
    Alpine.store('notification').display('error', message, duration);
};

window.showWarning = function (message, duration = 5000) {
    Alpine.store('notification').display('warning', message, duration);
};

window.showInfo = function (message, duration = 5000) {
    Alpine.store('notification').display('info', message, duration);
};
