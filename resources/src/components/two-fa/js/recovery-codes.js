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
 * Recovery Codes Modal Component
 * Alpine.js component for recovery codes display and management
 */

// Alpine.js component for recovery codes modal
window.recoveryCodesModal = function (modalId, codes = [], autoOpen = false, clearSessionRoute = null, nextModal = null) {
    return {
        modalId: modalId,
        codes: codes,
        isSaved: false,
        clearSessionRoute: clearSessionRoute,
        nextModal: nextModal,

        init() {
            // 回復コードデータをグローバルに保存（後方互換性）
            window.recoveryCodesData = window.recoveryCodesData || {};
            window.recoveryCodesData[this.modalId] = this.codes;

            // 自動表示
            if (autoOpen) {
                this.$nextTick(() => {
                    const modalElement = document.getElementById(this.modalId);
                    if (modalElement && modalElement._x_dataStack) {
                        const modalData = modalElement._x_dataStack[0];
                        if (modalData && typeof modalData.open === 'function') {
                            modalData.open();
                        }
                    }
                });
            }
        },

        formatCode(code) {
            return code.match(/.{1,5}/g).join('-');
        },

        get formattedCodes() {
            return this.codes.map(code => this.formatCode(code));
        },

        get canClose() {
            return this.isSaved;
        },

        async downloadCodes() {
            const text = this.formattedCodes.join('\n');
            const blob = new Blob([text], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'recovery-codes-' + new Date().toISOString().split('T')[0] + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        },

        async copyCodes() {
            const text = this.formattedCodes.join('\n');
            const copyBtn = this.$refs.copyBtn;
            const originalHTML = copyBtn.innerHTML;

            try {
                await navigator.clipboard.writeText(text);

                // コピー成功表示
                copyBtn.innerHTML = '<i class="fas fa-check mr-2"></i>' + (window.translations?.copied || 'Copied');
                copyBtn.classList.remove('bg-gray-600', 'hover:bg-gray-700');
                copyBtn.classList.add('bg-green-600', 'hover:bg-green-700');

                // 2秒後に元に戻す
                setTimeout(() => {
                    copyBtn.innerHTML = originalHTML;
                    copyBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
                    copyBtn.classList.add('bg-gray-600', 'hover:bg-gray-700');
                }, 2000);
            } catch (err) {
                console.error('Copy error:', err);
                alert(window.translations?.copy_failed || 'Failed to copy');
            }
        },

        async handleClose() {
            // セッションクリア処理
            if (this.clearSessionRoute) {
                try {
                    await fetch(this.clearSessionRoute, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    });
                    console.log(`[${this.modalId}] Recovery codes session cleared`);
                } catch (error) {
                    console.error(`[${this.modalId}] Failed to clear session:`, error);
                }
            }

            // モーダルを閉じる
            const modalElement = document.getElementById(this.modalId);
            if (modalElement && modalElement._x_dataStack) {
                const modalData = modalElement._x_dataStack[0];
                if (modalData && typeof modalData.close === 'function') {
                    modalData.close();

                    // 次のモーダルがある場合は開く
                    if (this.nextModal) {
                        setTimeout(() => {
                            if (typeof window.openModal === 'function') {
                                window.openModal(this.nextModal);
                            } else {
                                const nextModalElement = document.getElementById(this.nextModal);
                                if (nextModalElement && nextModalElement._x_dataStack) {
                                    const nextModalData = nextModalElement._x_dataStack[0];
                                    if (nextModalData && typeof nextModalData.open === 'function') {
                                        nextModalData.open();
                                    }
                                }
                            }
                        }, 300); // モーダルが閉じるアニメーション後に開く
                    }
                }
            }
        },

        displayCodes(codes) {
            this.codes = codes;
            window.recoveryCodesData[this.modalId] = codes;
        },

        displayError(errorMessage) {
            // エラー表示は親モーダルコンポーネントで処理
            console.error(`[${this.modalId}] Error:`, errorMessage);
        }
    };
};

// Backward compatibility: Global functions
window.recoveryCodesData = window.recoveryCodesData || {};

window.formatRecoveryCode = function (code) {
    return code.match(/.{1,5}/g).join('-');
};

window.displayRecoveryCodesInModal = function (modalId, codes) {
    window.recoveryCodesData[modalId] = codes;

    // Alpine.jsコンポーネントのデータを更新
    const modalElement = document.getElementById(modalId);

    if (modalElement) {
        // モーダル内の.space-y-4要素を探す（recoveryCodesModalがバインドされている要素）
        const contentElement = modalElement.querySelector('.space-y-4[x-data]');

        if (contentElement && contentElement._x_dataStack) {
            const alpineData = contentElement._x_dataStack[0];

            if (alpineData && alpineData.displayCodes) {
                alpineData.displayCodes(codes);
            }
        }
    }

    // フォールバック: DOMを直接更新（Alpine.jsが利用できない場合）
    const container = document.getElementById(modalId + '-codes-list');
    if (container) {
        container.innerHTML = codes.map(code =>
            `<div class="p-2 bg-white dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600 text-center">${window.formatRecoveryCode(code)}</div>`
        ).join('');
    }
};

window.displayRecoveryCodesError = function (modalId, errorMessage) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    // モーダルのコンテンツ部分を更新
    const modalContent = modal.querySelector('.space-y-4');
    if (modalContent) {
        const errorHTML = `
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <p class="text-sm text-red-800 dark:text-red-200">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    ${errorMessage}
                </p>
            </div>
        `;
        modalContent.innerHTML = errorHTML;
    }

    // modal-actionsを更新
    const modalActions = modal.querySelector('.modal-actions');
    if (modalActions) {
        const closeLabel = window.translations?.common?.close || window.translations?.close || '閉じる';
        const actionsHTML = `
            <button 
                type="button"
                onclick="closeModal('${modalId}')"
                class="inline-flex items-center justify-center font-semibold rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed bg-gray-200 dark:bg-gray-500 text-gray-900 dark:text-white hover:bg-gray-700 focus:ring-gray-500 px-4 py-2 text-sm mx-2">
                ${closeLabel}
            </button>
        `;
        modalActions.innerHTML = actionsHTML;
    }

    // タイトルを更新
    const modalTitle = modal.querySelector('h3');
    if (modalTitle) {
        modalTitle.textContent = window.translations?.common?.error || window.translations?.error || 'エラー';
    }
};

window.downloadRecoveryCodesFromModal = function (modalId) {
    const codes = window.recoveryCodesData[modalId] || [];
    const text = codes.map(code => window.formatRecoveryCode(code)).join('\n');
    const blob = new Blob([text], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'recovery-codes-' + new Date().toISOString().split('T')[0] + '.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};

window.copyRecoveryCodesFromModal = function (modalId) {
    const codes = window.recoveryCodesData[modalId] || [];
    const text = codes.map(code => window.formatRecoveryCode(code)).join('\n');
    const copyBtn = document.getElementById(modalId + '-copy-btn');
    const originalHTML = copyBtn.innerHTML;

    navigator.clipboard.writeText(text).then(() => {
        copyBtn.innerHTML = '<i class="fas fa-check mr-2"></i>' + (window.translations?.copied || 'Copied');
        copyBtn.classList.remove('bg-gray-600', 'hover:bg-gray-700');
        copyBtn.classList.add('bg-green-600', 'hover:bg-green-700');

        setTimeout(() => {
            copyBtn.innerHTML = originalHTML;
            copyBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
            copyBtn.classList.add('bg-gray-600', 'hover:bg-gray-700');
        }, 2000);
    }).catch(err => {
        console.error('Copy error:', err);
        alert(window.translations?.copy_failed || 'Failed to copy');
    });
};

window.checkRecoveryCodesSavedFromModal = function (modalId) {
    const checkbox = document.getElementById(modalId + '-saved-checkbox');
    const closeBtn = document.getElementById(modalId + '-close-btn');

    if (checkbox && closeBtn) {
        closeBtn.disabled = !checkbox.checked;

        if (checkbox.checked) {
            closeBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            closeBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }
};
