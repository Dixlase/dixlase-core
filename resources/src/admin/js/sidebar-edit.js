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

Alpine.data('sidebarEditor', (saveUrl, initialHidden) => ({
    editMode: false,
    hiddenMenus: initialHidden || [],
    saving: false,

    isHidden(key) {
        if (this.hiddenMenus.includes(key)) {
            return true;
        }
        // Check if any parent key is hidden (e.g. 'front' hides 'front.pages')
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
        this.editMode = true;
    },

    exitEditMode() {
        this.editMode = false;
        this.savePreferences();
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
            body: JSON.stringify({ hidden: this.hiddenMenus }),
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
