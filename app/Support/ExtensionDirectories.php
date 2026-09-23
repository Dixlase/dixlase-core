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

declare(strict_types=1);

namespace App\Support;

/**
 * Tells an installed extension directory apart from a leftover copy of one.
 *
 * `plugins/` and `themes/` accumulate copies over a site's lifetime:
 * operators and deploy tooling move the previous version aside instead of
 * deleting it, leaving names like `DixlaseOnePage.stale.20260705-033828` or
 * `DixlaseDeploy.bak` next to the live directory. Those copies are complete
 * — manifest, `database/migrations/`, `app/` — so every scan that walks
 * `glob('plugins/*', GLOB_ONLYDIR)` treats them as additional installed
 * extensions. Observed consequences:
 *
 *   - `dls:migration:resync` built one scope per directory, so a copy that
 *     predates a migration file made the *applied* ledger row for it look
 *     orphaned, and `--prune` would delete it.
 *   - `composer dump-autoload` reports the copies as PSR-4 violations
 *     (`Plugins\DixlaseDeploy\...` found under
 *     `plugins/DixlaseDeploy.stale.20260710-221107/`) and skips the classes.
 *
 * The rule below is deliberately narrow: an installed extension's directory
 * name is also the middle segment of its PHP namespace (`Plugins\{Name}\`,
 * `Themes\{Name}\`), so it can never contain a dot. A dot in the name is
 * therefore proof that the directory is not an installed extension, without
 * having to enumerate the naming habits of every tool that made the copy.
 */
final class ExtensionDirectories
{
    /**
     * Whether `$basename` can be the directory name of an installed
     * extension. Leading `.` and `_` are excluded as well: both mark a
     * directory as hidden or set-aside by convention, and neither is a
     * valid namespace segment either.
     */
    public static function isInstalledName(string $basename): bool
    {
        if ($basename === '' || $basename === '.' || $basename === '..') {
            return false;
        }

        if (str_starts_with($basename, '.') || str_starts_with($basename, '_')) {
            return false;
        }

        return ! str_contains($basename, '.');
    }

    /**
     * Absolute paths of the installed extension directories directly under
     * `$root`, sorted by name. Directories rejected by
     * {@see self::isInstalledName()} are collected into `$ignored` (as
     * basenames) so a caller can tell the operator what it passed over
     * instead of silently dropping it.
     *
     * @param  list<string>  $ignored
     * @return list<string>
     */
    public static function list(string $root, array &$ignored = []): array
    {
        $directories = [];

        foreach (glob(rtrim($root, '/').'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $basename = basename($dir);

            if (! self::isInstalledName($basename)) {
                $ignored[] = $basename;

                continue;
            }

            $directories[] = $dir;
        }

        sort($directories);
        sort($ignored);

        return $directories;
    }
}
