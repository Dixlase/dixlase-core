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
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * @api プラグイン/テーマから window.Dixlase.mixins.splitPaneMixin として使用可能
 *
 * Additional permission under GNU AGPL version 3 section 7:
 * Dixlase plugins and themes may use this file's exported functions via the
 * window.Dixlase.mixins runtime API without being subject to the copyleft
 * requirements of the AGPL. Direct import into plugin build bundles is NOT
 * covered by this exception.
 *
 * スプリットペインレイアウトの共通ミックスイン
 * エディタとプレビューの横並び/縦並び自動切替、ドラッグ分割、
 * スクロール制御等の共通ロジックを提供する。
 *
 * 使い方:
 *   import { splitPaneMixin } from './mixins/split-pane-mixin';
 *   Alpine.data('myEditor', (config) => ({
 *       ...splitPaneMixin(),
 *       // 固有のプロパティ・メソッド
 *   }));
 *
 * 必須 x-ref（Blade 側）:
 *   - splitContainer: スプリットペインコンテナ
 *   - editorPane: エディタペイン
 *   - previewPane: プレビューペイン
 */

const STORAGE_KEY_SPLIT_RATIO = 'dls-split-ratio';
const MIN_PANE_WIDTH = 320;
const HORIZONTAL_MIN_WIDTH = 900;

export { STORAGE_KEY_SPLIT_RATIO, MIN_PANE_WIDTH, HORIZONTAL_MIN_WIDTH };

/**
 * スプリットペイン共通ミックスイン
 *
 * @returns {object} Alpine.js データオブジェクト
 */
export function splitPaneMixin() {
    return {
        // --- レイアウト ---
        splitRatio: parseFloat(localStorage.getItem(STORAGE_KEY_SPLIT_RATIO) || '0.5'),
        isDragging: false,
        isHorizontal: false,
        _resizeObserver: null,

        // --- スプリットペイン初期化（init() 内から呼ぶ） ---
        initSplitPane() {
            this.$nextTick(() => {
                this.observeContainerWidth();
            });

            this.$watch('splitRatio', (v) => {
                localStorage.setItem(STORAGE_KEY_SPLIT_RATIO, v.toString());
            });
        },

        // --- コンテナ幅監視（横並び/縦並び自動切替） ---
        observeContainerWidth() {
            const container = this.$refs.splitContainer;
            if (!container) return;

            this._resizeObserver = new ResizeObserver((entries) => {
                for (const entry of entries) {
                    this.isHorizontal = entry.contentRect.width >= HORIZONTAL_MIN_WIDTH;
                }
            });
            this._resizeObserver.observe(container);
        },

        // --- ドラッグ ---
        startDrag(event) {
            if (!this.isHorizontal) return;
            this.isDragging = true;
            event.preventDefault();

            const container = this.$refs.splitContainer;

            const onMove = (e) => {
                if (!this.isDragging) return;
                const rect = container.getBoundingClientRect();
                let ratio = (e.clientX - rect.left) / rect.width;
                const minRatio = MIN_PANE_WIDTH / rect.width;
                ratio = Math.max(minRatio, Math.min(1 - minRatio, ratio));
                this.splitRatio = ratio;
            };

            const onUp = () => {
                this.isDragging = false;
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
            };

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        },

        // --- スクロール ---
        scrollToEditor() {
            const el = this.$refs.editorPane;
            if (!el) return;
            const offset = 80;
            window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - offset, behavior: 'smooth' });
        },

        scrollToPreview() {
            const el = this.$refs.previewPane;
            if (!el) return;
            const offset = 80;
            window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - offset, behavior: 'smooth' });
        },
    };
}
