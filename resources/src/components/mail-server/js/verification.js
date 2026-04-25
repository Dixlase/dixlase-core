/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 * メール認証ページ（成功・エラー）の機能を管理するクラス
 */
class MailVerification {
    constructor(config) {
        this.type = config.type; // 'success' or 'error'
        this.message = config.message;
        this.autoCloseDelay = config.autoCloseDelay || 5000;

        this.init();
    }

    init() {
        // ダークモードを適用
        this.applyTheme();

        // ページ読み込み完了時の処理
        window.addEventListener('load', () => {
            if (this.type === 'success') {
                this.handleSuccess();
            }
        });

        // 自動クローズタイマー
        this.setupAutoClose();
    }

    /**
     * ダークモードを適用
     */
    applyTheme() {
        let theme = null;

        // 1. 親ウィンドウの設定を確認
        if (window.opener && !window.opener.closed) {
            try {
                theme = window.opener.localStorage.getItem('theme');
            } catch (e) {
                console.log('親ウィンドウのテーマ取得失敗:', e);
            }
        }

        // 2. 親ウィンドウから取得できない場合は自身のlocalStorageを確認
        if (!theme) {
            theme = localStorage.getItem('theme');
        }

        // 3. どちらもない場合はシステム設定を使用
        if (!theme) {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                theme = 'dark';
            } else {
                theme = 'light';
            }
        }

        // 4. テーマを適用
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        console.log('適用されたテーマ:', theme);
    }

    /**
     * 成功時の処理
     */
    handleSuccess() {
        console.log('=== メール認証成功ページ読み込み完了 ===');

        // セッションストレージに受信テスト完了を記録
        try {
            sessionStorage.setItem('mail_receive_test_completed', 'true');
            sessionStorage.setItem('mail_receive_test_date', new Date().toLocaleString());
            console.log('✅ セッションストレージに受信テスト完了を記録しました');
        } catch (e) {
            console.error('❌ セッションストレージへの保存に失敗しました:', e);
        }

        // 親ウィンドウをリロード（可能な場合）
        if (window.opener && !window.opener.closed) {
            try {
                console.log('親ウィンドウをリロードします...');
                window.opener.location.reload();
                console.log('✅ 親ウィンドウのリロード完了');
            } catch (e) {
                console.error('❌ 親ウィンドウのリロードに失敗しました:', e);
            }
        } else {
            console.log('❌ 親ウィンドウが存在しないか閉じられています');
            console.log('💡 元のページに戻ってリロードしてください');

            // 代替案：BroadcastChannelを使用してタブ間通信
            try {
                const channel = new BroadcastChannel('mail_test_channel');
                channel.postMessage({
                    type: 'mail_receive_test_completed',
                    timestamp: new Date().toISOString()
                });
                console.log('✅ BroadcastChannelでメッセージを送信しました');
                channel.close();
            } catch (e) {
                console.error('❌ BroadcastChannelの送信に失敗しました:', e);
            }
        }
    }

    /**
     * ウィンドウを閉じる
     */
    closeWindow() {
        // 親ウィンドウにメッセージを送信
        if (window.opener) {
            const messageType = this.type === 'success'
                ? 'mail_receive_test_completed'
                : 'mail_verification_error';

            window.opener.postMessage({
                type: messageType,
                message: this.message
            }, window.location.origin);
        }

        // ウィンドウを閉じる
        window.close();
    }

    /**
     * 自動クローズタイマーを設定
     */
    setupAutoClose() {
        setTimeout(() => {
            if (window.opener) {
                window.close();
            }
        }, this.autoCloseDelay);
    }
}

// グローバルに公開
window.MailVerification = MailVerification;

// グローバル関数として公開（後方互換性のため）
window.closeWindow = function () {
    if (window.mailVerificationInstance) {
        window.mailVerificationInstance.closeWindow();
    }
};

// DOMContentLoaded時に自動初期化
document.addEventListener('DOMContentLoaded', function () {
    const container = document.querySelector('[data-mail-verification-config]');

    if (container) {
        try {
            const config = JSON.parse(container.dataset.mailVerificationConfig);
            window.mailVerificationInstance = new MailVerification(config);
        } catch (e) {
            console.error('Failed to initialize MailVerification:', e);
        }
    }
});
