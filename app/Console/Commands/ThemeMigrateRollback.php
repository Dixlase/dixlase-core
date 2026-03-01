<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
        $themeName = Str::studly($this->argument('theme'));
        $themePath = base_path("themes/{$themeName}");
        $migrationsPath = "{$themePath}/database/migrations";

        // テーマディレクトリの存在確認
        if (!File::isDirectory($themePath)) {
            $this->error("Theme directory not found: {$themePath}");
            return 1;
        }

        // マイグレーションディレクトリの存在確認
        if (!File::isDirectory($migrationsPath)) {
            $this->warn("No migrations directory found for theme: {$themeName}");
            return 0;
        }

        $this->info("Rolling back migrations for theme: {$themeName}");

        // ロールバック実行
        $options = [
            '--path' => "themes/{$themeName}/database/migrations",
            '--force' => $this->option('force'),
        ];

        if ($this->option('pretend')) {
            $options['--pretend'] = true;
        }

        if ($this->option('step')) {
            $options['--step'] = (int) $this->option('step');
        }

        $exitCode = Artisan::call('migrate:rollback', $options, $this->getOutput());

        if ($exitCode === 0) {
            $this->info("Rollback completed successfully for theme: {$themeName}");
        } else {
            $this->error("Rollback failed for theme: {$themeName}");
        }

        return $exitCode;
    }
}
