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
 * フロントページ作成画面の Alpine.js コンポーネント
 * 言語・エディタータイプ切り替えでテンプレートを自動適用し、
 * iframe テーマプレビューでリアルタイムプレビューを表示する。
 * 共通ミックスイン（preview-mixin, split-pane-mixin）を使用。
 * （編集画面は split-pane-editor.js の splitPaneEditor コンポーネントを使用）
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

function createFrontPageCreate(config) {
    const base = mergeMixins(splitPaneMixin(), previewMixin(config));
    return mergeMixins(base, {
        // --- フロントページ作成固有 ---
        lang: config.defaultLang,
        editorType: config.defaultEditorType,
        templates: config.templates,
        storageType: config.defaultStorageType || 'database',
        fileStorageBasePath: config.fileStorageBasePath || '',
        activeTab: 'content',

        // --- 内部状態 ---
        _debounceTimer: null,
        _cssDebounceTimer: null,
        _abortController: null,

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
            });
        },

        // --- テンプレート適用 ---
        applyTemplate() {
            const tpl = this.templates[this.lang]?.[this.editorType];
            if (tpl) {
                const contentEl = document.getElementById('content');
                if (contentEl) {
                    contentEl.value = tpl.content;
                    contentEl.dispatchEvent(new Event('input'));
                }

                const cssEl = document.getElementById('custom_css');
                if (cssEl && tpl.custom_css !== undefined) {
                    cssEl.value = tpl.custom_css;
                    cssEl.dispatchEvent(new Event('input'));
                }
                const jsEl = document.getElementById('custom_js');
                if (jsEl && tpl.custom_js !== undefined) {
                    jsEl.value = tpl.custom_js;
                    jsEl.dispatchEvent(new Event('input'));
                }
            }
            this.$nextTick(() => this.sendContentToPreview());
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

        // --- textarea auto-resize with manual override ---
        //
        // Two heights are in play on these textareas:
        //   1. Auto-grow on input — fits the field to its content
        //      between MIN_HEIGHT and MAX_HEIGHT_VH * viewport.
        //   2. Manual drag — the operator can override the auto
        //      height by dragging the vertical resize handle. Once
        //      they do, we stop touching the height so their size
        //      sticks for the rest of the session.
        //
        // A ResizeObserver watches the textarea and flips a one-way
        // manuallyResized flag whenever the observed height drifts
        // from the most recent auto-set height beyond a small
        // tolerance (sub-pixel rounding noise). After that flip, the
        // input handler skips its height write.
        initAutoResizeTextareas() {
            const MIN_HEIGHT = 150;
            const MAX_HEIGHT_VH = 0.6;
            const TOLERANCE_PX = 2;
            ['content', 'custom_css', 'custom_js'].forEach((id) => {
                const textarea = document.getElementById(id);
                if (!textarea) return;
                textarea.style.overflow = 'hidden';
                textarea.style.resize = 'vertical';
                textarea.style.minHeight = MIN_HEIGHT + 'px';

                let manuallyResized = false;
                let lastAutoHeight = 0;

                const autoResize = () => {
                    if (manuallyResized) return;
                    textarea.style.height = 'auto';
                    const maxH = Math.max(MIN_HEIGHT, window.innerHeight * MAX_HEIGHT_VH);
                    const h = Math.min(Math.max(textarea.scrollHeight, MIN_HEIGHT), maxH);
                    textarea.style.height = h + 'px';
                    textarea.style.overflow = h >= maxH ? 'auto' : 'hidden';
                    lastAutoHeight = h;
                };

                // Compare border-box heights: style.height (what autoResize
                // writes) is border-box, while contentRect excludes padding
                // and border and would always differ by ~18px. A height of
                // 0 means the field sits in a hidden tab; refit it once it
                // becomes visible instead of reading that as a drag.
                let hidden = false;
                const observer = new ResizeObserver((entries) => {
                    if (manuallyResized) return;
                    const entry = entries[0];
                    const currentHeight = entry.borderBoxSize?.[0]?.blockSize ?? textarea.offsetHeight;
                    if (currentHeight === 0) {
                        hidden = true;
                        return;
                    }
                    if (hidden) {
                        hidden = false;
                        autoResize();
                        return;
                    }
                    if (Math.abs(currentHeight - lastAutoHeight) < TOLERANCE_PX) return;
                    manuallyResized = true;
                    textarea.style.overflow = 'auto';
                });
                observer.observe(textarea);

                textarea.addEventListener('input', autoResize);
                autoResize();
            });
        },
    });
}

Alpine.data('frontPageCreate', createFrontPageCreate);
