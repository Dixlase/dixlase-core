/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * Passkey Registration Prompt Component
 * Handles automatic display of passkey registration prompt modal
 */

/**
 * Initialize passkey prompt modal auto-display
 * Automatically detects modals with data-passkey-prompt attribute
 */
document.addEventListener('DOMContentLoaded', function () {
    // data-passkey-prompt属性を持つモーダルを検索
    const passkeyPromptModals = document.querySelectorAll('[data-passkey-prompt="true"]');

    passkeyPromptModals.forEach(function (modal) {
        const hasRecoveryModal = modal.getAttribute('data-has-recovery-modal') === 'true';

        // 回復コードモーダルがある場合は、そちらが閉じた後に表示されるので何もしない
        if (hasRecoveryModal) {
            return;
        }

        // 回復コードモーダルがない場合は即座に表示
        if (modal._x_dataStack) {
            const modalData = modal._x_dataStack[0];
            if (modalData && typeof modalData.open === 'function') {
                modalData.open();
            }
        }
    });
});
