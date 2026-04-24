/*
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
 * Mail Server Verification Success Page
 * Handles window closing and parent window communication
 */

/**
 * Dark mode detection and cookie sync (Strict CSP compliant)
 * Sets prefers_dark cookie for server-side detection
 */
(function () {
    const theme = localStorage.getItem('appearance') || '0';
    const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = theme === '2' || (theme === '0' && systemDark);

    // Apply dark class if not already set by server
    if (isDark && !document.documentElement.classList.contains('dark')) {
        document.documentElement.classList.add('dark');
    }

    // Set cookie for server-side detection (1 year expiry)
    const cookieValue = isDark ? '1' : '0';
    document.cookie = `prefers_dark=${cookieValue}; path=/; max-age=31536000; SameSite=Lax`;
})();

/**
 * Close verification window and notify parent
 */
window.closeVerificationWindow = function (completedMessage) {
    // Send message to parent window
    if (window.opener) {
        window.opener.postMessage({
            type: 'mail_receive_test_completed',
            message: completedMessage
        }, window.location.origin);
    }

    // Close window
    window.close();
};

/**
 * Initialize verification success page
 */
function initVerificationSuccess() {
    // Record completion in session storage
    try {
        sessionStorage.setItem('mail_receive_test_completed', 'true');
        sessionStorage.setItem('mail_receive_test_date', new Date().toLocaleString());
    } catch (e) {
        console.error('[Mail Verification] Failed to save to session storage:', e);
    }

    // Reload parent window if available
    if (window.opener && !window.opener.closed) {
        try {
            window.opener.location.reload();
        } catch (e) {
            console.error('[Mail Verification] Failed to reload parent window:', e);
        }
    } else {
        // Alternative: Use BroadcastChannel for cross-tab communication
        try {
            const channel = new BroadcastChannel('mail_test_channel');
            channel.postMessage({
                type: 'mail_receive_test_completed',
                timestamp: new Date().toISOString()
            });
            channel.close();
        } catch (e) {
            console.error('[Mail Verification] Failed to send via BroadcastChannel:', e);
        }
    }
}

// Auto-close after 5 seconds if parent window exists
setTimeout(function () {
    if (window.opener) {
        window.close();
    }
}, 5000);

// Attach event listener to close button
function attachCloseButtonListener() {
    const closeBtn = document.getElementById('close-verification-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            const message = this.dataset.message || '';
            closeVerificationWindow(message);
        });
    }
}

// Attach listener immediately if DOM is ready, otherwise wait for DOMContentLoaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachCloseButtonListener);
} else {
    attachCloseButtonListener();
}

// Initialize on page load
window.addEventListener('load', initVerificationSuccess);
