<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Contracts\Cookie;

use App\Enums\ConsentCategory;

/**
 * Read the current visitor's cookie consent state.
 *
 * Implemented by a plugin (e.g. DixlaseCookie) that owns the consent
 * banner UI and the per-visitor storage. Consumed by any plugin that
 * needs to gate behavior on visitor consent — most commonly the SEO
 * plugin to defer the Google Analytics tag until
 * `has('analytics')` returns true.
 *
 * Soft-dependency pattern: consumers should check
 * `app()->bound(ConsentStateProviderInterface::class)` and degrade
 * gracefully (treat "no consent system installed" as "behave as it
 * always did") when no implementation is bound. This keeps the
 * consent plugin optional rather than a hard prerequisite of every
 * plugin that wants to integrate with it.
 *
 * Category identifiers: the four standard categories are documented
 * on {@see ConsentCategory}. The contract accepts arbitrary strings
 * so plugin-defined categories (e.g. a future newsletter plugin
 * exposing `marketing-email`) can ride the same interface without a
 * Core change.
 *
 * Identity model: per-request. Implementations are expected to look
 * up the active visitor's stored state on each call (cookie + session
 * being the most common storage). The contract does not pass a user
 * identifier — callers do not need to know how the provider keys its
 * records.
 */
interface ConsentStateProviderInterface
{
    /**
     * Whether the current visitor has consented to the given category.
     *
     * The category string is typically one of the {@see ConsentCategory}
     * cases (e.g. 'analytics', 'marketing'), but implementations may
     * accept and recognise plugin-defined categories too. Implementations
     * MUST return false for unknown categories. Implementations MUST NOT
     * default unknown non-necessary categories to true.
     *
     * The `necessary` category MUST return true whenever the banner is
     * showing at all (it is implicitly granted because the site cannot
     * function without it).
     */
    public function has(string $category): bool;

    /**
     * Snapshot of all category states the visitor has been asked about.
     *
     * Keys are category identifiers, values are booleans. Categories
     * the visitor has not been asked about are absent from the array
     * (NOT present as false). `necessary` should always be present and
     * true in any non-empty snapshot.
     *
     * Returns an empty array when no consent has been recorded yet (the
     * visitor has neither accepted nor rejected). Designed to be
     * serialisable as JSON for cross-plugin and Wasm boundary use.
     *
     * @return array<string, bool>
     */
    public function snapshot(): array;

    /**
     * Operator-controlled consent version at read time.
     *
     * Implementations bump this value to invalidate every persisted
     * consent record at once (typically after a privacy policy
     * revision). Consumers may store this alongside their own records
     * to detect that the stored decision predates the current policy.
     * The value MUST be >= 1.
     */
    public function version(): int;
}
