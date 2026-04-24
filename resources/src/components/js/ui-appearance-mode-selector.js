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
 * Appearance Form Component
 * 
 * 外観設定フォームの保存成功時にテーマストアを更新
 */

export default function appearanceForm() {
    return {
        /**
         * 初期化
         */
        init() {
            // フォーム送信成功時にグローバルテーマストアを更新
            const successMessage = this.$el.dataset.success;
            const savedAppearance = this.$el.dataset.appearance;

            if (successMessage && savedAppearance && window.themeStore) {
                window.themeStore.theme = savedAppearance;
                window.themeStore.applyTheme();
            }
        }
    };
}

// グローバルに登録
window.appearanceForm = appearanceForm;
