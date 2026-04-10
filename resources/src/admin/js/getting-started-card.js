/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * はじめにカード — ダッシュボード上部に表示するオンボーディングカード
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

import Alpine from 'alpinejs';

Alpine.data('gettingStartedCard', () => ({
    dismissed: false,

    async dismiss() {
        this.dismissed = true;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            await fetch(this.$el.dataset.dismissUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });
        } catch (e) {
            // dismiss はベストエフォート — 失敗しても UI は閉じたまま
        }
    },
}));
