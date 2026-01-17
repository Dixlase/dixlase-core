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
 */

/**
 * Alpine.js Email Input Component
 * 
 * Usage in Blade:
 * <div x-data="emailInput({ 
 *     originalEmail: '{{ $value }}',
 *     showConfirmation: {{ $showConfirmation ? 'true' : 'false' }},
 *     showConfirmationOnChange: {{ $showConfirmationOnChange ? 'true' : 'false' }}
 * })" ...>
 */
window.emailInput = function (config = {}) {
    return {
        email: config.originalEmail || '',
        emailConfirmation: '',
        originalEmail: config.originalEmail || '',
        showConfirmation: config.showConfirmation || false,
        showConfirmationOnChange: config.showConfirmationOnChange || false,

        init() {
            // Watch email changes
            this.$watch('email', () => {
                this.checkMatch();
            });

            this.$watch('emailConfirmation', () => {
                this.checkMatch();
            });
        },

        // メールアドレスが変更されたかチェック
        get isEmailChanged() {
            return this.email.trim() !== this.originalEmail && this.email.trim() !== '';
        },

        // 確認欄を表示するかどうか
        get shouldShowConfirmation() {
            if (!this.showConfirmation) return false;
            if (!this.showConfirmationOnChange) return true;
            return this.isEmailChanged;
        },

        // メールアドレスが一致しているかチェック
        get isMatching() {
            const email = this.email.trim();
            const confirm = this.emailConfirmation.trim();
            return email === confirm && email !== '';
        },

        // 確認欄に入力があるかチェック
        get hasConfirmationInput() {
            return this.emailConfirmation.trim() !== '';
        },

        // 一致アイコンを表示するかどうか
        get showMatchIndicator() {
            return this.hasConfirmationInput;
        },

        // アイコンのクラス
        get matchIconClass() {
            if (this.isMatching) {
                return 'fas fa-check text-green-500';
            }
            return 'fas fa-times text-red-500';
        },

        // アイコンのaria-label
        get matchIconLabel() {
            if (this.isMatching) {
                return this.$el.dataset.matchSuccess || '';
            }
            return this.$el.dataset.matchError || '';
        },

        // チェック処理（互換性のため残す）
        checkMatch() {
            // Alpine.jsのリアクティブシステムが自動的に処理するため、
            // 特別な処理は不要
        },

        // コピー&ペースト防止
        preventCopyPaste(event) {
            event.preventDefault();
            return false;
        }
    };
};
