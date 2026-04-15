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
 * オンラインテーマ一覧 Alpine.js コンポーネント
 */

import Alpine from 'alpinejs';

Alpine.data('onlineThemes', (config) => ({
    themes: [],
    status: 'idle',
    errorMessage: '',
    downloadingSlug: null,
    downloadingName: '',

    init() {
        this.fetchThemes();
    },

    async fetchThemes() {
        this.status = 'loading';
        this.errorMessage = '';

        try {
            const response = await fetch(config.listUrl, {
                headers: { 'Accept': 'application/json' },
            });

            const data = await response.json();

            if (data.success) {
                this.themes = data.themes || [];
                this.status = this.themes.length > 0 ? 'loaded' : 'empty';
            } else {
                this.status = 'error';
                this.errorMessage = data.message || '';
            }
        } catch (error) {
            this.status = 'error';
            this.errorMessage = error.message;
        }
    },

    download(theme) {
        if (this.downloadingSlug) return;
        this.downloadingSlug = theme.slug;
        this.downloadingName = theme.name || theme.slug;

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
        slugInput.value = theme.slug;
        form.appendChild(slugInput);

        document.body.appendChild(form);
        form.submit();
    },
}));
