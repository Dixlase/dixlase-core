/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * Passkey Result Modal Component
 * Alpine.js component for passkey result display modal
 */

// Alpine.js component for passkey result modal
window.passkeyResultModal = function () {
    return {
        show: false,
        type: 'success',
        title: '',
        message: '',
        callback: null,

        init() {
            // スライドとフェードアニメーション
            this.$watch('show', (value) => {
                const container = this.$el.querySelector('.modal-container');
                if (container) {
                    if (value) {
                        // 表示時: 下から上へスライド + フェードイン
                        container.style.transform = 'translateY(1rem)';
                        container.style.opacity = '0';
                        requestAnimationFrame(() => {
                            requestAnimationFrame(() => {
                                container.style.transform = 'translateY(0)';
                                container.style.opacity = '1';
                            });
                        });
                    } else {
                        // 非表示時: 下へスライド + フェードアウト
                        container.style.transform = 'translateY(1rem)';
                        container.style.opacity = '0';
                    }
                }
            });
        },

        open(type, title, message, callback) {
            this.type = type;
            this.title = title;
            this.message = message;
            this.callback = callback;
            this.show = true;
        },

        close() {
            this.show = false;

            if (this.callback) {
                const cb = this.callback;
                this.callback = null;
                cb();
            }
        },

        get iconClass() {
            const iconMap = {
                success: 'fa-check-circle',
                danger: 'fa-times-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            return 'fas ' + (iconMap[this.type] || iconMap.info);
        },

        get modalIconClass() {
            return 'modal-icon modal-icon--' + this.type;
        }
    };
};

// Backward compatibility: Global PasskeyResultModal object
if (typeof window.PasskeyResultModal === 'undefined') {
    window.PasskeyResultModal = {
        callbacks: {},

        showSuccess: function (modalId, title, message, callback) {
            this.show(modalId, 'success', title, message, callback);
        },

        showError: function (modalId, title, message, callback) {
            this.show(modalId, 'danger', title, message, callback);
        },

        show: function (modalId, type, title, message, callback) {
            const modalElement = document.getElementById(modalId);
            if (modalElement && modalElement._x_dataStack) {
                const alpineData = modalElement._x_dataStack[0];
                if (alpineData && typeof alpineData.open === 'function') {
                    alpineData.open(type, title, message, callback);
                }
            }
        },

        close: function (modalId) {
            const modalElement = document.getElementById(modalId);
            if (modalElement && modalElement._x_dataStack) {
                const alpineData = modalElement._x_dataStack[0];
                if (alpineData && typeof alpineData.close === 'function') {
                    alpineData.close();
                }
            }
        }
    };
}
