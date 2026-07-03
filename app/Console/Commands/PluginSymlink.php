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

use App\Support\RelativeSymlink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PluginSymlink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:plugin:symlink
                            {action : The action to perform (create|remove)}
                            {plugin? : The directory name of the plugin (omit when using --all)}
                            {--all : Apply the action to every plugin directory on disk}';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->description = __('admin/command/plugin-symlink.description');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');
        $pluginDirName = $this->argument('plugin');
        $all = (bool) $this->option('all');

        if (! in_array($action, ['create', 'remove'])) {
            $this->error(__('admin/command/plugin-symlink.invalid_action'));

            return 1;
        }

        if (! $all && ($pluginDirName === null || $pluginDirName === '')) {
            $this->error(__('admin/command/plugin-symlink.plugin_required'));

            return 1;
        }

        if ($all && ($pluginDirName !== null && $pluginDirName !== '')) {
            $this->error(__('admin/command/plugin-symlink.plugin_and_all_conflict'));

            return 1;
        }

        if ($all) {
            return $this->handleAll($action);
        }

        return $this->handleSingle($action, $pluginDirName);
    }

    /**
     * Handle a single plugin. Preserves the pre-`--all` behaviour: the
     * internal create/remove helpers are idempotent, so re-running is
     * always safe.
     */
    protected function handleSingle(string $action, string $pluginDirName): int
    {
        if ($action === 'create') {
            $this->createPluginSymlink($pluginDirName);
            $this->info(__('admin/command/plugin-symlink.created', ['plugin' => $pluginDirName]));
        } else {
            $this->removePluginSymlink($pluginDirName);
            $this->info(__('admin/command/plugin-symlink.removed', ['plugin' => $pluginDirName]));
        }

        return 0;
    }

    /**
     * Handle every plugin on disk. Idempotent — safe to re-run against
     * an already-consistent tree.
     *
     * On `create`: enumerate `plugins/*` on disk and (re)link the ones
     * that carry a `resources/assets/` directory. Directories without
     * that layout are skipped silently (nothing to serve).
     *
     * On `remove`: enumerate `public/assets/plugins/*` and unlink every
     * existing symlink there. This intentionally covers stale links
     * whose plugin directory has already been deleted from disk.
     */
    protected function handleAll(string $action): int
    {
        if ($action === 'create') {
            $pluginsRoot = base_path('plugins');
            if (! File::isDirectory($pluginsRoot)) {
                $this->info(__('admin/command/plugin-symlink.all_summary_create', [
                    'created' => 0,
                    'skipped' => 0,
                ]));

                return 0;
            }

            $created = 0;
            $skipped = 0;
            foreach (File::directories($pluginsRoot) as $pluginPath) {
                $pluginDirName = basename($pluginPath);
                $target = "{$pluginPath}/resources/assets";
                if (! File::isDirectory($target)) {
                    $skipped++;

                    continue;
                }

                $link = public_path("assets/plugins/{$pluginDirName}");
                if (File::exists($link) || is_link($link)) {
                    $skipped++;

                    continue;
                }

                $this->createPluginSymlink($pluginDirName);
                $created++;
            }

            $this->info(__('admin/command/plugin-symlink.all_summary_create', [
                'created' => $created,
                'skipped' => $skipped,
            ]));

            return 0;
        }

        // remove --all
        $linksRoot = public_path('assets/plugins');
        if (! File::isDirectory($linksRoot)) {
            $this->info(__('admin/command/plugin-symlink.all_summary_remove', [
                'removed' => 0,
            ]));

            return 0;
        }

        $removed = 0;
        foreach (scandir($linksRoot) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $link = "{$linksRoot}/{$entry}";
            if (! is_link($link) && ! File::exists($link)) {
                continue;
            }
            $this->removePluginSymlink($entry);
            $removed++;
        }

        $this->info(__('admin/command/plugin-symlink.all_summary_remove', [
            'removed' => $removed,
        ]));

        return 0;
    }

    /**
     * Create symlink for plugin assets.
     *
     * Uses `File::relativeLink()` — not `File::link()` — so the recorded
     * symlink target is a relative path, not an absolute one rooted at
     * whatever `base_path()` returns in the container that ran this
     * command. On split-container deployments (typical prod topology:
     * one container serves PHP at `/var/www/html`, another serves
     * nginx at `/var/www/apps/brand`), an absolute target baked with
     * the PHP container's mount point does not resolve from nginx, so
     * every `/assets/plugins/{Name}/…` request returns 404 even though
     * the file exists. A relative symlink resolves correctly from
     * whichever container walks it because the resolution is anchored
     * to the symlink's own location, which is the same physical path
     * in every mount.
     *
     * @return void
     */
    protected function createPluginSymlink(string $pluginDirName)
    {
        $target = base_path("plugins/{$pluginDirName}/resources/assets");
        $link = public_path("assets/plugins/{$pluginDirName}");

        if (File::exists($target) && ! File::exists($link)) {
            File::ensureDirectoryExists(dirname($link));
            RelativeSymlink::create($target, $link);
        }
    }

    /**
     * Remove symlink for plugin assets
     *
     * @return void
     */
    protected function removePluginSymlink(string $pluginDirName)
    {
        $link = public_path("assets/plugins/{$pluginDirName}");

        if (File::exists($link) || is_link($link)) {
            File::delete($link);
        }
    }
}
