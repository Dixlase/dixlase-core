/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * @api Stable API available for plugins/themes to import
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
 * Shared Alpine.data() registrations.
 *
 * All registered factories here are written to be valid under the
 * @alpinejs/csp build (the strict-mode bundle): no inline JS expressions,
 * data is exposed as named registrations referenced by `x-data="name"`.
 *
 * Usage in a Vite entry:
 *
 *     import Alpine from 'alpinejs';
 *     import { registerSharedAlpineData } from '@/common/js/alpine-data/index.js';
 *
 *     registerSharedAlpineData(Alpine);
 *     Alpine.start();
 *
 * The `@` alias maps to `resources/src` (see vite.config.js). From a file
 * outside that root (or without the alias available), use a relative path.
 *
 * Usage in a Blade view:
 *
 *     <div x-data="accordion" :class="open ? 'open' : ''">
 *         <button type="button" @click="toggle">...</button>
 *         <div x-show="open">...</div>
 *     </div>
 *
 * Most factories accept an init payload via Alpine's built-in mechanism:
 * pass arguments through `x-data="accordion({ open: true })"` — the @alpinejs/csp
 * build allows method calls but not object literals as expression bodies, so
 * for strict-mode code prefer setting initial state via `data-*` attributes
 * read in `init()`.
 */

/**
 * Collapsible / disclosure widget.
 *
 *     <div x-data="accordion">
 *         <button @click="toggle" :aria-expanded="open">Toggle</button>
 *         <div x-show="open">Body</div>
 *     </div>
 *
 * Initial state can be set with `data-open="1"`.
 */
export const accordion = () => ({
    open: false,

    init() {
        if (this.$el?.dataset?.open === '1') {
            this.open = true;
        }
    },

    toggle() {
        this.open = !this.open;
    },

    show() {
        this.open = true;
    },

    hide() {
        this.open = false;
    },
});

/**
 * Generic boolean toggle.
 *
 * Same shape as accordion but the property is named `enabled` to read
 * naturally for switches, on/off settings, etc.
 *
 *     <div x-data="toggle">
 *         <button @click="flip" :class="enabled ? 'on' : 'off'">...</button>
 *     </div>
 */
export const toggle = () => ({
    enabled: false,

    init() {
        const initial = this.$el?.dataset?.enabled;
        if (initial === '1' || initial === 'true') {
            this.enabled = true;
        }
    },

    flip() {
        this.enabled = !this.enabled;
    },

    on() {
        this.enabled = true;
    },

    off() {
        this.enabled = false;
    },
});

/**
 * Tab strip.
 *
 *     <div x-data="tabs">
 *         <button @click="setTab('one')" :class="isActive('one') ? 'active' : ''">One</button>
 *         <button @click="setTab('two')" :class="isActive('two') ? 'active' : ''">Two</button>
 *         <section x-show="isActive('one')">...</section>
 *         <section x-show="isActive('two')">...</section>
 *     </div>
 *
 * Initial active tab via `data-active-tab="one"`.
 */
export const tabs = () => ({
    activeTab: '',

    init() {
        const initial = this.$el?.dataset?.activeTab;
        if (initial) {
            this.activeTab = initial;
        }
    },

    setTab(name) {
        this.activeTab = name;
    },

    isActive(name) {
        return this.activeTab === name;
    },
});

/**
 * Modal / dialog state.
 *
 *     <div x-data="modal">
 *         <button @click="open">Open</button>
 *         <div x-show="isOpen" @keydown.escape.window="close">
 *             <button @click="close">×</button>
 *             ...
 *         </div>
 *     </div>
 */
export const modal = () => ({
    isOpen: false,

    init() {
        if (this.$el?.dataset?.open === '1') {
            this.isOpen = true;
        }
    },

    open() {
        this.isOpen = true;
    },

    close() {
        this.isOpen = false;
    },

    toggle() {
        this.isOpen = !this.isOpen;
    },
});

/**
 * Copy-to-clipboard with a transient "copied" indicator.
 *
 *     <div x-data="clipboardCopy">
 *         <span x-ref="source">https://example.com</span>
 *         <button @click="copyFromRef('source')">
 *             <span x-show="!copied">Copy</span>
 *             <span x-show="copied">Copied!</span>
 *         </button>
 *     </div>
 */
export const clipboardCopy = () => ({
    copied: false,
    timeoutMs: 2000,

    init() {
        const t = parseInt(this.$el?.dataset?.timeoutMs || '', 10);
        if (Number.isFinite(t) && t > 0) {
            this.timeoutMs = t;
        }
    },

    async copy(text) {
        try {
            await navigator.clipboard.writeText(text);
            this._flash();
        } catch (err) {
            console.warn('[Dixlase] clipboard copy failed', err);
        }
    },

    copyFromRef(refName) {
        const node = this.$refs?.[refName];
        if (!node) {
            return;
        }
        this.copy(node.textContent ?? '');
    },

    _flash() {
        this.copied = true;
        setTimeout(() => {
            this.copied = false;
        }, this.timeoutMs);
    },
});

/**
 * Register all shared data factories with an Alpine instance.
 *
 * Idempotent — safe to call from multiple entry points; the second
 * registration of the same name is a no-op in Alpine.
 */
export function registerSharedAlpineData(Alpine) {
    if (!Alpine || typeof Alpine.data !== 'function') {
        console.error('[Dixlase] registerSharedAlpineData: invalid Alpine instance');
        return;
    }

    Alpine.data('accordion', accordion);
    Alpine.data('toggle', toggle);
    Alpine.data('tabs', tabs);
    Alpine.data('modal', modal);
    Alpine.data('clipboardCopy', clipboardCopy);
}

export default registerSharedAlpineData;
