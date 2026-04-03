/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * フロントページ作成画面の Alpine.js コンポーネント
 * 言語・エディタータイプ切り替えでテンプレートを自動適用し、
 * iframe テーマプレビューでリアルタイムプレビューを表示する。
 * （編集画面は split-pane-editor.js の splitPaneEditor コンポーネントを使用）
 */

import Alpine from 'alpinejs';

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

function createFrontPageCreate(config) {
    return {
        lang: config.defaultLang,
        editorType: config.defaultEditorType,
        templates: config.templates,
        storageType: config.defaultStorageType || 'database',
        fileStorageBasePath: config.fileStorageBasePath || '',
        activeTab: 'content',

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

        // --- 内部状態 ---
        _debounceTimer: null,
        _cssDebounceTimer: null,
        _abortController: null,
        _previewResizeObserver: null,

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

            this.$watch('lang', () => this.applyTemplate());
            this.$watch('editorType', (value) => {
                this.applyTemplate();
                if (value !== 'html') {
                    this.activeTab = 'content';
                }
            });

            this.$nextTick(() => {
                this.applyTemplate();
                this.watchContentChanges();
                this.initAutoResizeTextareas();
                this.observePreviewContainerWidth();
            });
        },

        // --- テンプレート適用 ---
        applyTemplate() {
            const tpl = this.templates[this.lang]?.[this.editorType];
            if (tpl) {
                const contentEl = document.getElementById('content');
                if (contentEl) {
                    contentEl.value = tpl.content;
                    // textarea の高さを再計算
                    contentEl.dispatchEvent(new Event('input'));
                }
            }
            this.$nextTick(() => this.sendContentToPreview());
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

        // --- デバイスプレビュー ---
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

            const startY = event.clientY;
            const startH = this.freeHeight;

            const onMove = (e) => {
                if (!this.isResizingPreview) return;
                if (direction === 'vertical') {
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
                        editor_type: this.editorType,
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

Alpine.data('frontPageCreate', createFrontPageCreate);
