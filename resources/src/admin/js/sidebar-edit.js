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

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

Alpine.data('sidebarEditor', (saveUrl, resetUrl, initialHidden, initialOrder) => ({
    editMode: false,
    hiddenMenus: initialHidden || [],
    menuOrder: initialOrder || {},
    saving: false,
    resetting: false,
    sortableInstances: [],

    isHidden(key) {
        if (this.hiddenMenus.includes(key)) {
            return true;
        }
        const parts = key.split('.');
        for (let i = 1; i < parts.length; i++) {
            const parentKey = parts.slice(0, i).join('.');
            if (this.hiddenMenus.includes(parentKey)) {
                return true;
            }
        }
        return false;
    },

    toggleMenu(key) {
        if (this.isHidden(key)) {
            this.hiddenMenus = this.hiddenMenus.filter(k => k !== key);
        } else {
            this.hiddenMenus.push(key);
        }
    },

    enterEditMode() {
        // The eye toggles, the drag handles and the labels have no room in a
        // 96px rail, so ask the layout for the full menu before editing.
        window.dispatchEvent(new CustomEvent('dls-sidebar-expand'));
        this.editMode = true;
        // Wait for DOM to fully update (x-show, x-cloak removal)
        setTimeout(() => this.initSortables(), 100);
    },

    /**
     * Whether the icon rail is the shape on screen. adminLayout() publishes
     * that as a class on <html>, which is what the stylesheet reads too, so
     * there is one answer rather than two that can disagree.
     *
     * @returns {boolean}
     */
    railActive() {
        return document.documentElement.classList.contains('dls-sidebar-rail-active');
    },

    /**
     * Open or close a top-level submenu.
     *
     * In the full menu this is the accordion it has always been, and several
     * sections may stay open at once. While the rail is showing, the submenu
     * is a panel beside the icons instead: it is anchored to the button that
     * opened it, and any other open panel closes, because two panels would
     * sit on top of each other.
     *
     * @param {Object} scope the item's own Alpine scope
     * @param {string} key   that scope's open flag, e.g. 'open_systems'
     * @param {Event} event  the click, used for the button's position
     */
    togglePanel(scope, key, event) {
        const willOpen = ! scope[key];
        scope[key] = willOpen;

        if (! willOpen || ! this.railActive()) {
            return;
        }

        const button = event.currentTarget;
        const item = button.closest('[data-menu-key]');
        const panel = item ? item.querySelector(':scope > .dls-sidebar-panel') : null;
        if (panel) {
            const top = Math.round(button.getBoundingClientRect().top);
            panel.style.setProperty('--dls-sidebar-panel-top', top + 'px');
        }

        window.dispatchEvent(new CustomEvent('dls-sidebar-panel', { detail: { key } }));
    },

    /**
     * Close this item's panel because another one just opened.
     *
     * @param {Object} scope
     * @param {string} key
     * @param {CustomEvent} event
     */
    syncPanel(scope, key, event) {
        if (this.railActive() && event.detail && event.detail.key !== key) {
            scope[key] = false;
        }
    },

    /**
     * Close this item's panel on a click elsewhere or on Escape. In the full
     * menu an open section is left exactly as the member put it.
     *
     * @param {Object} scope
     * @param {string} key
     */
    closeIfRail(scope, key) {
        if (this.railActive()) {
            scope[key] = false;
        }
    },

    exitEditMode() {
        this.destroySortables();
        this.editMode = false;
        this.savePreferences();
    },

    initSortables() {
        const root = this.$refs.sidebarRoot;
        if (!root) {
            return;
        }
        const containers = root.querySelectorAll('[data-sortable-group]');
        containers.forEach(container => {
            const groupKey = container.dataset.sortableGroup;
            const options = {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'opacity-30',
                forceFallback: true,
                fallbackOnBody: true,
                fallbackClass: 'sortable-fallback',
                draggable: '[data-menu-key]',
                group: { name: groupKey, pull: false, put: false },
                onEnd: () => {
                    const keys = Array.from(container.querySelectorAll(':scope > [data-menu-key]'))
                        .map(el => el.dataset.menuKey);
                    this.menuOrder = { ...this.menuOrder, [groupKey]: keys };
                },
            };

            // トップレベルではダッシュボードの上にドロップ不可
            if (groupKey === '_top') {
                options.onMove = (evt) => {
                    if (evt.related.dataset.menuKey === 'dashboard' && !evt.willInsertAfter) {
                        return false;
                    }
                };
            }

            const instance = Sortable.create(container, options);
            this.sortableInstances.push(instance);
        });
    },

    destroySortables() {
        this.sortableInstances.forEach(instance => instance.destroy());
        this.sortableInstances = [];
    },

    confirmReset() {
        // Expose executeReset globally for the modal's confirm button
        window._sidebarExecuteReset = () => this.executeReset();
        if (typeof window.openModal === 'function') {
            window.openModal('sidebarResetModal');
        }
    },

    executeReset() {
        this.resetting = true;
        if (typeof window.closeModal === 'function') {
            window.closeModal('sidebarResetModal');
        }

        fetch(resetUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(response => response.json())
        .then(() => {
            this.resetting = false;
            this.destroySortables();
            this.editMode = false;
            window.location.reload();
        })
        .catch(error => {
            console.error('Error resetting sidebar preferences:', error);
            this.resetting = false;
        });
    },

    savePreferences() {
        this.saving = true;

        fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                hidden: this.hiddenMenus,
                order: this.menuOrder,
            }),
        })
        .then(response => response.json())
        .then(() => {
            this.saving = false;
        })
        .catch(error => {
            console.error('Error saving sidebar preferences:', error);
            this.saving = false;
        });
    },
}));
