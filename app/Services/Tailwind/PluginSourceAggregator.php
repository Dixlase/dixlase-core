<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Services\Tailwind;

use App\Models\Plugin;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Generate resources/css/dixlase-tailwind-plugin-sources.css from each
 * enabled plugin's `declares.tailwind_content` paths.
 *
 * Themes import the generated file so Tailwind v4 can scan content
 * directories owned by plugins (typically user-editable HTML or
 * Markdown that lives under storage/) without the theme itself having
 * to know which plugins are active.
 *
 * The file is overwritten in place on every regenerate so it always
 * matches the current set of enabled plugins. Disabled / uninstalled
 * plugins drop out automatically.
 */
class PluginSourceAggregator
{
    /**
     * Output path of the aggregated CSS, relative to the Laravel
     * project root.
     */
    public const OUTPUT_PATH = 'resources/css/dixlase-tailwind-plugin-sources.css';

    /**
     * Regenerate the aggregator CSS file from the current set of
     * enabled plugins. Always writes a valid file (possibly empty
     * with just the header) so the theme Vite build can succeed
     * even when no plugin declares Tailwind sources.
     *
     * @return array{path: string, plugin_count: int, source_count: int}
     */
    public function regenerate(): array
    {
        $contributions = $this->collectContributions();

        $sourceCount = 0;
        foreach ($contributions as $entry) {
            $sourceCount += count($entry['paths']);
        }

        $css = $this->buildCss($contributions);

        $absolutePath = base_path(self::OUTPUT_PATH);
        File::ensureDirectoryExists(dirname($absolutePath));

        // Atomic write — write to a sibling temp file and rename so the
        // Vite watcher never sees a half-written file.
        $tempPath = $absolutePath.'.tmp';
        File::put($tempPath, $css);
        File::move($tempPath, $absolutePath);

        return [
            'path' => $absolutePath,
            'plugin_count' => count($contributions),
            'source_count' => $sourceCount,
        ];
    }

    /**
     * Walk every enabled plugin, read its plugin.json, and pull the
     * `declares.tailwind_content` array out of it.
     *
     * @return array<int, array{slug: string, directory: string, paths: array<int, string>}>
     */
    private function collectContributions(): array
    {
        // Plugin::scopeEnabled() filters by enabled_at, matching the
        // rest of the codebase. Newly installed-but-not-enabled plugins
        // intentionally do not contribute scan paths — Tailwind would
        // otherwise pick up classes from code paths the user has not
        // turned on.
        $plugins = Plugin::query()
            ->enabled()
            ->orderBy('slug')
            ->get(['id', 'slug', 'directory']);

        $contributions = [];

        foreach ($plugins as $plugin) {
            $pluginJsonPath = base_path("plugins/{$plugin->directory}/plugin.json");
            if (! File::exists($pluginJsonPath)) {
                continue;
            }

            try {
                $data = json_decode(File::get($pluginJsonPath), true);
            } catch (\Throwable $e) {
                Log::warning('PluginSourceAggregator: failed to read plugin.json', [
                    'plugin' => $plugin->slug,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if (! is_array($data)) {
                continue;
            }

            $declared = $data['declares']['tailwind_content'] ?? null;
            if (! is_array($declared) || empty($declared)) {
                continue;
            }

            $paths = [];
            foreach ($declared as $path) {
                if (! is_string($path) || $path === '') {
                    continue;
                }
                // Normalize to forward slashes and trim leading/trailing
                // separators so the resulting @source directive is
                // consistent regardless of how the plugin author wrote
                // the entry.
                $normalized = trim(str_replace('\\', '/', $path), '/');
                if ($normalized === '') {
                    continue;
                }
                $paths[] = $normalized;
            }

            if (empty($paths)) {
                continue;
            }

            $contributions[] = [
                'slug' => $plugin->slug,
                'directory' => $plugin->directory,
                'paths' => $paths,
            ];
        }

        return $contributions;
    }

    /**
     * Build the final CSS body. Each path becomes a Tailwind v4
     * `@source` directive resolved relative to the output file's
     * location (resources/css/), so we prepend `../../` to climb back
     * to the project root before joining the plugin's declared path.
     *
     * @param  array<int, array{slug: string, directory: string, paths: array<int, string>}>  $contributions
     */
    private function buildCss(array $contributions): string
    {
        $lines = [];
        $lines[] = '/*';
        $lines[] = ' * AUTO-GENERATED by Dixlase core. Do not edit by hand — any manual';
        $lines[] = ' * changes will be overwritten the next time a plugin is enabled,';
        $lines[] = ' * disabled, installed, updated, or uninstalled, or when';
        $lines[] = ' * `php artisan dls:tailwind:regenerate-plugin-sources` runs.';
        $lines[] = ' *';
        $lines[] = ' * Aggregates @source directives from each enabled plugin\'s';
        $lines[] = ' * `declares.tailwind_content` array in plugin.json. Themes import';
        $lines[] = ' * this partial so Tailwind v4 scans plugin-owned content';
        $lines[] = ' * directories without the theme having to know plugin slugs.';
        $lines[] = ' */';
        $lines[] = '';

        if (empty($contributions)) {
            $lines[] = '/* No enabled plugin currently declares Tailwind content sources. */';
            $lines[] = '';

            return implode("\n", $lines);
        }

        foreach ($contributions as $entry) {
            $lines[] = "/* {$entry['slug']} */";
            foreach ($entry['paths'] as $path) {
                // Paths in plugin.json are written relative to the
                // project root (base_path). The aggregator file lives
                // at resources/css/, so prefix with `../../` to climb
                // back out before appending the declared path.
                $lines[] = "@source \"../../{$path}\";";
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
