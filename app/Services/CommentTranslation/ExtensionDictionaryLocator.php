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

namespace App\Services\CommentTranslation;

/**
 * @api
 *
 * Locate per-locale comment-translation dictionary directories across
 * the project tree (Core + plugins + themes).
 *
 * Each "extension" (Core, a plugin, or a theme) may carry its own
 * per-locale dictionary alongside its source code:
 *
 *     resources/comment-translations/{locale}/...                   (Core)
 *     plugins/{Name}/resources/comment-translations/{locale}/...   (plugin)
 *     themes/{Name}/resources/comment-translations/{locale}/...    (theme)
 *
 * The build pipeline (`dls:comment:build`) iterates the dictionary
 * directories returned by `locate()` and applies each one to the
 * matching source root.
 */
class ExtensionDictionaryLocator
{
    /**
     * @return array<int, array{root: string, dictRoot: string, kind: string, name: string}>
     */
    public function locate(string $locale, bool $includePlugins = false, bool $includeThemes = false): array
    {
        $entries = [];

        // 1. Core itself.
        $coreDictRoot = base_path('resources/comment-translations/'.$locale);
        if (is_dir($coreDictRoot)) {
            $entries[] = [
                'root' => base_path(),
                'dictRoot' => $coreDictRoot,
                'kind' => 'core',
                'name' => 'core',
            ];
        }

        // 2. Plugins (when requested).
        if ($includePlugins) {
            foreach ($this->subdirectories(base_path('plugins')) as $pluginDir) {
                $dictRoot = $pluginDir.'/resources/comment-translations/'.$locale;
                if (is_dir($dictRoot)) {
                    $entries[] = [
                        'root' => $pluginDir,
                        'dictRoot' => $dictRoot,
                        'kind' => 'plugin',
                        'name' => basename($pluginDir),
                    ];
                }
            }
        }

        // 3. Themes (when requested).
        if ($includeThemes) {
            foreach ($this->subdirectories(base_path('themes')) as $themeDir) {
                $dictRoot = $themeDir.'/resources/comment-translations/'.$locale;
                if (is_dir($dictRoot)) {
                    $entries[] = [
                        'root' => $themeDir,
                        'dictRoot' => $dictRoot,
                        'kind' => 'theme',
                        'name' => basename($themeDir),
                    ];
                }
            }
        }

        return $entries;
    }

    /**
     * Enumerate available locales by scanning the Core dictionary root.
     * Sub-directory names that begin with `_` (metadata) are skipped.
     *
     * @return array<int, string>
     */
    public function availableLocales(): array
    {
        $root = base_path('resources/comment-translations');
        if (! is_dir($root)) {
            return [];
        }

        $locales = [];
        foreach ($this->subdirectories($root) as $dir) {
            $name = basename($dir);
            if ($name === '' || $name[0] === '_') {
                continue;
            }
            $locales[] = $name;
        }

        sort($locales);

        return $locales;
    }

    /**
     * @return array<int, string>
     */
    protected function subdirectories(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $entries = @scandir($dir);
        if ($entries === false) {
            return [];
        }

        $result = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.'/'.$entry;
            if (is_dir($path)) {
                $result[] = $path;
            }
        }
        sort($result);

        return $result;
    }
}
