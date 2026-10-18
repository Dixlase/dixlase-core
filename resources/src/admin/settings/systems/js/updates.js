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
 *
 * Alpine.js components for System > Updates
 * (resources/views/admin/settings/systems/updates/index.blade.php).
 *
 * Written for the @alpinejs/csp build: the view only calls methods and reads
 * properties, and translated strings arrive through data-* attributes read
 * in init() instead of being spliced into directive expressions.
 */

import Alpine from 'alpinejs';

const IN_PROGRESS_MODAL = 'updatesInProgressModal';

function openModalById(id) {
    if (typeof window.openModal === 'function') {
        window.openModal(id);
    }
}

function closeModalById(id) {
    if (typeof window.closeModal === 'function') {
        window.closeModal(id);
    }
}

/**
 * Page root: the shared "back up first" flag and the per-row update handoff.
 *
 * A row's "Update" button calls askSingleUpdateFrom($el); the target is kept
 * in pendingSingleUpdate until the confirm modal submits the matching hidden
 * mini-form (singleUpdateForm_<kind>_<id>).
 */
Alpine.data('systemUpdatesPage', () => ({
    pendingSingleUpdate: { kind: null, id: null, name: null, formId: null },
    backupFirst: true,
    singleConfirmTemplate: '',

    init() {
        this.singleConfirmTemplate = this.$el.dataset.singleConfirmTemplate || '';
    },

    askSingleUpdateFrom(button) {
        const kind = button.dataset.updateKind;
        const id = parseInt(button.dataset.updateId, 10);

        this.pendingSingleUpdate = {
            kind: kind,
            id: id,
            name: button.dataset.updateName || '',
            formId: 'singleUpdateForm_' + kind + '_' + id,
        };
        openModalById('confirmSingleUpdateModal');
    },

    backupFirstValue() {
        return this.backupFirst ? '1' : '0';
    },

    singleConfirmMessage() {
        return this.singleConfirmTemplate.replace('%name%', this.pendingSingleUpdate.name || '');
    },

    // @submit handler of the standalone core forms: swap the confirm modal
    // for the in-progress one once the request is under way.
    showProgressAfter(confirmModalId) {
        closeModalById(confirmModalId);
        this.$nextTick(() => openModalById(IN_PROGRESS_MODAL));
    },
}));

/**
 * The bulk apply form: counts the checked rows and drives the confirm modal.
 */
Alpine.data('systemUpdatesApplyForm', () => ({
    selectedCount: 0,
    applying: false,
    confirmTemplate: '',

    init() {
        this.confirmTemplate = this.$el.dataset.confirmTemplate || '';
        this.recompute();
    },

    checkboxes(kind) {
        const form = document.getElementById('systemUpdatesApplyForm');
        if (!form) {
            return [];
        }
        const selector = kind
            ? 'input[type=checkbox][data-update-target=' + kind + ']'
            : 'input[type=checkbox][data-update-target]';

        return Array.from(form.querySelectorAll(selector));
    },

    recompute() {
        this.selectedCount = this.checkboxes(null).filter((cb) => cb.checked).length;
    },

    // "Select all" checkbox of a table; its data-select-all names the kind.
    toggleAll(event) {
        const checked = event.target.checked;
        this.checkboxes(event.target.dataset.selectAll).forEach((cb) => {
            cb.checked = checked;
        });
        this.recompute();
    },

    openConfirm() {
        if (this.selectedCount > 0) {
            openModalById('confirmSystemUpdatesModal');
        }
    },

    confirmMessage() {
        return this.confirmTemplate.replace('%count%', String(this.selectedCount));
    },

    submitApply() {
        this.applying = true;
        closeModalById('confirmSystemUpdatesModal');
        this.$nextTick(() => openModalById(IN_PROGRESS_MODAL));
    },
}));

/**
 * Release notes disclosure. One instance per row, so rows expand separately.
 */
Alpine.data('systemUpdatesReleaseNotes', () => ({
    notesOpen: false,

    toggleNotes() {
        this.notesOpen = !this.notesOpen;
    },

    chevronClass() {
        return this.notesOpen ? 'rotate-90' : '';
    },
}));

/**
 * Copy button for the CLI alternative. Labels come from data-copy-label /
 * data-copied-label on the component root.
 */
Alpine.data('systemUpdatesCliCopy', () => ({
    copied: false,
    copyLabel: '',
    copiedLabel: '',

    init() {
        this.copyLabel = this.$el.dataset.copyLabel || '';
        this.copiedLabel = this.$el.dataset.copiedLabel || '';
    },

    copy() {
        const text = this.$refs.cmd ? this.$refs.cmd.textContent.trim() : '';
        navigator.clipboard.writeText(text).then(() => {
            this.copied = true;
            setTimeout(() => {
                this.copied = false;
            }, 2000);
        });
    },

    iconClass() {
        return this.copied ? 'fa-check text-green-500' : 'fa-copy';
    },

    label() {
        return this.copied ? this.copiedLabel : this.copyLabel;
    },
}));
