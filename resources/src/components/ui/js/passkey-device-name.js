/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * Passkey Device Name Modal Component
 * Alpine.js component for passkey device name input modal
 */

// Alpine.js component for passkey device name modal
window.passkeyDeviceNameModal = function () {
    return {
        show: false,
        deviceName: '',
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

                        // コールバック処理
                        if (this.callback) {
                            const cb = this.callback;
                            this.callback = null;
                            cb(null);
                        }
                    }
                }
            });
        },

        open(callback, defaultValue = '') {
            this.callback = callback;
            this.deviceName = defaultValue;
            this.show = true;

            // フォーカスを設定（少し遅延）
            this.$nextTick(() => {
                setTimeout(() => {
                    const input = this.$el.querySelector('input[type="text"]');
                    if (input) {
                        input.focus();
                        // デフォルト値がある場合は全選択
                        if (defaultValue) {
                            input.select();
                        }
                    }
                }, 100);
            });
        },

        confirm() {
            const value = this.deviceName.trim();
            this.show = false;

            if (this.callback) {
                const cb = this.callback;
                this.callback = null;
                cb(value);
            }
        },

        cancel() {
            this.show = false;

            if (this.callback) {
                const cb = this.callback;
                this.callback = null;
                cb(null);
            }
        },

        handleEnter(event) {
            if (event.key === 'Enter' && this.show) {
                event.preventDefault();
                this.confirm();
            }
        }
    };
};

// Backward compatibility: Global PasskeyDeviceNameModal object
if (typeof window.PasskeyDeviceNameModal === 'undefined') {
    window.PasskeyDeviceNameModal = {
        callbacks: {},

        open: function (modalId, callback, defaultValue = '') {
            const modalElement = document.getElementById(modalId);
            if (modalElement && modalElement._x_dataStack) {
                const alpineData = modalElement._x_dataStack[0];
                if (alpineData && typeof alpineData.open === 'function') {
                    alpineData.open(callback, defaultValue);
                }
            }
        },

        confirm: function (modalId) {
            const modalElement = document.getElementById(modalId);
            if (modalElement && modalElement._x_dataStack) {
                const alpineData = modalElement._x_dataStack[0];
                if (alpineData && typeof alpineData.confirm === 'function') {
                    alpineData.confirm();
                }
            }
        },

        cancel: function (modalId) {
            const modalElement = document.getElementById(modalId);
            if (modalElement && modalElement._x_dataStack) {
                const alpineData = modalElement._x_dataStack[0];
                if (alpineData && typeof alpineData.cancel === 'function') {
                    alpineData.cancel();
                }
            }
        }
    };
}
