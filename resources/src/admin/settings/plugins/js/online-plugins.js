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
 * オンラインプラグイン一覧 Alpine.js コンポーネント
 */

import Alpine from 'alpinejs';

Alpine.data('onlinePlugins', (config) => ({
    plugins: [],
    status: 'idle',
    errorMessage: '',
    downloadingSlug: null,
    downloadingName: '',
    pendingPlugin: null,

    init() {
        this.fetchPlugins();
    },

    async fetchPlugins() {
        this.status = 'loading';
        this.errorMessage = '';

        try {
            const response = await fetch(config.listUrl, {
                headers: { 'Accept': 'application/json' },
            });

            const data = await response.json();

            if (data.success) {
                this.plugins = data.plugins || [];
                this.status = this.plugins.length > 0 ? 'loaded' : 'empty';
            } else {
                this.status = 'error';
                this.errorMessage = data.message || '';
            }
        } catch (error) {
            this.status = 'error';
            this.errorMessage = error.message;
        }
    },

    download(plugin) {
        if (this.downloadingSlug) return;

        // API が想定外の型を返した場合に備え、slug を文字列化して防御する。
        const slug = typeof plugin.slug === 'string' ? plugin.slug.trim() : '';
        if (! slug) {
            console.warn('[onlinePlugins] plugin.slug is not a usable string:', plugin.slug);
            return;
        }

        // ダウンロード前に確認モーダルを表示
        this.pendingPlugin = plugin;
        const name = typeof plugin.name === 'string' ? plugin.name : slug;
        const messageEl = document.getElementById('confirmDownloadPluginMessage');
        if (messageEl) {
            const template = messageEl.dataset.template || '';
            messageEl.textContent = template.replace('__NAME__', name);
        }
        if (typeof window.openModal === 'function') {
            window.openModal('confirmDownloadPluginModal');
        }
    },

    confirmDownload() {
        const plugin = this.pendingPlugin;
        if (! plugin || this.downloadingSlug) return;

        const slug = typeof plugin.slug === 'string' ? plugin.slug.trim() : '';
        if (! slug) return;

        this.pendingPlugin = null;
        this.downloadingSlug = slug;
        this.downloadingName = typeof plugin.name === 'string' ? plugin.name : slug;

        if (typeof window.closeModal === 'function') {
            window.closeModal('confirmDownloadPluginModal');
        }

        const nameEl = document.getElementById('downloadingPluginName');
        if (nameEl) {
            nameEl.textContent = this.downloadingName;
        }
        if (typeof window.openModal === 'function') {
            window.openModal('downloadingPluginModal');
        }

        // hidden form を構築して送信。モーダルが確実に描画されるよう次フレームで submit する
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = config.downloadUrl;

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = config.csrfToken;
        form.appendChild(csrfInput);

        const slugInput = document.createElement('input');
        slugInput.type = 'hidden';
        slugInput.name = 'slug';
        slugInput.value = slug;
        form.appendChild(slugInput);

        document.body.appendChild(form);

        // 2 回 RAF を挟んでモーダルの開く遷移を描画してからフォーム送信する
        requestAnimationFrame(() => {
            requestAnimationFrame(() => form.submit());
        });
    },
}));
