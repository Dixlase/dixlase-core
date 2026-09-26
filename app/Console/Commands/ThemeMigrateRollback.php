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

use App\Models\Theme;
use App\Services\ThemeMigrator;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ThemeMigrateRollback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:migrate:rollback
                            {theme : The name of the theme (e.g. MyTheme)}
                            {--pretend : Dump the SQL queries that would be run}
                            {--step= : Number of migrations to rollback}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the last database migration for a specific theme';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Accept the slug or the directory name. Str::studly() alone turned
        // the slug 'dixlase-onepage' into 'DixlaseOnepage', but the directory
        // is DixlaseOnePage.
        $themeName = Theme::resolveDirectoryFromSlug($this->argument('theme'))
            ?? Str::studly($this->argument('theme'));
        $themePath = base_path("themes/{$themeName}");
        $migrationsPath = "{$themePath}/database/migrations";

        // Check theme directory existence
        if (! File::isDirectory($themePath)) {
            $this->error("Theme directory not found: {$themePath}");

            return 1;
        }

        // Check migration directory existence
        if (! File::isDirectory($migrationsPath)) {
            $this->warn("No migrations directory found for theme: {$themeName}");

            return 0;
        }

        $this->info("Rolling back migrations for theme: {$themeName}");

        // Roll back through ThemeMigrator so it reverses rows in the
        // dls_theme_migrations ledger. The previous implementation delegated
        // to the stock `migrate:rollback`, which operates on dls_migrations
        // and therefore reversed NOTHING recorded by ThemeMigrator — the
        // theme migration rollback silently no-opped and left orphan schema.
        // The ledger is keyed on the theme's canonical slug (theme.json / DB),
        // resolved here so it matches what the update path recorded under.
        $themeSlug = Theme::resolveSlug($themeName);
        $migrator = new ThemeMigrator(
            app(Filesystem::class),
            app(ConnectionResolverInterface::class),
            'theme_migrations',
            $themeSlug,
        );

        try {
            $rolledBack = $migrator->rollback($themeName, [
                'step' => $this->option('step') ? (int) $this->option('step') : 1,
                'pretend' => (bool) $this->option('pretend'),
                'force' => (bool) $this->option('force'),
            ]);
        } catch (\Throwable $e) {
            $this->error("Rollback failed for theme: {$themeName}: {$e->getMessage()}");

            return 1;
        }

        if (empty($rolledBack)) {
            $this->info("No migrations to roll back for theme: {$themeName}.");
        } else {
            foreach ($rolledBack as $file) {
                $this->info('Rolled back: '.$file);
            }
            $this->info("Rollback completed successfully for theme: {$themeName}");
        }

        return 0;
    }
}
