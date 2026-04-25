/**
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
 * ダークモード検出と適用
 * デバイスの外観モード（ライト/ダーク）を検出してHTMLクラスに適用
 */
(function() {
    'use strict';

    /**
     * ダークモードの状態を適用
     */
    function applyDarkMode(isDark) {
        if (isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

    /**
     * 初期化：現在のダークモード設定を適用
     */
    function init() {
        const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        // 初期状態を適用
        applyDarkMode(darkModeMediaQuery.matches);
        
        // 外観モード変更の監視
        darkModeMediaQuery.addEventListener('change', function(e) {
            applyDarkMode(e.matches);
        });
    }

    // DOMContentLoaded前でも実行（FOUC防止）
    init();
})();
