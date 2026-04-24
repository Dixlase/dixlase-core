/*
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
 * Media Index - Delete Modal and Copy URL Functionality
 * Handles media deletion confirmation and URL copying
 */

let currentFileId = null;
let currentFileName = '';

/**
 * Open delete confirmation modal
 */
window.openDeleteModal = function (fileId, fileName) {
    currentFileId = fileId;
    currentFileName = fileName;

    const modal = document.getElementById('deleteModal');
    if (!modal) {
        console.error('[Media Index] deleteModal not found');
        return;
    }

    const messageElement = modal.querySelector('.modal-message p');
    if (messageElement) {
        const deleteMessage = modal.dataset.deleteMessage || '「{fileName}」を削除しますか？この操作は取り消せません。';
        const escapedName = fileName.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        messageElement.innerHTML = deleteMessage.replace('{fileName}', escapedName);
    }

    const form = document.getElementById('deleteForm');
    if (form) {
        const baseUrl = form.dataset.baseUrl || '';
        form.action = baseUrl.replace('__ID__', fileId);
    }

    if (typeof window.openModal === 'function') {
        window.openModal('deleteModal');
    } else {
        console.error('[Media Index] openModal function not found');
    }
};

/**
 * Copy media URL to clipboard
 */
window.copyMediaUrl = function (url, button) {
    const originalIcon = button.innerHTML;

    navigator.clipboard.writeText(url).then(function () {
        button.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i>';
        button.classList.remove('action-btn--copy');
        button.classList.add('action-btn--success');

        setTimeout(function () {
            button.innerHTML = originalIcon;
            button.classList.remove('action-btn--success');
            button.classList.add('action-btn--copy');
        }, 2000);
    }).catch(function (err) {
        try {
            const textArea = document.createElement('textarea');
            textArea.value = url;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);

            button.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i>';
            setTimeout(function () {
                button.innerHTML = originalIcon;
            }, 2000);
        } catch (e) {
            console.error('[Media Index] Copy failed:', e);
            button.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
            setTimeout(function () {
                button.innerHTML = originalIcon;
            }, 2000);
        }
    });
};

console.log('[Media Index] Script loaded');
