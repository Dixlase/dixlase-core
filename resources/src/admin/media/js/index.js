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

/**
 * Replace a form's ids[] with the selected ids.
 */
function fillIds(form, ids) {
    form.querySelectorAll('input[data-bulk-id]').forEach((el) => el.remove());
    ids.forEach((id) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = String(id);
        input.dataset.bulkId = '1';
        form.appendChild(input);
    });
}

/**
 * Media index bulk-selection state (iPhone Photos-style).
 *
 * Selection mode is a page-wide toggle; while it is on, tapping a card
 * anywhere (except action buttons and links) toggles the card's ID in
 * `selectedIds`. The delete confirmation modal reads the ID list on open
 * and submits it as `ids[]` to `admin.media.bulk-delete`.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('mediaIndex', () => ({
        selectionMode: false,
        selectedIds: [],

        enterSelectMode() {
            this.selectionMode = true;
            this.selectedIds = [];
        },

        exitSelectMode() {
            this.selectionMode = false;
            this.selectedIds = [];
        },

        toggleSelect(id) {
            const numericId = Number(id);
            const idx = this.selectedIds.indexOf(numericId);
            if (idx === -1) {
                this.selectedIds.push(numericId);
            } else {
                this.selectedIds.splice(idx, 1);
            }
        },

        isSelected(id) {
            return this.selectedIds.indexOf(Number(id)) !== -1;
        },

        cardClasses(id) {
            return this.selectionMode && this.isSelected(id) ? 'media-card--selected' : '';
        },

        handleCardClick(id, event) {
            // Runs in the capture phase (see @click.capture on .media-card),
            // so we intercept clicks before <a href> navigation, before the
            // action buttons' own @click handlers, and before the preview
            // <a>'s target=_blank kicks in. Bail immediately when we are
            // not in selection mode so normal card interactions stay intact.
            if (!this.selectionMode) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            this.toggleSelect(id);
        },

        selectAllOnPage() {
            const cards = document.querySelectorAll('[data-media-card-id]');
            const ids = [];
            cards.forEach((el) => {
                const n = Number(el.dataset.mediaCardId);
                if (!Number.isNaN(n)) {
                    ids.push(n);
                }
            });
            this.selectedIds = ids;
        },

        clearSelection() {
            this.selectedIds = [];
        },

        selectedCountLabel() {
            const bar = document.getElementById('mediaBulkStrings');
            const tpl = (bar && bar.dataset.selectedTpl) || ':count';
            return tpl.replace(':count', String(this.selectedIds.length));
        },

        openBulkDeleteModal() {
            if (this.selectedIds.length === 0) {
                return;
            }

            const modal = document.getElementById('bulkDeleteModal');
            if (!modal) {
                console.error('[Media Index] bulkDeleteModal not found');
                return;
            }

            const messageElement = modal.querySelector('.modal-message p');
            if (messageElement) {
                const tpl = modal.dataset.confirmMessage || '';
                messageElement.innerHTML = tpl.replace(':count', String(this.selectedIds.length));
            }

            const form = document.getElementById('bulkDeleteForm');
            if (form) {
                fillIds(form, this.selectedIds);
            }

            if (typeof window.openModal === 'function') {
                window.openModal('bulkDeleteModal');
            } else {
                console.error('[Media Index] openModal function not found');
            }
        },

        /**
         * Bulk tagging (#766): "add" or "remove". The modal's tag input
         * starts empty, and the selected ids go in as ids[] like the bulk
         * delete's.
         */
        openBulkTagModal(mode) {
            if (this.selectedIds.length === 0) {
                return;
            }

            const prefix = mode === 'remove' ? 'bulkTagRemove' : 'bulkTagAdd';
            const modal = document.getElementById(prefix + 'Modal');
            const form = document.getElementById(prefix + 'Form');
            if (!modal || !form) {
                return;
            }

            const message = modal.querySelector('[data-bulk-tag-message]');
            if (message) {
                message.innerHTML = message.dataset.bulkTagMessage.replace(':count', String(this.selectedIds.length));
            }

            fillIds(form, this.selectedIds);
            document.getElementById(prefix + 'Input')?.dispatchEvent(new CustomEvent('media-tag-input-reset'));
            window.openModal(prefix + 'Modal');
        },
    }));
});

