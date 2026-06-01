/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * Alpine.js component for the install wizard's "Download Theme" button.
 * Shows the shared ui-modal as a progress overlay while the AJAX call runs,
 * then reloads the page so the requirements re-check picks up the new theme.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

import Alpine from 'alpinejs';

Alpine.data('installThemeDownload', ({ endpoint, csrf, modalId, invalidMessage }) => ({
    downloading: false,
    errorMessage: '',

    async downloadTheme(directory) {
        if (!directory) {
            this.errorMessage = invalidMessage || 'Invalid theme';
            return;
        }
        if (this.downloading) {
            return;
        }

        this.downloading = true;
        this.errorMessage = '';

        // Show the shared progress modal (uses window.openModal from ui-modal.js)
        if (typeof window.openModal === 'function') {
            window.openModal(modalId);
        }

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ directory }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.success === false) {
                this.errorMessage = data.message || `HTTP ${response.status}`;
                this.downloading = false;
                if (typeof window.closeModal === 'function') {
                    window.closeModal(modalId);
                }
                return;
            }

            // Reload so the requirements check re-runs with the new theme present.
            window.location.reload();
        } catch (e) {
            this.errorMessage = e?.message || 'Network error';
            this.downloading = false;
            if (typeof window.closeModal === 'function') {
                window.closeModal(modalId);
            }
        }
    },
}));
