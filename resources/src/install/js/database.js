/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
 * データベース設定ページの機能
 */

let isDbTestSuccessful = false;

/**
 * パスワード表示/非表示の切り替え
 */
window.togglePassword = function () {
    const passwordField = document.getElementById('db_password');
    const eyeIcon = document.getElementById('password-eye');

    if (!passwordField || !eyeIcon) {
        return;
    }

    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
};

/**
 * データベース接続テスト
 */
window.testDatabaseConnection = function () {
    const dbHost = document.getElementById('db_host')?.value;
    const dbPort = document.getElementById('db_port')?.value;
    const dbDatabase = document.getElementById('db_database')?.value;
    const dbUsername = document.getElementById('db_username')?.value;
    const dbPassword = document.getElementById('db_password')?.value;
    const dbConnection = document.getElementById('db_connection')?.value;

    const testUrl = document.getElementById('db-test-url')?.value || '/install/test-db';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch(testUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            db_connection: dbConnection,
            db_host: dbHost,
            db_port: dbPort,
            db_database: dbDatabase,
            db_username: dbUsername,
            db_password: dbPassword
        })
    })
        .then(response => response.json())
        .then(data => {
            const resultElement = document.getElementById('db-test-result');
            if (resultElement) {
                resultElement.innerText = data.message;

                if (data.success) {
                    resultElement.classList.remove('text-red-600', 'dark:text-red-400');
                    resultElement.classList.add('text-green-600', 'dark:text-green-400');
                } else {
                    resultElement.classList.remove('text-green-600', 'dark:text-green-400');
                    resultElement.classList.add('text-red-600', 'dark:text-red-400');
                }
            }

            isDbTestSuccessful = data.success;
            updateNextButtonState();
        })
        .catch(error => {
            console.error('Error:', error);
            isDbTestSuccessful = false;
            updateNextButtonState();
        });
};

/**
 * 次へボタンの状態を更新
 */
function updateNextButtonState() {
    const nextButton = document.getElementById('next-button');
    const resultMessage = document.getElementById('db-test-result');
    const tooltip = document.getElementById('tooltip');

    if (!nextButton) {
        return;
    }

    if (isDbTestSuccessful) {
        nextButton.disabled = false;
        nextButton.classList.remove('bg-blue-400', 'dark:bg-blue-400', 'cursor-not-allowed');
        nextButton.classList.add('bg-blue-600', 'dark:bg-blue-500', 'hover:bg-blue-700', 'dark:hover:bg-blue-600');

        if (resultMessage) {
            const successMessage = document.getElementById('db-success-message')?.value || 'Database connection successful';
            resultMessage.innerText = successMessage;
            resultMessage.classList.remove('text-red-600', 'dark:text-red-400');
            resultMessage.classList.add('text-green-600', 'dark:text-green-400');
        }

        if (tooltip) {
            tooltip.classList.add('hidden');
        }
    } else {
        nextButton.disabled = true;
        nextButton.classList.remove('bg-blue-600', 'dark:bg-blue-500', 'hover:bg-blue-700', 'dark:hover:bg-blue-600');
        nextButton.classList.add('bg-blue-400', 'dark:bg-blue-400', 'cursor-not-allowed');

        if (tooltip) {
            tooltip.classList.remove('hidden');
        }
    }
}
