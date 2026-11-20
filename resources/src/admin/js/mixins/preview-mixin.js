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
 * @api プラグイン/テーマから window.Dixlase.mixins.previewMixin として使用可能
 *
 * Additional permission under GNU AGPL version 3 section 7:
 * Dixlase plugins and themes may use this file's exported functions via the
 * window.Dixlase.mixins runtime API without being subject to the copyleft
 * requirements of the AGPL. Direct import into plugin build bundles is NOT
 * covered by this exception.
 *
 * プレビュー機能の共通ミックスイン
 * iframe テーマプレビュー、デバイス切替、スケーリング、リサイズ等の
 * 共通ロジックを提供する。フロントページ、ページプラグイン、リーガルプラグイン等で共用。
 *
 * 使い方:
 *   import { previewMixin } from './mixins/preview-mixin';
 *   Alpine.data('myEditor', (config) => ({
 *       ...previewMixin(config),
 *       // 固有のプロパティ・メソッド
 *   }));
 *
 * 必須 config:
 *   - previewFrameUrl: string  (iframe の src URL)
 *
 * オプション config:
 *   - previewUrl: string       (サーバーサイドレンダリング用 URL)
 *
 * 必須 x-ref（Blade 側）:
 *   - previewIframe: iframe 要素
 *   - previewContainer: プレビューコンテナ（スケーリング計算用）
 */

const DEVICE_PRESETS = {
    mobile: { width: 375, height: 667 },
    tablet: { width: 768, height: 1024 },
    desktop: { width: 1440, height: 900 },
};

export { DEVICE_PRESETS };

/**
 * プレビュー共通ミックスイン
 *
 * @param {object} config - 設定オブジェクト
 * @returns {object} Alpine.js データオブジェクト
 */
export function previewMixin(config) {
    return {
        // --- プレビュー設定 ---
        previewFrameUrl: config.previewFrameUrl || '',
        previewUrl: config.previewUrl || '',
        previewReady: false,
        previewLoading: true,
        previewVisible: true,

        // --- デバイスプレビュー ---
        previewDevice: 'desktop',
        freeWidth: 1440,
        freeHeight: 900,
        isResizingPreview: false,
        _previewContainerWidth: 0,
        _previewResizeObserver: null,

        // --- Computed ---
        get currentPreviewWidth() {
            if (this.previewDevice === 'free') {
                return this.freeWidth;
            }
            return DEVICE_PRESETS[this.previewDevice]?.width ?? 1440;
        },

        get currentPreviewHeight() {
            if (this.previewDevice === 'free') {
                return this.freeHeight;
            }
            return DEVICE_PRESETS[this.previewDevice]?.height ?? 900;
        },

        get previewScale() {
            if (this._previewContainerWidth <= 0 || this.currentPreviewWidth <= this._previewContainerWidth) {
                return 1;
            }
            return this._previewContainerWidth / this.currentPreviewWidth;
        },

        get scaledPreviewWidth() {
            return Math.round(this.currentPreviewWidth * this.previewScale);
        },

        get scaledPreviewHeight() {
            return Math.round(this.currentPreviewHeight * this.previewScale);
        },

        // The scale as a percentage label. Kept here because the strict
        // Alpine build does not expose Math to directive expressions.
        get previewScaleLabel() {
            return Math.round(this.previewScale * 100) + '%';
        },

        /**
         * Apply the frame URL, size and scale to the preview iframe.
         *
         * The strict Alpine build refuses to evaluate any directive on an
         * iframe, so the wrapper calls this from x-effect instead. The src is
         * set only when it changes, because assigning it again reloads the
         * frame. The iframe is looked up in the wrapper rather than through
         * $refs, which is not filled in yet when the effect first runs.
         */
        syncPreviewIframe(wrapper) {
            const iframe = wrapper.querySelector('iframe');
            if (!iframe) return;

            const url = this.previewFrameUrl;
            if (url && iframe.getAttribute('src') !== url) {
                iframe.setAttribute('src', url);
            }

            iframe.style.width = this.currentPreviewWidth + 'px';
            iframe.style.height = this.currentPreviewHeight + 'px';
            iframe.style.transform = 'scale(' + this.previewScale + ')';
            iframe.style.transformOrigin = 'top left';
        },

        // --- プレビュー初期化（init() 内から呼ぶ） ---
        initPreview() {
            this._onMessage = (e) => {
                if (e.origin !== window.location.origin) return;
                if (e.data?.type === 'dixlase-preview-ready') {
                    this.previewReady = true;
                    this.previewLoading = false;
                    if (typeof this.sendContentToPreview === 'function') {
                        this.sendContentToPreview();
                    }
                }
            };
            window.addEventListener('message', this._onMessage);

            this.$nextTick(() => {
                this.observePreviewContainerWidth();
            });
        },

        // --- プレビューコンテナ幅監視（スケーリング計算用） ---
        observePreviewContainerWidth() {
            const container = this.$refs.previewContainer;
            if (!container) return;

            this._previewResizeObserver = new ResizeObserver((entries) => {
                for (const entry of entries) {
                    this._previewContainerWidth = entry.contentRect.width;
                }
            });
            this._previewResizeObserver.observe(container);
        },

        // --- プレビュー表示/非表示 ---
        togglePreview() {
            this.previewVisible = !this.previewVisible;
        },

        // --- デバイス切替 ---
        setPreviewDevice(device) {
            this.previewDevice = device;
        },

        // --- フリーサイズ リサイズハンドル ---
        startPreviewResize(event, direction) {
            event.preventDefault();
            this.isResizingPreview = true;

            if (this.previewDevice !== 'free') {
                this.freeWidth = this.currentPreviewWidth;
                this.freeHeight = this.currentPreviewHeight;
                this.previewDevice = 'free';
            }

            const startX = event.clientX;
            const startY = event.clientY;
            const startW = this.freeWidth;
            const startH = this.freeHeight;

            const onMove = (e) => {
                if (!this.isResizingPreview) return;
                if (direction === 'horizontal' || direction === 'both') {
                    this.freeWidth = Math.max(200, startW + (e.clientX - startX));
                }
                if (direction === 'vertical' || direction === 'both') {
                    this.freeHeight = Math.max(200, startH + (e.clientY - startY));
                }
            };

            const onUp = () => {
                this.isResizingPreview = false;
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
            };

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        },

        // --- iframe へのメッセージ送信 ---
        postToIframe(action, data) {
            const iframe = this.$refs.previewIframe;
            if (!iframe?.contentWindow) return;
            iframe.contentWindow.postMessage(
                { type: 'dixlase-preview-update', action, ...data },
                window.location.origin
            );
        },
    };
}
