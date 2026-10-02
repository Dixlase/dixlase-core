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

namespace App\Console\Commands;

use App\Services\Extension\ExtensionDisplayName;
use App\Services\Update\SystemUpdateFlash;
use Illuminate\Console\Command;

/**
 * Batch-update selected plugins and themes sequentially.
 *
 * The admin updates page spawns this as a detached background process
 * (mirroring dls:core:update) so the slow part of an extension update —
 * the npm front-end build a theme runs — does not block the web request
 * and time it out. The controller raises the in-progress flag before
 * spawning; this command clears it in a finally block so the polling UI
 * is released on success or failure.
 */
class ExtensionsUpdate extends Command
{
    protected $signature = 'dls:extensions:update
        {--plugin=* : Plugin slug(s) to update}
        {--theme=* : Theme slug(s) to update}
        {--applied-by= : Member id passed on to each update, for the audit log and the version history}';

    protected $description = 'Update the given plugins and themes sequentially (used by the admin updates page).';

    /**
     * On-disk flag marking a web-triggered extension update in flight.
     *
     * Kept under storage/app/private (never touched by an extension
     * update, which only rewrites plugins/ and themes/) so it survives
     * the run, and read by the updates page to show a polling placeholder.
     */
    public static function inProgressFlagPath(): string
    {
        return storage_path('app/private/extension-update/.in-progress');
    }

    public function handle(): int
    {
        $pluginSlugs = array_values(array_filter((array) $this->option('plugin')));
        $themeSlugs = array_values(array_filter((array) $this->option('theme')));
        $appliedBy = is_numeric($this->option('applied-by')) ? (string) $this->option('applied-by') : null;

        // The admin screen appends every run to one log; mark where this one starts.
        $this->line('=== dls:extensions:update '.now()->toIso8601String()
            .' plugins=['.implode(',', $pluginSlugs).'] themes=['.implode(',', $themeSlugs).'] ===');

        $failed = 0;
        // Display names (not slugs) of what actually updated, kept split by
        // kind so the completion flash can read "プラグイン「A」・テーマ「X」".
        $updatedPlugins = [];
        $updatedThemes = [];
        // Slugs run parallel to the display-name arrays; the completion flash
        // needs them to link straight to a single extension's detail page.
        $updatedPluginSlugs = [];
        $updatedThemeSlugs = [];

        try {
            foreach ($pluginSlugs as $slug) {
                $this->line("[extensions-update] updating plugin {$slug}...");
                $code = $this->call('dls:plugin:update', array_filter(['slug' => $slug, '--force' => true, '--applied-by' => $appliedBy], fn ($v) => $v !== null));
                if ($code === 0) {
                    $this->line("[extensions-update] plugin {$slug} done");
                    $updatedPlugins[] = ExtensionDisplayName::for('plugin', $slug);
                    $updatedPluginSlugs[] = $slug;
                } else {
                    $failed++;
                }
            }

            foreach ($themeSlugs as $slug) {
                $this->line("[extensions-update] updating theme {$slug}...");
                $code = $this->call('dls:theme:update', array_filter(['slug' => $slug, '--force' => true, '--applied-by' => $appliedBy], fn ($v) => $v !== null));
                if ($code === 0) {
                    $this->line("[extensions-update] theme {$slug} done");
                    $updatedThemes[] = ExtensionDisplayName::for('theme', $slug);
                    $updatedThemeSlugs[] = $slug;
                } else {
                    $failed++;
                }
            }
        } finally {
            // Record the completion for the System Updates page's one-shot
            // "update complete" flash. Written BEFORE the flag is cleared so
            // index() finds it once the polling UI is released. Only when
            // something actually updated — a fully-failed run leaves the
            // per-row failure surfaces to explain what happened.
            if ($updatedPlugins !== [] || $updatedThemes !== []) {
                SystemUpdateFlash::record([
                    'status' => 'success',
                    'kind' => 'extension',
                    'updated_plugins' => $updatedPlugins,
                    'updated_plugin_slugs' => $updatedPluginSlugs,
                    'updated_themes' => $updatedThemes,
                    'updated_theme_slugs' => $updatedThemeSlugs,
                    'failed_count' => $failed,
                ]);
            }

            // Release the polling UI regardless of outcome.
            @unlink(self::inProgressFlagPath());
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
