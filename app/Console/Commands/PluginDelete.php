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

use App\Helpers\ComposerLocalHelper;
use App\Helpers\GitExcludeHelper;
use App\Helpers\GitIgnoreHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PluginDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:plugin:delete {pluginDirectory : The directory name of the plugin to delete}
                            {--force : Force delete without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete plugin files and directories (plugin must be uninstalled first)';

    /**
     * Whether the argument names a directory rather than a path.
     *
     * Kept deliberately strict: a plugin directory is one segment under
     * plugins/, so anything with a separator, a traversal, a NUL, or a leading
     * dot is not one. Rejecting is safe -- the caller reports "not found",
     * which is what a path that is not a plugin directory should look like.
     */
    protected function isSinglePathSegment(mixed $candidate): bool
    {
        if (! is_string($candidate)) {
            return false;
        }

        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $candidate) !== 1) {
            return false;
        }

        return ! str_contains($candidate, '..');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pluginDirectory = $this->argument('pluginDirectory');

        // The directory name reaches this command from
        // AdminPluginsSettingsController::delete(), whose request rule is only
        // `required|string`. Interpolating it produced a real path:
        // base_path('plugins/../app') normalises to the application's own app/
        // directory, File::exists() said yes, no `plugins` row matched so the
        // "still installed" guard did not fire, and File::deleteDirectory()
        // would then have removed it. Anything the process can write to and
        // reach with ../ was in range.
        //
        // Containment lives here rather than only in the request rule because
        // this is the last point before the recursive delete, and three
        // separate request classes feed this class of path.
        if (! $this->isSinglePathSegment($pluginDirectory)) {
            $this->error(__('admin/command/plugin-delete.not_found', ['directory' => $pluginDirectory]));

            return 1;
        }

        $pluginPath = base_path('plugins/'.$pluginDirectory);

        // Check if plugin directory exists
        if (! File::exists($pluginPath)) {
            $this->error(__('admin/command/plugin-delete.not_found', ['directory' => $pluginDirectory]));

            return 1;
        }

        // Check if registered in database (whether uninstalled or not)
        $plugin = DB::table('plugins')->where('directory', $pluginDirectory)->first();

        if ($plugin) {
            $this->error(__('admin/command/plugin-delete.still_installed', ['pluginName' => $plugin->name]));
            $this->warn(__('admin/command/plugin-delete.uninstall_first'));

            return 1;
        }

        // Confirmation prompt
        if (! $this->option('force')) {
            if (! $this->confirm(__('admin/command/plugin-delete.confirm', ['directory' => $pluginDirectory]), false)) {
                $this->info(__('admin/command/plugin-delete.cancelled'));

                return 0;
            }
        }

        // Delete directory
        try {
            File::deleteDirectory($pluginPath);
            $this->info(__('admin/command/plugin-delete.deleted', ['path' => $pluginPath]));
        } catch (\Exception $e) {
            $this->error(__('admin/command/plugin-delete.failed', ['error' => $e->getMessage()]));

            return 1;
        }

        // Remove plugin exclusion rule from .git/info/exclude
        if (GitExcludeHelper::removePluginExclusion($pluginDirectory)) {
            $this->info(__('console/commands/plugin_delete.plugin_removed_from_git_exclude', ['pluginDirectory' => $pluginDirectory]));
        } else {
            $this->warn(__('console/commands/plugin_delete.plugin_remove_from_git_exclude_failed', ['pluginDirectory' => $pluginDirectory]));
        }

        // Remove plugin exclusion rule from .gitignore
        if (GitIgnoreHelper::removePluginExclusion($pluginDirectory)) {
            $this->info(__('console/commands/plugin_delete.plugin_removed_from_gitignore', ['pluginDirectory' => $pluginDirectory]));
        }

        // Update composer.local.json
        ComposerLocalHelper::syncAutoload();
        $this->info(__('console/commands/plugin_delete.updated_composer_local_json'));

        // Note: Only update composer.local.json, keep composer.json in pristine state
        // Reflect autoload changes by manually running `composer dump-autoload`

        $this->info(__('admin/command/plugin-delete.completed', ['directory' => $pluginDirectory]));

        return 0;
    }
}
