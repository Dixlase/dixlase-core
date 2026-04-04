/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * スプリットペイン + ライブプレビュー Alpine.js コンポーネント
 * フロントページ編集画面で使用。エディタとiframeテーマプレビューを配置し、
 * 編集内容をリアルタイムにプレビューに反映する。
 * 共通ミックスイン（preview-mixin, split-pane-mixin）を使用。
 */

import Alpine from 'alpinejs';
import { previewMixin } from './mixins/preview-mixin';
import { splitPaneMixin } from './mixins/split-pane-mixin';
import { mergeMixins } from './mixins/merge-mixin';

const DEBOUNCE_DELAYS = {
    html: 150,
    markdown: 200,
    blade: 400,
    gui: 400,
};

function createSplitPaneEditor(config) {
    return mergeMixins(splitPaneMixin(), previewMixin(config), {
        // --- エディタ設定 ---
        editorType: config.editorType || 'html',
        editorTypeValue: config.editorTypeValue || 'html',

        // --- ストレージ関連 ---
        storageType: config.defaultStorageType || 'database',
        fileStorageBasePath: config.fileStorageBasePath || '',
        activeTab: 'content',

        // --- 内部状態 ---
        _debounceTimer: null,
        _cssDebounceTimer: null,
        _abortController: null,

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

        // --- 初期化 ---
        init() {
            this.$dispatch('right-sidebar-active');
            this.initPreview();
            this.initSplitPane();

            this.$nextTick(() => {
                this.initAutoResizeTextareas();
                this.watchContentChanges();
            });
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
            if (this.isHtmlEditor) {
                const css = document.getElementById('custom_css')?.value || '';
                if (css) {
                    this.postToIframe('updateCustomCss', { css });
                }
            }
        },

        // --- textarea自動リサイズ ---
        initAutoResizeTextareas() {
            const MIN_HEIGHT = 150;
            const MAX_HEIGHT_VH = 0.6;
            ['content', 'custom_css', 'custom_js'].forEach((id) => {
                const textarea = document.getElementById(id);
                if (!textarea) return;
                textarea.style.overflow = 'hidden';
                textarea.style.resize = 'none';
                textarea.style.minHeight = MIN_HEIGHT + 'px';
                const resize = () => {
                    textarea.style.height = 'auto';
                    const maxH = Math.max(MIN_HEIGHT, window.innerHeight * MAX_HEIGHT_VH);
                    const h = Math.min(Math.max(textarea.scrollHeight, MIN_HEIGHT), maxH);
                    textarea.style.height = h + 'px';
                    textarea.style.overflow = h >= maxH ? 'auto' : 'hidden';
                };
                textarea.addEventListener('input', resize);
                resize();
            });
        },
    });
}

Alpine.data('splitPaneEditor', createSplitPaneEditor);
