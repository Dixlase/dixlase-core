<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @internal Core only. Do not reference from plugins/themes
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

namespace App\Services\Core;

use App\Models\Plugin;
use App\Models\Theme;
use App\Support\RelativeSymlink;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Recreates the public asset symlinks that a source swap leaves behind.
 *
 * `public/assets/themes/<Theme>` and `public/assets/plugins/<Plugin>` are
 * symlinks into the extension trees, and `public/storage` is the usual
 * storage link. None of them belongs to the core source payload, so every
 * operation that replaces `public/` wholesale — an update, a rollback, a
 * backup restore — can leave the slot empty (the snapshot or archive never
 * carried the link) or occupied by a real directory (a release ZIP built
 * without `zip -y` stores the link dereferenced). Both break the same way:
 * the theme's `@vite` assets 404 and the front page renders unstyled, with
 * `appearanceTheme is not defined` in the browser console.
 *
 * Extracted from CoreRestoreService so the update and rollback paths run the
 * same repair, and shared with `dls:theme:symlink` / `dls:plugin:symlink`.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
class PublicAssetRelinker
{
    /**
     * Relink public/storage plus every installed theme and plugin.
     *
     * A single failed link never aborts the run: callers invoke this as a
     * repair step in the middle of an update or a rollback.
     *
     * @return array{storage: bool, themes: int, plugins: int} links in place afterwards
     */
    public function relink(): array
    {
        $result = [
            'storage' => $this->ensureStorageLink(),
            'themes' => 0,
            'plugins' => 0,
        ];

        foreach ($this->themeDirectories() as $directory) {
            if (self::ensureLink(base_path("themes/{$directory}/resources/assets"), public_path("assets/themes/{$directory}"))) {
                $result['themes']++;
            }
        }

        foreach ($this->pluginDirectories() as $directory) {
            if (self::ensureLink(base_path("plugins/{$directory}/resources/assets"), public_path("assets/plugins/{$directory}"))) {
                $result['plugins']++;
            }
        }

        return $result;
    }

    /**
     * Make `$link` a relative symlink to `$target`, replacing whatever
     * occupies the slot.
     *
     * Replacing is the point. The symlink commands used to skip when
     * `File::exists($link)` was true — which a **real directory** also
     * satisfies — so an install whose link had already been overwritten by a
     * dereferenced release payload was never repaired. A dangling link is
     * replaced too, since `symlink()` would otherwise fail with EEXIST.
     *
     * @return bool whether a link to `$target` is in place afterwards
     */
    public static function ensureLink(string $target, string $link): bool
    {
        if (! is_dir($target)) {
            // Nothing to serve (extension without a resources/assets tree).
            return false;
        }

        if (self::pointsAt($link, $target)) {
            return true;
        }

        if (self::occupied($link)) {
            if (is_dir($link) && ! is_link($link)) {
                File::deleteDirectory($link);
            } else {
                @unlink($link);
            }
        }

        File::ensureDirectoryExists(dirname($link));

        return RelativeSymlink::create($target, $link);
    }

    /**
     * Whether `$link` is a symlink that already resolves to `$target`.
     *
     * The recorded target is relative (see RelativeSymlink), so it is
     * resolved against the link's own directory before comparing.
     */
    protected static function pointsAt(string $link, string $target): bool
    {
        if (! is_link($link)) {
            return false;
        }

        $recorded = @readlink($link);
        if ($recorded === false) {
            return false;
        }

        $resolved = realpath(str_starts_with($recorded, '/') ? $recorded : dirname($link).'/'.$recorded);

        return $resolved !== false && $resolved === realpath($target);
    }

    /**
     * Create public/storage when it is missing.
     *
     * `--relative` for cross-container symlink resolution: see the matching
     * comment in InstallConfirmController.
     */
    protected function ensureStorageLink(): bool
    {
        $link = public_path('storage');

        if (self::occupied($link)) {
            return true;
        }

        Artisan::call('storage:link', ['--relative' => true]);
        clearstatcache(true, $link);

        return self::occupied($link);
    }

    /**
     * Whether anything sits at `$path` — a link (even a dangling one), a
     * file or a directory.
     */
    protected static function occupied(string $path): bool
    {
        return is_link($path) || file_exists($path);
    }

    /**
     * @return list<string>
     */
    protected function themeDirectories(): array
    {
        try {
            return Theme::query()->whereNotNull('directory')->pluck('directory')->all();
        } catch (Throwable) {
            // A source swap can run while the schema is mid-flight. An extra
            // link is a better outcome than an unstyled site.
            return self::directoriesOnDisk('themes');
        }
    }

    /**
     * @return list<string>
     */
    protected function pluginDirectories(): array
    {
        try {
            return Plugin::query()->whereNotNull('directory')->pluck('directory')->all();
        } catch (Throwable) {
            return self::directoriesOnDisk('plugins');
        }
    }

    /**
     * @return list<string>
     */
    protected static function directoriesOnDisk(string $root): array
    {
        $path = base_path($root);

        if (! File::isDirectory($path)) {
            return [];
        }

        return array_values(array_map('basename', File::directories($path)));
    }
}
