/**
 * Mail Server Verification Error Page
 * Handles window closing and error notification
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
 * Close verification error window and notify parent
 */
window.closeVerificationWindow = function (errorMessage) {
    // Send error message to parent window
    if (window.opener) {
        window.opener.postMessage({
            type: 'mail_verification_error',
            message: errorMessage
        }, window.location.origin);
    }

    // Close window
    window.close();
};

// Auto-close after 10 seconds if parent window exists
setTimeout(function () {
    if (window.opener) {
        window.close();
    }
}, 10000);

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
