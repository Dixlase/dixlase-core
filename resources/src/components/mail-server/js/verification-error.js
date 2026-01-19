/**
 * Mail Server Verification Error Page
 * Handles window closing and error notification
 */

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

console.log('[Mail Verification Error] Script loaded');
