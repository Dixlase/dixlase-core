/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * Mail Form Base Class
 * Shared functionality for mail form components across different contexts
 * - Field monitoring for mail settings
 * - Test status UI reset
 * - Server-side test session reset
 */

export class MailFormBase {
    constructor(config) {
        this.config = config;
        this.routes = config.routes || {};
        this.translations = config.translations || {};
    }

    /**
     * メール設定フィールドの監視をセットアップ
     * @param {Array<string>} fieldNames - 監視するフィールド名の配列
     * @param {string} selector - フィールドのセレクター（デフォルト: name属性）
     */
    setupFieldMonitoring(fieldNames, selector = null) {
        fieldNames.forEach(fieldName => {
            const field = selector
                ? document.querySelector(selector)
                : document.querySelector(`[name="${fieldName}"]`);

            if (field) {
                field.addEventListener('input', () => this.onFieldChange());
                field.addEventListener('change', () => this.onFieldChange());
            }
        });
    }

    /**
     * フィールド変更時のコールバック（サブクラスでオーバーライド可能）
     */
    onFieldChange() {
        this.resetMailTestStatus();
    }

    /**
     * メールテストステータスをリセット
     */
    async resetMailTestStatus() {
        // サーバーサイドのセッションをリセット
        await this.resetServerSession();

        // UIをリセット
        this.resetTestStatusUI();

        // テストボタンを無効化
        this.disableMailTestButton();
    }

    /**
     * サーバーサイドのテストセッションをリセット
     */
    async resetServerSession() {
        const resetUrl = this.routes.resetTests || this.routes.clearTestSession;

        if (!resetUrl) {
            console.warn('Reset URL not configured');
            return;
        }

        try {
            const response = await fetch(resetUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const text = await response.text();
            if (text) {
                const data = JSON.parse(text);
                console.log('Mail tests reset response:', data);
            }
        } catch (error) {
            console.error('Error resetting mail tests:', error);
        }
    }

    /**
     * テストステータスUIをリセット
     */
    resetTestStatusUI() {
        const testTypes = ['connection', 'send', 'receive'];

        testTypes.forEach(testType => {
            this.resetTestIcon(testType);
        });

        this.resetMainStatus();
    }

    /**
     * 個別テストアイコンをリセット
     * @param {string} testType - テストタイプ (connection, send, receive)
     */
    resetTestIcon(testType) {
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

        // テキストのスタイルをリセット
        const text = document.getElementById(`${testType}-test-text`);
        if (text) {
            // SVG要素の場合はsetAttributeを使用
            if (text.tagName === 'svg' || text instanceof SVGElement) {
                text.setAttribute('class', 'text-sm text-gray-600 dark:text-gray-400');
            } else {
                text.className = 'text-sm text-gray-600 dark:text-gray-400';
            }
        }

        // メイン表示のテキストもリセット
        const textMain = document.getElementById(`${testType}-test-text-main`);
        if (textMain) {
            // SVG要素の場合はsetAttributeを使用
            if (textMain.tagName === 'svg' || textMain instanceof SVGElement) {
                textMain.setAttribute('class', 'text-sm text-gray-600 dark:text-gray-400');
            } else {
                textMain.className = 'text-sm text-gray-600 dark:text-gray-400';
            }
        }

        // 日付をクリア
        const dateSpan = document.getElementById(`${testType}-test-date`);
        if (dateSpan) {
            dateSpan.textContent = '';
        }

        // メイン表示の日付もクリア
        const dateSpanMain = document.getElementById(`${testType}-test-date-main`);
        if (dateSpanMain) {
            dateSpanMain.textContent = '';
        }
    }

    /**
     * メインステータス表示をリセット
     */
    resetMainStatus() {
        // クラスベースのセレクター（管理画面）
        let mainStatusDiv = document.querySelector('.mt-6.p-4.border.rounded-lg');
        let mainIcon = mainStatusDiv?.querySelector('i');
        let mainTitle = mainStatusDiv?.querySelector('h3');

        // IDベースのセレクター（インストール画面）
        if (!mainStatusDiv) {
            mainStatusDiv = document.getElementById('mail-test-status');
            mainIcon = document.getElementById('status-icon');
            mainTitle = document.getElementById('status-title');
        }

        if (mainStatusDiv && mainIcon && mainTitle) {
            mainStatusDiv.className = 'mt-6 p-4 border rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';

            // SVGアイコンの場合はsetAttributeを使用
            if (mainIcon.tagName === 'svg' || mainIcon.classList.contains('svg-inline--fa')) {
                mainIcon.setAttribute('class', 'fas fa-exclamation-triangle text-yellow-400 text-xl');
            } else {
                mainIcon.className = 'fas fa-exclamation-triangle text-yellow-400 text-xl';
            }

            mainTitle.className = 'text-sm font-medium text-yellow-800 dark:text-yellow-200';
            mainTitle.textContent = this.translations.testIncomplete ||
                this.translations.mailTestIncomplete ||
                'メールテストが未完了です';
        }
    }

    /**
     * メールテストボタンを無効化
     */
    disableMailTestButton() {
        const mailTestBtn = document.getElementById('test-mail-btn');
        if (mailTestBtn) {
            mailTestBtn.disabled = true;
            mailTestBtn.className = 'py-2 px-4 rounded transition-colors duration-200 font-bold bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed';
        }
    }

    /**
     * 通知を表示
     * @param {string} type - 通知タイプ (success, error, warning, info)
     * @param {string} message - 通知メッセージ
     * @param {number} duration - 表示時間（ミリ秒）
     */
    showNotification(type, message, duration = 5000) {
        if (typeof window.showNotification === 'function') {
            window.showNotification(type, message, duration);
        }
    }
}
