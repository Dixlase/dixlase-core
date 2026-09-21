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

namespace App\Models\Traits;

use Illuminate\Support\Str;

/**
 * Resolves an extension's on-disk directory name from its slug.
 *
 * Shared by the Plugin and Theme models. Using models must implement
 * extensionRootDirectory() and have `slug` and `directory` columns.
 */
trait ResolvesExtensionDirectory
{
    /**
     * Directory under base_path() that holds this extension type
     * (e.g. `plugins` or `themes`).
     */
    abstract protected static function extensionRootDirectory(): string;

    /**
     * Resolve the on-disk extension directory name from a slug.
     *
     * Many call sites historically reconstructed the directory by piping
     * the slug through `Str::studly(str_replace('-', '_', $slug))`. That
     * conversion silently mangles extensions whose canonical name contains
     * an uppercase acronym or an inner capital — e.g. the slug `dixlase-seo`
     * becomes `DixlaseSeo` and `dixlase-devkit` becomes `DixlaseDevkit`,
     * which on a case-sensitive filesystem do not match the actual
     * `DixlaseSEO` / `DixlaseDevKit` directories. The result is either a
     * missing directory (no extension found at all) or, on case-insensitive
     * filesystems, a wrong-case path that PHP iterators
     * (`RecursiveDirectoryIterator::__construct`) refuse to open.
     *
     * The lookup runs three lanes in order:
     *
     *   1. **DB**: look up the row by slug and use the stored
     *      `directory` column as the source of truth. The on-disk index
     *      is consulted so the returned name always matches the actual
     *      filesystem casing.
     *   2. **Studly heuristic**: for names without acronyms,
     *      `Str::studly` still produces the right answer.
     *      Used as a no-DB fast path (early boot, tests, etc.).
     *   3. **Normalized fallback**: strip dashes and lowercase both
     *      sides; matches `dixlase-seo` → `DixlaseSEO` without needing
     *      the DB.
     *
     * Returns `null` when no on-disk directory matches.
     */
    public static function resolveDirectoryFromSlug(string $slug): ?string
    {
        if ($slug === '') {
            return null;
        }

        $base = base_path(static::extensionRootDirectory());
        $actualDirs = [];
        foreach (glob("{$base}/*", GLOB_ONLYDIR) ?: [] as $dir) {
            $name = basename($dir);
            $actualDirs[strtolower($name)] = $name;
        }

        if (empty($actualDirs)) {
            return null;
        }

        try {
            $stored = static::query()->where('slug', $slug)->value('directory');
            if (is_string($stored) && $stored !== '') {
                $key = strtolower($stored);
                if (isset($actualDirs[$key])) {
                    return $actualDirs[$key];
                }
            }
        } catch (\Throwable $e) {
            // DB may be unavailable during early boot or in unit tests.
        }

        $studly = Str::studly(str_replace('-', '_', $slug));
        if (isset($actualDirs[strtolower($studly)])) {
            return $actualDirs[strtolower($studly)];
        }

        $normalized = strtolower(str_replace('-', '', $slug));
        if (isset($actualDirs[$normalized])) {
            return $actualDirs[$normalized];
        }

        return null;
    }

    /**
     * Resolve the directory name from a slug, never returning null.
     *
     * Falls back to the Studly conversion when nothing on disk matches, so
     * callers that build a path and then check existence keep their
     * "not found" behaviour.
     */
    public static function directoryNameFromSlug(string $slug): string
    {
        return static::resolveDirectoryFromSlug($slug)
            ?? Str::studly(str_replace('-', '_', $slug));
    }
}
