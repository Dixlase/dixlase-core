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

import Alpine from 'alpinejs';

/**
 * CSP Settings Alpine.js Component
 *
 * CSP設定画面の状態管理
 */
Alpine.data('cspSettings', () => ({
    cspEnabled: '0',
    cspMode: 'development',
    appEnv: 'local',
    directiveMode: 'form',

    init() {
        // data-*属性から初期値を取得（文字列として扱う）
        this.cspEnabled = this.$el.dataset.cspEnabled || '0';
        this.cspMode = this.$el.dataset.cspMode || 'development';
        this.appEnv = this.$el.dataset.appEnv || 'local';
        this.directiveMode = this.$el.dataset.directiveMode || 'form';
    },

    /**
     * フォーム入力 → JSON文字列に変換
     */
    formToJson() {
        const directives = [
            'script-src', 'style-src', 'img-src',
            'connect-src', 'font-src', 'frame-src'
        ];
        const result = {};
        directives.forEach(d => {
            const fieldName = 'csp_directive_' + d.replace('-', '_');
            const el = document.querySelector('[name="' + fieldName + '"]');
            if (el && el.value.trim()) {
                const domains = el.value.trim().split('\n')
                    .map(line => line.trim())
                    .filter(line => line.length > 0);
                if (domains.length > 0) {
                    result[d] = domains;
                }
            }
        });
        const jsonEl = document.querySelector('[name="csp_custom_directives"]');
        if (jsonEl) {
            jsonEl.value = Object.keys(result).length > 0
                ? JSON.stringify(result, null, 2)
                : '';
        }
    },

    /**
     * JSON文字列 → フォーム入力に分解
     */
    jsonToForm() {
        const jsonEl = document.querySelector('[name="csp_custom_directives"]');
        if (!jsonEl || !jsonEl.value.trim()) return;
        try {
            const parsed = JSON.parse(jsonEl.value);
            const directives = [
                'script-src', 'style-src', 'img-src',
                'connect-src', 'font-src', 'frame-src'
            ];
            directives.forEach(d => {
                const fieldName = 'csp_directive_' + d.replace('-', '_');
                const el = document.querySelector('[name="' + fieldName + '"]');
                if (el && parsed[d] && Array.isArray(parsed[d])) {
                    el.value = parsed[d].join('\n');
                }
            });
        } catch (e) {
            // JSONパース失敗時は何もしない
        }
    }
}));

/**
 * CSP Blocklist Settings Alpine.js Component
 *
 * CSPブロックリスト設定の状態管理
 */
Alpine.data('cspBlocklistSettings', () => ({
    blocklistEnabled: '0',

    init() {
        // data-*属性から初期値を取得（文字列として扱う）
        this.blocklistEnabled = this.$el.dataset.blocklistEnabled || '0';
    }
}));
