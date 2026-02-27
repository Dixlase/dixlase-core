/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * フロントページ作成・編集画面の Alpine.js コンポーネント
 * テンプレート切り替え + 右サイドバー起動 + 保存方法選択
 */
document.addEventListener('alpine:init', () => {
    /**
     * フロントページ作成画面
     * 言語・エディタータイプ切り替えでテンプレートを自動適用
     */
    Alpine.data('frontPageCreate', (config) => ({
        lang: config.defaultLang,
        editorType: config.defaultEditorType,
        templates: config.templates,
        storageType: config.defaultStorageType || 'database',
        fileStorageBasePath: config.fileStorageBasePath || '',

        get isFileStorage() {
            return this.storageType === 'file';
        },

        get filePath() {
            if (!this.isFileStorage) return '';
            const ext = this.editorType === 'markdown' ? 'md' : 'html';
            if (this.lang === 'en') {
                return this.fileStorageBasePath + '/content.' + ext;
            }
            return this.fileStorageBasePath + '/content.' + this.lang + '.' + ext;
        },

        init() {
            this.$dispatch('right-sidebar-active');
            this.$watch('lang', () => this.applyTemplate());
            this.$watch('editorType', () => this.applyTemplate());

            // 初回ロード時にテンプレートを自動適用
            this.$nextTick(() => this.applyTemplate());
        },

        applyTemplate() {
            const tpl = this.templates[this.lang]?.[this.editorType];
            if (tpl) {
                const contentEl = document.getElementById('content');
                if (contentEl) contentEl.value = tpl.content;
            }
        },
    }));

    /**
     * フロントページ編集画面
     * 保存方法選択 + 右サイドバー起動
     */
    Alpine.data('frontPageEditor', (config) => ({
        storageType: config.defaultStorageType || 'database',
        fileStorageBasePath: config.fileStorageBasePath || '',
        editorType: config.editorType || 'html',
        langCode: config.langCode || 'en',

        get isFileStorage() {
            return this.storageType === 'file';
        },

        get filePath() {
            if (!this.isFileStorage) return '';
            const ext = this.editorType === 'markdown' ? 'md' : 'html';
            if (this.langCode === 'en') {
                return this.fileStorageBasePath + '/content.' + ext;
            }
            return this.fileStorageBasePath + '/content.' + this.langCode + '.' + ext;
        },

        init() {
            this.$dispatch('right-sidebar-active');
        },
    }));
});
