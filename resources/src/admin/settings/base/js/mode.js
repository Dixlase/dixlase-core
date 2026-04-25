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
 * 管理画面モード設定ページ用JavaScript（Alpine.jsコンポーネント）
 */

import Alpine from 'alpinejs';

Alpine.data('adminModeSettings', () => {
    const el = document.getElementById('mode-settings');
    const original = el ? el.dataset.currentMode : '0';

    return {
        selectedMode: original,
        originalMode: original,
        pendingMode: null,
        _skipWatch: false,

        init() {
            this.$watch('selectedMode', (newVal) => {
                if (this._skipWatch) {
                    this._skipWatch = false;
                    return;
                }
                // モードが変わった場合のみモーダルを表示
                if (newVal !== this.originalMode) {
                    this.pendingMode = newVal;
                    // 一旦元に戻してモーダルで確認
                    this._skipWatch = true;
                    this.selectedMode = this.originalMode;
                    this.$nextTick(() => {
                        this.openSwitchModal();
                    });
                }
            });
        },

        openSwitchModal() {
            const modal = document.getElementById('modeSwitchModal');
            if (modal) {
                const alpineData = Alpine.$data(modal);
                if (alpineData) {
                    alpineData.show = true;
                }
            }
        },

        closeSwitchModal() {
            const modal = document.getElementById('modeSwitchModal');
            if (modal) {
                const alpineData = Alpine.$data(modal);
                if (alpineData) {
                    alpineData.show = false;
                }
            }
        },

        confirmSwitch() {
            this._skipWatch = true;
            this.selectedMode = this.pendingMode;
            this.pendingMode = null;
            this.closeSwitchModal();
        },

        cancelSwitch() {
            this.pendingMode = null;
            this.closeSwitchModal();
        },
    };
});
