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
        this.downloadingSlug = plugin.slug;
        this.downloadingName = plugin.name || plugin.slug;

        // hidden form を使って POST リクエストを送信（CSRF 対応）
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
        slugInput.value = plugin.slug;
        form.appendChild(slugInput);

        document.body.appendChild(form);
        form.submit();
    },

    /**
     * 画像読み込み失敗時にデフォルトサムネイルに切り替え
     */
    handleThumbnailError(event) {
        if (event.target && config.defaultThumbnail) {
            event.target.src = config.defaultThumbnail;
        }
    },
}));
