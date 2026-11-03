/*
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
 * Tag input with suggestions (x-form-tag-input, #841; the media library's
 * x-media.tag-input, #766, is a wrapper around it).
 *
 * A combobox: typing narrows the existing tags, shown with their counts,
 * and "create «…»" comes one step below them, so an existing tag is the
 * first thing offered. Chosen tags become hidden inputs, the tag's name
 * or its id depending on data-value-field.
 *
 * With data-commit-typed, Space and leaving the field add what was typed
 * as it is, for a free-text tag field. With data-strip-hash, a leading "#"
 * is dropped.
 *
 * The tag list is fetched from data-tags-url the first time the field
 * gets focus. Configuration comes from data attributes, so the directive
 * stays a bare name (strict-mode ready).
 */

import Alpine from 'alpinejs';

const MAX_OPTIONS = 20;

/**
 * The form a tag name is stored in; mirrors MediaTag::normalizeName().
 */
export function normalizeTagName(name) {
    return String(name)
        .replace(/[\s　]+/g, ' ')
        // eslint-disable-next-line no-control-regex
        .replace(/[\u0000-\u001F\u007F-\u009F‪-‮⁦-⁩]+/g, '')
        .trim();
}

Alpine.data('tagInput', () => ({
    query: '',
    tags: [],
    loaded: false,
    loading: false,
    selected: [],
    open: false,
    activeIndex: 0,
    nextKey: 0,
    config: {},
    rootId: '',

    init() {
        // $el is the element a directive sits on, so methods called from the
        // field or a chip would read their own data attributes. Keep the
        // root's configuration here instead.
        this.config = Object.assign({}, this.$el.dataset);
        this.rootId = this.$el.id;

        let initial = [];
        try {
            initial = JSON.parse(this.config.selected || '[]');
        } catch (error) {
            initial = [];
        }
        this.selected = initial.map(name => ({ key: ++this.nextKey, id: null, name: String(name) }));

        // Each opening of a bulk modal starts empty
        this.$el.addEventListener('tag-input-reset', () => this.reset());
    },

    reset() {
        this.query = '';
        this.selected = [];
        this.open = false;
        this.activeIndex = 0;
    },

    allowCreate() {
        return this.config.allowCreate === '1';
    },

    valueField() {
        return this.config.valueField === 'id' ? 'id' : 'name';
    },

    inputName() {
        return this.config.name || 'tags[]';
    },

    formId() {
        return this.config.form || null;
    },

    commitTyped() {
        return this.config.commitTyped === '1';
    },

    /**
     * What was typed, as a tag name: normalised, and without a leading "#"
     * when data-strip-hash is set.
     */
    typedName() {
        const name = normalizeTagName(this.query);
        return this.config.stripHash === '1' ? name.replace(/^#+/, '').trim() : name;
    },

    async load() {
        if (this.loaded || this.loading) return;
        this.loading = true;
        try {
            const response = await fetch(this.config.tagsUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();
            this.tags = Array.isArray(data.tags) ? data.tags : [];
            this.loaded = true;
        } catch (error) {
            this.tags = [];
        } finally {
            this.loading = false;
        }
    },

    focused() {
        this.load();
        this.open = true;
        this.activeIndex = 0;
    },

    blurred() {
        // Options take mousedown.prevent, so choosing one does not blur.
        // A free-text field keeps an unfinished word rather than losing it.
        if (this.commitTyped()) this.addTyped(false);
        this.open = false;
    },

    typed() {
        this.open = true;
        this.activeIndex = 0;
    },

    isSelected(tag) {
        return this.selected.some(item => item.name === tag.name);
    },

    /**
     * Existing tags that match what was typed, then "create" when allowed
     * and no tag has exactly that name.
     */
    options() {
        const name = this.typedName();
        const needle = name.toLocaleLowerCase();
        const matches = this.tags
            .filter(tag => !this.isSelected(tag))
            .filter(tag => needle === '' || tag.name.toLocaleLowerCase().includes(needle))
            // The tag that is exactly what was typed comes first, so Enter
            // takes "news" rather than "newsletter"
            .sort((a, b) => Number(b.name.toLocaleLowerCase() === needle) - Number(a.name.toLocaleLowerCase() === needle))
            .slice(0, MAX_OPTIONS)
            .map(tag => ({ key: 't' + tag.id, tag: tag, create: false }));

        const exists = this.tags.some(tag => tag.name === name) || this.selected.some(item => item.name === name);
        if (this.allowCreate() && name !== '' && !exists) {
            matches.push({ key: 'new', tag: { id: null, name: name, count: 0 }, create: true });
        }

        return matches;
    },

    hasOptions() {
        return this.options().length > 0;
    },

    showList() {
        return this.open && this.hasOptions();
    },

    showEmpty() {
        return this.open && this.loaded && !this.hasOptions() && this.typedName() !== '';
    },

    optionId(index) {
        return this.rootId + '-option-' + index;
    },

    activeDescendant() {
        return this.showList() ? this.optionId(this.activeIndex) : '';
    },

    optionClass(index) {
        return index === this.activeIndex
            ? 'bg-blue-50 text-blue-900 dark:bg-blue-900/40 dark:text-blue-100'
            : 'text-gray-700 dark:text-gray-200';
    },

    isActive(index) {
        return index === this.activeIndex;
    },

    optionLabel(option) {
        if (option.create) {
            return (this.config.createLabel || 'Create “:name”').replace(':name', option.tag.name);
        }
        return option.tag.name;
    },

    optionCount(option) {
        return option.create ? '' : String(option.tag.count);
    },

    choose(option, refocus = true) {
        if (!option) return;
        this.selected.push({ key: ++this.nextKey, id: option.tag.id, name: option.tag.name });
        this.query = '';
        this.activeIndex = 0;
        if (!refocus) return;
        this.open = true;
        this.$refs.field?.focus();
    },

    /**
     * Adds what was typed as it is: the existing tag of that name, else a
     * new one when creating is allowed. Typing a tag already chosen just
     * clears the field.
     */
    addTyped(refocus = true) {
        const name = this.typedName();
        if (name === '') {
            this.query = '';
            return;
        }
        if (this.selected.some(item => item.name === name)) {
            this.query = '';
            return;
        }
        const tag = this.tags.find(entry => entry.name === name);
        if (tag) {
            this.choose({ tag: tag, create: false }, refocus);
        } else if (this.allowCreate()) {
            this.choose({ tag: { id: null, name: name, count: 0 }, create: true }, refocus);
        }
    },

    remove(item) {
        this.selected = this.selected.filter(entry => entry.key !== item.key);
        this.$refs.field?.focus();
    },

    removeLabel(item) {
        return (this.config.removeLabel || 'Remove :name').replace(':name', item.name);
    },

    // Not valueOf: inside x-for, Alpine finds the loop scope's own
    // Object.prototype.valueOf before this object's methods
    submittedValue(item) {
        return this.valueField() === 'id' ? String(item.id) : item.name;
    },

    keydown(event) {
        // Enter while an IME is converting confirms the conversion, not a tag
        if (event.isComposing || event.keyCode === 229) return;

        const options = this.options();
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            this.open = true;
            this.activeIndex = options.length ? (this.activeIndex + 1) % options.length : 0;
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            this.open = true;
            this.activeIndex = options.length ? (this.activeIndex - 1 + options.length) % options.length : 0;
        } else if (event.key === ' ' && this.commitTyped()) {
            // A free-text field takes Space as "this word is a tag"
            event.preventDefault();
            this.addTyped();
        } else if (event.key === 'Enter') {
            // Never submits the surrounding form
            event.preventDefault();
            if (this.open && options.length) {
                this.choose(options[this.activeIndex]);
            } else if (this.commitTyped()) {
                this.addTyped();
            }
        } else if (event.key === 'Escape') {
            if (this.open) {
                event.stopPropagation();
                this.open = false;
            }
        } else if (event.key === 'Backspace' && this.query === '' && this.selected.length > 0) {
            this.selected.pop();
        }
    },
}));
