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
 * Two-Factor Authentication - Email Challenge Component
 * Handles email verification code input and resend functionality.
 * The code is submitted only through the Verify button (or Enter), never automatically.
 */

/**
 * Initialize email challenge form
 * @param {Object} config - Configuration object
 * @param {string} config.resendAction - URL for resending code
 * @param {string} config.csrfToken - CSRF token
 * @param {number} config.codeLength - Length of verification code
 * @param {number} config.expireMinutes - Code expiration time in minutes
 * @param {number} config.resendIntervalSeconds - Interval before allowing resend
 * @param {boolean} config.showExpireTime - Whether to show expiration timer
 * @param {boolean} config.showResend - Whether to show resend button
 * @param {Object} config.translations - Translation strings
 */
function initEmailChallenge(config) {
    const inputs = document.querySelectorAll('#code-inputs input');
    const hiddenInput = document.getElementById('hidden-code');
    const submitButton = document.getElementById('submit-button');
    const resendButton = document.getElementById('resend-button');
    const form = document.getElementById('two-factor-form');

    let resendCountdown = 0;
    let countdownInterval = null;
    let expireInterval = null;

    // Flash message display function
    function showFlashMessage(message, type = 'success') {
        const existingMessage = document.querySelector('.flash-message-dynamic');
        if (existingMessage) {
            existingMessage.remove();
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `flash-message-dynamic mb-6 p-4 font-semibold rounded-xl ${type === 'success'
            ? 'text-green-800 bg-green-100 border border-green-200 dark:text-green-200 dark:bg-green-900 dark:border-green-700'
            : 'text-red-800 bg-red-100 border border-red-200 dark:text-red-200 dark:bg-red-900 dark:border-red-700'
            }`;
        messageDiv.textContent = message;

        form.parentNode.insertBefore(messageDiv, form);

        setTimeout(() => {
            messageDiv.style.transition = 'opacity 0.5s';
            messageDiv.style.opacity = '0';
            setTimeout(() => messageDiv.remove(), 500);
        }, 5000);
    }

    // Code input handling
    inputs.forEach((input, index) => {
        input.addEventListener('input', function (e) {
            const value = e.target.value.replace(/[^0-9]/g, '');
            e.target.value = value;

            if (value && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }

            updateHiddenInput();
            updateSubmitButton();
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                inputs[index - 1].focus();
                inputs[index - 1].value = '';
                updateHiddenInput();
                updateSubmitButton();
            }
        });

        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            const numbers = paste.replace(/[^0-9]/g, '').slice(0, config.codeLength);

            for (let i = 0; i < numbers.length && i < inputs.length; i++) {
                inputs[i].value = numbers[i];
            }

            updateHiddenInput();
            updateSubmitButton();
        });
    });

    function getCodeValue() {
        return Array.from(inputs).map(input => input.value).join('');
    }

    function updateHiddenInput() {
        hiddenInput.value = getCodeValue();
    }

    function updateSubmitButton() {
        const code = getCodeValue();
        submitButton.disabled = code.length !== config.codeLength;
    }

    // Expiration timer function (defined outside to be accessible in resend)
    function startExpireTimer() {
        if (!config.showExpireTime) return;

        if (expireInterval) {
            clearInterval(expireInterval);
        }

        let expireTime = config.expireMinutes * 60;
        const expireElement = document.getElementById('expire-time');

        expireInterval = setInterval(() => {
            expireTime--;
            const minutes = Math.floor(expireTime / 60);
            const seconds = expireTime % 60;
            expireElement.textContent = `${minutes}${config.translations.minutes_suffix}${seconds.toString().padStart(2, '0')}${config.translations.seconds_suffix}`;

            if (expireTime <= 0) {
                clearInterval(expireInterval);
                expireElement.textContent = config.translations.expired;
                inputs.forEach(input => input.disabled = true);
                submitButton.disabled = true;
            }
        }, 1000);
    }

    // Start expiration timer if enabled
    if (config.showExpireTime) {
        startExpireTimer();
    }

    // Resend functionality
    if (config.showResend && config.resendAction) {
        resendButton.addEventListener('click', function () {
            if (resendCountdown > 0) return;

            fetch(config.resendAction, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showFlashMessage(data.message, 'success');

                        startResendCountdown(config.resendIntervalSeconds);

                        if (config.showExpireTime) {
                            startExpireTimer();
                        }

                        inputs.forEach(input => {
                            input.value = '';
                            input.disabled = false;
                        });
                        inputs[0].focus();
                        updateHiddenInput();
                        updateSubmitButton();
                    } else {
                        showFlashMessage(data.message || config.translations.resend_failed, 'error');
                    }
                })
                .catch(error => {
                    console.error('Resend error:', error);
                    showFlashMessage(config.translations.network_error, 'error');
                });
        });

        function startResendCountdown(seconds) {
            resendCountdown = seconds;
            resendButton.disabled = true;
            document.getElementById('resend-countdown').classList.remove('hidden');

            countdownInterval = setInterval(() => {
                resendCountdown--;
                document.getElementById('resend-countdown').textContent = `(${resendCountdown}${config.translations.seconds_suffix})`;

                if (resendCountdown <= 0) {
                    clearInterval(countdownInterval);
                    resendButton.disabled = false;
                    document.getElementById('resend-countdown').classList.add('hidden');
                }
            }, 1000);
        }
    }

    // Focus first input
    inputs[0].focus();
}

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('two-factor-form');
    if (!form) return;

    const configElement = document.getElementById('email-challenge-config');
    if (!configElement) return;

    try {
        const config = JSON.parse(configElement.textContent);
        initEmailChallenge(config);
    } catch (error) {
        console.error('[Email Challenge] Failed to initialize:', error);
    }
});
