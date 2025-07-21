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
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeLanguageTrait;
use App\Console\Traits\MakeFileTrait;
use App\Console\Traits\MakeLicenseTrait;
use App\Console\Traits\MakePluginCommandTrait;

class MakePluginLanguage extends Command
{
    use MakeFileTrait;
    use MakeLicenseTrait;
    use MakeLanguageTrait;
    use MakePluginCommandTrait;

        /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new language file in the specified plugin\'s lang directory';

    public function __construct()
    {
        $this->signature = $this->makeSignature('make:plugin:lang
            {className? : The class name (e.g. messages)}
            {pluginName? : The plugin name (e.g. MyPlugin)}
            {lang? : The language code (e.g. en, ja)}',
        $this->getAdditionalOptions());
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $options = $this->options();
        $options['lang'] = $this->argument('lang');

        $this->generatePluginFile(
            $this->argument('className'),
            $this->argument('pluginName'),
            'lang',
            $options
        );
    }

    /**
     * プラグイン用 => "plugins/{Plugin}/lang"
     */
    protected function getDirectory(array $subDirs): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        return base_path("plugins/{$pluginName}/lang/" . implode('/', $subDirs));
    }
}
