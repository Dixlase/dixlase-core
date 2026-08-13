<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Available for plugins/themes to opt into automatic
 * settings-defaults syncing on install and update.
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

namespace App\Contracts\Extension;

/**
 * Opt-in contract for extensions that own a `name` / `value` settings
 * table and want core to keep the DB rows in sync with the extension's
 * declared defaults across install and update.
 *
 * Why this exists.
 * ----------------
 * When an extension release introduces a NEW settings key (say,
 * `hero_gradient_color_dark` in dixlase-onepage v0.1.2), the file swap
 * that installs the new version does not populate the corresponding
 * DB row on an already-installed site. Blade code that reads the new
 * key with `->prop ?: fallback` then trips a PHP 8 "Undefined
 * property" warning that production error handling escalates to a
 * 500 — which is exactly what happened on the sandbox during the
 * v0.1.1 → v0.1.2 dixlase-onepage upgrade.
 *
 * Historically each extension worked around it in one of three ways:
 *   1. A view-composer that merged defaults into `$themeSettings` at
 *      request time (does not touch the DB — just papers over the
 *      missing rows).
 *   2. A settings-model auto-seed that fired only on empty tables
 *      (works for fresh install; no-op on the update path where the
 *      table has old rows already).
 *   3. An admin-form "Save" that inserts the missing rows as a
 *      side effect (works but requires the operator to visit and
 *      submit the form after every update).
 *
 * None of those scale across the ecosystem. This contract makes it
 * a core concern: an extension declares what its defaults are, and
 * the install / update commands `insertOrIgnore` any rows the DB
 * does not yet carry — leaving operator-edited values untouched.
 *
 * Registration.
 * -------------
 * Implement this interface on the extension's service provider (the
 * class listed under `providers` in plugin.json / theme.json).
 * The install / update commands walk the manifest's provider list,
 * instantiate each provider once, and invoke the two methods below
 * on any provider that satisfies the interface. Providers that do
 * not implement it are ignored — the contract is fully opt-in.
 *
 * Idempotency contract.
 * ---------------------
 * The sync uses `insertOrIgnore` keyed by the `name` column, so:
 *   - existing rows are never overwritten
 *   - repeated invocations are safe
 *   - a value that the operator explicitly set to null via the
 *     admin form is preserved (the row already exists — the
 *     `insertOrIgnore` no-ops)
 *
 * Do NOT put "computed at request time" values in the defaults map
 * (`app()->getLocale()`, `now()`, etc.) — they are inserted at
 * install / update time and become the persisted starting state
 * for every operator on that release.
 */
interface ProvidesSettingsDefaultsInterface
{
    /**
     * The unquoted name of the extension's settings table.
     *
     * The table is expected to follow the Dixlase convention:
     *   - `name` column: unique string primary lookup key
     *   - `value` column: nullable text; may be JSON-encoded, hex,
     *     a bare string, "0"/"1" — the extension controls the
     *     representation, core does not interpret it
     *   - `created_at` / `updated_at`: standard Laravel timestamps
     *
     * Extensions that do not follow this shape (a legacy multi-column
     * settings table, for example) should not implement this contract
     * and should keep managing their defaults inside their own
     * migrations or seeders instead.
     *
     * Example: `'thm_dixlase_onepage_settings'`.
     */
    public function getSettingsTable(): string;

    /**
     * Map of setting `name` → default `value`, one entry per key
     * the extension expects to find in its settings table.
     *
     * Called once per install and once per update. Values that are
     * already present in the DB (matched by `name`) are left alone;
     * only keys missing from the DB are inserted.
     *
     * Value types the DB will accept without conversion: string,
     * int, float, bool (cast to "1"/"0" by the DB layer), or null.
     * Complex values must be pre-serialised (e.g. JSON-encoded).
     *
     * @return array<string, string|int|float|bool|null>
     */
    public function getSettingsDefaults(): array;
}
