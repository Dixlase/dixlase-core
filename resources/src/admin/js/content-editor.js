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
 * Shared content editor: the Alpine side of <x-form-content-editor>.
 *
 * One component for every content screen (the front page, and plugins
 * such as DixlasePages and DixlaseBlog). The Blade component passes its
 * settings as JSON in data-config, so x-data stays a bare name and the
 * CSP build of Alpine can run it. Optional parts, all driven by config:
 *
 *   - editor type cards and storage selection (availableEditors)
 *   - a split-pane live preview (config.preview, previewFrameUrl,
 *     previewUrl, previewRender: 'auto' renders HTML and Markdown in
 *     the browser and the rest on the server, 'server' always asks the
 *     server)
 *   - CSS / JS tabs for the HTML editor (config.assetTabs), and the CSS
 *     tab alone for Markdown when config.markdownCss is set too
 *   - starter templates per language and editor type (config.templates,
 *     with the language read from the form field named config.langField)
 *
 * Other parts of the page talk to it through window events:
 *   - content-editor-state            the editor's state, for sidebars
 *   - content-editor-request-state    ask the editor to send its state
 *   - content-editor-set-storage      change the storage type
 *   - editor-type-changed             the editor type changed (kept for
 *                                     existing listeners)
 *   - editor-content-updated          a GUI editor reports its content
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

const EDITORS_BY_STORAGE = {
    database: ['gui', 'markdown', 'html'],
    file: ['blade', 'markdown', 'html'],
};

const FILE_EXTENSIONS = {
    blade: 'blade.php',
    markdown: 'md',
    html: 'html',
    gui: 'json',
};

const AUTO_RESIZE_MIN_HEIGHT = 150;
const AUTO_RESIZE_MAX_HEIGHT_VH = 0.6;
const AUTO_RESIZE_TOLERANCE_PX = 2;

function readConfig(el) {
    if (!el) {
        return {};
    }
    try {
        return JSON.parse(el.dataset.config || '{}') || {};
    } catch (e) {
        console.warn('Dixlase: unreadable content editor settings.', e);
        return {};
    }
}

export function contentEditor() {
    // Alpine calls a registered data provider with its magics bound to
    // `this`, so the root element is known before the object is built;
    // the mixins need the settings at that point.
    const config = readConfig(this?.$el);

    const base = mergeMixins(splitPaneMixin(), previewMixin(config));

    return mergeMixins(base, {
        storageType: config.storageType || 'database',
        editorType: config.editorType || 'html',
        content: config.content ?? '',
        identifier: config.identifier || '',
        hasGuiEditor: !!config.hasGuiEditor,
        fixedEditors: Array.isArray(config.editors) && config.editors.length ? config.editors : null,
        fileStorageBasePath: config.fileStorageBasePath || '',
        previewEnabled: !!config.preview,
        inlineMarkdownPreview: config.inlineMarkdownPreview !== false,
        previewRender: config.previewRender || 'auto',
        assetTabs: !!config.assetTabs,
        markdownCss: !!config.markdownCss,
        templates: config.templates || {},
        langField: config.langField || '',
        lang: config.lang || '',
        rightSidebar: !!config.rightSidebar,
        activeTab: 'content',
        // A page without a preview has nothing to show it; previewMixin
        // starts it visible.
        previewVisible: !!config.preview,

        _debounceTimer: null,
        _cssDebounceTimer: null,
        _abortController: null,

        // --- Computed ---

        get availableEditors() {
            return this.fixedEditors || EDITORS_BY_STORAGE[this.storageType] || [];
        },

        get isFileStorage() {
            return this.storageType === 'file';
        },

        get isHtmlEditor() {
            return this.editorType === 'html';
        },

        get isGuiEditor() {
            return this.editorType === 'gui';
        },

        get isTextEditor() {
            return !this.isGuiEditor;
        },

        get showContentTab() {
            return this.isTextEditor && (this.activeTab === 'content' || !this.showAssetTabs);
        },

        get showAssetTabs() {
            return this.assetTabs && (this.isHtmlEditor || (this.markdownCss && this.editorType === 'markdown'));
        },

        // JavaScript stays with the HTML editor.
        get showJsTabButton() {
            return this.showAssetTabs && this.isHtmlEditor;
        },

        get showCssTab() {
            return this.showAssetTabs && this.activeTab === 'css';
        },

        get showJsTab() {
            return this.showJsTabButton && this.activeTab === 'js';
        },

        get showInlineMarkdownPreview() {
            return !this.previewEnabled && this.inlineMarkdownPreview && this.editorType === 'markdown';
        },

        get filePath() {
            if (!this.isFileStorage) {
                return '';
            }
            if (this.fileStorageBasePath) {
                const ext = this.editorType === 'markdown' ? 'md' : 'html';
                return this.fileStorageBasePath + '/content.' + ext;
            }
            if (!this.identifier) {
                return '';
            }
            return 'storage/app/pages/' + this.identifier + '.' + (FILE_EXTENSIONS[this.editorType] || 'txt');
        },

        get jsFilePath() {
            return this.isFileStorage && this.isHtmlEditor && this.fileStorageBasePath
                ? this.fileStorageBasePath + '/script.js'
                : '';
        },

        get cssFilePath() {
            return this.isFileStorage && this.isHtmlEditor && this.fileStorageBasePath
                ? this.fileStorageBasePath + '/style.css'
                : '';
        },

        // --- Template helpers (CSP build: no logic in directives) ---

        isAvailable(editor) {
            return this.availableEditors.includes(editor);
        },

        isEditorDisabled(editor) {
            return editor === 'gui' && !this.hasGuiEditor;
        },

        isEditorSelected(editor) {
            return this.editorType === editor;
        },

        isEditorCardChecked(editor) {
            return this.editorType === editor && !this.isEditorDisabled(editor);
        },

        editorCardClass(editor) {
            const classes = [];
            if (this.isEditorDisabled(editor)) {
                classes.push('opacity-50 cursor-not-allowed');
            }
            classes.push(this.editorType === editor
                ? 'border-blue-600 dark:border-blue-500 ring-3 ring-blue-600 dark:ring-blue-500 bg-blue-50 dark:bg-blue-900/30'
                : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-500');
            return classes.join(' ');
        },

        editorCardTitleClass(editor) {
            return this.editorType === editor ? 'text-blue-700 dark:text-blue-300' : 'text-gray-900 dark:text-white';
        },

        selectEditor(editor) {
            if (!this.isEditorDisabled(editor)) {
                this.editorType = editor;
            }
        },

        isTab(tab) {
            return this.activeTab === tab;
        },

        showTab(tab) {
            this.activeTab = tab;
        },

        tabClass(tab) {
            return this.activeTab === tab
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300';
        },

        contentGridClass() {
            return this.showInlineMarkdownPreview ? 'grid grid-cols-1 lg:grid-cols-2 gap-4' : '';
        },

        markdownHtml() {
            return window.marked ? window.marked.parse(this.content || '') : '';
        },

        previewToggleClass() {
            return this.previewVisible
                ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-700'
                : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:text-gray-700 dark:hover:text-gray-300';
        },

        previewToggleIcon() {
            return this.previewVisible ? 'fas fa-eye' : 'fas fa-eye-slash';
        },

        previewToggleLabel() {
            const labels = this.$root.dataset;
            return this.previewVisible ? (labels.previewHide || '') : (labels.previewShow || '');
        },

        splitContainerClass() {
            const classes = [this.isHorizontal ? 'flex-row' : 'flex-col'];
            if (this.isDragging || this.isResizingPreview) {
                classes.push('select-none');
            }
            return classes.join(' ');
        },

        editorPaneClass() {
            return this.isHorizontal && this.previewVisible ? 'overflow-y-auto' : '';
        },

        editorPaneStyle() {
            return this.isHorizontal && this.previewVisible
                ? 'width: ' + (this.splitRatio * 100) + '%; max-height: calc(100vh - 160px)'
                : '';
        },

        // --- Lifecycle ---

        init() {
            if (this.rightSidebar) {
                this.$dispatch('right-sidebar-active');
            }

            this.$watch('storageType', () => {
                if (!this.isAvailable(this.editorType)) {
                    this.editorType = this.availableEditors[0] || 'html';
                }
                this.broadcastState();
            });
            this.$watch('editorType', (value) => {
                this.$dispatch('editor-type-changed', { editorType: value, storageType: this.storageType });
                if (value !== 'html') {
                    this.activeTab = 'content';
                }
                if (this.hasTemplates()) {
                    this.applyTemplate();
                }
                this.broadcastState();
                this.scheduleRender();
            });

            this._onSetStorage = (event) => {
                if (event.detail?.storageType) {
                    this.storageType = event.detail.storageType;
                }
            };
            this._onRequestState = () => this.broadcastState();
            this._onGuiContent = (event) => {
                if (event.detail && event.detail.content !== undefined) {
                    this.content = event.detail.content;
                }
            };
            window.addEventListener('content-editor-set-storage', this._onSetStorage);
            window.addEventListener('content-editor-request-state', this._onRequestState);
            window.addEventListener('editor-content-updated', this._onGuiContent);

            if (this.previewEnabled) {
                this.initPreview();
                this.initSplitPane();
            }

            this.$nextTick(() => {
                this.watchLanguage();
                // A form sent back with errors keeps what was typed.
                if (this.hasTemplates() && !(this.field('content')?.value || '')) {
                    this.applyTemplate();
                }
                this.watchFields();
                this.initAutoResizeTextareas();
                this.broadcastState();
            });
        },

        destroy() {
            window.removeEventListener('content-editor-set-storage', this._onSetStorage);
            window.removeEventListener('content-editor-request-state', this._onRequestState);
            window.removeEventListener('editor-content-updated', this._onGuiContent);
        },

        broadcastState() {
            window.dispatchEvent(new CustomEvent('content-editor-state', {
                detail: {
                    storageType: this.storageType,
                    editorType: this.editorType,
                    isFileStorage: this.isFileStorage,
                    isHtmlEditor: this.isHtmlEditor,
                    filePath: this.filePath,
                    jsFilePath: this.jsFilePath,
                    cssFilePath: this.cssFilePath,
                },
            }));
        },

        // --- Fields ---

        field(name) {
            return this.$root.querySelector('[data-editor-field="' + name + '"]');
        },

        watchFields() {
            const content = this.field('content');
            if (content) {
                content.addEventListener('input', () => this.scheduleRender());
            }
            const css = this.field('css');
            if (css) {
                css.addEventListener('input', () => this.scheduleCssUpdate());
            }
        },

        // --- Templates ---

        hasTemplates() {
            return Object.keys(this.templates).length > 0;
        },

        watchLanguage() {
            if (!this.langField) {
                return;
            }
            const form = this.$root.closest('form') || document;
            const select = form.querySelector('[name="' + this.langField + '"]');
            if (!select) {
                return;
            }
            this.lang = select.value || this.lang;
            select.addEventListener('change', () => {
                this.lang = select.value;
                this.applyTemplate();
            });
        },

        applyTemplate() {
            const tpl = this.templates[this.lang]?.[this.editorType];
            if (tpl) {
                this.setField('content', tpl.content);
                if (tpl.custom_css !== undefined) {
                    this.setField('css', tpl.custom_css);
                }
                if (tpl.custom_js !== undefined) {
                    this.setField('js', tpl.custom_js);
                }
            }
            this.$nextTick(() => this.sendContentToPreview());
        },

        setField(name, value) {
            const el = this.field(name);
            if (el) {
                el.value = value;
                el.dispatchEvent(new Event('input', { bubbles: true }));
            }
        },

        // --- Preview ---

        scheduleRender() {
            if (!this.previewEnabled) {
                return;
            }
            clearTimeout(this._debounceTimer);
            const delay = DEBOUNCE_DELAYS[this.editorType] ?? 400;
            this._debounceTimer = setTimeout(() => this.renderAndSend(), delay);
        },

        scheduleCssUpdate() {
            if (!this.previewEnabled) {
                return;
            }
            clearTimeout(this._cssDebounceTimer);
            this._cssDebounceTimer = setTimeout(() => {
                this.postToIframe('updateCustomCss', { css: this.field('css')?.value || '' });
            }, 200);
        },

        renderAndSend() {
            if (!this.previewReady) {
                return;
            }
            const content = this.field('content')?.value ?? this.content ?? '';

            if (this.previewRender !== 'server' && this.editorType === 'html') {
                this.postToIframe('updateContent', { html: content });
            } else if (this.previewRender !== 'server' && this.editorType === 'markdown') {
                const html = window.marked ? window.marked.parse(content) : content;
                this.postToIframe('updateContent', { html });
            } else {
                this.serverRenderAndSend(content);
            }
        },

        async serverRenderAndSend(content) {
            if (!this.previewUrl) {
                return;
            }
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
                    body: JSON.stringify({ content, editor_type: this.editorType }),
                });

                if (!response.ok) {
                    return;
                }
                const data = await response.json();
                this.postToIframe('updateContent', { html: data.html || '' });
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Preview render failed:', error);
                }
            }
        },

        // Called by previewMixin once the iframe is ready.
        sendContentToPreview() {
            if (!this.previewEnabled) {
                return;
            }
            this.renderAndSend();
            if (this.showAssetTabs) {
                const css = this.field('css')?.value || '';
                if (css) {
                    this.postToIframe('updateCustomCss', { css });
                }
            }
        },

        // --- textarea auto-resize with manual override ---
        //
        // Two heights are in play on these textareas:
        //   1. Auto-grow on input — fits the field to its content
        //      between the minimum and a share of the viewport.
        //   2. Manual drag — once the operator drags the resize handle,
        //      their size sticks for the rest of the session.
        //
        // A ResizeObserver flips a one-way manuallyResized flag when the
        // observed height drifts from the last auto-set height beyond a
        // small tolerance (sub-pixel rounding noise).
        initAutoResizeTextareas() {
            this.$root.querySelectorAll('textarea[data-auto-resize]').forEach((textarea) => {
                textarea.style.overflow = 'hidden';
                textarea.style.resize = 'vertical';
                textarea.style.minHeight = AUTO_RESIZE_MIN_HEIGHT + 'px';

                let manuallyResized = false;
                let lastAutoHeight = 0;

                const autoResize = () => {
                    if (manuallyResized) return;
                    textarea.style.height = 'auto';
                    const maxH = Math.max(AUTO_RESIZE_MIN_HEIGHT, window.innerHeight * AUTO_RESIZE_MAX_HEIGHT_VH);
                    const h = Math.min(Math.max(textarea.scrollHeight, AUTO_RESIZE_MIN_HEIGHT), maxH);
                    textarea.style.height = h + 'px';
                    textarea.style.overflow = h >= maxH ? 'auto' : 'hidden';
                    lastAutoHeight = h;
                };

                // Compare border-box heights: style.height (what autoResize
                // writes) is border-box, while contentRect excludes padding
                // and border. A height of 0 means the field sits in a hidden
                // tab; refit it once it becomes visible instead of reading
                // that as a drag.
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
                    if (Math.abs(currentHeight - lastAutoHeight) < AUTO_RESIZE_TOLERANCE_PX) return;
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

/**
 * Sidebar part of <x-content-editor.storage-info>: shows the editor's file
 * paths and sends a changed storage type back to it, through the events
 * described above.
 */
export function contentEditorStorageInfo() {
    return {
        storageType: this?.$el?.dataset.storageType || 'database',
        isFileStorage: false,
        isHtmlEditor: false,
        filePath: '',
        jsFilePath: '',
        cssFilePath: '',

        init() {
            this._onState = (event) => {
                const state = event.detail || {};
                this.storageType = state.storageType || this.storageType;
                this.isFileStorage = !!state.isFileStorage;
                this.isHtmlEditor = !!state.isHtmlEditor;
                this.filePath = state.filePath || '';
                this.jsFilePath = state.jsFilePath || '';
                this.cssFilePath = state.cssFilePath || '';
            };
            window.addEventListener('content-editor-state', this._onState);
            window.dispatchEvent(new CustomEvent('content-editor-request-state'));

            this.$watch('storageType', (value) => {
                window.dispatchEvent(new CustomEvent('content-editor-set-storage', { detail: { storageType: value } }));
            });
        },

        destroy() {
            window.removeEventListener('content-editor-state', this._onState);
        },

        get showJsPath() {
            return this.isHtmlEditor && this.jsFilePath !== '';
        },

        get showCssPath() {
            return this.isHtmlEditor && this.cssFilePath !== '';
        },
    };
}

/**
 * Root-relative path of a media URL, so inserted content keeps working when
 * the site's domain changes. A URL on another origin is kept whole.
 */
export function mediaPath(url) {
    try {
        const parsed = new URL(url, window.location.origin);
        return parsed.origin === window.location.origin
            ? parsed.pathname + parsed.search + parsed.hash
            : parsed.href;
    } catch (e) {
        return String(url || '');
    }
}

function escapeHtmlAttribute(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function escapeMarkdownText(value) {
    return String(value ?? '').replace(/([\\\[\]])/g, '\\$1');
}

function markdownUrl(path) {
    // Spaces and parentheses would end the link early.
    let decoded = path;
    try {
        decoded = decodeURI(path);
    } catch (e) {
        // Keep a path whose escapes do not decode as it is.
    }
    return encodeURI(decoded).replace(/\(/g, '%28').replace(/\)/g, '%29');
}

/**
 * The tag for a media item: an image, or a link for any other file.
 * Markdown gets Markdown; HTML and Blade get HTML.
 */
export function mediaTag(media, editorType) {
    const path = mediaPath(media.url || '');
    const isImage = String(media.type || '').startsWith('image/');
    const text = media.alt_text || media.name || '';

    if (editorType === 'markdown') {
        return isImage
            ? '![' + escapeMarkdownText(text) + '](' + markdownUrl(path) + ')'
            : '[' + escapeMarkdownText(media.name || path) + '](' + markdownUrl(path) + ')';
    }

    if (!isImage) {
        return '<a href="' + escapeHtmlAttribute(path) + '">' + escapeHtmlAttribute(media.name || path) + '</a>';
    }

    let tag = '<img src="' + escapeHtmlAttribute(path) + '" alt="' + escapeHtmlAttribute(text) + '"';
    if (media.width && media.height) {
        tag += ' width="' + parseInt(media.width, 10) + '" height="' + parseInt(media.height, 10) + '"';
    }
    return tag + ' loading="lazy">';
}

/**
 * The tags for several media items, in the order given. In Markdown each
 * item is its own paragraph (a blank line between); in HTML and Blade each
 * tag is on its own line.
 */
export function mediaTags(items, editorType) {
    const separator = editorType === 'markdown' ? '\n\n' : '\n';
    return items.map(media => mediaTag(media, editorType)).join(separator);
}

/**
 * "Insert image" button of the content editor. Opens the media selector
 * in multiple mode and writes a tag for every chosen item, in the order
 * they were chosen, over the field's selection (or at the cursor), then
 * fires input, so x-model and the preview follow.
 * The editor type comes from the field's data-editor-type.
 */
export function contentEditorInsertImage() {
    return {
        targetId: '',
        selectorId: '',

        init() {
            this.targetId = this.$el.dataset.target || '';
            this.selectorId = this.$el.dataset.selectorId || '';
            this._onSelected = (event) => {
                if (event.detail?.inputId === this.selectorId + '_input') {
                    this.insert(event.detail.media);
                }
            };
            document.addEventListener('media-selected', this._onSelected);
        },

        destroy() {
            document.removeEventListener('media-selected', this._onSelected);
        },

        open() {
            if (typeof window.openMediaSelector === 'function') {
                // Multiple mode: the selection comes back in the order it was made
                window.openMediaSelector(this.selectorId, this.selectorId + '_input', this.selectorId + '_preview', true);
            }
        },

        insert(media) {
            const field = document.getElementById(this.targetId);
            const items = (Array.isArray(media) ? media : [media]).filter(Boolean);
            if (!field || items.length === 0) {
                return;
            }
            const tag = mediaTags(items, field.dataset.editorType || 'html');
            const start = field.selectionStart ?? field.value.length;
            const end = field.selectionEnd ?? start;
            field.focus();
            field.setRangeText(tag, start, end, 'end');
            field.dispatchEvent(new Event('input', { bubbles: true }));
        },
    };
}

/**
 * The Alpine side of <x-content-editor.sidebar-section>.
 *
 * The Blade component decides the initial state (data-open) and whether a
 * validation error forces the section open (data-force-open). With a
 * data-remember-key, the viewer's last choice is kept in localStorage and
 * wins over data-open, except when an error forces it open. Storage can be
 * unavailable (private windows, blocked site data), so every access is
 * guarded and the section then just starts from data-open.
 */
const SIDEBAR_SECTION_STORAGE_PREFIX = 'dixlase.sidebarSection.';

function readSidebarSectionState(key) {
    try {
        return window.localStorage.getItem(SIDEBAR_SECTION_STORAGE_PREFIX + key);
    } catch {
        return null;
    }
}

function writeSidebarSectionState(key, open) {
    try {
        window.localStorage.setItem(SIDEBAR_SECTION_STORAGE_PREFIX + key, open ? '1' : '0');
    } catch {
        // Not remembered; the section still opens and closes.
    }
}

export function contentEditorSidebarSection() {
    return {
        open: false,

        init() {
            const data = this.$el.dataset;
            this._key = data.rememberKey || '';
            this.open = data.open === '1';

            if (data.forceOpen === '1' || this._key === '') {
                return;
            }

            const remembered = readSidebarSectionState(this._key);
            if (remembered === '1' || remembered === '0') {
                this.open = remembered === '1';
            }
        },

        toggle() {
            this.open = !this.open;
            if (this._key !== '') {
                writeSidebarSectionState(this._key, this.open);
            }
        },

        expanded() {
            return this.open ? 'true' : 'false';
        },

        chevronClass() {
            return this.open ? 'rotate-180' : '';
        },
    };
}

Alpine.data('contentEditor', contentEditor);
Alpine.data('contentEditorInsertImage', contentEditorInsertImage);
Alpine.data('contentEditorStorageInfo', contentEditorStorageInfo);
Alpine.data('contentEditorSidebarSection', contentEditorSidebarSection);
