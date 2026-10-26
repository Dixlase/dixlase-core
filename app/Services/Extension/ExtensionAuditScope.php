<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

namespace App\Services\Extension;

/**
 * Marks the stretch of a command that its own audit row already covers
 * (dixlase-core#749).
 *
 * An install, update or rollback records one row for the whole operation,
 * and runs seeders and migration rollbacks through the step commands on the
 * way. Those step commands record a row of their own when run by hand, so
 * the parent wraps its call in run() and they stay quiet inside it.
 *
 * A static depth rather than a command option: Artisan::call() and
 * Command::call() run in the same process, and an option would let a
 * command-line run switch the record off.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
final class ExtensionAuditScope
{
    private static int $depth = 0;

    /**
     * Run $callback with step recording suppressed.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function run(callable $callback): mixed
    {
        self::$depth++;

        try {
            return $callback();
        } finally {
            self::$depth--;
        }
    }

    /**
     * Whether a parent operation that records itself is running.
     */
    public static function active(): bool
    {
        return self::$depth > 0;
    }
}
