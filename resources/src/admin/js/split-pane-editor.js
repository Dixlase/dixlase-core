/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * スプリットペイン + ライブプレビュー Alpine.js コンポーネント
 * フロントページ編集画面で使用。エディタとiframeテーマプレビューを配置し、
 * 編集内容をリアルタイムにプレビューに反映する。
 * コンテナ実幅に基づき横並び/縦並びを自動切替する。
 */

import Alpine from 'alpinejs';

const STORAGE_KEY_SPLIT_RATIO = 'dls-split-ratio';
const MIN_PANE_WIDTH = 320;
const HORIZONTAL_MIN_WIDTH = 900;

const DEVICE_PRESETS = {
    mobile: { width: 375, height: 667 },
    tablet: { width: 768, height: 1024 },
    desktop: { width: 1440, height: 900 },
};

const DEBOUNCE_DELAYS = {
    html: 150,
    markdown: 200,
    blade: 400,
    gui: 400,
};

function createSplitPaneEditor(config) {
    return {
        // --- レイアウト ---
        splitRatio: parseFloat(localStorage.getItem(STORAGE_KEY_SPLIT_RATIO) || '0.5'),
        isDragging: false,
        isHorizontal: false,

        // --- プレビュー状態 ---
        previewReady: false,
        previewLoading: true,

        // --- デバイスプレビュー ---
        previewDevice: 'desktop',
        freeWidth: 1440,
        freeHeight: 900,
        isResizingPreview: false,

        // --- エディタ設定 ---
        editorType: config.editorType || 'html',
        editorTypeValue: config.editorTypeValue || 'html',
        previewUrl: config.previewUrl || '',
        previewFrameUrl: config.previewFrameUrl || '',

        // --- ストレージ関連 ---
        storageType: config.defaultStorageType || 'database',
        fileStorageBasePath: config.fileStorageBasePath || '',
        activeTab: 'content',

        // --- 内部状態 ---
        _debounceTimer: null,
        _cssDebounceTimer: null,
        _abortController: null,
        _resizeObserver: null,

        // --- Computed ---
        get isHtmlEditor() {
            return this.editorType === 'html';
        },

        get isFileStorage() {
            return this.storageType === 'file';
        },

        get filePath() {
            if (!this.isFileStorage) {
                return '';
            }
            const ext = this.editorType === 'markdown' ? 'md' : 'html';
            return this.fileStorageBasePath + '/content.' + ext;
        },

        get jsFilePath() {
            if (!this.isFileStorage || !this.isHtmlEditor) {
                return '';
            }
            return this.fileStorageBasePath + '/script.js';
        },

        get cssFilePath() {
            if (!this.isFileStorage || !this.isHtmlEditor) {
                return '';
            }
            return this.fileStorageBasePath + '/style.css';
        },

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

        // --- 初期化 ---
        init() {
            this.$dispatch('right-sidebar-active');

            // iframe ready リスナー
            this._onMessage = (e) => {
                if (e.origin !== window.location.origin) return;
                if (e.data?.type === 'dixlase-preview-ready') {
                    this.previewReady = true;
                    this.previewLoading = false;
                    this.sendContentToPreview();
                }
            };
            window.addEventListener('message', this._onMessage);

            this.$nextTick(() => {
                this.initAutoResizeTextareas();
                this.watchContentChanges();
                this.observeContainerWidth();
            });

            // splitRatio永続化
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

        // --- スプリットペイン ドラッグ ---
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

        // --- デバイスプレビュー ---
        setPreviewDevice(device) {
            this.previewDevice = device;
        },

        // --- フリーサイズ リサイズハンドル ---
        startPreviewResize(event, direction) {
            event.preventDefault();
            this.isResizingPreview = true;

            // フリーモードに自動切替
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

        // --- コンテンツ監視 ---
        watchContentChanges() {
            const contentEl = document.getElementById('content');
            if (contentEl) {
                contentEl.addEventListener('input', () => this.onContentChange());
            }

            const cssEl = document.getElementById('custom_css');
            if (cssEl) {
                cssEl.addEventListener('input', () => this.onCssChange());
            }
        },

        onContentChange() {
            clearTimeout(this._debounceTimer);
            const delay = DEBOUNCE_DELAYS[this.editorType] ?? 400;
            this._debounceTimer = setTimeout(() => this.renderAndSend(), delay);
        },

        onCssChange() {
            clearTimeout(this._cssDebounceTimer);
            this._cssDebounceTimer = setTimeout(() => {
                const css = document.getElementById('custom_css')?.value || '';
                this.postToIframe('updateCustomCss', { css });
            }, 200);
        },

        // --- プレビューレンダリング ---
        renderAndSend() {
            const content = document.getElementById('content')?.value || '';
            if (!this.previewReady) return;

            if (this.editorType === 'html') {
                this.postToIframe('updateContent', { html: content });
            } else if (this.editorType === 'markdown') {
                const html = window.marked ? window.marked.parse(content) : content;
                this.postToIframe('updateContent', { html });
            } else {
                this.serverRenderAndSend(content);
            }
        },

        async serverRenderAndSend(content) {
            // 前回の未完了リクエストをキャンセル
            if (this._abortController) {
                this._abortController.abort();
            }
            this._abortController = new AbortController();

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                const response = await fetch(this.previewUrl, {
                    method: 'POST',
                    signal: this._abortController.signal,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken?.content || '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        content: content,
                        editor_type: this.editorTypeValue,
                    }),
                });

                if (!response.ok) return;
                const data = await response.json();
                this.postToIframe('updateContent', { html: data.html || '' });
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Preview render failed:', error);
                }
            }
        },

        sendContentToPreview() {
            this.renderAndSend();
            // カスタムCSSも送信
            if (this.isHtmlEditor) {
                const css = document.getElementById('custom_css')?.value || '';
                if (css) {
                    this.postToIframe('updateCustomCss', { css });
                }
            }
        },

        postToIframe(action, data) {
            const iframe = this.$refs.previewIframe;
            if (!iframe?.contentWindow) return;
            iframe.contentWindow.postMessage(
                { type: 'dixlase-preview-update', action, ...data },
                window.location.origin
            );
        },

        // --- モバイルUX ---
        scrollToEditor() {
            this.$refs.editorPane?.scrollIntoView({ behavior: 'smooth' });
        },

        scrollToPreview() {
            this.$refs.previewPane?.scrollIntoView({ behavior: 'smooth' });
        },

        // --- textarea自動リサイズ ---
        initAutoResizeTextareas() {
            ['content', 'custom_css', 'custom_js'].forEach((id) => {
                const textarea = document.getElementById(id);
                if (!textarea) return;
                textarea.style.overflow = 'hidden';
                textarea.style.resize = 'none';
                const resize = () => {
                    textarea.style.height = 'auto';
                    textarea.style.height = textarea.scrollHeight + 'px';
                };
                textarea.addEventListener('input', resize);
                resize();
            });
        },
    };
}

Alpine.data('splitPaneEditor', createSplitPaneEditor);
