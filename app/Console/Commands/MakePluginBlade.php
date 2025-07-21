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
use App\Console\Traits\MakeBladeTrait;
use App\Console\Traits\MakePluginCommandTrait;

class MakePluginBlade extends Command
{
    use MakeBladeTrait;
    use MakePluginCommandTrait;

    protected $signature;

    protected $description = 'Create a new Blade template in a plugin';

    public function __construct()
    {
        $this->signature = $this->makeSignature('make:plugin:blade
            {className? : The name of the model class (e.g., User)}
            {pluginName? : The name of the plugin}
            {scope? : The scope of the controller (e.g., admin, api)}', 
            $this->getAdditionalOptions()
        );
        
        parent::__construct();
    }

    /**
     * Get additional options specific to this command
     */
    protected function getAdditionalOptions(): array
    {
        return [
            
        ];
    }

    /**
     * Handle the command execution.
     *
     * @return int
     */
    public function handle()
    {
        return $this->generatePluginFile(
            $this->argument('className'),
            $this->argument('pluginName'),
            'blade',
            $this->options(),
            true,
            $this->argument('scope')
        );

    }

    /**
     * プラグイン用のベースディレクトリを返す
     * "plugins/{Plugin}/resources/views"
     */
    protected function getDirectory(array $subDirs): string
    {
        $pluginName = Str::studly($this->argument('pluginName'));
        $base = base_path("plugins/{$pluginName}/resources/views");
        
        // サブディレクトリが指定されていれば追加
        if (!empty($subDirs)) {
            $base .= '/' . implode('/', $subDirs);
        }
        
        return $base;
    }

    /**
     * Blade ファイルにはネームスペースは不要なので空文字を返す
     */
    protected function getNamespace(array $subDirs): string
    {
        return '';
    }
}
