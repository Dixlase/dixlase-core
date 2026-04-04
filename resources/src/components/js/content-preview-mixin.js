/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * コンテンツプレビュー用 Alpine.js ミックスイン
 *
 * 編集/プレビュー切替の状態管理とサーバーサイドプレビューの fetch ロジックを提供します。
 * <x-content-editor.preview-tabs> コンポーネントと併用してください。
 *
 * 使い方:
 *   Alpine.data('myEditor', (config) => ({
 *       ...contentPreviewMixin({
 *           previewUrl: config.previewUrl,
 *           editorTypeValue: config.editorTypeValue,
 *           contentElementId: 'content',
 *       }),
 *       // 他のプロパティ・メソッド
 *   }));
 */

/**
 * コンテンツプレビューミックスイン
 *
 * @param {Object} options - 設定オプション
 * @param {string} options.previewUrl - プレビューAPI の URL
 * @param {string} options.editorTypeValue - エディタータイプのスラッグ（'html', 'markdown' 等）
 * @param {string} [options.contentElementId='content'] - コンテンツ textarea の DOM ID
 */
window.contentPreviewMixin = function (options = {}) {
    const previewUrl = options.previewUrl || '';
    const editorTypeValue = options.editorTypeValue || 'html';
    const contentElementId = options.contentElementId || 'content';

    return {
        previewMode: false,
        previewLoading: false,
        previewError: false,

        /**
         * プレビューモードに切り替え、サーバーからレンダリング済みHTMLを取得
         */
        async loadPreview() {
            this.previewMode = true;
            this.previewLoading = true;
            this.previewError = false;

            const contentEl = document.getElementById(contentElementId);
            const content = contentEl ? contentEl.value : '';

            if (!content.trim()) {
                this.previewLoading = false;
                return;
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                const response = await fetch(previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        content: content,
                        editor_type: editorTypeValue,
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
                this.previewError = true;
                const slot = document.getElementById('preview-content-slot');
                if (slot) {
                    slot.innerHTML = '';
                }
            } finally {
                this.previewLoading = false;
            }
        },

        /**
         * 編集モードに戻る
         */
        showEditor() {
            this.previewMode = false;
        },
    };
};
