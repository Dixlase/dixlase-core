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

    init() {
        if (initialAllCompleted) {
            setTimeout(() => this.dismiss(), 1500);
        }
    },

    isVisited(step) {
        return this.visited.includes(step);
    },

    async visit(step) {
        if (this.visited.includes(step)) {
            return;
        }

        this.visited.push(step);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(this.$el.dataset.visitUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ step }),
            });

            const data = await response.json();

            if (data.allCompleted) {
                setTimeout(() => this.dismiss(), 1500);
            }
        } catch (e) {
            // ベストエフォート
        }
    },

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
            // ベストエフォート
        }
    },
}));
