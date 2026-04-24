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
 * Two-Factor Authentication - Passkey Challenge Component
 * Handles WebAuthn passkey authentication
 */

import { base64urlToBuffer, bufferToBase64url } from './webauthn-utils';

/**
 * Initialize passkey challenge
 * @param {Object} config - Configuration object
 * @param {string} config.challengeAction - URL to get challenge
 * @param {string} config.verifyAction - URL to verify authentication
 * @param {string} config.csrfToken - CSRF token
 * @param {string} config.dashboardRoute - Redirect URL after success
 * @param {Object} config.translations - Translation strings
 */
function initPasskeyChallenge(config) {
    let challengeData = null;

    // WebAuthn support check
    function checkWebAuthnSupport() {
        if (!window.PublicKeyCredential) {
            showUnsupported();
            return false;
        }
        return true;
    }

    // Start passkey challenge
    function startPasskeyChallenge() {
        if (!checkWebAuthnSupport()) {
            return;
        }

        showProcessing();

        fetch(config.challengeAction, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    challengeData = data.challenge;
                    performPasskeyAuth();
                } else {
                    showError(data.message || config.translations.challenge_failed);
                }
            })
            .catch(error => {
                console.error('Passkey challenge error:', error);
                showError(config.translations.network_error);
            });
    }

    // Perform WebAuthn authentication
    function performPasskeyAuth() {
        if (!challengeData) {
            showError(config.translations.no_challenge_data);
            return;
        }

        // Base64URL decode
        const challenge = base64urlToBuffer(challengeData.challenge);

        const allowCredentials = challengeData.allowCredentials.map(cred => ({
            id: base64urlToBuffer(cred.id),
            type: cred.type,
            transports: cred.transports
        }));

        const publicKeyCredentialRequestOptions = {
            challenge: challenge,
            allowCredentials: allowCredentials,
            timeout: challengeData.timeout || 60000,
            userVerification: challengeData.userVerification || 'preferred'
        };

        navigator.credentials.get({
            publicKey: publicKeyCredentialRequestOptions
        })
            .then(credential => {
                // Send authentication result to server
                const response = {
                    id: credential.id,
                    rawId: bufferToBase64url(credential.rawId),
                    response: {
                        authenticatorData: bufferToBase64url(credential.response.authenticatorData),
                        clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
                        signature: bufferToBase64url(credential.response.signature),
                        userHandle: credential.response.userHandle ? bufferToBase64url(credential.response.userHandle) : null
                    },
                    type: credential.type
                };

                return fetch(config.verifyAction, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({
                        challenge_id: challengeData.id,
                        response: response
                    })
                });
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess();
                    setTimeout(() => {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            window.location.href = config.dashboardRoute;
                        }
                    }, 2000);
                } else {
                    showError(data.message || config.translations.verification_failed);
                }
            })
            .catch(error => {
                console.error('Passkey authentication error:', error);
                if (error.name === 'NotAllowedError') {
                    showError(config.translations.auth_cancelled);
                } else if (error.name === 'InvalidStateError') {
                    showError(config.translations.invalid_state);
                } else if (error.name === 'NotSupportedError') {
                    showUnsupported();
                } else {
                    showError(config.translations.auth_failed);
                }
            });
    }

    // State display functions
    function showWaiting() {
        hideAllStates();
        document.getElementById('passkey-waiting').classList.remove('hidden');
    }

    function showProcessing() {
        hideAllStates();
        document.getElementById('passkey-processing').classList.remove('hidden');
    }

    function showSuccess() {
        hideAllStates();
        document.getElementById('passkey-success').classList.remove('hidden');
    }

    function showError(message) {
        hideAllStates();
        document.getElementById('passkey-error').classList.remove('hidden');
        document.getElementById('passkey-error-message').textContent = message;
    }

    function showUnsupported() {
        hideAllStates();
        document.getElementById('passkey-unsupported').classList.remove('hidden');
    }

    function hideAllStates() {
        document.getElementById('passkey-waiting').classList.add('hidden');
        document.getElementById('passkey-processing').classList.add('hidden');
        document.getElementById('passkey-success').classList.add('hidden');
        document.getElementById('passkey-error').classList.add('hidden');
        document.getElementById('passkey-unsupported').classList.add('hidden');
    }

    // Event listeners
    document.getElementById('start-passkey-auth').addEventListener('click', startPasskeyChallenge);
    document.getElementById('retry-passkey-auth').addEventListener('click', function () {
        showWaiting();
        startPasskeyChallenge();
    });

    // Initialize
    if (!checkWebAuthnSupport()) {
        showUnsupported();
    } else {
        showWaiting();
    }
}

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('passkey-auth-container');
    if (!container) return;

    const configElement = document.getElementById('passkey-challenge-config');
    if (!configElement) return;

    try {
        const config = JSON.parse(configElement.textContent);
        initPasskeyChallenge(config);
    } catch (error) {
        console.error('[Passkey Challenge] Failed to initialize:', error);
    }
});
