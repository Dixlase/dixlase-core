/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * Mail Server Form Component
 * Handles mail server form input monitoring and test result reset for installation context
 */

class MailServerForm {
    constructor(config) {
        this.config = config;
        this.routes = config.routes || {};
        this.translations = config.translations || {};

        this.init();
    }

    init() {
        // メール設定の入力フィールドを監視
        const mailInputs = document.querySelectorAll('.mail-setting-input');

        mailInputs.forEach(input => {
            input.addEventListener('change', () => this.resetMailTestResults());
            input.addEventListener('input', () => this.resetMailTestResults());
        });
    }

    resetMailTestResults() {
        // セッションをリセット（サーバーサイドでの処理）
        fetch(this.routes.resetTests, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    console.log('Mail tests reset response:', data);
                } catch (e) {
                    console.error('JSON parse error:', e);
                }
            })
            .catch(error => {
                console.error('Error resetting mail tests:', error);
            });

        // エラーが発生してもUIはリセットする
        this.resetTestStatusUI();
    }

    resetTestStatusUI() {
        // メインステータスをリセット
        const mainStatusDiv = document.getElementById('mail-test-status');
        const mainIcon = document.getElementById('status-icon');
        const mainTitle = document.getElementById('status-title');

        if (mainStatusDiv && mainIcon && mainTitle) {
            mainStatusDiv.className = 'mt-6 p-4 border rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';

            // SVGアイコンの場合はsetAttributeを使用
            if (mainIcon.tagName === 'svg' || mainIcon.classList.contains('svg-inline--fa')) {
                mainIcon.setAttribute('class', 'fas fa-exclamation-triangle text-yellow-400 text-xl');
            } else {
                mainIcon.className = 'fas fa-exclamation-triangle text-yellow-400 text-xl';
            }

            mainTitle.className = 'text-sm font-medium text-yellow-800 dark:text-yellow-200';
            mainTitle.textContent = this.translations.testIncomplete || 'メールテストが未完了です';
        }

        // JavaScript変数もリセット
        if (typeof connectionTested !== 'undefined') {
            window.connectionTested = false;
        }
        if (typeof sendTested !== 'undefined') {
            window.sendTested = false;
        }
        if (typeof receiveTested !== 'undefined') {
            window.receiveTested = false;
        }

        // 個別テストアイコンをリセット
        this.resetTestIcon('connection');
        this.resetTestIcon('send');
        this.resetTestIcon('receive');

        // メインステータスを強制的に更新（mail-test.jsの関数を呼び出し）
        if (window.mailTestInstance && typeof window.mailTestInstance.updateInstallMainStatus === 'function') {
            window.mailTestInstance.updateInstallMainStatus();
        }

        // テストボタンを無効化
        const connectionBtn = document.getElementById('test-connection-btn');
        const mailBtn = document.getElementById('test-mail-btn');

        if (connectionBtn) {
            connectionBtn.disabled = false;
        }

        if (mailBtn) {
            mailBtn.disabled = true;
            mailBtn.className = 'py-2 px-4 rounded transition-colors duration-200 font-bold bg-gray-400 text-white cursor-not-allowed';
        }
    }

    resetTestIcon(testType) {
        const icon = document.getElementById(testType + '-test-icon');
        const text = document.getElementById(testType + '-test-text');
        const dateSpan = document.getElementById(testType + '-test-date');

        if (icon) {
            icon.className = 'mr-2 fas fa-times-circle text-gray-400';
        }

        if (text) {
            text.className = 'text-sm text-gray-600 dark:text-gray-400';
        }

        if (dateSpan) {
            dateSpan.textContent = '';
        }
    }
}

// グローバルに公開
window.MailServerForm = MailServerForm;

// DOMContentLoaded時に自動初期化
document.addEventListener('DOMContentLoaded', function () {
    const mailServerFormContainer = document.querySelector('[data-mail-server-form-config]');

    if (mailServerFormContainer) {
        try {
            const config = JSON.parse(mailServerFormContainer.dataset.mailServerFormConfig);
            window.mailServerFormInstance = new MailServerForm(config);
        } catch (e) {
            console.error('Failed to initialize MailServerForm:', e);
        }
    }
});
