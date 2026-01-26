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
 * ページ読み込み前にダークモードを即座に適用（フラッシュ防止）
 */
(function () {
    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.classList.add('dark');
    }
})();

/**
 * Alpine.js ダークモード検出関数
 */
window.installTheme = function () {
    return {
        isDark: window.matchMedia('(prefers-color-scheme: dark)').matches,

        init() {
            // ダークモード設定の変更を監視
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                this.isDark = e.matches;
            });
        }
    }
};

/**
 * ブラウザの言語設定を取得して、利用可能な言語と照合する
 */
window.detectBrowserLanguage = function (availableLocales) {
    // ブラウザの言語設定を取得
    const browserLanguages = navigator.languages || [navigator.language || navigator.userLanguage];

    // 利用可能な言語と照合
    for (const lang of browserLanguages) {
        // 完全一致を確認 (例: 'ja')
        if (availableLocales.includes(lang)) {
            return lang;
        }

        // 言語コードのみで一致を確認 (例: 'ja-JP' から 'ja' を抽出)
        const langCode = lang.split('-')[0];
        if (availableLocales.includes(langCode)) {
            return langCode;
        }
    }

    // デフォルトは 'en' を返す
    return 'en';
};

/**
 * 言語切り替え機能の初期化
 */
window.initLanguageSwitcher = function (config) {
    const {
        availableLocales,
        currentLocale,
        fallbackLocale,
        languageChangeUrl,
        csrfToken,
        messages
    } = config;

    // 言語切り替えフォームとセレクタの取得
    const languageForm = document.getElementById('language-form');
    const languageSelector = document.getElementById('language-selector');

    if (!languageForm || !languageSelector) {
        return;
    }

    // セッションが開始されているかチェック
    const hasExistingSession = sessionStorage.getItem('language_manually_changed') ||
        config.sessionLocale ||
        document.cookie.includes('install_locale=');

    // セッションが存在しない初回のみブラウザ言語を自動検出
    if (!hasExistingSession && currentLocale === fallbackLocale && !new URLSearchParams(window.location.search).has('lang')) {
        const detectedLang = window.detectBrowserLanguage(availableLocales);
        if (detectedLang && detectedLang !== currentLocale) {
            // 初回のみAJAXで言語を変更
            changeLanguage(detectedLang);
        }
    }

    // セレクト変更で即時適用
    languageSelector.addEventListener('change', function (e) {
        e.preventDefault();
        const locale = languageSelector.value;
        console.log('Language selector changed to:', locale);
        languageSelector.disabled = true;
        changeLanguage(locale);
    });

    // フォーム送信を完全に無効化
    languageForm.addEventListener('submit', function (e) {
        e.preventDefault();
        console.log('Form submit prevented');
        return false;
    });

    function changeLanguage(locale) {
        // 手動変更フラグを設定
        sessionStorage.setItem('language_manually_changed', 'true');

        fetch(`${languageChangeUrl}/${locale}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({})
        })
            .then(async (response) => {
                const contentType = response.headers.get('content-type') || '';
                if (!response.ok) {
                    const text = await response.text().catch(() => '');
                    console.error('Language change failed. Status:', response.status, text);
                    throw new Error('Request failed');
                }
                if (contentType.includes('application/json')) {
                    return response.json();
                } else {
                    // 予期せぬHTML等が返った場合でもリロードを試みる
                    return { success: true };
                }
            })
            .then(data => {
                console.log('Language change response:', data);
                if (data && data.success) {
                    console.log('Language change successful, reloading page...');
                    window.location.reload();
                } else {
                    console.error('Language change failed:', data);
                    alert((data && data.message) || messages.switchFailed);
                    if (languageSelector) languageSelector.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(messages.errorOccurred);
                if (languageSelector) languageSelector.disabled = false;
            });
    }
};

/**
 * ページ読み込み時に自動初期化
 */
document.addEventListener('DOMContentLoaded', function () {
    const configElement = document.getElementById('install-layout-config');

    if (configElement) {
        const config = {
            availableLocales: JSON.parse(configElement.dataset.availableLocales || '[]'),
            currentLocale: configElement.dataset.currentLocale || 'en',
            fallbackLocale: configElement.dataset.fallbackLocale || 'en',
            languageChangeUrl: configElement.dataset.languageChangeUrl || '',
            csrfToken: configElement.dataset.csrfToken || '',
            sessionLocale: configElement.dataset.sessionLocale || '',
            messages: {
                switchFailed: configElement.dataset.msgSwitchFailed || 'Language switch failed.',
                errorOccurred: configElement.dataset.msgErrorOccurred || 'An error occurred. Please reload the page.'
            }
        };

        window.initLanguageSwitcher(config);
    }
});
