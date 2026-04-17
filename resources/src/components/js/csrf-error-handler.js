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
 * CSRF / Session Expired ハンドラ
 *
 * Laravel が 419 を返した場合に、共通の「セッションが切れました」モーダルを開く。
 *
 * 方針:
 * 1. window.fetch をラップして 419 レスポンスを検出する
 * 2. 検出したら `csrfErrorModal` を開く
 * 3. 既存の `.then(data => data.message)` 経由のアラートで古い文言が出ないよう、
 *    レスポンスボディをローカライズ済みメッセージで置き換えた新しい Response を返す
 *
 * これにより、個別の fetch 呼び出しに手を入れなくてもメッセージが統一される。
 */

function getCsrfTranslations() {
    if (typeof window !== 'undefined' && window.csrfErrorTranslations) {
        return window.csrfErrorTranslations;
    }

    return {
        title: 'Session Expired',
        message: 'Your session has expired. Please reload the page.',
        reload: 'Reload Page',
    };
}

function showCsrfErrorModal() {
    if (typeof window.openModal === 'function') {
        window.openModal('csrfErrorModal');
    }
}

if (typeof window !== 'undefined' && typeof window.fetch === 'function' && ! window.__dixlaseCsrfFetchPatched) {
    const originalFetch = window.fetch.bind(window);

    window.fetch = async function (...args) {
        const response = await originalFetch(...args);
        if (response && response.status === 419) {
            showCsrfErrorModal();

            // 呼び出し側で .json() される場合に、既存ロジックのアラート文言が「CSRF token mismatch」になるのを防ぐため、
            // ローカライズ済みメッセージで新しい Response を返す。
            const translations = getCsrfTranslations();

            return new Response(JSON.stringify({
                success: false,
                message: translations.message,
                csrf_expired: true,
            }), {
                status: 419,
                headers: { 'Content-Type': 'application/json' },
            });
        }

        return response;
    };

    window.__dixlaseCsrfFetchPatched = true;
}

// 外部から利用できるようにグローバル公開
if (typeof window !== 'undefined') {
    window.showCsrfErrorModal = showCsrfErrorModal;
}

export { showCsrfErrorModal };
