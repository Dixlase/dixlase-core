/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Dixlase Core Bootloader
 * 
 * CSP厳格モード対応の初期化システム。
 * インライン実行コードを使わずに、宣言的にUIを初期化できる仕組みを提供。
 */

// グローバルDixlaseオブジェクト
const Dixlase = window.Dixlase || {};

/**
 * ウィジェット登録システム
 */
Dixlase.widgets = {
    _registry: new Map(),

    /**
     * ウィジェットを登録
     * @param {string} name - ウィジェット名
     * @param {Function} initializer - 初期化関数 (el, props) => void
     */
    register(name, initializer) {
        if (typeof initializer !== 'function') {
            console.error(`[Dixlase] Widget "${name}": initializer must be a function`);
            return;
        }
        this._registry.set(name, initializer);
    },

    /**
     * ウィジェットを初期化
     * @param {HTMLElement} el - 対象要素
     */
    init(el) {
        const widgetName = el.dataset.dixWidget;
        if (!widgetName) return;

        const initializer = this._registry.get(widgetName);
        if (!initializer) {
            console.warn(`[Dixlase] Widget "${widgetName}" not registered`);
            return;
        }

        // data-dix-propsからpropsを復元
        let props = {};
        if (el.dataset.dixProps) {
            try {
                props = JSON.parse(el.dataset.dixProps);
            } catch (e) {
                console.error(`[Dixlase] Widget "${widgetName}": Invalid JSON in data-dix-props`, e);
            }
        }

        try {
            initializer(el, props);
        } catch (e) {
            console.error(`[Dixlase] Widget "${widgetName}": Initialization failed`, e);
        }
    },

    /**
     * すべてのウィジェットを初期化
     */
    initAll() {
        document.querySelectorAll('[data-dix-widget]').forEach(el => {
            this.init(el);
        });
    }
};

/**
 * アクション登録システム（イベント委譲）
 */
Dixlase.actions = {
    _registry: new Map(),
    _initialized: false,

    /**
     * アクションを登録
     * @param {string} name - アクション名（例: "contact.submit"）
     * @param {Function} handler - ハンドラ関数 (context) => void
     */
    register(name, handler) {
        if (typeof handler !== 'function') {
            console.error(`[Dixlase] Action "${name}": handler must be a function`);
            return;
        }
        this._registry.set(name, handler);
    },

    /**
     * イベント委譲を初期化
     */
    init() {
        if (this._initialized) return;

        // クリックイベントを委譲
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[data-dix-action]');
            if (!target) return;

            const actionName = target.dataset.dixAction;
            const handler = this._registry.get(actionName);

            if (!handler) {
                console.warn(`[Dixlase] Action "${actionName}" not registered`);
                return;
            }

            // コンテキストを構築
            const context = {
                event: e,
                element: target,
                form: target.closest('form'),
                data: this._parseData(target)
            };

            try {
                handler(context);
            } catch (err) {
                console.error(`[Dixlase] Action "${actionName}": Handler failed`, err);
            }
        });

        // submitイベントも委譲
        document.addEventListener('submit', (e) => {
            const target = e.target;
            if (!target.dataset.dixAction) return;

            const actionName = target.dataset.dixAction;
            const handler = this._registry.get(actionName);

            if (!handler) return;

            const context = {
                event: e,
                element: target,
                form: target,
                data: new FormData(target)
            };

            try {
                handler(context);
            } catch (err) {
                console.error(`[Dixlase] Action "${actionName}": Handler failed`, err);
            }
        });

        this._initialized = true;
    },

    /**
     * data-*属性からデータを抽出
     */
    _parseData(element) {
        const data = {};
        for (const key in element.dataset) {
            if (key.startsWith('dix') && key !== 'dixAction') {
                const dataKey = key.replace(/^dix/, '').replace(/^./, c => c.toLowerCase());
                try {
                    data[dataKey] = JSON.parse(element.dataset[key]);
                } catch {
                    data[dataKey] = element.dataset[key];
                }
            }
        }
        return data;
    }
};

/**
 * ページ固有の初期化
 */
Dixlase.pages = {
    _registry: new Map(),

    /**
     * ページ初期化関数を登録
     * @param {string} pageName - ページ名（例: "admin.dashboard"）
     * @param {Function} initializer - 初期化関数
     */
    register(pageName, initializer) {
        if (typeof initializer !== 'function') {
            console.error(`[Dixlase] Page "${pageName}": initializer must be a function`);
            return;
        }
        this._registry.set(pageName, initializer);
    },

    /**
     * 現在のページを初期化
     */
    init() {
        const body = document.body;
        const pageName = body.dataset.dixPage;

        if (!pageName) return;

        const initializer = this._registry.get(pageName);
        if (!initializer) {
            console.warn(`[Dixlase] Page "${pageName}" not registered`);
            return;
        }

        try {
            initializer();
        } catch (e) {
            console.error(`[Dixlase] Page "${pageName}": Initialization failed`, e);
        }
    }
};

/**
 * 設定データの読み込み
 */
Dixlase.config = {
    /**
     * JSON scriptから設定を読み込み
     * @param {string} id - script要素のID
     * @returns {Object|null}
     */
    load(id) {
        const script = document.getElementById(id);
        if (!script || script.type !== 'application/json') {
            console.warn(`[Dixlase] Config script "${id}" not found or invalid type`);
            return null;
        }

        try {
            return JSON.parse(script.textContent);
        } catch (e) {
            console.error(`[Dixlase] Config "${id}": Invalid JSON`, e);
            return null;
        }
    },

    /**
     * data-*属性から設定を読み込み
     * @param {HTMLElement} element
     * @param {string} key - data-dix-config-{key}
     * @returns {any}
     */
    get(element, key) {
        const dataKey = `dixConfig${key.charAt(0).toUpperCase()}${key.slice(1)}`;
        const value = element.dataset[dataKey];

        if (value === undefined) return null;

        try {
            return JSON.parse(value);
        } catch {
            return value;
        }
    }
};

/**
 * ユーティリティ
 */
Dixlase.utils = {
    /**
     * 要素が表示されているか
     */
    isVisible(element) {
        return !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
    },

    /**
     * 要素を表示/非表示
     */
    toggle(element, show) {
        if (show === undefined) {
            show = !this.isVisible(element);
        }
        element.style.display = show ? '' : 'none';
    },

    /**
     * クラスをトグル
     */
    toggleClass(element, className, force) {
        element.classList.toggle(className, force);
    },

    /**
     * 安全なHTML挿入（XSS対策）
     */
    setHTML(element, html) {
        // DOMPurifyが利用可能な場合は使用
        if (window.DOMPurify) {
            element.innerHTML = DOMPurify.sanitize(html);
        } else {
            // フォールバック: textContentを使用（HTMLタグは無効化される）
            console.warn('[Dixlase] DOMPurify not available, using textContent instead');
            element.textContent = html;
        }
    },

    /**
     * 安全なテキスト挿入
     */
    setText(element, text) {
        element.textContent = text;
    },

    /**
     * debounce
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * throttle
     */
    throttle(func, limit) {
        let inThrottle;
        return function (...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
};

/**
 * ブートローダーの初期化
 */
Dixlase.boot = function () {
    // DOMContentLoadedで初期化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            this._init();
        });
    } else {
        this._init();
    }
};

/**
 * 内部初期化処理
 */
Dixlase._init = function () {
    console.log('[Dixlase] Bootloader initializing...');

    // アクションシステムを初期化
    this.actions.init();

    // ページ固有の初期化
    this.pages.init();

    // ウィジェットを初期化
    this.widgets.initAll();

    console.log('[Dixlase] Bootloader initialized');

    // カスタムイベントを発火
    window.dispatchEvent(new CustomEvent('dixlase:ready'));
};

// グローバルに公開
window.Dixlase = Dixlase;

// 自動ブート
Dixlase.boot();

