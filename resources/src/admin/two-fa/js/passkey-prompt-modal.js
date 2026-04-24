/*
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

/**
 * Passkey Prompt Modal Component
 *
 * パスキー登録促進モーダルのAlpine.jsコンポーネント
 */

import Alpine from 'alpinejs';

Alpine.data('passkeyPromptModal', (dismissUrl, modalId) => ({
    dontShowAgain: false,

    handleClose() {
        if (this.dontShowAgain) {
            this.dismissPrompt();
        }
    },

    closeModal() {
        if (this.dontShowAgain) {
            this.dismissPrompt();
        }

        // グローバル関数を使用してモーダルを閉じる
        if (typeof window.closeModal === 'function') {
            window.closeModal(modalId);
        }
    },

    dismissPrompt() {
        fetch(dismissUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .catch(error => {
            console.error('Error dismissing passkey prompt:', error);
        });
    }
}));
