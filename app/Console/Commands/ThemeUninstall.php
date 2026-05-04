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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

use App\Models\Theme;
use App\Services\ThemeMigrator;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;

class ThemeUninstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:uninstall 
                            {themeName : '.'command.theme_uninstall.theme_name_prompt'.'}
                            {--rollback : Rollback database migrations}
                            {--force : Force uninstall even if theme is enabled}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command.theme_uninstall.description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $themeName = $this->argument('themeName');

        // Find the theme
        $theme = Theme::where('name', $themeName)
            ->orWhere('slug', $themeName)
            ->first();

        if (! $theme) {
            $this->error(__('admin/command.theme_uninstall.theme_not_found', ['themeName' => $themeName]));

            return Command::FAILURE;
        }

        // Check if theme is enabled (unless --force is specified)
        if ($theme->isEnabled() && ! $this->option('force')) {
            $this->error(__('admin/command.theme_uninstall.cannot_uninstall_enabled', ['themeName' => $theme->name]));
            $this->warn(__('admin/command.theme_uninstall.disable_first'));

            return Command::FAILURE;
        }

        // Ask for confirmation (unless --no-interaction is specified)
        if (! $this->option('no-interaction')) {
            if (! $this->confirm(__('admin/command.theme_uninstall.confirmation', ['themeName' => $theme->name]))) {
                $this->info(__('admin/command.theme_uninstall.cancelled'));

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
        $this->info(__('admin/command.theme_uninstall.uninstalled', ['themeName' => $theme->name]));
        $this->info(__('admin/command.theme_uninstall.files_preserved'));
        $this->info(__('admin/command.theme_uninstall.delete_hint'));

        return Command::SUCCESS;
    }
}
