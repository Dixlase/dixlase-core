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

namespace App\Services\Plugin\Scanning;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Detection patterns for theme assets (theme only)
 *
 * Detects assets.custom_css, assets.custom_js, assets.external_resources
 */
class ThemeAssetDetectionPattern extends DetectionPattern
{
    public function __construct(
        protected string $subKey = 'custom_css',
    ) {}

    public function permissionKey(): string
    {
        return "assets.{$this->subKey}";
    }

    public function applicableTo(): string
    {
        return 'theme';
    }

    /**
     * Flat-layout globs, as written by the `dls:make:theme` scaffold.
     *
     * `resources/assets/` is deliberately not globbed: it is the Vite build
     * output, gitignored, absent from the repository, the release ZIP and every
     * deployed checkout, so matching it only ever happened on a developer
     * machine right after a build -- the same commit scanned differently
     * depending on where it ran.
     */
    public function filePatterns(): array
    {
        return match ($this->subKey) {
            'custom_css' => [
                'resources/src/css/*.css',
                'resources/src/scss/*.scss',
            ],
            'custom_js' => [
                'resources/src/js/*.js',
            ],
            default => [],
        };
    }

    /**
     * The assets the theme declares in theme.json that actually exist.
     *
     * Core's own loader (load_theme_assets() in AssetHelper) resolves theme
     * assets from resources/src/, and theme.json's `assets` block lists them
     * relative to it by area -- e.g. "front/js/app.js" is
     * resources/src/front/js/app.js. Themes that follow the loader keep their
     * sources under resources/src/<area>/, which the flat globs above never
     * match, so their correct custom_css / custom_js declarations were reported
     * as unused. Checking the declared files works for either layout.
     *
     * @return array<string>
     */
    public function detectFiles(string $extensionDir): array
    {
        $extensions = match ($this->subKey) {
            'custom_css' => ['css', 'scss', 'sass', 'less'],
            'custom_js' => ['js', 'mjs', 'ts'],
            default => [],
        };

        if ($extensions === []) {
            return [];
        }

        $manifest = $extensionDir.'/theme.json';
        if (! is_file($manifest)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($manifest), true);
        $assets = is_array($data) && is_array($data['assets'] ?? null) ? $data['assets'] : [];

        $found = [];
        foreach ($assets as $entries) {
            if (! is_array($entries)) {
                continue;
            }

            foreach ($entries as $entry) {
                if (! is_string($entry) || $entry === '' || str_contains($entry, "\0")
                    || str_starts_with($entry, '/') || in_array('..', explode('/', $entry), true)) {
                    continue;
                }

                if (! in_array(strtolower(pathinfo($entry, PATHINFO_EXTENSION)), $extensions, true)) {
                    continue;
                }

                $relative = 'resources/src/'.$entry;
                if (is_file($extensionDir.'/'.$relative)) {
                    $found[] = $relative;
                }
            }
        }

        return array_values(array_unique($found));
    }

    public function regexPatterns(): array
    {
        return match ($this->subKey) {
            'external_resources' => [
                '/https?:\/\/[^\s\'"]+\.(js|css)/i',
                '/<script[^>]+src=[\'"]https?:\/\//i',
                '/<link[^>]+href=[\'"]https?:\/\//i',
                '/import\s+.*from\s+[\'"]https?:\/\//i',
            ],
            default => [],
        };
    }
}
