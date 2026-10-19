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

namespace App\Services\Custom;

use App\Helpers\PluginHelper;
use Illuminate\Support\Facades\File;

/**
 * Compares the view overrides under `custom/` with the upstream files they
 * replace (dixlase-core#482).
 *
 * An override replaces its upstream view wholesale, so a control upstream
 * adds later never reaches a site that overrides the view. The two files
 * always differ — that is what an override is — so "differs" says nothing.
 * The question that matters is whether upstream has changed since the
 * override was taken from it. record() stores the upstream hash of every
 * override in `custom/.overrides.json`; inspect() reports each override
 * against that baseline.
 *
 * Only views are compared. Plugin config and lang files under `custom/` are
 * merged over upstream rather than replacing it, a PHP override is a
 * subclass, and the remaining directories are reserved; those files are
 * listed as not compared. The mapping mirrors the view namespaces
 * AppServiceProvider and PluginLoaderTrait register:
 *
 *   custom/resources/views/{admin theme|components|layouts}/X -> resources/views/.../X
 *   custom/themes/{Theme}/resources/views/X  -> themes/{Theme}/resources/views/X
 *                                               (then the default theme's X, as the themes:: namespace falls back)
 *   custom/plugins/{Plugin}/resources/views/X -> plugins/{Plugin}/resources/views/X
 *
 * Every root comes from config, so a test points all of them at a
 * throwaway tree.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
class CustomOverrideInspector
{
    /**
     * Baseline file, relative to the custom directory.
     */
    public const MANIFEST = '.overrides.json';

    /** Upstream is unchanged since the baseline was recorded. */
    public const CURRENT = 'current';

    /** Upstream has changed since the baseline was recorded. */
    public const UPSTREAM_CHANGED = 'upstream_changed';

    /** The upstream file the override replaces no longer exists. */
    public const UPSTREAM_MISSING = 'upstream_missing';

    /** No baseline has been recorded for this override yet. */
    public const UNTRACKED = 'untracked';

    /** Not a view override, or a path no view namespace reads. */
    public const NOT_COMPARED = 'not_compared';

    /**
     * Files in the custom directory that are not overrides.
     */
    private const IGNORED = ['README.md', 'README.ja.md', self::MANIFEST];

    /**
     * Every file under the custom directory with its upstream and status,
     * sorted by path.
     *
     * @return list<array{path: string, upstream: ?string, status: string, identical: bool}>
     */
    public function inspect(): array
    {
        $baselines = $this->readManifest();
        $results = [];

        foreach ($this->overrideFiles() as $relative) {
            $upstreamRelative = $this->upstreamFor($relative);
            if ($upstreamRelative === null) {
                $results[] = ['path' => $relative, 'upstream' => null, 'status' => self::NOT_COMPARED, 'identical' => false];

                continue;
            }

            $upstream = base_path($upstreamRelative);
            if (! is_file($upstream)) {
                $results[] = ['path' => $relative, 'upstream' => $upstreamRelative, 'status' => self::UPSTREAM_MISSING, 'identical' => false];

                continue;
            }

            $upstreamHash = $this->hash($upstream);
            $baseline = $baselines[$relative]['upstream_sha256'] ?? null;

            $results[] = [
                'path' => $relative,
                'upstream' => $upstreamRelative,
                'status' => match (true) {
                    ! is_string($baseline) => self::UNTRACKED,
                    hash_equals($baseline, $upstreamHash) => self::CURRENT,
                    default => self::UPSTREAM_CHANGED,
                },
                'identical' => hash_equals($upstreamHash, $this->hash($this->customRoot().'/'.$relative)),
            ];
        }

        return $results;
    }

    /**
     * Record the current upstream of every compared override as its
     * baseline, and drop baselines for overrides that no longer exist. An
     * override whose upstream is missing keeps the baseline it had, so the
     * report goes on saying so.
     *
     * @return int The number of baselines recorded
     */
    public function record(): int
    {
        $previous = $this->readManifest();
        $files = [];

        foreach ($this->inspect() as $result) {
            if ($result['status'] === self::NOT_COMPARED) {
                continue;
            }

            if ($result['status'] === self::UPSTREAM_MISSING) {
                if (isset($previous[$result['path']])) {
                    $files[$result['path']] = $previous[$result['path']];
                }

                continue;
            }

            $files[$result['path']] = [
                'upstream' => $result['upstream'],
                'upstream_sha256' => $this->hash(base_path((string) $result['upstream'])),
                'recorded_at' => now()->toIso8601String(),
            ];
        }

        ksort($files);
        File::ensureDirectoryExists($this->customRoot());
        File::put(
            $this->customRoot().'/'.self::MANIFEST,
            json_encode(['version' => 1, 'files' => $files], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        );

        return count(array_filter($files, fn (array $entry): bool => isset($entry['upstream_sha256'])));
    }

    /**
     * Absolute path of the custom directory.
     */
    public function customRoot(): string
    {
        return base_path(trim((string) config('custom.custom_files_dir', 'custom'), '/'));
    }

    /**
     * The upstream view an override replaces, relative to the base path, or
     * null when the path is not one a view namespace reads from.
     */
    public function upstreamFor(string $relative): ?string
    {
        $segments = explode('/', $relative);
        $themeRoot = $this->root('themes.theme_directory', 'themes');

        // custom/resources/views/{admin theme|components|layouts}/...
        if (count($segments) > 3 && $segments[0] === 'resources' && $segments[1] === 'views') {
            $namespaced = [(string) config('themes.admin_theme', 'admin'), 'components', 'layouts'];

            return in_array($segments[2], $namespaced, true)
                ? $this->relativeToBase(resource_path('views/'.implode('/', array_slice($segments, 2))))
                : null;
        }

        // custom/{theme directory}/{Theme}/resources/views/... The theme
        // directory is the same config value on both sides, as in the
        // themes:: namespace, and may itself contain slashes.
        $inTheme = str_starts_with($relative, $themeRoot.'/')
            ? explode('/', substr($relative, strlen($themeRoot) + 1))
            : [];
        if (count($inTheme) > 3 && $inTheme[1] === 'resources' && $inTheme[2] === 'views') {
            $view = implode('/', array_slice($inTheme, 3));
            $own = "{$themeRoot}/{$inTheme[0]}/resources/views/{$view}";
            $fallback = $themeRoot.'/'.config('themes.default_theme', 'DixlaseOnePage')."/resources/views/{$view}";

            return ! is_file(base_path($own)) && is_file(base_path($fallback)) ? $fallback : $own;
        }

        // custom/plugins/{Plugin}/resources/views/...
        if (count($segments) > 4 && $segments[0] === 'plugins' && $segments[2] === 'resources' && $segments[3] === 'views') {
            return $this->relativeToBase(PluginHelper::getPluginPath($segments[1]).'/'.implode('/', array_slice($segments, 2)));
        }

        return null;
    }

    /**
     * Files under the custom directory, relative to it, sorted.
     *
     * @return list<string>
     */
    private function overrideFiles(): array
    {
        $root = $this->customRoot();
        if (! is_dir($root)) {
            return [];
        }

        $files = [];
        foreach (File::allFiles($root, hidden: true) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            if (in_array($relative, self::IGNORED, true) || str_starts_with(basename($relative), '.')) {
                continue;
            }
            $files[] = $relative;
        }

        sort($files);

        return $files;
    }

    /**
     * Baselines keyed by override path. A missing or unreadable manifest is
     * no baselines.
     *
     * @return array<string, array<string, mixed>>
     */
    private function readManifest(): array
    {
        $path = $this->customRoot().'/'.self::MANIFEST;
        if (! is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) && is_array($data['files'] ?? null) ? $data['files'] : [];
    }

    private function root(string $key, string $default): string
    {
        $root = trim((string) config($key, $default), '/');

        return $root !== '' ? $root : $default;
    }

    private function relativeToBase(string $absolute): string
    {
        return ltrim(substr($absolute, strlen(base_path())), '/');
    }

    private function hash(string $path): string
    {
        return (string) hash_file('sha256', $path);
    }
}
