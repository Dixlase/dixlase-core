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
 * Two-factor authentication settings screen.
 *
 * The saved modes are read from the element's data attributes.
 */
Alpine.data('twoFaGlobalSettings', () => ({
    twoFaMode: '0',
    passkeyMode: '0',

    init() {
        this.twoFaMode = this.$el.dataset.twoFaMode;
        this.passkeyMode = this.$el.dataset.passkeyMode;
    },

    get twoFaEnabled() {
        return this.twoFaMode !== '0';
    },

    get passkeyEnabled() {
        return this.passkeyMode !== '0';
    },
}));
