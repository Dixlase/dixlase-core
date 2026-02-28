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
 *
 * CAPTCHA設定ページ用JavaScript
 */

/**
 * 設定値を読み取るヘルパー
 */
function getCaptchaConfig() {
    const el = document.getElementById('captcha-config');
    if (!el) return {};
    return {
        validateUrl: el.dataset.validateUrl || '',
        msgRequiredFieldsEmpty: el.dataset.msgRequiredFieldsEmpty || '',
        msgValidationError: el.dataset.msgValidationError || '',
        msgSuccessTitle: el.dataset.msgSuccessTitle || '',
        msgFailedTitle: el.dataset.msgFailedTitle || '',
    };
}

/**
 * CAPTCHAウィジェットの検証を開始
 */
window.validateCaptchaWidget = function () {
    const config = getCaptchaConfig();
    const driver = document.getElementById('captcha_driver').value;
    const siteKey = document.getElementById('captcha_site_key').value;
    const secretKey = document.getElementById('captcha_secret_key').value;

    if (!driver || !siteKey || !secretKey) {
        showTestResult('error', config.msgRequiredFieldsEmpty);
        return;
    }

    // ドライバーに応じてCAPTCHAを読み込み・実行
    if (driver === 'google') {
        loadGoogleRecaptcha(siteKey);
    } else if (driver === 'turnstile') {
        loadTurnstile(siteKey);
    } else if (driver === 'google_enterprise') {
        loadGoogleEnterprise(siteKey);
    }
};

/**
 * Google reCAPTCHA v2/v3 を読み込み
 */
function loadGoogleRecaptcha(siteKey) {
    const version = document.getElementById('captcha_google_version').value;
    const container = document.getElementById('captcha-widget-container');

    // 既存スクリプトを削除
    const existingScript = document.getElementById('recaptcha-script');
    if (existingScript) existingScript.remove();

    const script = document.createElement('script');
    script.id = 'recaptcha-script';

    if (version === 'v3') {
        script.src = `https://www.google.com/recaptcha/api.js?render=${siteKey}`;
        script.onload = () => {
            grecaptcha.ready(() => {
                grecaptcha.execute(siteKey, {action: 'test'}).then(token => {
                    validateToken(token);
                });
            });
        };
    } else {
        script.src = 'https://www.google.com/recaptcha/api.js';
        script.onload = () => {
            container.innerHTML = '<div id="recaptcha-widget"></div>';
            grecaptcha.ready(() => {
                grecaptcha.render('recaptcha-widget', {
                    sitekey: siteKey,
                    callback: validateToken
                });
            });
        };
    }

    document.head.appendChild(script);
}

/**
 * Cloudflare Turnstile を読み込み
 */
function loadTurnstile(siteKey) {
    const container = document.getElementById('captcha-widget-container');
    container.innerHTML = '<div class="cf-turnstile" data-sitekey="' + siteKey + '" data-callback="validateToken"></div>';

    const script = document.createElement('script');
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    document.head.appendChild(script);
}

/**
 * Google reCAPTCHA Enterprise を読み込み
 */
function loadGoogleEnterprise(siteKey) {
    const script = document.createElement('script');
    script.src = `https://www.google.com/recaptcha/enterprise.js?render=${siteKey}`;
    script.onload = () => {
        grecaptcha.enterprise.ready(() => {
            grecaptcha.enterprise.execute(siteKey, {action: 'test'}).then(token => {
                validateToken(token);
            });
        });
    };
    document.head.appendChild(script);
}

/**
 * CAPTCHAトークンをサーバーで検証
 */
window.validateToken = function (token) {
    const config = getCaptchaConfig();

    fetch(config.validateUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            token: token,
            driver: document.getElementById('captcha_driver').value,
            secret_key: document.getElementById('captcha_secret_key').value,
            site_key: document.getElementById('captcha_site_key').value,
            project_id: document.getElementById('captcha_google_project_id')?.value || '',
            min_score: document.getElementById('captcha_google_min_score')?.value || '0.5'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showTestResult('success', data.message);
            document.getElementById('captcha-authentication-result').value = '1';
        } else {
            showTestResult('error', data.message);
        }
    })
    .catch(() => {
        showTestResult('error', config.msgValidationError);
    });
};

/**
 * テスト結果を表示
 */
function showTestResult(type, message) {
    const config = getCaptchaConfig();
    const resultDiv = document.getElementById('captcha-test-result');
    const icon = document.getElementById('captcha-test-icon');
    const title = document.getElementById('captcha-test-title');
    const msg = document.getElementById('captcha-test-message');

    resultDiv.style.display = 'block';

    if (type === 'success') {
        resultDiv.className = 'mb-4 p-3 border rounded-lg bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
        icon.className = 'fas fa-check-circle text-green-500 text-xl mr-3';
        title.className = 'font-semibold text-green-700 dark:text-green-300';
        title.textContent = config.msgSuccessTitle;

        // 認証成功時に「認証テストが必要です」メッセージを非表示にする
        const requiredNotice = document.getElementById('auth-test-required-notice');
        if (requiredNotice) requiredNotice.style.display = 'none';

        // 認証成功表示を表示
        const successDisplay = document.getElementById('captcha-success-display');
        if (successDisplay) successDisplay.style.display = 'block';
    } else {
        resultDiv.className = 'mb-4 p-3 border rounded-lg bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
        icon.className = 'fas fa-times-circle text-red-500 text-xl mr-3';
        title.className = 'font-semibold text-red-700 dark:text-red-300';
        title.textContent = config.msgFailedTitle;
    }

    msg.textContent = message;
}

/**
 * CAPTCHAウィジェットをクリアする
 */
window.clearCaptchaWidget = function () {
    const container = document.getElementById('captcha-widget-container');
    if (container) {
        container.innerHTML = '';
    }

    // 既存のスクリプトを削除
    const recaptchaScript = document.getElementById('recaptcha-script');
    if (recaptchaScript) recaptchaScript.remove();

    // Turnstileのスクリプトも削除
    document.querySelectorAll('script[src*="challenges.cloudflare.com"]').forEach(s => s.remove());
    document.querySelectorAll('script[src*="recaptcha/enterprise"]').forEach(s => s.remove());
    document.querySelectorAll('script[src*="recaptcha/api"]').forEach(s => s.remove());

    // Google reCAPTCHAの右下バッジを削除
    document.querySelectorAll('.grecaptcha-badge').forEach(el => el.remove());

    // Google reCAPTCHAが追加するiframeやdivを削除
    document.querySelectorAll('iframe[src*="recaptcha"]').forEach(el => el.remove());
    document.querySelectorAll('div[style*="visibility: visible"]').forEach(el => {
        if (el.querySelector('iframe[src*="recaptcha"]')) {
            el.remove();
        }
    });

    // grecaptchaオブジェクトをリセット
    if (typeof grecaptcha !== 'undefined' && grecaptcha.reset) {
        try { grecaptcha.reset(); } catch (e) { /* ignore */ }
    }

    // grecaptchaオブジェクト自体を削除
    if (typeof grecaptcha !== 'undefined') {
        try {
            delete window.grecaptcha;
            delete window.___grecaptcha_cfg;
        } catch (e) { /* ignore */ }
    }
};
