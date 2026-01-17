/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * Recovery Codes Modal Component
 * Alpine.js component for recovery codes display and management
 */

// Alpine.js component for recovery codes modal
window.recoveryCodesModal = function (modalId, codes = [], autoOpen = false, clearSessionRoute = null) {
    return {
        modalId: modalId,
        codes: codes,
        isSaved: false,
        clearSessionRoute: clearSessionRoute,

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

    const modalContent = modal.querySelector('.space-y-4');
    if (!modalContent) return;

    const errorHTML = `
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <p class="text-sm text-red-800 dark:text-red-200">
                <i class="fas fa-exclamation-circle mr-2"></i>
                ${errorMessage}
            </p>
        </div>
    `;

    modalContent.innerHTML = errorHTML;

    const modalTitle = modal.querySelector('h3');
    if (modalTitle) {
        modalTitle.textContent = window.translations?.error || 'Error';
    }

    const closeBtn = document.getElementById(modalId + '-close-btn');
    if (closeBtn) {
        closeBtn.disabled = false;
        closeBtn.classList.remove('opacity-50', 'cursor-not-allowed');
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
