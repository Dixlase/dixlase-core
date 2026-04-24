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
 * Mail Test Component
 * Handles mail server connection testing, mail send testing, and receive verification
 * Supports BroadcastChannel for tab communication and localStorage fallback
 */

/**
 * メールテスト管理クラス
 */
class MailTest {
    constructor(config) {
        this.config = config;
        this.translations = config.translations || {};
        this.routes = config.routes || {};
        this.isInstall = config.isInstall || false;
        this.context = config.context || 'admin';
        this.broadcastChannel = null;

        this.init();
    }

    init() {
        // インストール時はページロード時にテスト状態をクリア
        if (this.isInstall) {
            this.clearInstallTestData();
        }

        // ページ読み込み時にセッションストレージから受信テスト完了状態をチェック
        this.checkReceiveTestCompletion();

        // BroadcastChannelでタブ間通信を受信
        this.setupBroadcastChannel();

        // イベントリスナーを設定
        this.setupEventListeners();

        // localStorage監視
        this.setupStorageListener();

        // 定期的にlocalStorageをチェック
        setInterval(() => this.checkLocalStorageForMailTest(), 2000);
        this.checkLocalStorageForMailTest();
    }

    clearInstallTestData() {
        sessionStorage.removeItem('mail_connection_tested');
        sessionStorage.removeItem('mail_send_tested');
        sessionStorage.removeItem('mail_receive_tested');
        sessionStorage.removeItem('mail_connection_test_date');
        sessionStorage.removeItem('mail_send_test_date');
        sessionStorage.removeItem('mail_receive_test_date');
        sessionStorage.removeItem('install_data');
    }

    setupBroadcastChannel() {
        try {
            this.broadcastChannel = new BroadcastChannel('mail_test_channel');
            this.broadcastChannel.addEventListener('message', (event) => {
                if (event.data && event.data.type === 'mail_receive_test_completed') {
                    const testDate = new Date().toLocaleString();
                    this.updateTestStatus('receive', true, testDate);
                    this.showNotification('success', this.translations.mailReceiveVerified);
                }
            });
        } catch (e) {
            console.warn('BroadcastChannel not supported');
        }
    }

    setupEventListeners() {
        // 接続テストボタン
        const connectionTestBtn = document.getElementById('test-connection-btn');
        if (connectionTestBtn) {
            connectionTestBtn.addEventListener('click', () => this.testConnection());
        }

        // メール送信テストボタン
        const mailTestBtn = document.getElementById('test-mail-btn');
        if (mailTestBtn) {
            mailTestBtn.addEventListener('click', () => this.testMail());
        }

        // window.message イベント（メール受信確認完了）
        window.addEventListener('message', (event) => {
            // MetaMaskなどの不要なメッセージをフィルタリング
            if (event.data && typeof event.data === 'object' && event.data.target && event.data.target.includes('metamask')) {
                return;
            }

            if (event.data && event.data.type === 'mail_receive_test_completed') {
                this.updateTestStatus('receive', true, new Date().toLocaleString());
                this.showNotification('success', event.data.message);
            }
        });
    }

    setupStorageListener() {
        window.addEventListener('storage', (e) => {
            if (e.key === 'mail_receive_test_completed' && e.newValue === 'true') {
                const testDate = localStorage.getItem('mail_receive_test_date');
                this.updateTestStatus('receive', true, testDate);

                // localStorage をクリア
                localStorage.removeItem('mail_receive_test_completed');
                localStorage.removeItem('mail_receive_test_date');
            }
        });
    }

    checkReceiveTestCompletion() {
        try {
            const receiveTestCompleted = sessionStorage.getItem('mail_receive_test_completed');
            const receiveTestDate = sessionStorage.getItem('mail_receive_test_date');

            if (receiveTestCompleted === 'true') {
                this.updateTestStatus('receive', true, receiveTestDate);

                // セッションストレージから削除（一度だけ処理）
                sessionStorage.removeItem('mail_receive_test_completed');
                sessionStorage.removeItem('mail_receive_test_date');

                // 成功通知を表示
                this.showNotification('success', this.translations.mailReceiveVerified);
            }
        } catch (e) {
            console.error('Error checking receive test completion:', e);
        }
    }

    async testConnection() {
        const connectionTestRoute = this.routes.connectionTest;

        if (!connectionTestRoute) {
            this.showTestResult('error', this.translations.testRouteNotSet);
            return;
        }

        // ボタンを無効化
        const btn = document.getElementById('test-connection-btn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = this.translations.testing;
        }

        // CSRF トークンを取得
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // フォームからメール設定を取得
        const mailSettings = this.getMailSettings();

        try {
            const response = await fetch(connectionTestRoute, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(mailSettings)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(`HTTP ${response.status}: ${response.statusText} - ${JSON.stringify(errorData)}`);
            }

            const data = await response.json();

            if (data.success) {
                this.updateTestStatus('connection', true, data.test_date);
                this.enableMailTestButton();
                this.showTestResult('success', data.message || this.translations.connectionTestSuccessDefault);
            } else {
                const failedMessage = data.message || this.translations.connectionTestFailedDefault;
                const noteMessage = this.translations.mailTestFailedSideNote;
                this.showTestResult('error', failedMessage + ' ' + noteMessage);
            }
        } catch (error) {
            const mainMessage = this.translations.connectionTestError;
            const noteMessage = this.translations.mailTestFailedSideNote;
            const errorDetails = ': ' + error.message;
            this.showTestResult('error', mainMessage + noteMessage + errorDetails);
        } finally {
            // ボタンを再有効化
            if (btn) {
                btn.disabled = false;
                btn.textContent = this.translations.testConnectionButton;
            }
        }
    }

    async testMail() {
        const mailTestRoute = this.routes.mailTest;

        if (!mailTestRoute) {
            this.showTestResult('error', this.translations.mailTestRouteNotSet);
            return;
        }

        // 接続テストが完了しているかチェック
        if (this.isInstall) {
            const installData = JSON.parse(sessionStorage.getItem('install_data') || '{}');
            if (!installData.mail_connection_tested) {
                this.showTestResult('error', this.translations.connectionTestFirst);
                return;
            }
        }

        // ボタンを無効化
        const btn = document.getElementById('test-mail-btn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = this.translations.testing;
        }

        // CSRF トークンを取得
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // フォームからメール設定を取得
        const mailSettings = this.getMailSettings(true);

        try {
            const response = await fetch(mailTestRoute, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(mailSettings)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(`HTTP ${response.status}: ${response.statusText} - ${JSON.stringify(errorData)}`);
            }

            const data = await response.json();

            if (data.success) {
                this.updateTestStatus('send', true, data.test_date);
                this.showTestResult('success', data.message || this.translations.mailTestSuccessDefault);
            } else {
                const failedMessage = data.message || this.translations.mailTestFailedDefault;
                const noteMessage = this.translations.mailTestFailedSideNote;
                this.showTestResult('error', failedMessage + ' ' + noteMessage);
            }
        } catch (error) {
            const mainMessage = this.translations.mailTestError;
            const noteMessage = this.translations.mailTestFailedSideNote;
            const errorDetails = ': ' + error.message;
            this.showTestResult('error', mainMessage + noteMessage + errorDetails);
        } finally {
            // ボタンを再有効化
            if (btn) {
                btn.disabled = false;
                btn.textContent = this.translations.testMailButton;
            }
        }
    }

    getMailSettings(includeFromName = false) {
        const settings = {
            mail_mailer: document.querySelector('select[name="mail_mailer"]')?.value || '',
            mail_host: document.querySelector('input[name="mail_host"]')?.value || '',
            mail_port: document.querySelector('input[name="mail_port"]')?.value || '',
            mail_username: document.querySelector('input[name="mail_username"]')?.value || '',
            mail_password: document.querySelector('input[name="mail_password"]')?.value || '',
            mail_encryption: document.querySelector('select[name="mail_encryption"]')?.value || '',
            mail_from_address: document.querySelector('input[name="mail_from_address"]')?.value || ''
        };

        if (includeFromName) {
            settings.mail_from_name = document.querySelector('input[name="mail_from_name"]')?.value || '';
        }

        return settings;
    }

    showTestResult(type, message) {
        const resultDiv = document.getElementById('test-result');
        if (!resultDiv) {
            return;
        }

        resultDiv.className = `mt-4 p-4 rounded-xl font-semibold border ${type === 'success'
            ? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900 dark:text-green-200 dark:border-green-700'
            : 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900 dark:text-red-200 dark:border-red-700'
            }`;
        resultDiv.textContent = message;
        resultDiv.classList.remove('hidden');

        // 5秒後に非表示
        setTimeout(() => {
            resultDiv.classList.add('hidden');
        }, 5000);
    }

    showNotification(type, message, duration = 5000) {
        // グローバル通知システムを使用（notification.js）
        if (typeof window.showNotification === 'function') {
            window.showNotification(type, message, duration);
        }
    }

    updateTestStatus(testType, success, testDate) {
        if (success) {
            // 直接IDでアイコンを更新
            const iconId = testType + '-test-icon';
            const icon = document.getElementById(iconId);
            if (icon) {
                icon.outerHTML = `<i class="mr-2 fas fa-circle-check text-green-600" id="${iconId}"></i>`;
            }

            // ステータス表示の要素を更新
            const text = document.getElementById(testType + '-test-text');
            if (text) {
                if (text.tagName === 'svg' || text instanceof SVGElement) {
                    text.setAttribute('class', 'text-sm text-green-700 dark:text-green-300');
                } else {
                    text.className = 'text-sm text-green-700 dark:text-green-300';
                }
            }

            const dateSpan = document.getElementById(testType + '-test-date');
            if (dateSpan && testDate) {
                dateSpan.textContent = `(${testDate})`;
            }

            // メイン表示の要素も更新
            const iconMain = document.getElementById(testType + '-test-icon-main');
            if (iconMain) {
                if (iconMain.tagName === 'svg' || iconMain instanceof SVGElement) {
                    iconMain.setAttribute('class', 'mr-2 fas fa-check-circle text-green-500');
                } else {
                    iconMain.className = 'mr-2 fas fa-check-circle text-green-500';
                }
            }

            const textMain = document.getElementById(testType + '-test-text-main');
            if (textMain) {
                if (textMain.tagName === 'svg' || textMain instanceof SVGElement) {
                    textMain.setAttribute('class', 'text-sm text-green-700 dark:text-green-300');
                } else {
                    textMain.className = 'text-sm text-green-700 dark:text-green-300';
                }
            }

            const dateSpanMain = document.getElementById(testType + '-test-date-main');
            if (dateSpanMain && testDate) {
                dateSpanMain.textContent = `(${testDate})`;
            }

            // セッションストレージに保存（インストール時）
            if (this.isInstall) {
                sessionStorage.setItem(`mail_${testType}_tested`, 'true');
                if (testDate) {
                    sessionStorage.setItem(`mail_${testType}_test_date`, testDate);
                }

                // 従来のinstall_dataも更新（互換性のため）
                const installData = JSON.parse(sessionStorage.getItem('install_data') || '{}');
                installData[`mail_${testType}_tested`] = true;
                installData[`mail_${testType}_test_date`] = testDate;
                sessionStorage.setItem('install_data', JSON.stringify(installData));
            }
        }

        // メインステータス更新
        this.updateInstallMainStatus();
    }

    updateInstallMainStatus() {
        const mainStatusDiv = document.getElementById('mail-test-status');
        const mainIcon = document.getElementById('status-icon');
        const mainTitle = document.getElementById('status-title');

        if (!mainStatusDiv || !mainIcon || !mainTitle) {
            return;
        }

        // テスト完了状態をチェック
        let allTestsComplete = false;

        if (this.isInstall) {
            const testData = {
                mail_connection_tested: sessionStorage.getItem('mail_connection_tested') === 'true',
                mail_send_tested: sessionStorage.getItem('mail_send_tested') === 'true',
                mail_receive_tested: sessionStorage.getItem('mail_receive_tested') === 'true'
            };
            allTestsComplete = testData.mail_connection_tested && testData.mail_send_tested && testData.mail_receive_tested;
        } else {
            const connectionIcon = document.getElementById('connection-test-icon');
            const sendIcon = document.getElementById('send-test-icon');
            const receiveIcon = document.getElementById('receive-test-icon');

            allTestsComplete = connectionIcon?.classList.contains('fa-circle-check') &&
                sendIcon?.classList.contains('fa-circle-check') &&
                receiveIcon?.classList.contains('fa-circle-check');
        }

        if (allTestsComplete) {
            if (mainStatusDiv.tagName === 'svg' || mainStatusDiv instanceof SVGElement) {
                mainStatusDiv.setAttribute('class', 'mt-6 p-4 border rounded-lg bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800');
            } else {
                mainStatusDiv.className = 'mt-6 p-4 border rounded-lg bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
            }
            if (mainIcon.tagName === 'svg' || mainIcon instanceof SVGElement) {
                mainIcon.setAttribute('class', 'fas fa-check-circle text-green-400 text-xl');
            } else {
                mainIcon.className = 'fas fa-check-circle text-green-400 text-xl';
            }
            if (mainTitle.tagName === 'svg' || mainTitle instanceof SVGElement) {
                mainTitle.setAttribute('class', 'text-sm font-medium text-green-800 dark:text-green-200');
            } else {
                mainTitle.className = 'text-sm font-medium text-green-800 dark:text-green-200';
            }
            mainTitle.textContent = this.translations.threeStageTestComplete;
        } else {
            if (mainStatusDiv.tagName === 'svg' || mainStatusDiv instanceof SVGElement) {
                mainStatusDiv.setAttribute('class', 'mt-6 p-4 border rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800');
            } else {
                mainStatusDiv.className = 'mt-6 p-4 border rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';
            }
            if (mainIcon.tagName === 'svg' || mainIcon instanceof SVGElement) {
                mainIcon.setAttribute('class', 'fas fa-exclamation-triangle text-yellow-400 text-xl');
            } else {
                mainIcon.className = 'fas fa-exclamation-triangle text-yellow-400 text-xl';
            }
            if (mainTitle.tagName === 'svg' || mainTitle instanceof SVGElement) {
                mainTitle.setAttribute('class', 'text-sm font-medium text-yellow-800 dark:text-yellow-200');
            } else {
                mainTitle.className = 'text-sm font-medium text-yellow-800 dark:text-yellow-200';
            }
            mainTitle.textContent = this.translations.threeStageTestIncomplete;
        }
    }

    checkLocalStorageForMailTest() {
        try {
            const storedData = localStorage.getItem('mail_receive_test_completed');
            if (storedData) {
                const data = JSON.parse(storedData);
                const now = Date.now();
                // 5分以内のデータのみ有効とする
                if (now - data.timestamp < 300000) {
                    this.updateTestStatus('receive', true, null);
                    this.updateInstallMainStatus();
                    this.showNotification('success', data.message);

                    // 使用済みデータを削除
                    localStorage.removeItem('mail_receive_test_completed');

                    // 管理画面用：セッションに受信テスト完了を記録
                    if (this.context === 'admin' && this.routes.mailSettings) {
                        fetch(this.routes.mailSettings, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                _method: 'POST',
                                action: 'update_receive_test_status'
                            })
                        });
                    }
                }
            }
        } catch (e) {
            console.error('Error checking localStorage:', e);
        }
    }

    enableMailTestButton() {
        const mailTestBtn = document.getElementById('test-mail-btn');
        if (mailTestBtn) {
            mailTestBtn.disabled = false;
            mailTestBtn.className = 'py-2 px-4 rounded transition-colors duration-200 font-bold bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white';
        }
    }
}

// グローバルに公開
window.MailTest = MailTest;

// グローバル関数として公開（後方互換性のため）
window.updateTestStatus = function (testType, success, testDate) {
    if (window.mailTestInstance) {
        window.mailTestInstance.updateTestStatus(testType, success, testDate);
    }
};

// window.showNotificationは削除（notification.jsのグローバル関数を使用）

// DOMContentLoaded時に自動初期化
document.addEventListener('DOMContentLoaded', function () {
    const mailTestContainer = document.querySelector('[data-mail-test-config]');

    if (mailTestContainer) {
        try {
            const config = JSON.parse(mailTestContainer.dataset.mailTestConfig);
            window.mailTestInstance = new MailTest(config);
        } catch (e) {
            console.error('Failed to initialize MailTest:', e);
        }
    }
});
