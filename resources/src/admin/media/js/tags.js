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
 * Tag page of the media library (#766): fills and opens the rename and
 * delete modals from the button that was pressed. The tag's name comes
 * from a data attribute and is escaped before it goes into the message.
 */

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('mediaTagsPage', () => ({
        openRenameTagModal(button) {
            const form = document.getElementById('renameTagForm');
            const field = document.getElementById('renameTagName');
            if (!form || !field) {
                return;
            }

            form.action = form.dataset.baseUrl.replace('__ID__', button.dataset.tagId);
            field.value = button.dataset.tagName || '';
            window.openModal('renameTagModal');
            setTimeout(() => field.select(), 50);
        },

        openDeleteTagModal(button) {
            const modal = document.getElementById('deleteTagModal');
            const form = document.getElementById('deleteTagForm');
            if (!modal || !form) {
                return;
            }

            const message = modal.querySelector('.modal-message p');
            if (message) {
                message.innerHTML = (modal.dataset.confirmMessage || '')
                    .replace(':name', escapeHtml(button.dataset.tagName || ''))
                    .replace(':count', String(Number(button.dataset.tagCount) || 0));
            }

            form.action = form.dataset.baseUrl.replace('__ID__', button.dataset.tagId);
            window.openModal('deleteTagModal');
        },
    }));
});
