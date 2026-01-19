/**
 * Mail Server Verification Success Page
 * Handles window closing and parent window communication
 */

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
    console.log('[Mail Verification] Success page loaded');

    // Record completion in session storage
    try {
        sessionStorage.setItem('mail_receive_test_completed', 'true');
        sessionStorage.setItem('mail_receive_test_date', new Date().toLocaleString());
        console.log('[Mail Verification] Recorded completion in session storage');
    } catch (e) {
        console.error('[Mail Verification] Failed to save to session storage:', e);
    }

    // Reload parent window if available
    if (window.opener && !window.opener.closed) {
        try {
            console.log('[Mail Verification] Reloading parent window...');
            window.opener.location.reload();
            console.log('[Mail Verification] Parent window reloaded');
        } catch (e) {
            console.error('[Mail Verification] Failed to reload parent window:', e);
        }
    } else {
        console.log('[Mail Verification] Parent window not available');
        console.log('[Mail Verification] Please return to the original page and reload');

        // Alternative: Use BroadcastChannel for cross-tab communication
        try {
            const channel = new BroadcastChannel('mail_test_channel');
            channel.postMessage({
                type: 'mail_receive_test_completed',
                timestamp: new Date().toISOString()
            });
            console.log('[Mail Verification] Sent message via BroadcastChannel');
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

// Initialize on page load
window.addEventListener('load', initVerificationSuccess);

console.log('[Mail Verification Success] Script loaded');
