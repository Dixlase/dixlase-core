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
 * Per-member two-factor settings (<x-two-fa.individual-settings>).
 *
 * The initial values are read from the element's data attributes. When the
 * global settings force the passkey value, isPasskeyActuallyEnabled keeps
 * returning that forced value whatever the toggle says.
 */
Alpine.data('twoFaIndividualSettings', () => ({
    twoFaMode: '0',
    passkeyEnabled: false,
    isPasskeyEditable: false,
    forcedPasskeyEnabled: false,

    init() {
        const data = this.$el.dataset;

        this.twoFaMode = data.twoFaMode;
        this.passkeyEnabled = data.passkeyEnabled === '1';
        this.isPasskeyEditable = data.passkeyEditable === '1';
        this.forcedPasskeyEnabled = data.passkeyEnabled === '1';
    },

    get twoFaEnabled() {
        return this.twoFaMode !== '0';
    },

    get isPasskeyActuallyEnabled() {
        return this.isPasskeyEditable ? this.passkeyEnabled : this.forcedPasskeyEnabled;
    },
}));
