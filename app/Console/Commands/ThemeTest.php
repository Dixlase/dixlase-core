<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

class ThemeTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:test 
                            {theme : The name of the theme (e.g. MyTheme)}
                            {--filter= : Filter which tests to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run tests for a specific theme';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $themeName = Str::studly($this->argument('theme'));
        $themePath = base_path("themes/{$themeName}");
        $testsPath = "{$themePath}/tests";

        // テーマディレクトリの存在確認
        if (!File::isDirectory($themePath)) {
            $this->error("Theme directory not found: {$themePath}");
            return 1;
        }

        // テストディレクトリの存在確認
        if (!File::isDirectory($testsPath)) {
            $this->warn("No tests directory found for theme: {$themeName}");
            return 0;
        }

        $this->info("Running tests for theme: {$themeName}");

        // テスト実行
        $options = [
            '--testsuite' => $themeName,
        ];

        if ($filter = $this->option('filter')) {
            $options['--filter'] = $filter;
        }

        $exitCode = Artisan::call('test', $options, $this->getOutput());

        if ($exitCode === 0) {
            $this->info("Tests completed successfully for theme: {$themeName}");
        } else {
            $this->error("Tests failed for theme: {$themeName}");
        }

        return $exitCode;
    }
}
