<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a visitor's cookie consent state changes.
 *
 * Carries before/after snapshots so listeners can detect
 * category-level transitions (e.g. analytics flipped on → enable
 * previously-deferred GA script for the rest of the request). The
 * payload is intentionally a pair of plain arrays so it can be
 * serialised across the Wasm / Capability Broker boundary; any
 * future change to the payload shape MUST bump
 * {@see self::SCHEMA_VERSION}.
 *
 * When to dispatch:
 * - First-time acceptance: `$previous` is the empty array, `$current`
 *   is the accepted snapshot.
 * - Explicit re-consent via the visitor-facing "Cookie settings" UI:
 *   both `$previous` and `$current` are populated.
 *
 * When NOT to dispatch:
 * - Operator-triggered global version bump: the visitor's browser
 *   cookie becomes stale but no per-visitor state has been re-read
 *   yet. The change event should fire when the visitor next
 *   re-accepts, not at the operator action.
 *
 * Example:
 * ```php
 * Event::listen(ConsentChanged::class, function (ConsentChanged $event) {
 *     $wasOff = !($event->previous['analytics'] ?? false);
 *     $isOn   = ($event->current['analytics'] ?? false);
 *     if ($wasOff && $isOn) {
 *         // emit deferred analytics tag for the rest of this request
 *     }
 * });
 * ```
 */
class ConsentChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Schema version of the payload carried by this event.
     *
     * Subscribers may branch on the shape of `$previous` / `$current`
     * by reading this constant. Bump the value if the payload shape
     * changes incompatibly (e.g. arrays gain mandatory keys).
     */
    public const SCHEMA_VERSION = 1;

    /**
     * @param  array<string, bool>  $previous  Category → consent map before the change; empty array for first-time acceptance
     * @param  array<string, bool>  $current   Category → consent map after the change
     * @param  int  $version  Consent version at the time of this change (matches the provider's version() value)
     */
    public function __construct(
        public readonly array $previous,
        public readonly array $current,
        public readonly int $version,
    ) {}
}
