/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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
