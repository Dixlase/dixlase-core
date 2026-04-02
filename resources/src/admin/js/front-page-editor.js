/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * フロントページ作成画面の Alpine.js コンポーネント
 * 言語・エディタータイプ切り替えでテンプレートを自動適用し、リアルタイムプレビューを表示
 * （編集画面は split-pane-editor.js の splitPaneEditor コンポーネントを使用）
 */

import Alpine from 'alpinejs';

/**
 * フロントページ作成画面
 * 言語・エディタータイプ切り替えでテンプレートを自動適用
 */
function createFrontPageCreate(config) {
    return {
        lang: config.defaultLang,
        editorType: config.defaultEditorType,
        templates: config.templates,
        storageType: config.defaultStorageType || 'database',
        fileStorageBasePath: config.fileStorageBasePath || '',
        activeTab: 'content',

        // --- プレビュー内部状態 ---
        _debounceTimer: null,

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

        init() {
            this.$dispatch('right-sidebar-active');
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
            });
        },

        applyTemplate() {
            const tpl = this.templates[this.lang]?.[this.editorType];
            if (tpl) {
                const contentEl = document.getElementById('content');
                if (contentEl) {
                    contentEl.value = tpl.content;
                }
            }
            // テンプレート適用後にプレビューを更新
            this.$nextTick(() => this.renderToPreview());
        },

        // --- コンテンツ監視・プレビュー ---
        watchContentChanges() {
            const contentEl = document.getElementById('content');
            if (contentEl) {
                contentEl.addEventListener('input', () => {
                    clearTimeout(this._debounceTimer);
                    this._debounceTimer = setTimeout(() => this.renderToPreview(), 200);
                });
            }

            const cssEl = document.getElementById('custom_css');
            if (cssEl) {
                cssEl.addEventListener('input', () => {
                    clearTimeout(this._debounceTimer);
                    this._debounceTimer = setTimeout(() => this.updateCustomCssPreview(), 200);
                });
            }
        },

        renderToPreview() {
            const content = document.getElementById('content')?.value || '';
            const previewEl = this.$refs.previewContent;
            if (!previewEl) return;

            if (this.editorType === 'html') {
                previewEl.innerHTML = content;
                this.updateCustomCssPreview();
            } else if (this.editorType === 'markdown') {
                previewEl.innerHTML = window.marked ? window.marked.parse(content) : content;
            } else {
                // Blade/GUI はクライアントサイドレンダリング不可 — テキストとして表示
                previewEl.textContent = content;
            }
        },

        updateCustomCssPreview() {
            const css = document.getElementById('custom_css')?.value || '';
            let styleEl = document.getElementById('dls-preview-custom-css');
            if (!styleEl) {
                styleEl = document.createElement('style');
                styleEl.id = 'dls-preview-custom-css';
                document.head.appendChild(styleEl);
            }
            styleEl.textContent = css;
        },
    };
}

Alpine.data('frontPageCreate', createFrontPageCreate);
