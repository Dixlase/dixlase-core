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
 *
 * API設定ページ用JavaScript（Alpine.jsコンポーネント）
 */

import Alpine from 'alpinejs';

Alpine.data('apiSettings', () => {
    const configEl = document.getElementById('api-settings-config');
    const config = configEl ? JSON.parse(configEl.textContent) : {};

    return {
        apiEnabled: config.apiEnabled || '0',
        showCreateModal: false,
        showViewModal: false,
        // The key whose details modal is open. The modal binds each value
        // with x-text, because the strict Alpine build refuses x-html.
        selectedKey: null,

        viewKey(id) {
            const keys = config.apiKeys || [];
            const key = keys.find(k => k.id === id);
            if (!key) return;

            this.selectedKey = key;
            window.openModal('viewKeyModal');
        },

        keyField(name) {
            const value = this.selectedKey ? this.selectedKey[name] : null;
            return value === null || value === undefined ? '' : String(value);
        },

        isLiveKey() {
            return this.keyField('environment') === 'live';
        },

        keyPrefixLabel() {
            return this.keyField('key_prefix') + '********...';
        },

        keyRateLimitLabel() {
            const t = config.translations || {};
            const limit = this.selectedKey ? this.selectedKey.rate_limit : null;
            return limit ? limit + ' ' + (t.requests_per_minute || '') : (t.unlimited || '');
        },

        keyExpiresLabel() {
            const t = config.translations || {};
            const expires = this.keyField('expires_at');
            return expires ? new Date(expires).toLocaleDateString() : (t.no_expiry || '');
        },

        keyUsageLabel() {
            const count = this.selectedKey ? this.selectedKey.usage_count : 0;
            return Number(count || 0).toLocaleString();
        },

        keyCreatedLabel() {
            const created = this.keyField('created_at');
            return created ? new Date(created).toLocaleString() : '';
        },

        keyScopes() {
            return (this.selectedKey && this.selectedKey.scopes) || [];
        },

        keyAllowedIps() {
            const ips = (this.selectedKey && this.selectedKey.allowed_ips) || [];
            return ips.join(', ');
        },

        // The copy button of the one-time key notice. A bare name in a
        // directive is looked up only in Alpine's scope in the strict build.
        copyToClipboard(elementId) {
            window.copyToClipboard(elementId);
        },
    };
});

window.copyToClipboard = function (elementId) {
    const configEl = document.getElementById('api-settings-config');
    const config = configEl ? JSON.parse(configEl.textContent) : {};
    const t = config.translations || {};

    const element = document.getElementById(elementId);
    const text = element.textContent || element.innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert(t.copied_to_clipboard || 'Copied!');
    });
};
