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
 * Mail Form Component (Install Context)
 * Handles mail server form input monitoring and test result reset for installation context
 */

import { MailFormBase } from './settings-base';

class MailForm extends MailFormBase {
    constructor(config) {
        super(config);
        this.init();
    }

    init() {
        // メール設定の入力フィールドを監視（.mail-setting-inputクラスを持つ要素）
        const mailInputs = document.querySelectorAll('.mail-setting-input');

        mailInputs.forEach(input => {
            input.addEventListener('change', () => this.onFieldChange());
            input.addEventListener('input', () => this.onFieldChange());
        });
    }

    /**
     * フィールド変更時の処理（オーバーライド）
     */
    async onFieldChange() {
        await this.resetMailTestStatus();

        // インストール画面固有: グローバル変数をリセット
        this.resetGlobalVariables();

        // インストール画面固有: mail-test.jsのメインステータス更新を呼び出し
        this.updateInstallMainStatus();
    }

    /**
     * グローバル変数をリセット（インストール画面固有）
     */
    resetGlobalVariables() {
        if (typeof connectionTested !== 'undefined') {
            window.connectionTested = false;
        }
        if (typeof sendTested !== 'undefined') {
            window.sendTested = false;
        }
        if (typeof receiveTested !== 'undefined') {
            window.receiveTested = false;
        }
    }

    /**
     * インストール画面のメインステータスを更新（インストール画面固有）
     */
    updateInstallMainStatus() {
        if (window.mailTestInstance && typeof window.mailTestInstance.updateInstallMainStatus === 'function') {
            window.mailTestInstance.updateInstallMainStatus();
        }
    }

    /**
     * テストボタンを無効化（オーバーライド）
     */
    disableMailTestButton() {
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
}

// グローバルに公開
window.MailForm = MailForm;

// DOMContentLoaded時に自動初期化
document.addEventListener('DOMContentLoaded', function () {
    const mailFormContainer = document.querySelector('[data-mail-server-form-config]');

    if (mailFormContainer) {
        try {
            const config = JSON.parse(mailFormContainer.dataset.mailServerFormConfig);
            window.mailFormInstance = new MailForm(config);
        } catch (e) {
            console.error('Failed to initialize MailForm:', e);
        }
    }
});
