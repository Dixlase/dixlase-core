/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * Account verification email sender
 * Handles sending verification emails to members/users
 */

console.log('[Account Verification] Script loaded');

(function () {
    let currentEntityId = null;

    /**
     * Open verification email modal
     * @param {number} entityId - Entity ID (member or user)
     */
    window.sendVerificationEmail = function (entityId) {
        console.log('[Account Verification] sendVerificationEmail called with ID:', entityId);
        currentEntityId = entityId;
        if (typeof window.openModal === 'function') {
            console.log('[Account Verification] Opening modal');
            window.openModal('verificationEmailModal');
        } else {
            console.error('[Account Verification] openModal function not found');
        }
    };

    console.log('[Account Verification] sendVerificationEmail function registered');

    /**
     * Initialize verification email sender
     */
    document.addEventListener('DOMContentLoaded', function () {
        const verificationModal = document.getElementById('verificationEmailModal');

        if (verificationModal) {
            const buttons = verificationModal.querySelectorAll('button');
            const confirmButton = buttons[1];

            if (confirmButton) {
                confirmButton.removeAttribute('onclick');
                confirmButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    confirmSendVerificationEmail();
                });
            }
        }
    });

    /**
     * Send verification email after confirmation
     */
    function confirmSendVerificationEmail() {
        if (!currentEntityId) {
            return;
        }

        const button = document.getElementById('send-verification-email-btn');
        const buttonText = button ? (button.querySelector('span') || button) : null;
        const originalText = buttonText ? buttonText.textContent : '';

        if (typeof window.closeModal === 'function') {
            window.closeModal('verificationEmailModal');
        }

        if (button) {
            button.disabled = true;
            if (buttonText) {
                const sendingText = button.dataset.sendingText || 'Sending...';
                buttonText.textContent = sendingText;
            }
        }

        const route = button?.dataset.sendRoute;
        if (!route) {
            console.error('[Account Verification] No send route found');
            return;
        }

        const finalRoute = route.replace(':id', currentEntityId);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const errorMessage = button?.dataset.errorMessage || 'An error occurred';

        fetch(finalRoute, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || errorMessage);
                    if (button) {
                        button.disabled = false;
                        if (buttonText) {
                            buttonText.textContent = originalText;
                        }
                    }
                }
            })
            .catch(error => {
                console.error('[Account Verification] Error:', error);
                alert(errorMessage);
                if (button) {
                    button.disabled = false;
                    if (buttonText) {
                        buttonText.textContent = originalText;
                    }
                }
            });

        currentEntityId = null;
    }
})();
