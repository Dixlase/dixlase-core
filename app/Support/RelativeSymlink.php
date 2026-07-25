<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Available for plugins/themes as \App\Support\RelativeSymlink
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

namespace App\Support;

/**
 * @api Stable API available for plugins/themes.
 *
 * Creates POSIX symlinks whose recorded target is a relative path,
 * not an absolute one anchored at the container that ran the code.
 *
 * Motivation: on split-container deployments (typical prod: php-fpm
 * container mounts the app at `/var/www/html`, nginx mounts the same
 * bind at `/var/www/apps/brand`), an absolute symlink baked with
 * `base_path()` from the PHP container resolves to a path that
 * simply does not exist inside the nginx container. Every request
 * that walks that symlink from nginx (most visibly the media
 * manager under /storage/ and the extension thumbnails under
 * /assets/plugins/) then 404s even though the file is present on
 * shared storage. A relative
 * symlink resolves correctly from either container because the
 * resolution is anchored to the symlink file's own location, which
 * is the same physical path in every mount.
 *
 * Laravel exposes {@see \Illuminate\Filesystem\Filesystem::relativeLink()}
 * but delegates to `symfony/filesystem`, which Dixlase Core does not
 * declare as a dependency. Rather than add a runtime dep for a
 * ten-line utility, we inline the relative-path calculation here and
 * call `symlink()` directly.
 */
class RelativeSymlink
{
    /**
     * Create a symlink at `$link` whose recorded target is `$target`
     * expressed as a POSIX relative path from `$link`'s parent
     * directory. Both arguments must be absolute paths.
     *
     * The function does not check whether `$target` exists (matching
     * Laravel's semantics for the corresponding built-in); callers
     * that want that guarantee should do so beforehand.
     */
    public static function create(string $target, string $link): bool
    {
        $relative = self::relativePath($target, dirname($link));

        return symlink($relative, $link);
    }

    /**
     * Compute a POSIX relative path from `$from` (a directory) to
     * `$to` (a target file or directory).
     *
     * The algorithm is the standard relative-path decomposition
     * shared with `symfony/filesystem`'s `makePathRelative`:
     *
     *   1. Strip a common leading segment sequence.
     *   2. Prepend one `../` for each remaining segment in `$from`.
     *   3. Append the remaining segments of `$to`.
     *
     * Trailing slashes are trimmed so the resulting path never ends
     * with one (matching what `symlink(2)` expects as the target).
     */
    public static function relativePath(string $to, string $from): string
    {
        $to = self::normalize($to);
        $from = self::normalize($from);

        if ($to === $from) {
            return '.';
        }

        $toSegments = explode('/', ltrim($to, '/'));
        $fromSegments = explode('/', ltrim($from, '/'));

        $common = 0;
        $max = min(count($toSegments), count($fromSegments));
        while ($common < $max && $toSegments[$common] === $fromSegments[$common]) {
            $common++;
        }

        $up = array_fill(0, count($fromSegments) - $common, '..');
        $down = array_slice($toSegments, $common);

        $result = implode('/', array_merge($up, $down));

        return $result === '' ? '.' : $result;
    }

    /**
     * Collapse `.` / `..` segments so the algorithm above operates on
     * canonical inputs. Not a full realpath — we intentionally do
     * *not* resolve symlinks inside the input, so callers can compute
     * relative paths against directories that do not yet exist on
     * disk.
     */
    private static function normalize(string $path): string
    {
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);

                continue;
            }
            $segments[] = $segment;
        }

        return '/'.implode('/', $segments);
    }
}
