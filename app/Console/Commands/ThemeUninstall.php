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

use App\Console\Traits\RecordsExtensionOperation;
use App\Models\Theme;
use App\Services\Extension\ExtensionSourceSidecar;
use App\Services\ExtensionOperationService;
use App\Services\ThemeMigrator;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;

class ThemeUninstall extends Command
{
    use RecordsExtensionOperation;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:uninstall 
                            {themeName : The name of the theme to uninstall}
                            {--rollback : Rollback database migrations}
                            {--force : Force uninstall even if theme is enabled}
                            {--applied-by= : Member id recorded as the actor (the admin screen passes it; CLI runs record none)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uninstall a theme (its files are preserved)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ExtensionSourceSidecar $sidecar)
    {
        $themeName = $this->argument('themeName');

        // Find the theme
        $theme = Theme::where('name', $themeName)
            ->orWhere('slug', $themeName)
            ->first();

        if (! $theme) {
            $this->error(__('admin/command/theme-uninstall.theme_not_found', ['themeName' => $themeName]));

            return Command::FAILURE;
        }

        // Check if theme is enabled (unless --force is specified)
        if ($theme->isEnabled() && ! $this->option('force')) {
            $this->error(__('admin/command/theme-uninstall.cannot_uninstall_enabled', ['themeName' => $theme->name]));
            $this->warn(__('admin/command/theme-uninstall.disable_first'));

            return Command::FAILURE;
        }

        // Ask for confirmation (unless --no-interaction is specified)
        if (! $this->option('no-interaction')) {
            if (! $this->confirm(__('admin/command/theme-uninstall.confirmation', ['themeName' => $theme->name]))) {
                $this->info(__('admin/command/theme-uninstall.cancelled'));

                return Command::SUCCESS;
            }
        }

        // Roll back migrations
        if ($this->option('rollback')) {
            $this->info('Rolling back theme migrations...');
            try {
                $migrator = new ThemeMigrator(
                    app(Filesystem::class),
                    app(ConnectionResolverInterface::class),
                    'theme_migrations',
                    $theme->slug
                );
                // Set step to a large value to roll back all migrations
                $migrator->rollback($theme->directory, ['step' => 999]);
                $this->info('Theme migrations rolled back successfully');
            } catch (\Exception $e) {
                $this->warn('Failed to rollback migrations: '.$e->getMessage());
            }
        } elseif (! $this->option('no-interaction') && $this->confirm('Do you want to rollback database migrations?', false)) {
            $this->info('Rolling back theme migrations...');
            try {
                $migrator = new ThemeMigrator(
                    app(Filesystem::class),
                    app(ConnectionResolverInterface::class),
                    'theme_migrations',
                    $theme->slug
                );
                // Set step to a large value to roll back all migrations
                $migrator->rollback($theme->directory, ['step' => 999]);
                $this->info('Theme migrations rolled back successfully');
            } catch (\Exception $e) {
                $this->warn('Failed to rollback migrations: '.$e->getMessage());
            }
        } else {
            $this->info('Skipping migration rollback');
        }

        // Note: .git/info/exclude and composer.local.json are updated in ThemeDelete,
        // not here because uninstall does not delete files

        // Delete theme from database
        $theme->delete();

        // The row held the source linkage while the theme was installed.
        // Hand it back to the files that stay on disk, as a download leaves
        // it, so a stale copy is still flagged and a reinstall keeps its
        // update source (#485).
        $manifestPath = $theme->manifestPath();
        if ($manifestPath !== null) {
            $sidecar->restoreFromRow(dirname($manifestPath), $theme);
        }

        $this->recordExtensionOperation(ExtensionOperationService::TYPE_THEME, ExtensionOperationService::OPERATION_UNINSTALLED, $theme, [
            'name' => $theme->name,
            'slug' => $theme->slug,
            'version' => $theme->version,
        ]);

        $this->info(__('admin/command/theme-uninstall.uninstalled', ['themeName' => $theme->name]));
        $this->info(__('admin/command/theme-uninstall.files_preserved'));
        $this->info(__('admin/command/theme-uninstall.delete_hint'));

        return Command::SUCCESS;
    }
}
