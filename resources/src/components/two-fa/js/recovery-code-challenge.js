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
 * Two-Factor Authentication - Recovery Code Challenge Component
 * Handles recovery code input with 4 blocks of 5 digits
 */

/**
 * Initialize recovery code challenge
 * @param {Object} config - Configuration object
 * @param {Object} config.translations - Translation strings
 */
function initRecoveryCodeChallenge(config) {
    const inputs = ['code1', 'code2', 'code3', 'code4'];
    const form = document.getElementById('recoveryCodeForm');

    // Set up event listeners for each input field
    inputs.forEach((id, index) => {
        const input = document.getElementById(id);

        // Input handling
        input.addEventListener('input', function (e) {
            // Allow only numbers
            this.value = this.value.replace(/[^0-9]/g, '');

            // Move to next field when 5 digits entered
            if (this.value.length === 5 && index < inputs.length - 1) {
                document.getElementById(inputs[index + 1]).focus();
            }
        });

        // Key handling
        input.addEventListener('keydown', function (e) {
            // Backspace to previous field
            if (e.key === 'Backspace' && this.value.length === 0 && index > 0) {
                document.getElementById(inputs[index - 1]).focus();
            }

            // Left arrow to previous field
            if (e.key === 'ArrowLeft' && this.selectionStart === 0 && index > 0) {
                const prevInput = document.getElementById(inputs[index - 1]);
                prevInput.focus();
                prevInput.setSelectionRange(prevInput.value.length, prevInput.value.length);
            }

            // Right arrow to next field
            if (e.key === 'ArrowRight' && this.selectionStart === this.value.length && index < inputs.length - 1) {
                const nextInput = document.getElementById(inputs[index + 1]);
                nextInput.focus();
                nextInput.setSelectionRange(0, 0);
            }
        });

        // Paste handling
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');

            if (pastedData.length >= 20) {
                // If 20+ digits, distribute 5 digits to each field
                document.getElementById('code1').value = pastedData.substring(0, 5);
                document.getElementById('code2').value = pastedData.substring(5, 10);
                document.getElementById('code3').value = pastedData.substring(10, 15);
                document.getElementById('code4').value = pastedData.substring(15, 20);
                document.getElementById('code4').focus();
            } else {
                // For shorter data, fill from current field onwards
                let remaining = pastedData;
                for (let i = index; i < inputs.length && remaining.length > 0; i++) {
                    const field = document.getElementById(inputs[i]);
                    const chunk = remaining.substring(0, 5);
                    field.value = chunk;
                    remaining = remaining.substring(5);
                    if (remaining.length > 0 && i < inputs.length - 1) {
                        document.getElementById(inputs[i + 1]).focus();
                    }
                }
            }
        });
    });

    // Form submission - combine values into hidden field
    form.addEventListener('submit', function (e) {
        const code1 = document.getElementById('code1').value;
        const code2 = document.getElementById('code2').value;
        const code3 = document.getElementById('code3').value;
        const code4 = document.getElementById('code4').value;

        // Check if all fields have 5 digits
        if (code1.length !== 5 || code2.length !== 5 || code3.length !== 5 || code4.length !== 5) {
            e.preventDefault();
            alert(config.translations.format_hint);
            return false;
        }

        // Set combined value to hidden field
        document.getElementById('recovery_code').value = code1 + code2 + code3 + code4;
    });
}

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('recoveryCodeForm');
    if (!form) return;

    const configElement = document.getElementById('recovery-code-challenge-config');
    if (!configElement) return;

    try {
        const config = JSON.parse(configElement.textContent);
        initRecoveryCodeChallenge(config);
    } catch (error) {
        console.error('[Recovery Code Challenge] Failed to initialize:', error);
    }
});
