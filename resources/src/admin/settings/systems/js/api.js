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
 * API設定ページ用JavaScript（Alpine.jsコンポーネント）
 */

window.apiSettings = function () {
    const configEl = document.getElementById('api-settings-config');
    const config = configEl ? JSON.parse(configEl.textContent) : {};

    return {
        apiEnabled: config.apiEnabled || '0',
        showCreateModal: false,
        showViewModal: false,
        keyDetails: '',

        viewKey(id) {
            const keys = config.apiKeys || [];
            const key = keys.find(k => k.id === id);
            if (!key) return;

            const t = config.translations || {};

            let scopesHtml = '';
            if (key.scopes && key.scopes.length > 0) {
                scopesHtml = key.scopes.map(s => `<span class="inline-block px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded mr-1 mb-1">${s}</span>`).join('');
            } else {
                scopesHtml = `<span class="text-gray-400">${t.no_scopes || ''}</span>`;
            }

            let allowedIpsHtml = '';
            if (key.allowed_ips && key.allowed_ips.length > 0) {
                allowedIpsHtml = key.allowed_ips.join(', ');
            } else {
                allowedIpsHtml = `<span class="text-gray-400">${t.all_ips_allowed || ''}</span>`;
            }

            this.keyDetails = `
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="text-gray-500 dark:text-gray-400">${t.key_name || ''}</div>
                    <div class="font-medium">${key.name}</div>

                    <div class="text-gray-500 dark:text-gray-400">${t.environment || ''}</div>
                    <div>${key.environment === 'live' ? '<span class="text-green-600">Live</span>' : '<span class="text-orange-600">Test</span>'}</div>

                    <div class="text-gray-500 dark:text-gray-400">${t.key_prefix || ''}</div>
                    <div><code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">${key.key_prefix}********...</code></div>

                    <div class="text-gray-500 dark:text-gray-400">${t.rate_limit || ''}</div>
                    <div>${key.rate_limit ? key.rate_limit + ' ' + (t.requests_per_minute || '') : (t.unlimited || '')}</div>

                    <div class="text-gray-500 dark:text-gray-400">${t.expires_at || ''}</div>
                    <div>${key.expires_at ? new Date(key.expires_at).toLocaleDateString() : (t.no_expiry || '')}</div>

                    <div class="text-gray-500 dark:text-gray-400">${t.usage_count || ''}</div>
                    <div>${key.usage_count.toLocaleString()}</div>

                    <div class="text-gray-500 dark:text-gray-400">${t.created_at || ''}</div>
                    <div>${new Date(key.created_at).toLocaleString()}</div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                    <div class="text-gray-500 dark:text-gray-400 text-sm mb-2">${t.scopes || ''}</div>
                    <div>${scopesHtml}</div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                    <div class="text-gray-500 dark:text-gray-400 text-sm mb-2">${t.allowed_ips || ''}</div>
                    <div class="text-sm">${allowedIpsHtml}</div>
                </div>

                ${key.description ? `
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                    <div class="text-gray-500 dark:text-gray-400 text-sm mb-2">${t.key_description || ''}</div>
                    <div class="text-sm">${key.description}</div>
                </div>
                ` : ''}
            `;

            openModal('viewKeyModal');
        }
    };
};

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
