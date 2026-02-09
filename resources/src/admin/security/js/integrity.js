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
 * File Integrity Check - Bulk Delete Functionality
 * 
 * This script handles the bulk delete modal functionality for scan history.
 * It sets the days value in the hidden form input before opening the modal.
 */

// 一括削除モーダルを開く前に日数を設定
document.addEventListener('alpine:init', () => {
    window.addEventListener('click', (e) => {
        if (e.target.closest('[x-click*="bulk-delete-modal"]')) {
            const days = document.getElementById('bulk-delete-days').value;
            document.getElementById('bulk-delete-days-input').value = days;

            // モーダルのメッセージを動的に更新
            const modal = document.getElementById('bulk-delete-modal');
            if (modal) {
                const messageEl = modal.querySelector('[data-modal-message]');
                if (messageEl) {
                    // Note: The translation message needs to be passed from the view
                    // as we cannot access Laravel's __() function from external JS
                    const confirmMessage = messageEl.getAttribute('data-confirm-template');
                    if (confirmMessage) {
                        messageEl.textContent = confirmMessage.replace(':days', days);
                    }
                }
            }
        }
    });
});
