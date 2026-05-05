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
 * デバッグモードの有効・無効を切り替える
 */
window.toggleDebugMode = function () {
    const envSelect = document.getElementById('app_env');
    const debugCheckbox = document.getElementById('app_debug');
    const debugNote = document.getElementById('debug-note');

    if (!envSelect || !debugCheckbox || !debugNote) {
        return;
    }

    if (envSelect.value === 'production') {
        debugCheckbox.disabled = true;
        debugCheckbox.checked = false;
        debugNote.style.display = 'block';
    } else {
        debugCheckbox.disabled = false;
        debugNote.style.display = 'none';
    }
};

/**
 * URL設定の初期化
 */
function initEnvironmentUrlSettings() {
    const appUrlInput = document.getElementById('app_url');
    const forceSslCheckbox = document.getElementById('force_ssl');
    const protocolDisplay = document.getElementById('protocol_display');
    const adminBaseUrl = document.getElementById('admin_base_url');

    if (!appUrlInput || !protocolDisplay) {
        return;
    }

    function updateUrls() {
        // force_sslチェックボックスがある場合はその値を使用
        // ない場合（かんたんモード）は現在のページのプロトコルを使用
        const protocol = forceSslCheckbox
            ? (forceSslCheckbox.checked ? 'https://' : 'http://')
            : window.location.protocol + '//';
        const appUrl = appUrlInput.value.trim().replace(/^(https?:\/\/)?/, '');

        // アプリケーションURLのプロトコル表示を更新
        protocolDisplay.innerText = protocol;

        // 管理画面URLのベースURL表示を更新
        if (adminBaseUrl) {
            adminBaseUrl.innerText = appUrl ? protocol + appUrl + '/' : protocol;
        }
    }

    // 初回ロード時にURLを更新
    updateUrls();

    if (forceSslCheckbox) {
        forceSslCheckbox.addEventListener('change', updateUrls);
    }

    appUrlInput.addEventListener('input', updateUrls);
    appUrlInput.addEventListener('focus', updateUrls);
    appUrlInput.addEventListener('blur', updateUrls);
}

/**
 * ブラウザのタイムゾーンを検出してセレクトに設定
 * - ユーザーが既に選択している（data-user-selected="1"）場合はスキップ
 * - 検出したタイムゾーンが選択肢に存在しない場合もスキップ
 */
function detectAndApplyTimezone() {
    const select = document.getElementById('app_timezone');
    if (!select) {
        return;
    }

    // ユーザーが既に選択済みなら上書きしない
    if (select.dataset.userSelected === '1') {
        return;
    }

    try {
        const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        if (!userTimeZone) {
            return;
        }

        // 検出したタイムゾーンが選択肢に存在するか確認
        const option = Array.from(select.options).find(opt => opt.value === userTimeZone);
        if (option) {
            select.value = userTimeZone;
        }
    } catch (e) {
        console.error('タイムゾーンの検出に失敗しました:', e);
    }
}

/**
 * 環境設定のイベントリスナーを設定
 */
function initEnvironmentEventListeners() {
    const envSelect = document.getElementById('app_env');

    if (envSelect) {
        envSelect.addEventListener('change', window.toggleDebugMode);
    }
}

/**
 * ページ読み込み時に実行
 */
document.addEventListener('DOMContentLoaded', function () {
    // タイムゾーン検出
    detectAndApplyTimezone();

    // デバッグモードの初期化
    window.toggleDebugMode();

    // URL設定の初期化
    initEnvironmentUrlSettings();

    // イベントリスナーの設定
    initEnvironmentEventListeners();
});
