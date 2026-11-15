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

/**
 * One menu item on the role permissions screen: a view range and an edit
 * range over the same list of roles.
 *
 * The initial indexes and the role values are read from the element's data
 * attributes. The edit range never sits below the view range: lowering the
 * edit range below it snaps back, and raising the view range above it pulls
 * the edit range up.
 */
Alpine.data('rolePermissionRange', () => ({
    accessIndex: 0,
    viewIndex: 0,
    roleValues: [],

    init() {
        this.accessIndex = Number(this.$el.dataset.accessIndex);
        this.viewIndex = Number(this.$el.dataset.viewIndex);
        this.roleValues = JSON.parse(this.$el.dataset.roleValues);

        this.$watch('accessIndex', (newValue) => {
            if (parseInt(newValue) < parseInt(this.viewIndex)) {
                this.$nextTick(() => {
                    this.accessIndex = this.viewIndex;
                });
            }
        });
        this.$watch('viewIndex', (newValue) => {
            if (parseInt(this.accessIndex) < parseInt(newValue)) {
                this.$nextTick(() => {
                    this.accessIndex = newValue;
                });
            }
        });
    },

    get accessValue() {
        return this.roleValues[this.accessIndex] || this.roleValues[0];
    },

    get viewValue() {
        return this.roleValues[this.viewIndex] || this.roleValues[0];
    },
}));
