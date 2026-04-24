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
