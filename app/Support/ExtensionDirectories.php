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

use InvalidArgumentException;

/**
 * Tells an installed extension directory apart from a leftover copy of one.
 *
 * `plugins/` and `themes/` accumulate copies over a site's lifetime:
 * operators and deploy tooling move the previous version aside instead of
 * deleting it, leaving names like `DixlaseOnePage.stale.20260705-033828` or
 * `AcmeExample.bak` next to the live directory. Those copies are complete
 * — manifest, `database/migrations/`, `app/` — so every scan that walks
 * `glob('plugins/*', GLOB_ONLYDIR)` treats them as additional installed
 * extensions. Observed consequences:
 *
 *   - `dls:migration:resync` built one scope per directory, so a copy that
 *     predates a migration file made the *applied* ledger row for it look
 *     orphaned, and `--prune` would delete it.
 *   - `composer dump-autoload` reports the copies as PSR-4 violations
 *     (`Plugins\AcmeExample\...` found under
 *     `plugins/AcmeExample.stale.20260710-221107/`) and skips the classes.
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
     * The directory that holds the installed plugins, or one plugin in it.
     *
     * `config('plugins.plugins_directory')` is a path relative to the
     * application root in production, where it is `plugins`. An absolute
     * value is used as given, so a test can point every reader of the root
     * at a throwaway tree instead of the checkout's own plugins/ (#653).
     * Every caller in core comes through here or through
     * {@see \App\Helpers\PluginHelper::pluginsPath()}, which delegates to
     * it, so one config override moves them all.
     */
    public static function pluginsRoot(?string $directory = null): string
    {
        return self::root(config('plugins.plugins_directory'), 'plugins', $directory);
    }

    /**
     * The directory that holds the installed themes, or one theme in it.
     *
     * The same rule as {@see self::pluginsRoot()}, for
     * `config('themes.theme_directory')`.
     */
    public static function themesRoot(?string $directory = null): string
    {
        return self::root(config('themes.theme_directory'), 'themes', $directory);
    }

    private static function root(mixed $configured, string $default, ?string $directory): string
    {
        $configured = is_string($configured) ? trim($configured) : '';

        $root = match (true) {
            $configured === '' => base_path($default),
            str_starts_with($configured, DIRECTORY_SEPARATOR) => rtrim($configured, DIRECTORY_SEPARATOR),
            default => base_path(trim($configured, '/')),
        };

        if ($directory === null || $directory === '') {
            return $root;
        }

        self::assertSegment($directory);

        return $root.DIRECTORY_SEPARATOR.$directory;
    }

    /**
     * Refuse anything that is not a single extension directory name.
     *
     * The segment is appended to a trusted root and the result is read,
     * and in one case `require`d -- a plugin's `config/admin/roles.php`.
     * Validating here covers every caller at once, rather than asking
     * each to remember; `isInstalledName()` was already the project's
     * test for a usable name and simply was not consulted on this path.
     *
     * A separator is refused as well. `isInstalledName()` allows one,
     * because it is asked about a basename that cannot contain one.
     *
     * @throws InvalidArgumentException
     */
    private static function assertSegment(string $directory): void
    {
        $invalid = str_contains($directory, '/')
            || str_contains($directory, '\\')
            || ! self::isInstalledName($directory);

        if ($invalid) {
            throw new InvalidArgumentException(
                'Not an extension directory name: '.$directory
            );
        }
    }

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
