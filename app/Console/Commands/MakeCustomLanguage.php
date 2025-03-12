<?php

/**
 * This file is part of MySoftware.
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
use App\Services\FileGenerator;
use App\Console\Traits\MakeLanguageTrait;

class MakeCustomLanguage extends Command
{
    use MakeLanguageTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:custom:lang
        {lang : The language code (e.g. en, ja)}
        {file : The language file name (e.g. messages)}
        {--force : Overwrite if the file already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new language file in the custom/lang directory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $langCode = strtolower($this->argument('lang'));
        $fileName = $this->argument('file');
        $options = ['force' => (bool) $this->option('force')];

        $this->makeLanguageFile($langCode, $fileName, $options);

        return 0;
    }

    /**
     * カスタム用 => custom/lang
     */
    protected function getDirectory(array $subDirs): string
    {
        return base_path('custom/lang/' . implode('/', $subDirs));
    }
}
