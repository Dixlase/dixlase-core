# Alpine.js CSP-Compatible Coding Rules

## Table of Contents

1. [Purpose and Background](#purpose-and-background)
2. [Quick Reference](#quick-reference)
3. [Rule Details](#rule-details)
4. [JS File Conventions](#js-file-conventions)
5. [Migration Table from Existing Patterns](#migration-table-from-existing-patterns)
6. [Code Review Checklist](#code-review-checklist)

---

## Purpose and Background

### Why These Rules Are Needed

Dixlase uses Alpine.js, which requires allowing `unsafe-eval` in the CSP (Content Security Policy). This is because Alpine.js internally uses `new Function()` to evaluate template expressions.

In the future, we plan to migrate to Alpine CSP Build (`@alpinejs/csp`) to eliminate `unsafe-eval` and achieve strict mode.

**The purpose of these rules is to write new code using CSP Build-compatible patterns, minimizing the cost of future migration.** CSP-compatible patterns work perfectly fine with the standard Alpine.js build, so they can be applied immediately.

### Limitations of Alpine CSP Build

Alpine CSP Build does not use `new Function()` to evaluate JavaScript expressions in templates. Therefore, the following are not supported:

- Inline object literals (`x-data="{ open: false }"`)
- The `x-model` directive
- The `x-html` directive
- Method calls with arguments (`@click="doSomething('arg')"`)
- Complex JavaScript expressions (ternary operators, template literals, assignment expressions, etc.)
- Access to global variables/functions (`window`, `document`, `console`, etc.)
- Direct access to `$store` in templates

### Scope of Application

- **All newly created Blade templates and JavaScript files**
- When modifying existing code, **only newly written code within the changed file** is subject to these rules (full rewriting of existing code is not required)

---

## Quick Reference

### Template (Blade) Patterns

| Pattern | NG | OK |
|---------|----|----|
| **x-data** | `x-data="{ open: false }"` | `x-data="myComponent"` |
| **@click (method)** | `@click="toggle()"` | `@click="toggle"` |
| **@click (arguments)** | `@click="openModal('id')"` | `@click="openModal" data-modal-target="id"` |
| **@click (assignment)** | `@click="open = !open"` | `@click="toggle"` |
| **x-model** | `x-model="name"` | `:value="name" @input="setName"` |
| **x-text (expression)** | `x-text="count + ' items'"` | `x-text="itemCountText"` |
| **x-text (ternary)** | `x-text="ok ? 'Yes' : 'No'"` | `x-text="statusText"` |
| **x-show (negation)** | `x-show="!open"` | `x-show="isClosed"` |
| **:class (object)** | `:class="{ 'active': isActive }"` | `:class="activeClass"` |
| **$store** | `x-text="$store.notification.message"` | `x-text="message"` (via getter) |
| **x-html** | `x-html="htmlContent"` | Use server-side rendering |
| **Global variables** | `@click="window.location.reload()"` | `@click="reload"` |

### JavaScript Patterns

| Pattern | NG (problematic for future migration) | OK |
|---------|----|----|
| **Component definition** | `window.func = function() {}` | `Alpine.data('name', () => ({}))` |
| **Argument passing** | `window.func = function(arg) {}` | `data-*` attributes + read in `init()` |
| **Computed properties** | Writing expressions in templates | `get propName() { return ... }` |

---

## Rule Details

### Rule 1: `x-data` — Register with `Alpine.data()`

#### NG: Inline object literal

```html
<div x-data="{ open: false, count: 0 }">
    ...
</div>
```

#### OK: Reference to `Alpine.data()`

```html
<div x-data="togglePanel">
    ...
</div>
```

```javascript
// resources/src/components/js/toggle-panel.js
document.addEventListener('alpine:init', () => {
    Alpine.data('togglePanel', () => ({
        open: false,
        count: 0,

        toggle() {
            this.open = !this.open;
        },
    }));
});
```

#### Passing initial values from the server

```html
<!-- Pass initial values via data-* attributes -->
<div x-data="emailInput"
     data-original-email="{{ $user->email }}"
     data-show-confirmation="{{ $showConfirmation ? 'true' : 'false' }}">
    ...
</div>
```

```javascript
Alpine.data('emailInput', () => ({
    email: '',
    showConfirmation: false,

    init() {
        this.email = this.$el.dataset.originalEmail || '';
        this.showConfirmation = this.$el.dataset.showConfirmation === 'true';
    },
}));
```

#### Passing complex initial data via JSON

```html
<div x-data="chartWidget">
    <script type="application/json" data-config>
        {!! json_encode(['labels' => $labels, 'values' => $values]) !!}
    </script>
    ...
</div>
```

```javascript
Alpine.data('chartWidget', () => ({
    labels: [],
    values: [],

    init() {
        const configEl = this.$el.querySelector('script[data-config]');
        if (configEl) {
            const config = JSON.parse(configEl.textContent);
            this.labels = config.labels;
            this.values = config.values;
        }
    },
}));
```

---

### Rule 2: Event Handlers — Use method references only

#### NG: Method calls with arguments

```html
<button @click="openModal('deleteModal')">Delete</button>
<button @click="selectTab('settings')">Settings</button>
```

#### OK: Pass data via `data-*` attributes

```html
<button @click="openModal" data-modal-target="deleteModal">Delete</button>
<button @click="selectTab" data-tab="settings">Settings</button>
```

```javascript
Alpine.data('myComponent', () => ({
    openModal(event) {
        const modalId = event.currentTarget.dataset.modalTarget;
        window.openModal(modalId);
    },

    selectTab(event) {
        this.activeTab = event.currentTarget.dataset.tab;
    },
}));
```

#### NG: Assignments and expressions in templates

```html
<button @click="open = !open">Toggle</button>
<button @click="count++">Count</button>
<button @click="if (!disabled) save()">Save</button>
```

#### OK: Method references

```html
<button @click="toggle">Toggle</button>
<button @click="increment">Count</button>
<button @click="saveIfEnabled">Save</button>
```

```javascript
Alpine.data('myComponent', () => ({
    open: false,
    count: 0,
    disabled: false,

    toggle() {
        this.open = !this.open;
    },

    increment() {
        this.count++;
    },

    saveIfEnabled() {
        if (!this.disabled) {
            this.save();
        }
    },
}));
```

#### NG: Direct calls to global functions

```html
<button @click="window.location.reload()">Reload</button>
<button @click="navigator.clipboard.writeText(url)">Copy</button>
```

#### OK: Via methods

```html
<button @click="reload">Reload</button>
<button @click="copyUrl" data-url="{{ $url }}">Copy</button>
```

```javascript
Alpine.data('myComponent', () => ({
    reload() {
        window.location.reload();
    },

    copyUrl(event) {
        const url = event.currentTarget.dataset.url;
        navigator.clipboard.writeText(url);
    },
}));
```

---

### Rule 3: `x-model` — Replace with `:value` + `@input`

#### NG: `x-model`

```html
<input type="text" x-model="name">
<textarea x-model="content"></textarea>
<select x-model="country">...</select>
<input type="checkbox" x-model="agreed">
```

#### OK: `:value` + `@input` (`:checked` + `@change`)

```html
<input type="text" :value="name" @input="setName">
<textarea :value="content" @input="setContent"></textarea>
<select :value="country" @change="setCountry">...</select>
<input type="checkbox" :checked="agreed" @change="toggleAgreed">
```

```javascript
Alpine.data('myForm', () => ({
    name: '',
    content: '',
    country: '',
    agreed: false,

    setName(event) {
        this.name = event.target.value;
    },

    setContent(event) {
        this.content = event.target.value;
    },

    setCountry(event) {
        this.country = event.target.value;
    },

    toggleAgreed(event) {
        this.agreed = event.target.checked;
    },
}));
```

#### Generic Setter Pattern

When there are many form fields, you can use a generic setter method:

```javascript
Alpine.data('myForm', () => ({
    name: '',
    email: '',
    phone: '',

    /**
     * Set the input value to the property specified by the data-field attribute
     */
    setField(event) {
        const field = event.target.dataset.field || event.target.name;
        if (field && field in this) {
            this[field] = event.target.value;
        }
    },
}));
```

```html
<input :value="name" @input="setField" data-field="name">
<input :value="email" @input="setField" data-field="email">
<input :value="phone" @input="setField" data-field="phone">
```

---

### Rule 4: `x-text` / `x-show` / `x-bind` — Use property references or getters

#### NG: Expressions in templates

```html
<span x-text="count + ' items'"></span>
<span x-text="saving ? 'Saving...' : 'Save'"></span>
<div x-show="items.length > 0"></div>
<div x-show="!open"></div>
```

#### OK: Property references or getters

```html
<span x-text="itemCountText"></span>
<span x-text="saveButtonText"></span>
<div x-show="hasItems"></div>
<div x-show="isClosed"></div>
```

```javascript
Alpine.data('myComponent', () => ({
    count: 0,
    saving: false,
    items: [],
    open: false,

    get itemCountText() {
        return this.count + ' items';
    },

    get saveButtonText() {
        return this.saving ? 'Saving...' : 'Save';
    },

    get hasItems() {
        return this.items.length > 0;
    },

    get isClosed() {
        return !this.open;
    },
}));
```

#### Simple property references are OK as-is

```html
<!-- These work fine with CSP Build -->
<span x-text="message"></span>
<div x-show="open"></div>
<input :disabled="loading">
```

---

### Rule 5: `:class` — Return dynamic classes via getters

#### NG: Object syntax

```html
<div :class="{ 'bg-blue-500': isActive, 'bg-gray-300': !isActive, 'opacity-50': disabled }"></div>
```

#### OK: Return a string from a getter method

```html
<div :class="stateClass"></div>
```

```javascript
Alpine.data('myComponent', () => ({
    isActive: false,
    disabled: false,

    get stateClass() {
        const classes = [];
        classes.push(this.isActive ? 'bg-blue-500' : 'bg-gray-300');
        if (this.disabled) {
            classes.push('opacity-50');
        }
        return classes.join(' ');
    },
}));
```

---

### Rule 6: `$store` — Access via `Alpine.data()` wrapper

#### NG: Referencing `$store` directly from templates

```html
<div x-data x-show="$store.notification.show">
    <span x-text="$store.notification.message"></span>
    <button @click="$store.notification.hide()">Close</button>
</div>
```

#### OK: Create an `Alpine.data()` wrapper

```html
<div x-data="notificationDisplay" x-show="show">
    <span x-text="message"></span>
    <button @click="hide">Close</button>
</div>
```

```javascript
Alpine.data('notificationDisplay', () => ({
    get show() {
        return Alpine.store('notification').show;
    },

    get message() {
        return Alpine.store('notification').message;
    },

    hide() {
        Alpine.store('notification').hide();
    },
}));
```

---

### Rule 7: `x-html` — Prohibited

`x-html` is completely unsupported in CSP Build. Use alternative approaches instead.

#### NG: `x-html`

```html
<div x-html="htmlContent"></div>
<div x-html="marked.parse(markdown)"></div>
```

#### OK: Alternatives

**Method A: Server-side rendering (recommended)**

Output the HTML from Blade.

```html
{!! $htmlContent !!}
```

**Method B: DOM manipulation within methods**

```javascript
Alpine.data('markdownPreview', () => ({
    init() {
        this.$watch('content', (value) => {
            this.renderMarkdown(value);
        });
    },

    renderMarkdown(value) {
        const previewEl = this.$refs.preview;
        if (previewEl && typeof marked !== 'undefined') {
            previewEl.innerHTML = marked.parse(value || '');
        }
    },
}));
```

```html
<div x-data="markdownPreview">
    <div x-ref="preview"></div>
</div>
```

---

### Rule 8: Global Variables — Do not use directly in template expressions

#### NG: Accessing global objects in templates

```html
<span x-text="document.title"></span>
<span x-text="Math.round(percentage)"></span>
<span x-text="JSON.stringify(data)"></span>
<button @click="console.log('debug')">Debug</button>
```

#### OK: Handle in JavaScript-side methods/getters

```html
<span x-text="pageTitle"></span>
<span x-text="roundedPercentage"></span>
```

```javascript
Alpine.data('myComponent', () => ({
    percentage: 0,

    get pageTitle() {
        return document.title;
    },

    get roundedPercentage() {
        return Math.round(this.percentage);
    },
}));
```

---

### Rule 9: Data Passing — `data-*` attributes or JSON script approach

Methods for passing data from the server to Alpine components.

#### Method A: `data-*` attributes (for simple values)

```html
<div x-data="userCard"
     data-user-id="{{ $user->id }}"
     data-user-name="{{ $user->name }}"
     data-is-admin="{{ $user->isAdmin() ? 'true' : 'false' }}">
</div>
```

```javascript
Alpine.data('userCard', () => ({
    userId: null,
    userName: '',
    isAdmin: false,

    init() {
        this.userId = this.$el.dataset.userId;
        this.userName = this.$el.dataset.userName;
        this.isAdmin = this.$el.dataset.isAdmin === 'true';
    },
}));
```

#### Method B: JSON script approach (for complex data)

```html
<div x-data="dataTable">
    <script type="application/json" data-initial>
        {!! json_encode([
            'columns' => $columns,
            'rows' => $rows,
            'options' => $options,
        ]) !!}
    </script>
    ...
</div>
```

```javascript
Alpine.data('dataTable', () => ({
    columns: [],
    rows: [],
    options: {},

    init() {
        const dataEl = this.$el.querySelector('script[data-initial]');
        if (dataEl) {
            const data = JSON.parse(dataEl.textContent);
            Object.assign(this, data);
        }
    },
}));
```

#### Passing translation strings

```html
<div x-data="myComponent"
     data-translations='@json([
         "confirm" => __("common.confirm"),
         "cancel" => __("common.cancel"),
     ])'>
</div>
```

```javascript
Alpine.data('myComponent', () => ({
    translations: {},

    init() {
        this.translations = JSON.parse(this.$el.dataset.translations || '{}');
    },

    /**
     * Get a translation string
     */
    t(key) {
        return this.translations[key] || key;
    },
}));
```

---

## JS File Conventions

### File Structure

JavaScript files for new Alpine components follow this structure:

```
resources/src/
├── components/js/          # Shared components
│   ├── ui-modal.js
│   ├── form-email.js
│   └── new-component.js    # ← New files go here
├── admin/js/               # Admin panel specific
├── install/js/             # Installer specific
└── common/js/
    └── app.js              # Main entry point (import here)
```

### Standard Template

```javascript
/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * [License header omitted]
 */

/**
 * ComponentName - Description of the component
 *
 * Usage in Blade:
 * <div x-data="componentName"
 *      data-initial-value="{{ $value }}">
 *     <button @click="doAction">Action</button>
 * </div>
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('componentName', () => ({
        // ===== Data Properties =====
        value: '',
        loading: false,

        // ===== Computed Properties (getters) =====
        get isEmpty() {
            return this.value === '';
        },

        get displayValue() {
            return this.value || 'Default value';
        },

        // ===== Lifecycle =====
        init() {
            // Read initial values from data-* attributes
            this.value = this.$el.dataset.initialValue || '';
        },

        // ===== Event Handlers =====
        doAction() {
            // Handle button click
        },

        // ===== Internal Methods =====
        _internalHelper() {
            // Prefix with _ to indicate internal methods (optional)
        },
    }));
});
```

### Registering in `app.js`

```javascript
// Add import in resources/src/common/js/app.js
import '../../components/js/new-component';
```

### Naming Conventions

| Target | Convention | Example |
|--------|-----------|---------|
| Alpine.data() name | camelCase | `emailInput`, `togglePanel`, `menuEditor` |
| Data properties | camelCase | `showPassword`, `isLoading` |
| Methods | camelCase, starts with verb | `toggle`, `handleClick`, `saveForm` |
| Getters | camelCase, noun/adjective | `isEmpty`, `displayText`, `activeClass` |
| Event handlers (via data-*) | camelCase, starts with verb | `openModal`, `selectTab` |
| JS file names | kebab-case | `form-email.js`, `ui-modal.js` |
| data-* attributes | kebab-case | `data-modal-target`, `data-user-id` |

---

## Migration Table from Existing Patterns

The current Dixlase codebase has two patterns coexisting. Use the `Alpine.data()` pattern for new code.

### Component Registration

| Current (window function) | Recommended for new code (Alpine.data) |
|---|---|
| `window.modal = function() { return {...} }` | `Alpine.data('modal', () => ({...}))` |
| `x-data="modal()"` | `x-data="modal"` |

### Constructors with Arguments

| Current | Recommended for new code |
|---|---|
| `window.emailInput = function(config) { return {...} }` | `Alpine.data('emailInput', () => ({...}))` |
| `x-data="emailInput({ email: '...' })"` | `x-data="emailInput" data-email="..."` |
| Access via `config.email` | Access via `this.$el.dataset.email` |

### Global Helper Functions

Global functions (`openModal()`, `showSuccess()`, etc.) are kept for backward compatibility, but should only be called from within `Alpine.data()` components.

| Current | Recommended for new code |
|---|---|
| `@click="openModal('deleteModal')"` | `@click="openModal" data-modal-target="deleteModal"` |
| `@click="showSuccess('Saved successfully')"` | Call `window.showSuccess('Saved successfully')` inside a method |

---

## Code Review Checklist

Items to check when reviewing new code or changes to existing code.

### Required (MUST)

- [ ] No inline object literals in `x-data` (`x-data="{ ... }"`)
- [ ] No `x-model` used (use `:value` + `@input` instead)
- [ ] No `x-html` used
- [ ] No method calls with arguments in event handlers like `@click`
- [ ] No assignment expressions in `@click` etc. (`open = !open`, `count++`)
- [ ] No access to global variables (`window`, `document`, `console`, etc.) in template expressions
- [ ] No direct `$store` references from templates
- [ ] Alpine components are registered with `Alpine.data()`
- [ ] Server data passing uses `data-*` attributes or JSON script approach

### Recommended (SHOULD)

- [ ] Complex expressions are defined as getters (computed properties)
- [ ] `:class` returns strings from getters instead of using object syntax
- [ ] JS files follow the standard template
- [ ] New component imports are added to `app.js`
- [ ] JSDoc comments include Usage examples

---

## References

- [Alpine.js CSP Build Official Documentation](https://alpinejs.dev/advanced/csp)
- [Dixlase CSP Complete Guide](../../operations/security/csp-guide.md)
- [Hyva Alpine CSP Guide](https://docs.hyva.io/hyva-themes/writing-code/csp/alpine-csp.html)
