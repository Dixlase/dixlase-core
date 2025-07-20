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
use App\Console\Traits\MakeFileTrait;
use App\Console\Traits\MakeLicenseTrait;
use App\Console\Traits\MakePluginCommandTrait;
use App\Console\Traits\MakeConfigTrait;


class MakePluginConfig extends Command
{
    use MakeFileTrait;
    use MakeLicenseTrait;
    use MakePluginCommandTrait;
    use MakeConfigTrait;


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
    protected $description = 'Create a new configuration file for a plugin';
    
    public function __construct()
    {
        //$this->signature = $this->makeSignature('make:plugin:config {className} {pluginName?} ', $this->getAdditionalOptions());

        $this->signature = $this->makeSignature('make:plugin:config
            {className : The class name (e.g. app)}
            {pluginName? : The plugin name (e.g. MyPlugin)}',
            $this->getAdditionalOptions()
        );
        parent::__construct();
    }

    

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $common = $this->initializePluginCommand($this->argument('className'), $this->argument('pluginName'));
        if (!$common) {
            return Command::FAILURE;
        }
        $common['category'] = 'config';

        // コンフィグファイルの生成
        return $this->makePluginFile($common, $this->options())
            ? Command::SUCCESS
            : Command::FAILURE;

    }
}
