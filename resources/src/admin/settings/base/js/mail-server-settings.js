/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * Mail Form Component (Admin Context)
 * Handles mail settings page functionality for admin context
 * - Session polling for test status updates
 * - Mail field monitoring and test reset
 * - postMessage handling for mail verification
 */

import { MailFormBase } from '../../../../components/mail-server/js/settings-base';

class MailForm extends MailFormBase {
    constructor(config) {
        super(config);

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
        this.setupMailFieldMonitoring();
    }

    setupMailFieldMonitoring() {
        const mailFields = [
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address'
        ];

        this.setupFieldMonitoring(mailFields);
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
}

// グローバルに公開
window.MailForm = MailForm;

// DOMContentLoaded時に自動初期化
document.addEventListener('DOMContentLoaded', function () {
    const mailFormContainer = document.querySelector('[data-mail-settings-config]');

    if (mailFormContainer) {
        try {
            const config = JSON.parse(mailFormContainer.dataset.mailSettingsConfig);
            window.mailFormInstance = new MailForm(config);
        } catch (e) {
            console.error('Failed to initialize MailForm:', e);
        }
    }
});
