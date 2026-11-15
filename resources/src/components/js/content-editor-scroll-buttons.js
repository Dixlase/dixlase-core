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
 * Floating "scroll to editor / preview" buttons of the content editor.
 *
 * Keeps the buttons 16px inside the right edge of the admin main content,
 * following it when the window or the content area is resized.
 */
Alpine.data('contentEditorScrollButtons', () => ({
    _btnRight: 16,

    init() {
        const main = document.getElementById('admin-main-content');

        this.updatePosition();

        if (main) {
            new ResizeObserver(() => this.updatePosition()).observe(main);
        }
    },

    updatePosition() {
        const main = document.getElementById('admin-main-content');

        if (main) {
            this._btnRight = Math.max(16, window.innerWidth - main.getBoundingClientRect().right + 16);
        }
    },
}));
