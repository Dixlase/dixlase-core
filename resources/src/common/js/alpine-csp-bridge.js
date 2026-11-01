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
 * Bridge from the `window.*` helpers to Alpine's own scope.
 *
 * The @alpinejs/csp build resolves a bare name in a directive only in
 * Alpine's scope, never on `window`, so `@click="openModal('x')"` and
 * `x-data="modal()"` stop working there. Each helper is registered as a
 * wrapper that looks it up on `window` when called, so the order in which
 * the page modules load does not matter, and a page that does not ship a
 * helper gets a console warning instead of a broken expression.
 *
 * On the standard build this changes nothing. Registered data providers
 * and magics take precedence over `window`, and they call the same helper.
 *
 * Templates (including plugins and themes) call the helpers as magics:
 *
 *     <button type="button" @click="$openModal('delete-modal')">Delete</button>
 *
 * The factories below keep their names, so `x-data="modal()"` and
 * `x-data="colorPicker('#fff')"` need no change.
 *
 * @api
 */

/**
 * Helpers exposed as magics: `$openModal('id')`, `$closeModal('id')`, ...
 */
export const BRIDGED_MAGICS = Object.freeze([
    'openModal',
    'closeModal',
    'openMediaSelector',
    'closeMediaSelector',
]);

/**
 * `window.*` factories used as `x-data="name(...)"`, registered under the
 * same name through `Alpine.data()`.
 */
export const BRIDGED_FACTORIES = Object.freeze([
    'adminBar',
    'adminLayout',
    'appearanceMode',
    'colorPicker',
    'emailInput',
    'installTheme',
    'modal',
    'paginationControls',
    'passkeyDeviceNameModal',
    'passkeyResultModal',
    'passwordTools',
    'recoveryCodesModal',
    'tooltip',
    'twoFaProfileSettings',
]);

function callWindowHelper(name, args) {
    const helper = window[name];

    if (typeof helper !== 'function') {
        console.warn(`[alpine-csp-bridge] window.${name} is not defined on this page.`);
        return undefined;
    }

    return helper(...args);
}

/**
 * Register the bridged magics and factories on an Alpine instance.
 * Call it before `Alpine.start()`.
 */
export function registerCspBridge(Alpine) {
    BRIDGED_MAGICS.forEach((name) => {
        Alpine.magic(name, () => (...args) => callWindowHelper(name, args));
    });

    BRIDGED_FACTORIES.forEach((name) => {
        Alpine.data(name, (...args) => callWindowHelper(name, args));
    });
}

export default registerCspBridge;
