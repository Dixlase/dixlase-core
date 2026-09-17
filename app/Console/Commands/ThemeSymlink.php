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

use App\Services\Core\PublicAssetRelinker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ThemeSymlink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:symlink
                            {action : The action to perform (create|remove)}
                            {theme? : The directory name of the theme (omit when using --all)}
                            {--all : Apply the action to every theme directory on disk}';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->description = __('admin/command/theme-symlink.description');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');
        $themeDirName = $this->argument('theme');
        $all = (bool) $this->option('all');

        if (! in_array($action, ['create', 'remove'])) {
            $this->error(__('admin/command/theme-symlink.invalid_action'));

            return 1;
        }

        if (! $all && ($themeDirName === null || $themeDirName === '')) {
            $this->error(__('admin/command/theme-symlink.theme_required'));

            return 1;
        }

        if ($all && ($themeDirName !== null && $themeDirName !== '')) {
            $this->error(__('admin/command/theme-symlink.theme_and_all_conflict'));

            return 1;
        }

        if ($all) {
            return $this->handleAll($action);
        }

        return $this->handleSingle($action, $themeDirName);
    }

    /**
     * Handle a single theme. Preserves the pre-`--all` behaviour: the
     * internal create/remove helpers are idempotent, so re-running is
     * always safe.
     */
    protected function handleSingle(string $action, string $themeDirName): int
    {
        if ($action === 'create') {
            $this->createThemeSymlink($themeDirName);
            $this->info(__('admin/command/theme-symlink.created', ['theme' => $themeDirName]));
        } else {
            $this->removeThemeSymlink($themeDirName);
            $this->info(__('admin/command/theme-symlink.removed', ['theme' => $themeDirName]));
        }

        return 0;
    }

    /**
     * Handle every theme on disk. Idempotent — safe to re-run against
     * an already-consistent tree.
     *
     * On `create`: enumerate `themes/*` on disk and (re)link the ones
     * that carry a `resources/assets/` directory. Directories without
     * that layout are skipped silently (nothing to serve).
     *
     * On `remove`: enumerate `public/assets/themes/*` and unlink every
     * existing symlink there. This intentionally covers stale links
     * whose theme directory has already been deleted from disk.
     */
    protected function handleAll(string $action): int
    {
        if ($action === 'create') {
            $themesRoot = base_path('themes');
            if (! File::isDirectory($themesRoot)) {
                $this->info(__('admin/command/theme-symlink.all_summary_create', [
                    'created' => 0,
                    'skipped' => 0,
                ]));

                return 0;
            }

            $created = 0;
            $skipped = 0;
            foreach (File::directories($themesRoot) as $themePath) {
                $themeDirName = basename($themePath);
                $target = "{$themePath}/resources/assets";
                if (! File::isDirectory($target)) {
                    $skipped++;

                    continue;
                }

                // A working link is left alone; a missing, dangling or
                // overwritten slot (a real directory from a dereferenced
                // release payload) is relinked below.
                $link = public_path("assets/themes/{$themeDirName}");
                if (is_link($link) && File::exists($link)) {
                    $skipped++;

                    continue;
                }

                $this->createThemeSymlink($themeDirName);
                $created++;
            }

            $this->info(__('admin/command/theme-symlink.all_summary_create', [
                'created' => $created,
                'skipped' => $skipped,
            ]));

            return 0;
        }

        // remove --all
        $linksRoot = public_path('assets/themes');
        if (! File::isDirectory($linksRoot)) {
            $this->info(__('admin/command/theme-symlink.all_summary_remove', [
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
            $this->removeThemeSymlink($entry);
            $removed++;
        }

        $this->info(__('admin/command/theme-symlink.all_summary_remove', [
            'removed' => $removed,
        ]));

        return 0;
    }

    /**
     * Create symlink for theme assets.
     *
     * Uses `File::relativeLink()` — not `File::link()` — so the recorded
     * symlink target is a relative path, not an absolute one rooted at
     * whatever `base_path()` returns in the container that ran this
     * command. Same rationale as the sibling {@see PluginSymlink}: on
     * split-container prod topologies the PHP container's absolute
     * path does not resolve from nginx, so an absolute symlink 404s
     * every `/assets/themes/{Name}/…` request.
     *
     * @return void
     */
    protected function createThemeSymlink(string $themeDirName)
    {
        // Replaces whatever occupies the slot, including a real directory:
        // a core update whose payload carried the dereferenced assets used
        // to leave one there, and the old `! File::exists($link)` guard
        // then skipped the repair silently. See PublicAssetRelinker.
        PublicAssetRelinker::ensureLink(
            base_path("themes/{$themeDirName}/resources/assets"),
            public_path("assets/themes/{$themeDirName}"),
        );
    }

    /**
     * Remove symlink for theme assets
     *
     * @return void
     */
    protected function removeThemeSymlink(string $themeDirName)
    {
        $link = public_path("assets/themes/{$themeDirName}");

        if (is_link($link)) {
            @unlink($link);

            return;
        }

        // File::delete() is unlink(): it cannot remove a directory, which is
        // what an overwritten link leaves behind.
        if (File::isDirectory($link)) {
            File::deleteDirectory($link);

            return;
        }

        if (File::exists($link)) {
            File::delete($link);
        }
    }
}
