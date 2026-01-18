/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * Mail Settings Component
 * Handles mail settings page functionality for admin context
 * - Session polling for test status updates
 * - Mail field monitoring and test reset
 * - postMessage handling for mail verification
 */

class MailSettings {
    constructor(config) {
        this.config = config;
        this.routes = config.routes || {};
        this.translations = config.translations || {};
        this.lastSessionState = null;
        this.sessionPollingInterval = null;

        this.init();
    }

    init() {
        // メール認証ウィンドウからのpostMessageを受信
        window.addEventListener('message', (event) => {
            if (event.origin !== window.location.origin) {
                return;
            }

            if (event.data.type === 'mail_receive_test_completed') {
                if (typeof window.updateTestStatus === 'function') {
                    window.updateTestStatus('receive', true, null);
                }
                this.showNotification('success', this.translations.mailReceiveTestCompleted);
            }
        });

        // セッションポーリング開始
        this.startSessionPolling();

        // ページ離脱時にポーリング停止
        window.addEventListener('beforeunload', () => {
            this.stopSessionPolling();
        });

        // メール設定フィールドの監視
        this.setupFieldMonitoring();
    }

    setupFieldMonitoring() {
        const mailFields = [
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address'
        ];

        mailFields.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.addEventListener('input', () => this.resetMailTestStatus());
                field.addEventListener('change', () => this.resetMailTestStatus());
            }
        });
    }

    startSessionPolling() {
        this.sessionPollingInterval = setInterval(() => this.checkSessionStatus(), 3000);
    }

    stopSessionPolling() {
        if (this.sessionPollingInterval) {
            clearInterval(this.sessionPollingInterval);
            this.sessionPollingInterval = null;
        }
    }

    async checkSessionStatus() {
        try {
            const response = await fetch(this.routes.checkTestSession, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                const currentState = data.data;

                if (this.lastSessionState === null) {
                    this.lastSessionState = currentState;
                    return;
                }

                if (this.hasSessionStateChanged(this.lastSessionState, currentState)) {
                    this.updateUIFromSessionState(currentState);
                    this.lastSessionState = currentState;
                }
            }
        } catch (error) {
            console.log('Session check error', error);
        }
    }

    hasSessionStateChanged(oldState, newState) {
        return (
            oldState.connection_tested !== newState.connection_tested ||
            oldState.send_tested !== newState.send_tested ||
            oldState.receive_tested !== newState.receive_tested ||
            oldState.connection_test_date !== newState.connection_test_date ||
            oldState.send_test_date !== newState.send_test_date ||
            oldState.receive_test_date !== newState.receive_test_date
        );
    }

    updateUIFromSessionState(state) {
        if (typeof window.updateTestStatus === 'function') {
            if (state.connection_tested) {
                window.updateTestStatus('connection', true, state.connection_test_date);
            }

            if (state.send_tested) {
                window.updateTestStatus('send', true, state.send_test_date);
            }

            if (state.receive_tested) {
                window.updateTestStatus('receive', true, state.receive_test_date);
                this.showNotification('success', this.translations.mailReceiveTestCompleted);
            }
        }
    }

    resetMailTestStatus() {
        fetch(this.routes.clearTestSession, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        }).catch(error => {
            console.log('Session clear error', error);
        });

        this.resetTestStatusUI();
        this.disableMailTestButton();
    }

    resetTestStatusUI() {
        const testTypes = ['connection', 'send', 'receive'];

        testTypes.forEach(testType => {
            // 詳細表示のアイコンをリセット
            const iconId = `${testType}-test-icon`;
            const icon = document.getElementById(iconId);
            if (icon) {
                icon.outerHTML = `<i class="mr-2 fas fa-times-circle text-gray-400" id="${iconId}"></i>`;
            }

            // メイン表示のアイコンもリセット
            const iconMainId = `${testType}-test-icon-main`;
            const iconMain = document.getElementById(iconMainId);
            if (iconMain) {
                iconMain.outerHTML = `<i class="mr-2 fas fa-times-circle text-gray-400" id="${iconMainId}"></i>`;
            }

            const text = document.getElementById(`${testType}-test-text`);
            if (text) {
                text.className = 'text-sm text-gray-600 dark:text-gray-400';
            }

            // メイン表示のテキストもリセット
            const textMain = document.getElementById(`${testType}-test-text-main`);
            if (textMain) {
                textMain.className = 'text-sm text-gray-600 dark:text-gray-400';
            }

            const dateSpan = document.getElementById(`${testType}-test-date`);
            if (dateSpan) {
                dateSpan.textContent = '';
            }

            // メイン表示の日付もリセット
            const dateSpanMain = document.getElementById(`${testType}-test-date-main`);
            if (dateSpanMain) {
                dateSpanMain.textContent = '';
            }
        });

        const mainStatusDiv = document.querySelector('.mt-6.p-4.border.rounded-lg');
        const mainIcon = mainStatusDiv?.querySelector('i');
        const mainTitle = mainStatusDiv?.querySelector('h3');

        if (mainStatusDiv && mainIcon && mainTitle) {
            mainStatusDiv.className = 'mt-6 p-4 border rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';
            mainIcon.className = 'fas fa-exclamation-triangle text-yellow-400 text-xl';
            mainTitle.className = 'text-sm font-medium text-yellow-800 dark:text-yellow-200';
            mainTitle.textContent = this.translations.mailTestIncomplete;
        }
    }

    disableMailTestButton() {
        const mailTestBtn = document.getElementById('test-mail-btn');
        if (mailTestBtn) {
            mailTestBtn.disabled = true;
            mailTestBtn.className = 'py-2 px-4 rounded transition-colors duration-200 font-bold bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed';
        }
    }

    showNotification(type, message, duration = 5000) {
        // グローバル通知システムを使用（notification.js）
        if (typeof window.showNotification === 'function') {
            window.showNotification(type, message, duration);
        }
    }
}

// グローバルに公開
window.MailSettings = MailSettings;

// DOMContentLoaded時に自動初期化
document.addEventListener('DOMContentLoaded', function () {
    const mailSettingsContainer = document.querySelector('[data-mail-settings-config]');

    if (mailSettingsContainer) {
        try {
            const config = JSON.parse(mailSettingsContainer.dataset.mailSettingsConfig);
            window.mailSettingsInstance = new MailSettings(config);
        } catch (e) {
            console.error('Failed to initialize MailSettings:', e);
        }
    }
});
