/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * フロントページ作成・編集画面の Alpine.js コンポーネント
 * テンプレート切り替え + 右サイドバー起動 + 保存方法選択
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
        },
    };
}

/**
 * フロントページ編集画面
 * 保存方法選択 + 右サイドバー起動
 */
function createFrontPageEditor(config) {
    return {
        storageType: config.defaultStorageType || 'database',
        fileStorageBasePath: config.fileStorageBasePath || '',
        editorType: config.editorType || 'html',
        langCode: config.langCode || 'en',
        activeTab: 'content',
        previewMode: false,
        previewLoading: false,
        previewUrl: config.previewUrl || '',
        editorTypeValue: config.editorTypeValue || 'html',

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
        },

        async loadPreview() {
            this.previewMode = true;
            this.previewLoading = true;

            const contentEl = document.getElementById('content');
            const content = contentEl ? contentEl.value : '';

            if (!content.trim()) {
                this.previewLoading = false;
                return;
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                const response = await fetch(this.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        content: content,
                        editor_type: this.editorTypeValue,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Preview request failed');
                }

                const data = await response.json();
                const slot = document.getElementById('preview-content-slot');
                if (slot) {
                    slot.innerHTML = data.html || '';
                }
            } catch (error) {
                console.error('Preview failed:', error);
                const slot = document.getElementById('preview-content-slot');
                if (slot) {
                    slot.innerHTML = '';
                }
                this.previewError = true;
            } finally {
                this.previewLoading = false;
            }
        },

        showEditor() {
            this.previewMode = false;
        },
    };
}

Alpine.data('frontPageCreate', createFrontPageCreate);
Alpine.data('frontPageEditor', createFrontPageEditor);
