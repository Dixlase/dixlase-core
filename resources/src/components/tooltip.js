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
 * Alpine.js Tooltip Component
 * 
 * Usage in Blade:
 * <div x-data="tooltip()" ...>
 */
window.tooltip = function () {
    return {
        isOpen: false,

        toggle() {
            this.isOpen = !this.isOpen;
        },

        show() {
            this.isOpen = true;
        },

        hide() {
            this.isOpen = false;
        },

        // 外側クリックで閉じる
        closeOnClickAway(event) {
            if (!this.$el.contains(event.target)) {
                this.hide();
            }
        }
    };
};
