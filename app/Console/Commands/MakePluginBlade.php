<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

class MakePluginBlade extends Command
{
    use MakeBladeTrait;

    protected $signature = 'make:plugin:blade
        {plugin : The plugin name (e.g. MyPlugin)}
        {file : The blade file name (e.g. admin/dashboard)}
        {--force : Overwrite if the blade file already exists}';

    protected $description = 'Create a new Blade template in the specified plugin\'s resources/views directory';

    public function handle()
    {
        $plugin = Str::studly($this->argument('plugin'));
        $file   = $this->argument('file');
        $force  = (bool) $this->option('force');

        $this->pluginName = $plugin; // 後で getBladeBasePath() で使用

        $this->makeBlade($file, $force);

        return 0;
    }

    /**
     * プラグイン用 => "plugins/{Plugin}/resources/views"
     */
    protected function getBladeBasePath(): string
    {
        return base_path("plugins/{$this->pluginName}/resources/views");
    }
}
