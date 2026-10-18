/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

/**
 * Asks before an admin page with unsaved changes is left.
 *
 * <x-admin.save-button> marks the form it saves with
 * data-unsaved-changes-form="<form id>". Each marked form's contents are
 * recorded once the page has settled; on leaving, a form whose contents
 * differ from the record makes the browser ask (beforeunload; the wording
 * is the browser's own and cannot be set).
 *
 * - Submitting any form on the page releases the guard: the user chose to
 *   leave. A form posted to another tab (the new-tab preview) does not.
 * - data-unsaved-changes-dirty="1" (the page came back with validation
 *   errors) starts the form out dirty: its input was never saved.
 * - Editors fill in templates and normalise values while they start, so
 *   the record is taken after that, and taken again once the page has
 *   loaded unless the user has already touched the form.
 */

const guards = [];
let released = false;

/**
 * The form's contents as one comparable string. The CSRF token is left
 * out; a chosen file counts by its name and size.
 */
function serialize(form) {
    const parts = [];
    for (const [name, value] of new FormData(form)) {
        if (name === '_token') continue;
        const text = value instanceof File ? `file:${value.name}:${value.size}` : value;
        parts.push(`${name}=${text}`);
    }
    return parts.join('\u0000');
}

function isDirty() {
    if (released) return false;
    return guards.some((guard) => guard.dirtyFromStart || serialize(guard.form) !== guard.initial);
}

function release(form) {
    if (form instanceof HTMLFormElement && form.target === '_blank') return;
    released = true;
}

function record() {
    for (const guard of guards) {
        if (!guard.touched) {
            guard.initial = serialize(guard.form);
        }
    }
}

function collect() {
    document.querySelectorAll('[data-unsaved-changes-form]').forEach((marker) => {
        const form = document.getElementById(marker.dataset.unsavedChangesForm);
        if (!(form instanceof HTMLFormElement)) return;

        const existing = guards.find((guard) => guard.form === form);
        const dirtyFromStart = marker.dataset.unsavedChangesDirty === '1';
        if (existing) {
            existing.dirtyFromStart = existing.dirtyFromStart || dirtyFromStart;
            return;
        }

        const guard = { form, initial: '', dirtyFromStart, touched: false };
        // Only a person's own input counts as touching the form; editors
        // dispatch synthetic input events while they start.
        const touch = (event) => {
            if (event.isTrusted) guard.touched = true;
        };
        form.addEventListener('input', touch, true);
        form.addEventListener('change', touch, true);
        guards.push(guard);
    });
}

function start() {
    collect();
    if (guards.length === 0) return;

    record();
    if (document.readyState === 'complete') {
        setTimeout(record, 0);
    } else {
        window.addEventListener('load', () => setTimeout(record, 0), { once: true });
    }

    // Bubbling phase, so a handler on the form that cancels the submit
    // (validation, a confirmation) has run first.
    window.addEventListener('submit', (event) => {
        if (!event.defaultPrevented) release(event.target);
    });

    // form.submit() fires no submit event; several screens call it.
    const nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function submit() {
        release(this);
        return nativeSubmit.call(this);
    };

    window.addEventListener('beforeunload', (event) => {
        if (!isDirty()) return;
        event.preventDefault();
        // Older browsers need returnValue set to show the prompt.
        event.returnValue = '';
    });
}

// Alpine has started by DOMContentLoaded (common/js/app.js); wait one
// frame more so x-model values and editor templates are in the fields.
function startWhenSettled() {
    requestAnimationFrame(() => setTimeout(start, 0));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startWhenSettled, { once: true });
} else {
    startWhenSettled();
}
