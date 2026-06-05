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
 * Alpine.js Modal Component
 * 
 * Usage in Blade:
 * <div x-data="modal()" ...>
 * 
 * Global functions (backward compatibility):
 * - openModal('modalId')
 * - closeModal('modalId')
 */

window.modal = function () {
    return {
        show: false,
        submitting: false,

        /**
         * 初期化
         */
        init() {
            // モーダルコンテナを取得
            this.$watch('show', (value) => {
                const container = this.$el.querySelector('.modal-container');
                if (container) {
                    if (value) {
                        // 開く: 初期位置を下に設定してからアニメーション
                        container.style.transform = 'translateY(1rem)';
                        container.style.opacity = '0';
                        // 次のフレームで元の位置に戻す
                        requestAnimationFrame(() => {
                            requestAnimationFrame(() => {
                                container.style.transform = 'translateY(0)';
                                container.style.opacity = '1';
                            });
                        });
                        // スクロール状態を検出してフッターボーダーを切り替え
                        requestAnimationFrame(() => this._updateScrollBorder());
                    } else {
                        // 閉じる: 下に移動
                        container.style.transform = 'translateY(1rem)';
                        container.style.opacity = '0';
                    }
                }
            });

            // コンテンツの変更を監視してスクロール状態を更新
            this._setupScrollObserver();
        },

        /**
         * スクロール状態に基づいてフッターボーダーを更新
         */
        _updateScrollBorder() {
            const content = this.$el.querySelector('.modal-content');
            const actions = this.$el.querySelector('.modal-actions');
            if (!content || !actions) return;

            const hasScroll = content.scrollHeight > content.clientHeight;
            actions.classList.toggle('modal-actions--has-scroll', hasScroll);
        },

        /**
         * ResizeObserver + MutationObserver でコンテンツ変更を監視
         */
        _setupScrollObserver() {
            const content = this.$el.querySelector('.modal-content');
            if (!content) return;

            const update = () => {
                if (this.show) {
                    this._updateScrollBorder();
                }
            };

            if (typeof ResizeObserver !== 'undefined') {
                const ro = new ResizeObserver(update);
                ro.observe(content);
            }

            const mo = new MutationObserver(update);
            mo.observe(content, { childList: true, subtree: true, characterData: true });
        },

        /**
         * モーダルを開く
         */
        open() {
            this.show = true;
            this.submitting = false;
        },

        /**
         * モーダルを閉じる（送信中は無効）
         */
        close() {
            if (this.submitting) {
                return;
            }
            this.show = false;
        },

        /**
         * ESCキーでモーダルを閉じる（送信中は無効）
         */
        handleEscape(event) {
            if (event.key === 'Escape' && this.show && !this.submitting) {
                this.close();
            }
        },

        /**
         * 背景クリックでモーダルを閉じる（送信中は無効）
         */
        closeOnBackdrop(dismissible) {
            if (dismissible && !this.submitting) {
                this.close();
            }
        }
    };
};

/**
 * グローバル関数（後方互換性のため）
 */
window.openModal = function (modalId) {
    const modalElement = document.getElementById(modalId);

    if (modalElement) {
        // Alpine.jsが初期化されているか確認
        if (typeof Alpine !== 'undefined' && modalElement._x_dataStack) {
            // Alpine.jsのデータスタックから最初のコンテキストを取得
            const alpineData = modalElement._x_dataStack[0];

            if (alpineData && typeof alpineData.open === 'function') {
                alpineData.open();
            }
        }
    }
};

window.closeModal = function (modalId) {
    const modalElement = document.getElementById(modalId);

    if (modalElement) {
        if (typeof Alpine !== 'undefined' && modalElement._x_dataStack) {
            const alpineData = modalElement._x_dataStack[0];
            if (alpineData) {
                // Force-close: skip the `submitting` guard that close()
                // applies. That guard exists to stop user-driven close
                // paths (backdrop click, ESC key) from racing with an
                // in-flight form submit, and remains in place for those.
                // Programmatic callers of closeModal() — e.g. a form's
                // @submit handler that swaps the confirm modal for an
                // in-progress modal — are deliberately requesting close,
                // so honour it unconditionally.
                alpineData.submitting = false;
                alpineData.show = false;
            }
        }
    }
};

window.submitModalForm = function (formId) {
    // Check if it's a 2FA management modal action
    const actionMap = {
        'deleteTrustedDeviceForm': 'deleteTrustedDevice',
        'deleteAllTrustedDevicesForm': 'deleteAllTrustedDevices',
        'deletePasskeyForm': 'deletePasskey',
        'deleteAllPasskeysForm': 'deleteAllPasskeys',
        'deleteRecoveryCodesForm': 'deleteRecoveryCodes',
        'confirmGenerateRecoveryCodesForm': 'confirmGenerateRecoveryCodes'
    };

    if (actionMap[formId]) {
        if (typeof window[actionMap[formId]] === 'function') {
            window[actionMap[formId]]();
            return;
        }
    }

    // Default behavior: submit the actual form.
    //
    // Use requestSubmit() rather than submit() so the form's `submit`
    // event listeners fire — `form.submit()` programmatically bypasses
    // them by spec, which prevents callers from hooking into the moment
    // a confirm-modal triggers form navigation (e.g. to swap the confirm
    // modal for an "in-progress" modal during a long-running apply).
    // requestSubmit() is supported in all modern browsers (Chrome 76+,
    // Firefox 75+, Safari 16+); fall back to submit() if the runtime
    // is older to preserve the previous behaviour rather than no-op.
    const form = document.getElementById(formId);
    if (form) {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }
};
