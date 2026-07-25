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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ThemeMigrateRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:migrate:refresh
                            {theme : The name of the theme (e.g. MyTheme)}
                            {--seed : Indicates if the seed task should be re-run}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset and re-run all migrations for a specific theme';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $themeName = Str::studly($this->argument('theme'));
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

        $this->info("Refreshing migrations for theme: {$themeName}");

        // Execute rollback
        $this->call('dls:theme:migrate:rollback', [
            'theme' => $themeName,
            '--force' => $this->option('force'),
        ]);

        // Execute migration
        $this->call('dls:theme:migrate', [
            'theme' => $themeName,
            '--force' => $this->option('force'),
        ]);

        // Execute seed (optional)
        if ($this->option('seed')) {
            $this->call('dls:theme:seed', [
                'theme' => $themeName,
                '--force' => $this->option('force'),
            ]);
        }

        $this->info("Migration refresh completed for theme: {$themeName}");

        return 0;
    }
}
