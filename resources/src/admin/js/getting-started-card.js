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

Alpine.data('gettingStartedCard', (initialVisited = [], initialAllCompleted = false) => ({
    dismissed: false,
    visited: initialVisited,
    visitUrl: '',
    dismissUrl: '',

    init() {
        this.visitUrl = this.$el.dataset.visitUrl;
        this.dismissUrl = this.$el.dataset.dismissUrl;

        if (initialAllCompleted) {
            setTimeout(() => this.dismiss(), 1500);
        }
    },

    isVisited(step) {
        return this.visited.includes(step);
    },

    visit(step) {
        if (this.visited.includes(step)) {
            return;
        }

        this.visited.push(step);

        // sendBeacon でページ遷移中でも確実にリクエストを送信
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const data = new FormData();
        data.append('step', step);
        data.append('_token', csrfToken);
        navigator.sendBeacon(this.visitUrl, data);
    },

    async dismiss() {
        this.dismissed = true;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            await fetch(this.dismissUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });
        } catch (e) {
            // ベストエフォート
        }
    },
}));
