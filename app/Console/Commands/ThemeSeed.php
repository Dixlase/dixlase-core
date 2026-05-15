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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ThemeSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:seed
                            {theme : The name of the theme (e.g. MyTheme)}
                            {--class=DatabaseSeeder : The seeder class name to run}
                            {--force : Force the operation to run in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database seeds for a specific theme';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $themeName = Str::studly($this->argument('theme'));
        $themePath = base_path("themes/{$themeName}");
        $seederClassName = $this->option('class');

        // Check theme directory existence
        if (! File::isDirectory($themePath)) {
            $this->error("Theme directory not found: {$themePath}");

            return 1;
        }

        // Check seeder class
        $fullSeederClass = "Themes\\{$themeName}\\Database\\Seeders\\{$seederClassName}";
        if (! class_exists($fullSeederClass)) {
            $this->error("Seeder class not found: {$fullSeederClass}");

            return 1;
        }

        $this->info("Seeding {$fullSeederClass}...");

        try {
            Artisan::call('db:seed', [
                '--class' => $fullSeederClass,
                '--force' => $this->option('force'),
            ]);

            $this->info(Artisan::output());
            $this->info("Seeding completed successfully for theme: {$themeName}");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error executing seeder: '.$e->getMessage());

            return 1;
        }
    }
}
