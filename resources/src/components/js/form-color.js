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
 * Alpine.js Color Picker Component
 * 
 * Usage in Blade:
 * <div x-data="colorPicker('{{ old($name, $value) }}')" ...>
 */
window.colorPicker = function (initialValue = '#000000') {
    return {
        color: initialValue,

        init() {
            // Watch for color changes
            this.$watch('color', value => {
                // Ensure the value is always a valid hex color
                if (value && !value.startsWith('#')) {
                    this.color = '#' + value;
                }
            });
        }
    };
};
