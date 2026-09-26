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

class ThemeMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:migrate
                            {theme : The name of the theme (e.g. MyTheme)}
                            {--pretend : Dump the SQL queries that would be run}
                            {--step= : Number of migrations to run}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the migrations for a specific theme';

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

        // Check theme directory exists
        if (! File::isDirectory($themePath)) {
            $this->error("Theme directory not found: {$themePath}");

            return 1;
        }

        // Check migration directory exists
        if (! File::isDirectory($migrationsPath)) {
            $this->warn("No migrations directory found for theme: {$themeName}");

            return 0;
        }

        // Check migration files
        $migrationFiles = File::files($migrationsPath);
        if (empty($migrationFiles)) {
            $this->info("No migration files found for theme: {$themeName}");

            return 0;
        }

        $this->info("Running migrations for theme: {$themeName}");

        // Use ThemeMigrator so rows land in dls_theme_migrations (not
        // dls_migrations). The previous Artisan::call('migrate', --path)
        // recorded into the stock ledger and exposed the theme migrations
        // to any subsequent stock `php artisan migrate` run, which would
        // re-apply them and hit "table already exists".
        // Record under the theme's canonical slug (theme.json / DB), NOT a
        // slug re-derived from the directory name — the update and rollback
        // paths key the theme_migrations ledger on the canonical slug, and a
        // re-derived one (e.g. DixlaseOnePage -> dixlase-one-page) would
        // diverge from the declared dixlase-onepage.
        $themeSlug = Theme::resolveSlug($themeName);
        $migrator = new ThemeMigrator(
            app(Filesystem::class),
            app(ConnectionResolverInterface::class),
            'theme_migrations',
            $themeSlug,
        );

        try {
            $migrated = $migrator->migrate($themeName, null, [
                'pretend' => (bool) $this->option('pretend'),
            ]);
        } catch (\Throwable $e) {
            $this->error("Migration failed for theme: {$themeName}: ".$e->getMessage());

            return 1;
        }

        $this->info("Migrations completed for theme: {$themeName} (".count($migrated).' applied)');

        return 0;
    }
}
