/**
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
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * Two-Factor Authentication Profile Settings Component
 * Handles 2FA settings state management on profile page
 */

/**
 * Initialize 2FA profile settings Alpine.js component
 */
window.twoFaProfileSettings = function(initialTwoFaMode, initialPasskeyEnabled) {
    return {
        twoFaMode: initialTwoFaMode,
        passkeyEnabled: initialPasskeyEnabled,
        
        get twoFaEnabled() {
            return this.twoFaMode !== '0';
        }
    };
};
